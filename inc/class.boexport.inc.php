<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2011 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *                                                                       *
    * This file is part of ALIX.                                               *
    *                                                                          *
    * ALIX is free software: you can redistribute it and/or modify             *
    * it under the terms of the GNU General Public License as published by     *
    * the Free Software Foundation, either version 3 of the License, or        *
    * (at your option) any later version.                                      *
    *                                                                          *
    * ALIX is distributed in the hope that it will be useful,                  *
    * but WITHOUT ANY WARRANTY; without even the implied warranty of           *
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            *
    * GNU General Public License for more details.                             *
    *                                                                          *
    * You should have received a copy of the GNU General Public License        *
    * along with ALIX.  If not, see <http://www.gnu.org/licenses/>.            *
    \**************************************************************************/
    
require_once("class.CommonFunctions.php");

class boexport extends CommonFunctions
{  

  //Constructeur
  function __construct(&$tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }               

  /**
  *@desc Produit un nouvel export des données. Les règles sont les suivantes :
  *      1 fichier csv est généré par ItemGroup
  *      Les variables à exporter sont indiquées dans le fichier de configuration
  *      Les fichiers csv sont zippés dans un fichier qui s'ajoute à la liste des exports précédent (pas d'annule et remplace)  
  @author wlt
  */   
  function export($type)
  { 	    
    
    //L'export est piloté par le fichier de configuration principale
    if(isset($this->m_tblConfig['EXPORT']['TYPE'][$type]['index'])){
      $exportIndex = $this->m_tblConfig['EXPORT']['TYPE'][$type]['index']; 
    }else{
      $this->addLog("export : type $type inconnu",FATAL);
    }
        
    //Stockage temporaire des fichiers csv avant zippage
    $tmp = sys_get_temp_dir();
  	$uid = uniqid($type);
  	mkdir($tmp.'/'.$uid);    
    
    ob_end_flush();
    ob_flush(); 
    flush();
    
    //Extraction des metadatas
    $query = "
             let \$MetaDataVersion := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion
                
             for \$ItemGroupDef in \$MetaDataVersion/odm:ItemGroupDef
              return
                <ItemGroupDef OID='{\$ItemGroupDef/@OID}'>
                  {
                  for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                  let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                  return
                    <ItemDef OID='{\$ItemDef/@OID}' 
                             Name='{\$ItemDef/@Name}' 
                             Question='{\$ItemDef/odm:Question/odm:TranslatedText}'
                             DataType='{\$ItemDef/@DataType}'
                             Length='{\$ItemDef/@Length}'
                             SignificantDigits='{\$ItemDef/@SignificantDigits}'
                             CodelistOID='{\$ItemDef/odm:CodeListRef/@CodeListOID}'
                             MeasurementUnit='{\$ItemDef/odm:MeasurementUnitRef/@MeasurementUnitOID}'
                    />
                  }
                </ItemGroupDef>
             ";
    try{
      $ItemGroupDefs = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : exportDSMB() => $str",FATAL);
      die($str);
    }     

    $filename = $tmp.'/'.$uid."/Structure.csv";
    $fp = fopen($filename, 'w');
    
    $line = array('TABLE','FIELD','QUESTION','DATATYPE','LENGTH','CODELIST','UNIT');
    fputcsv($fp, $line,';');
    
    $tblCodeList = array('CL.$COUNT');
   
    foreach($ItemGroupDefs as $ItemGroupDef){
      $ItemGroupOID = (string)$ItemGroupDef['OID'];
      
      if(isset($this->m_tblConfig[$exportIndex][$ItemGroupOID])){

        foreach($ItemGroupDef as $ItemDef){
          $ItemOID = (string)$ItemDef['OID'];
          if(!isset($this->m_tblConfig[$exportIndex][$ItemGroupOID]['FIELDLIST']) || 
             in_array($ItemOID,$this->m_tblConfig[$exportIndex][$ItemGroupOID]['FIELDLIST'])){
            //Champ demandé dans l'export
            if($ItemDef['Length']!="" && $ItemDef['SignificantDigits']!=""){
              $length = $ItemDef['Length'] . "." . $ItemDef['SignificantDigits']; 
            }else{
              $length = $ItemDef['Length'];
            }
            $line = array($this->m_tblConfig[$exportIndex][$ItemGroupOID]['FILEDEST'],
                          $ItemGroupOID . "." . $ItemDef['Name'],
                          utf8_decode($ItemDef['Question']),
                          $ItemDef['DataType'],
                          $length,
                          $ItemDef['CodelistOID'],
                          $ItemDef['MeasurementUnit']);
            if((string)$ItemDef['CodelistOID']!=""){
              $tblCodeList[] = (string)$ItemDef['CodelistOID']; 
            }
            fputcsv($fp, $line,';');                            
          }
        }  
      }  
    }
    
    fclose($fp);
    
    //Fichier Codelist
    $filename = $tmp.'/'.$uid."/Codelists.csv";
    $fp = fopen($filename, 'w');
    
    $line = array('CODELIST','VALUE','DECODE');
    fputcsv($fp, $line,';');
    
    //sas format file
    $procSasFormat = "
/*************************************************************************************/
/*************************************************************************************/
/** General information                                                             **/
/** -------------------                                                             **/
/** Program : importFormat.sas                                                            **/
/** Project : ".$this->m_tblConfig['CODE_PROJET'] . " " . $this->m_tblConfig['APP_NAME'] ."**/
/** Client  : ".$this->m_tblConfig['CLIENT']."                                      **/
/** Version : SAS V9                                                                **/
/** Author  : WLT                                                                   **/
/** Date    : ".date("d/m/Y")."                                                **/
/*************************************************************************************/
/** Description                                                                     **/
/** -----------                                                                     **/
/**   IMPORT of eCRF Data in CDISC ODM format into SAS                              **/
/**                                                                                 **/
/*************************************************************************************/
/** Modifications                                                                   **/
/** -------------                                                                   **/
/** Date     | Author | Explications                                                **/
/**                                                                                 **/
/**                                                                                 **/
/*************************************************************************************/
/*************************************************************************************/"; 
   
    $query = "
             let \$MetaDataVersion := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion
                
             for \$CodeList in \$MetaDataVersion/odm:CodeList
              return
                <CodeList OID='{\$CodeList/@OID}' DataType='{\$CodeList/@DataType}'>
                  {
                  for \$CodeListItem in \$CodeList/odm:CodeListItem
                  let \$Value := \$CodeListItem/@CodedValue
                  let \$Decode := \$CodeListItem/odm:Decode/odm:TranslatedText
                  order by \$Value
                  return
                    <CodeListItem Value='{\$Value}' Decode='{\$Decode}'/>
                  }
                </CodeList>
             ";
    try{
      $Codelists = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : export() => $str",FATAL);
      die($str);
    }         
      $procSasFormat .= "
proc format;
";    
    foreach($Codelists as $Codelist){
      $CodelistOID = (string)$Codelist['OID'];
      $DataType = (string)$Codelist['DataType'];
      if(in_array($CodelistOID,$tblCodeList)){
        //We remove the $ character
        $CodelistOID = str_replace(".\$","_",$CodelistOID);
      $procSasFormat .= "
  value ".($DataType=="string"?"\$":"")." $CodelistOID";
        foreach($Codelist as $CodeListItem){
          $line = array($CodelistOID,$CodeListItem['Value'],$CodeListItem['Decode']);
          fputcsv($fp, $line,';');
          $sasCodeListValue = (string)$CodeListItem['Value'];
          if($DataType=="string"){
            $sasCodeListValue = "\"" . $sasCodeListValue . "\"";  
          }
          $sasCodeListDecode = (string)$CodeListItem['Decode'];
          $procSasFormat .= "
  $sasCodeListValue = \"$sasCodeListDecode\"";
        }
          $procSasFormat .= "
;
";        
      }
    }

          $procSasFormat .= "

run;";   
    
    fclose($fp);
    
    //travail préparatoire : pour chaque patient, on va extraire les clés qui seront ajoutés dans toutes les tables
    $subjCol = $this->m_ctrl->socdiscoo()->getClinicalDataCollection();
    $query = "for \$SubjectData in $subjCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK']";

    foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $key=>$col){
      if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
        $query .= "let \$col$key := \$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['value']['SEOID']}' and @StudyEventRepeatKey='{$col['value']['SERK']}']/
                                                          odm:FormData[@FormOID='{$col['value']['FRMOID']}' and @FormRepeatKey='{$col['value']['FRMRK']}']/
                                                          odm:ItemGroupData[@ItemGroupOID='{$col['value']['IGOID']}' and @ItemGroupRepeatKey='{$col['value']['IGRK']}']/
                                                          odm:*[@ItemOID='{$col['value']['ITEMOID']}'][last()]/string()";      
      }

    }  
    $query .= "
              return
                <Subject     
                     SubjectKey='{\$SubjectData/@SubjectKey}' ";
    
    foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $key=>$col){
      if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
        $query .= "$key='{\$col$key}' ";
      }
    }                     
    $query .= "/>";    
    
    try{
      $subjs = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : export() => $str",FATAL);
      die($str);
    } 
    $tblSubj = array();
    foreach($subjs as $subj){
      $subjKey = (string)$subj['SubjectKey'];
      foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $key=>$col){
        if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
          $tblSubj[$subjKey]["$key"] = (string)$subj["$key"];
        }
      }
    }

    //Préparation du fichier Macro SAS
    $procSAS = "
/*************************************************************************************/
/*************************************************************************************/
/** General information                                                             **/
/** -------------------                                                             **/
/** Program : import.sas                                                            **/
/** Project : ".$this->m_tblConfig['CODE_PROJET'] . " " . $this->m_tblConfig['APP_NAME'] ."**/
/** Client  : ".$this->m_tblConfig['CLIENT']."                                      **/
/** Version : SAS V9                                                                **/
/** Author  : WLT                                                                   **/
/** Date    : ".date("d/m/Y")."                                                **/
/*************************************************************************************/
/** Description                                                                     **/
/** -----------                                                                     **/
/**   IMPORT of eCRF Data in CDISC ODM format into SAS                              **/
/**                                                                                 **/
/*************************************************************************************/
/** Modifications                                                                   **/
/** -------------                                                                   **/
/** Date     | Author | Explications                                                **/
/**                                                                                 **/
/**                                                                                 **/
/*************************************************************************************/
/*************************************************************************************/

%let PATH_TO_CSV=PATH_TO_CSV_WITHOUT_ENDING_SLASH;

%include \"&PATH_TO_CSV\\importFormat.sas\";
";

    //Boucle sur les itemgroup, traitement des tables 
    //non poolées horizontalement (les pools verticaux sont gérés ici)
    //Les tables poolées horizontalement sont stockées dans le table TblPoolH pour utilisation en deuxième temps
    $tblPoolH = array();
    $tblHeadLine = array(); //Entete - correspondance nom du champ - offset
    foreach($this->m_tblConfig[$exportIndex] as $ItemGroupOID => $ItemGroupDest){
      $tblFields = $ItemGroupDest['FIELDLIST'];
      if(isset($ItemGroupDest['CUSTOM_HEADLINE'])){
        $tblCustomHeadLine = $ItemGroupDest['CUSTOM_HEADLINE']; 
      }else{
        $tblCustomHeadLine = null;
      }
      $destFile = $ItemGroupDest['FILEDEST'];
      if(isset($ItemGroupDest['POOL']) && $ItemGroupDest['POOL']=="H"){
        $tblPoolH[$destFile][] = $ItemGroupOID;
      }else{
        if(!isset($ItemGroupDest['POOL']) || isset($ItemGroupDest['POOL']) && $ItemGroupDest['POOL']=="V"){
          //2 minutes par ItemGroup
          set_time_limit(240);
          
          echo "<br/>Exporting $ItemGroupOID...";
          flush();
          
          //Lecture des metadatas - pour connaitre les champs
          $query = "let \$ItemGroupDef := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemGroupDef[@OID='$ItemGroupOID']
                    for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                    let \$ItemDef := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                    
                    return
                      <ItemRef ItemOID='{\$ItemRef/@ItemOID}' 
                               Name='{\$ItemDef/@SASFieldName}' 
                               DataType='{\$ItemDef/@DataType}' 
                               CodeListOID='{\$ItemDef/odm:CodeListRef/@CodeListOID}'
                               Label='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()}'
                               Length='{\$ItemDef/@Length}'
                               SignificantDigits='{\$ItemDef/@SignificantDigits}'/>";
          try{
            $tblItemRef = $this->m_ctrl->socdiscoo()->query($query);
          }catch(xmlexception $e){
            $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
            $this->addLog("Erreur : export() => $str",FATAL);
            die($str);
          } 
                          
          //Creation du fichier csv destination
          $filename = $tmp.'/'.$uid."/$destFile.csv";
          $fexists = file_exists($filename);
          $fp = fopen($filename, 'a+');
          
          $procSAS .= " 

  data $destFile;
  infile \"&PATH_TO_CSV\\$destFile.csv\" 
  delimiter = ';' MISSOVER DSD lrecl=32767 firstobs=2;
    
    INPUT";
          
          $procSASformat = "
                              
    Format ";
          
          $procSASlabel = "
                   
    Label ";
              
          //Ligne d'entete - noms des champs
          if($fexists==false){
            $tblHeadLine = array();
            
            $line = array();
            foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $contextVar => $infos){
              $line[] = $contextVar;  
              $procSAS .= "
       $contextVar";
                  
              if($infos['type']=="string" || $infos['type']==="text"){
                $procSAS .= " \$ ";
              }
                             
              if($infos['codelist']!=""){
                //We remove the $ character
                $CodelistOID = str_replace(".\$","_",$infos['codelist']);
                $procSASformat .= $contextVar . " " . $CodelistOID. ".
       ";
              }else{
                $procSASformat .= $contextVar . " " . $this->getSasFormat($infos['type'],
                                                                          $infos['length'],
                                                                          $infos['significantDigits']) . "
       ";
              }
              $procSASlabel .= $contextVar . "= \"" . $infos['name'] . "\" 
       ";
            }
    
            //The headline fields could be customised
            if($tblCustomHeadLine==null){          
              foreach($tblItemRef as $ItemRef){
                $OID = (string)$ItemRef['ItemOID'];
                $SASName = (string)$ItemRef['Name'];
                if(!is_array($tblFields) || in_array($OID,$tblFields)){
                  $line[] = $SASName;                 
                }
              }
            }else{
              foreach($tblCustomHeadLine as $SasName){
                $line[] = $SasName;
              }
            }
            
            $colNum = 0;

            foreach($line as $col){
              $tblHeadLine["$col"] = $colNum;

              $bItemFind = false;
              $i=0;
              while($bItemFind==false && $i<count($tblItemRef)){
                if((string)($tblItemRef[$i]['Name'])==$col){
                  $DataType = (string)($tblItemRef[$i]['DataType']);
                  $CodeListOID = (string)($tblItemRef[$i]['CodeListOID']); 
                  if($DataType=="string" || $DataType=="text" || $DataType=="partialDate" || $DataType=="partialTime"){
                    $procSAS .= "
       $col \$";
                  }else{
                    $procSAS .= "
       $col";
                  }
                  
                  if($CodeListOID!=""){
                    //We remove the $ character
                    $CodeListOID = str_replace(".\$","_",$CodeListOID);
                    $procSASformat .= $col . " " . $CodeListOID . ".
       ";
                  }else{
                    $procSASformat .= $col . " " . $this->getSasFormat($DataType,
                                                          (string)($tblItemRef[$i]['Length']),
                                                          (string)($tblItemRef[$i]['SignificantDigits'])) . "
       ";
                  }
                  $procSASlabel .= $col . "= \"" . (string)($tblItemRef[$i]['Label']) . "\" 
       ";
                  $bItemFind = true;    
                }
                $i++;  
              }             
              $colNum++;
            }
            fputcsv($fp, $line,';');     
          }else{
            //File already exists, data are append to the end of file.
            //here we check that whe have the all columns are present in the headline
            foreach($tblItemRef as $ItemRef){
              if(!array_key_exists((string)$ItemRef['Name'],$tblHeadLine)){
                $str = "<html>Export / ItemGroupOID $ItemGroupOID / Field {$ItemRef['Name']} Not found in headline = ".print_r($tblHeadLine).".</html> (". __METHOD__ .")";
                $this->addLog("Erreur : export() => $str",FATAL);
                die($str);                  
              }
            }
          }
          
          $procSAS .= "\n;" . $procSASformat . "\n;" . $procSASlabel . "\n; \nrun;";
          
          //$subjCol = "collection('0703.dbxml')";
          //Data extraction
          $query = "
                    let \$SubjectData := $subjCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK']
                    
                    let \$BLANKSubjectData := collection('BLANK.dbxml')/odm:ODM/odm:ClinicalData/odm:SubjectData
                    
                    let \$MetaDataVersion := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')
                    let \$ItemGroupDef := \$MetaDataVersion/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemGroupDef[@OID='$ItemGroupOID']
                    for \$ItemGroupData in \$SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @TransactionType!='Remove']
                    let \$SubjectKey := \$ItemGroupData/../../../@SubjectKey
                    let \$StudyEventOID := \$ItemGroupData/../../@StudyEventOID
                    let \$StudyEventRepeatKey := \$ItemGroupData/../../@StudyEventRepeatKey
                    let \$FormOID := \$ItemGroupData/../@FormOID
                    let \$FormRepeatKey := \$ItemGroupData/../@FormRepeatKey
                    let \$StudyEventName := \$MetaDataVersion/odm:ODM/odm:Study/odm:MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventOID]/odm:Description/odm:TranslatedText               
                    where count(\$ItemGroupData/odm:*) gt count(\$BLANKSubjectData/odm:StudyEventData[@StudyEventOID=\$StudyEventOID and @StudyEventRepeatKey=\$StudyEventRepeatKey]/odm:FormData[@FormOID=\$FormOID and @FormRepeatKey=\$FormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey=\$ItemGroupData/@ItemGroupRepeatKey]/odm:*) 
                    (:
                    for \$ItemGroupData in \$SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @TransactionType!='Remove']
                    :)
                    return
                      <ItemGroupData SubjectKey='{\$SubjectKey}' 
                                     StudyEventOID='{\$StudyEventOID}'
                                     StudyEventName='{\$StudyEventName}'>
                      {
                        for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                        let \$ItemDef := \$MetaDataVersion/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                        let \$ItemData := (\$ItemGroupData/odm:*[@ItemOID=\$ItemRef/@ItemOID])[last()]
                        return
                          <ItemData OID='{\$ItemRef/@ItemOID}' Name='{\$ItemDef/@SASFieldName}' Value='{\$ItemData/string()}'/>
                      }
                      </ItemGroupData>  
                   ";
      
          try{         
            $ItemGroupDatas = $this->m_ctrl->socdiscoo()->query($query);
          }catch(xmlexception $e){
            $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
            $this->addLog("Erreur : exportDSMB() => $str",FATAL);
            die($str);
          } 
          
          //Boucle sur les lignes
          $rowNum = 0;
          foreach($ItemGroupDatas as $ItemGroupData){
            $line = array_fill(0,count($tblHeadLine),"");
            $subjKey = (string)$ItemGroupData['SubjectKey'];

            //Fill the context vars
            foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $key=>$col){
              if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
                $line[$tblHeadLine["$key"]] = (string)$tblSubj[$subjKey]["$key"];
              }else{
                //Handling of magic keywords
                switch($key){
                  case 'SUBJID' :
                            $line[$tblHeadLine["SUBJID"]] = (string)$ItemGroupData['SubjectKey'];
                            break;
                  case 'VISITNUM' :
                            $line[$tblHeadLine["VISITNUM"]] = (string)$ItemGroupData['StudyEventOID'];
                            break;
                  case 'VISITNAME' :
                            $line[$tblHeadLine["VISITNAME"]] = (string)$ItemGroupData['StudyEventName'];
                            break;
                }  
              }
            }
                        
           foreach($ItemGroupData as $ItemData){
              $itemName = (string)$ItemData['Name'];
              $OID = (string)$ItemData['OID'];
              if(!is_array($tblFields) || in_array($OID,$tblFields)){
                $line[$tblHeadLine[$itemName]] = utf8_decode($ItemData['Value']);
              }
            }
            $rowNum++;
            //Add line of data to csv file
            fputcsv($fp, $line,';');
          }
          fclose($fp); 
          
          echo "$rowNum lignes exportées vers le fichier $destFile.csv";
          flush();                          
        }
      }
    }

    //Boucle sur les tables, traitement des itemgroup poolées horizontalement
    //Un pooling horizontal implique qu'il n'y ai q'un seul itemgroupdata de chaque itemgroup par visite
    $tblFields = array();
    foreach($tblPoolH as  $destFile => $ItemGroupOIDList){
      //2 minutes par ItemGroup
      set_time_limit(240);
      
      $xQuerySelect = "";
      $xQuerySelectIGOID = "";
      $lstIGOID = "";  //Just for printing
      for($i=0;$i<count($ItemGroupOIDList);$i++){
        $ItemGroupOID = $ItemGroupOIDList[$i];
        $xQuerySelect .= "@OID='$ItemGroupOID'";
        $xQuerySelectIGOID .= "@ItemGroupOID='$ItemGroupOID'";
        $lstIGOID .= "$ItemGroupOID ,";
        if( $i<count($ItemGroupOIDList)-1 ){
          $xQuerySelect .= " or ";
          $xQuerySelectIGOID .= " or ";  
        }
        if(isset($this->m_tblConfig[$exportIndex][$ItemGroupOID]['FIELDLIST'])){
          $tblFields = array_merge($tblFields,$this->m_tblConfig[$exportIndex][$ItemGroupOID]['FIELDLIST']);
        }
      }   
        
      //Lecture des metadatas - pour connaitre les champs
      $query = "let \$ItemGroupDef := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemGroupDef[$xQuerySelect]
                for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                let \$ItemDef := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                return
                  <ItemRef ItemOID='{\$ItemRef/@ItemOID}' 
                           Name='{\$ItemDef/@SASFieldName}'
                           DataType='{\$ItemDef/@DataType}' 
                           CodeListOID='{\$ItemDef/odm:CodeListRef/@CodeListOID}'
                           Label='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()}'
                           Length='{\$ItemDef/@Length}'
                           SignificantDigits='{\$ItemDef/@SignificantDigits}'/>";
      try{
        $tblItemRef = $this->m_ctrl->socdiscoo()->query($query);
      }catch(xmlexception $e){
        $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
        $this->addLog("Erreur : export() => $str",FATAL);
        die($str);
      } 
      
      $procSAS .= " 

data $destFile;
infile \"&PATH_TO_CSV\\$destFile.csv\" 
delimiter = ';' MISSOVER DSD lrecl=32767 firstobs=2;

  INPUT
    ";

      $procSASformat = "
                              
    Format ";
          
      $procSASlabel = "
                   
    Label ";                      
      //Creation du fichier csv destination
      $filename = $tmp.'/'.$uid."/$destFile.csv";
      $fexists = file_exists($filename);
      $fp = fopen($filename, 'a+');
      
      //Ligne d'entete - noms des champs
      if($fexists==false){
        $colNum = 0;
        $line = array();
        foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $contextVar => $infos){
          $line[] = $contextVar;  
          $procSAS .= "
       $contextVar";
              
          if($infos['type']=="string" || $infos['type']==="text"){
            $procSAS .= " \$ ";
          }
                         
          if($infos['codelist']!=""){
            //We remove the $ character
            $CodelistOID = str_replace(".\$","_",$infos['codelist']);    
            $procSASformat .= $contextVar . " " . $CodelistOID . "
       ";
          }else{
            $procSASformat .= $contextVar . " " . $this->getSasFormat($infos['type'],
                                                                   $infos['length'],
                                                                   $infos['significantDigits']) . "
       ";
          }
          $procSASlabel .= $contextVar . " = \"" . $infos['name'] . "\" 
       ";
        }  

        foreach($tblItemRef as $ItemRef){
          $OID = (string)$ItemRef['ItemOID'];
          if(count($tblFields)==0 || in_array($OID,$tblFields)){
            $line[] = (string)$ItemRef['Name']; 
            $colNum++;
            
            $procSAS .= "
       ".(string)$ItemRef['Name'];
            
            $bItemFind = false;
            $i=0;              
            while($bItemFind==false && $i<count($tblItemRef)){
              if((string)($tblItemRef[$i]['Name'])==(string)$ItemRef['Name']){
                $DataType = (string)($tblItemRef[$i]['DataType']);
                $CodeListOID = (string)($tblItemRef[$i]['CodeListOID']);
                $SASName = (string)$ItemRef['Name']; 
                if($DataType=="string" || $DataType=="text" || $DataType=="partialDate" || $DataType=="partialTime"){
                  $procSAS .= " \$ ";
                }
                if($CodeListOID!=""){
                  //We remove the $ character
                  $CodeListOID = str_replace(".\$","_",$CodeListOID);    
                  $procSASformat .= $SASName . " " . $CodeListOID . ".
       ";
                }else{
                  $procSASformat .= $SASName . " " . $this->getSasFormat($DataType,
                                                        (string)($tblItemRef[$i]['Length']),
                                                        (string)($tblItemRef[$i]['SignificantDigits'])) . "
       ";
                }
                $procSASlabel .= $SASName . " = \"" . (string)($tblItemRef[$i]['Label']) . "\" 
         ";
                $bItemFind = true;    
              }
              $i++;  
            }
          }
        }
        fputcsv($fp, $line,';');     
      }

      $procSAS .= "\n;" . $procSASformat . "\n;" . $procSASlabel . "\n; \nrun;";
 
      $query = "
                let \$SubjectData := $subjCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK']
                let \$BLANKSubjectData := doc('BLANK.dbxml')/odm:ODM/odm:ClinicalData/odm:SubjectData
                
                let \$MetaDataVersion := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')
                let \$ItemGroupDef := \$MetaDataVersion/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemGroupDef[$xQuerySelect]
                for \$StudyEventData in \$SubjectData/odm:StudyEventData[odm:FormData/odm:ItemGroupData[$xQuerySelectIGOID and @TransactionType!='Remove']]
                let \$SubjectKey := \$StudyEventData/../@SubjectKey
                let \$StudyEventOID := \$StudyEventData/@StudyEventOID
                let \$StudyEventRepeatKey := \$StudyEventData/@StudyEventRepeatKey
                let \$StudyEventName := \$MetaDataVersion/odm:ODM/odm:Study/odm:MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventOID]/odm:Description/odm:TranslatedText               
                let \$nbIG := count(\$StudyEventData/odm:FormData/odm:ItemGroupData[$xQuerySelectIGOID and @TransactionType!='Remove'])
                return
                  <StudyEventData SubjectKey='{\$SubjectKey}' 
                                 StudyEventOID='{\$StudyEventOID}'
                                 StudyEventName='{\$StudyEventName}'
                                 NbIG='{\$nbIG}'>
                  {
                    for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                    let \$ItemDef := doc('MetaDataVersion.dbxml/{$this->m_tblConfig["METADATAVERSION"]}')/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                    let \$ItemData := (\$StudyEventData/odm:FormData/odm:ItemGroupData/odm:*[@ItemOID=\$ItemRef/@ItemOID])[last()]
                    return
                      <ItemData OID='{\$ItemRef/@ItemOID}' Name='{\$ItemDef/@SASFieldName}' Value='{\$ItemData/string()}'/>
                  }
                  </StudyEventData>  
               ";
  
      try{         
        $StudyEventDatas = $this->m_ctrl->socdiscoo()->query($query);
      }catch(xmlexception $e){
        $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
        $this->addLog("Erreur : exportDSMB() => $str",FATAL);
        die($str);
      } 
 
        
      //Boucle sur les lignes
      $rowNum = 0;
      foreach($StudyEventDatas as $ItemGroupData){
        //Preliminary check : we expect only one ItemGroupData of each Itemgroup listed per StudyEventData
        if($ItemGroupData['NbIG']>count($ItemGroupOIDList)){
          $str = "<html>Export / Subject {$ItemGroupData['SubjectKey']} / Visit {$ItemGroupData['StudyEventOID']}-{$ItemGroupData['StudyEventName']} : Expecting ".count($ItemGroupOIDList)." ItemGroupData, but {$ItemGroupData['NbIG']} found. ($xQuerySelectIGOID)</html> (". __METHOD__ .")";
          $this->addLog("Erreur : exportDSMB() => $str",FATAL);
          die($str);          
        }
        $colNum = 0;
        $line = array();
        $subjKey = (string)$ItemGroupData['SubjectKey'];

        foreach($this->m_tblConfig['EXPORT']['TYPE'][$type]['contextVars'] as $key=>$col){
          if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
            $line[$tblHeadLine["$key"]] = (string)$tblSubj[$subjKey]["$key"];
          }else{
            //Handling of magic keywords
            switch($key){
              case 'SUBJID' :
                        $line[$tblHeadLine["SUBJID"]] = (string)$ItemGroupData['SubjectKey'];
                        break;
              case 'VISITNUM' :
                        $line[$tblHeadLine["VISITNUM"]] = (string)$ItemGroupData['StudyEventOID'];
                        break;
              case 'VISITNAME' :
                        $line[$tblHeadLine["VISITNAME"]] = (string)$ItemGroupData['StudyEventName'];
                        break;
            }  
          }
        }

        foreach($ItemGroupData as $ItemData){
          $OID = (string)$ItemData['OID'];
          if(count($tblFields)==0 || in_array($OID,$tblFields)){
            $line[] = utf8_decode($ItemData['Value']);
          }
          $colNum++;
        }
        $rowNum++;
        //Ajout de la ligne dans le fichier csv
        fputcsv($fp, $line,';');
      }
      fclose($fp); 
      
      echo "<br/>$rowNum lignes exportées vers le fichier $destFile.csv depuis les itemgroups $lstIGOID";
      flush();  
    }
    
    file_put_contents($tmp.'/'.$uid . '/import.sas',$procSAS);
    file_put_contents($tmp.'/'.$uid . '/importFormat.sas',$procSasFormat);
    
    $dsmbFileName = $type."_".date('Y_m_d_H_i').".zip";
    $dsmbFile = $this->m_tblConfig["EXPORT_BASE_PATH"] . $dsmbFileName;
    
    shell_exec('cd '.$tmp.'/'.$uid.';zip -P '.$uid.' -r '.escapeshellarg($dsmbFile).' ./');
    
    $sql = "INSERT INTO egw_alix_export(exportname,exportpath,exporttype,exportdate,exportpassword,exportuser,currentapp)
            VALUES('$dsmbFileName','{$this->m_tblConfig["EXPORT_BASE_PATH"]}','$type',now(),'$uid','{$GLOBALS['egw_info']['user']['userid']}','{$GLOBALS['egw_info']['flags']['currentapp']}')";
  
    $GLOBALS['egw']->db->query($sql);
  }
  
  public function getExportList(){
    $sql = "SELECT exportid,exportname,exportpath,exportdate,exportpassword,exportuser,exporttype
            FROM egw_alix_export
            WHERE currentapp='".$GLOBALS['egw_info']['flags']['currentapp']."'
            ORDER BY exportdate DESC";
            
    $GLOBALS['egw']->db->query($sql);        

    $tblExport = array();
    while($GLOBALS['egw']->db->next_record()){
      $tblExport[] = array('exportid' => $GLOBALS['egw']->db->f('exportid'),
                           'exporttype' => $GLOBALS['egw']->db->f('exporttype'),
                           'exportname' => $GLOBALS['egw']->db->f('exportname'), 
                           'exportpath' => $GLOBALS['egw']->db->f('exportpath'),
                           'exportdate' => $GLOBALS['egw']->db->f('exportdate'),
                           'exportpassword' => $GLOBALS['egw']->db->f('exportpassword'),
                           'exportuser' => $GLOBALS['egw']->db->f('exportuser'));  
    }
    return $tblExport;
  }
  
  public function getExportFile($exportId){    
    
    //Recuperation des informations sur le fichier demandé
    $sql = "SELECT exportname,exportpath
            FROM egw_alix_export
            WHERE exportid='$exportId'";
            
    $GLOBALS['egw']->db->query($sql);    
    
    if($GLOBALS['egw']->db->next_record()){
      $filename = $GLOBALS['egw']->db->f('exportname');
      $filepath = $GLOBALS['egw']->db->f('exportpath') . $filename;
    }
    
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    
    readfile($filepath);
  } 
  
  /*                    
  @param string DataType : ODM itemDef DataType : string, text, integer, float,datetime,date,partialDate,partialTime
  @return string sas format to be inserted in the SAS Import Macro
  @author wlt
  */
  private function getSasFormat($DataType,$Length,$SignificantDigits){
    $sasFormat = "";
    switch($DataType){
      case "string" :
      case "text" :
        $sasFormat = "\$CHAR" . $Length .  ".";
        break;
      case "integer" :
        $sasFormat =  $Length . ".";
        break;
      case "float" : 
        $sasFormat = $Length . "." . $SignificantDigits . ".";
        break;
      case "date" :
        $sasFormat = "is8601da.";
        break;
      case "partialDate" :
        $sasFormat = "\$CHAR19.";
        break;
      case "time" :
        $sasFormat = "is8601tm.";
        break;
      case "partialTime" :
        $sasFormat = "\$CHAR10.";
        break;  
      case "datetime" :
        $sasFormat = "is8601dt.";
        break;
      default :
        $this->addLog("boexport::getSasFormat() => Unkown datatype $DataType",FATAL);
    }     
    return $sasFormat;
  } 
}