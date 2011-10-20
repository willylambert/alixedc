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

class boimport extends CommonFunctions
{  

  //Constructeur
  function __construct($tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }               

/*
@desc import un des droits fichiers de coding : AE, CM ou MH
      le fichier d'import est au format csv (avec virgule)
      les étapes de vérification sont décrites dans le protocole de coding  
@author wlt
*/
  function importCoding($codingFile)
  {
    //Première étape : vérification de la structure
    $filename = basename($codingFile);
    $ItemGroupOID = substr($filename,4,2);
    
    if(!in_array($ItemGroupOID,array('CM','MH','AE'))){
      die("Fichier $filename incompatible, attendu : table CM, MH ou AE");  
    }
    
    $tblErrors = array();
    
    //Vérification des colonnes
    $tblCol = $this->m_tblConfig['CODING_VAR']["$ItemGroupOID"]['FIELDLIST'];

    switch($ItemGroupOID){
      case 'AE' :
          $StudyEventOID = 'AE';
          $StudyEventRepeatKey = '0';
          $FormOID = 'FORM.AE';
          $SEQFIELD = "AE.AESEQ";
          $TERMFIELD = "AE.AETERM";
          //Ajout des colonnes de contexte. Note : on rajoute "AE" par convention pour le test de présence qui suit
          $tblCol = array_merge($tblCol,array('AE.SUBJID','AE.PATID','AE.SITEID','AE.SUBJINIT','AE.COUNTID'));
          break;
      case 'CM' :
          $StudyEventOID = 'CM';
          $StudyEventRepeatKey = '0';
          $FormOID = 'FORM.CM';
          $SEQFIELD = "CM.CMSEQ";
          $TERMFIELD = "CM.CMTRT";
          //Ajout des colonnes de contexte. Note : on rajoute "CM" par convention pour le test de présence qui suit
          $tblCol = array_merge($tblCol,array('CM.SUBJID','CM.PATID','CM.SITEID','CM.SUBJINIT','CM.COUNTID'));
          break;
      case 'MH' :
          $StudyEventOID = '1';
          $StudyEventRepeatKey = '0';
          $FormOID = 'FORM.MH';
          $SEQFIELD = "MH.MHSEQ";
          $TERMFIELD = "MH.MHTERM";
          //Ajout des colonnes de contexte. Note : on rajoute "MH" par convention pour le test de présence qui suit
          $tblCol = array_merge($tblCol,array('MH.SUBJID','MH.PATID','MH.SITEID','MH.SUBJINIT','MH.COUNTID'));
          break;
    }
    
    $handle = fopen($codingFile, "r");
    $headLine = fgetcsv($handle, 1000, ",") or die("unable to read the headline of the file $codingFile");
    
    foreach($headLine as $col){
      $ItemOID = $ItemGroupOID . "." . strtoupper($col); 
      if(!in_array($ItemOID,$tblCol)){
        die("Column $col ($ItemOID) not found in table $ItemGroupOUD");
      }
    }
    
    //Lecture des lignes
    while (($line = fgetcsv($handle, 1000, ",")) !== FALSE){
      //Préparation des clés
      $SubjectKey = $line[0];
      $subjInit = $line[3];      
      $SEQVAL = $line[5];
      $TERMVAL = $line[6];
      
      set_time_limit(240);
                
      //Recuperation de la ligne correspondante dans la base eCRF
      $query = "  
        declare function local:getLastValue(\$ItemData as node()*) as xs:string?
        {
          let \$v := '' (:car il nous faut un let :)
          return \$ItemData[last()]/string()
        };
  
        for \$ItemGroupData in collection('$SubjectKey.dbxml')/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID']/odm:FormData[@FormOID='$FormOID']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @TransactionType!='Remove']
        let \$FormData := \$ItemGroupData/..
        let \$SubjectData := \$ItemGroupData/../../../../odm:SubjectData
        let \$ItemGroupDataENROL := \$SubjectData/odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']

        let \$SubjId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SUBJID'])
  			let \$SubjInit := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SUBJINIT'])

        let \$SEQ := local:getLastValue(\$ItemGroupData/odm:ItemDataInteger[@ItemOID='$SEQFIELD'])
        let \$TERM := local:getLastValue(\$ItemGroupData/odm:ItemDataString[@ItemOID='$TERMFIELD'])
        where \$SEQ='$SEQVAL'  
        return
          <itemgroup
              subjId='{\$SubjId}'
              subjInit='{\$SubjInit}'
              formRepeatKey='{\$FormData/@FormRepeatKey}'
              itemGroupRepeatKey='{\$ItemGroupData/@ItemGroupRepeatKey}'
              term='{\$TERM}'
          /> 
        "; 
  
      try{
        $result = $this->m_ctrl->socdiscoo($SubjectKey, true)->query($query);
      }catch(xmlexception $e){
        $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html>";
        $this->addLog($str,FATAL);
        die($str);
      }           

      //On ne doit avoir qu'une seule ligne
      if(count($result)==0){
        $tblErrors[] = "SUBJID=$SubjectKey, SEQ=$SEQVAL : No corresponding ItemGroupData found in eCRF Database";
      }else{
        if(count($result)>1){
          $tblErrors[] = "SUBJID=$SubjectKey, SEQ=$SEQVAL : More than 1 ItemGroupData found in eCRF Database (n=".count($result).")";
        }else{
          //On a retrouvé notre ligne dans la base eCRF
          //Vérification des valeurs des clés (SUBJID, SUBJINIT)
          $itemgroupdata = $result[0];
          if($SubjectKey!=$itemgroupdata['subjId'] || strtoupper($subjInit)!=strtoupper($itemgroupdata['subjInit'])){
            $tblErrors[] = "SUBJID=$SubjectKey, SEQ=$SEQVAL : SUBJID (import=$SubjectKey eCRF={$itemgroupdata['subjId']} or SUBJINIT (import=$subjInit eCRF={$itemgroupdata['subjInit']}) are not matching. Importation of the current line is canceled";  
          }else{
            //Les clés sont bonnes, on vérifie que le terme à coder n'a pas été modifié entre temps
            if($TERMVAL!=utf8_decode($itemgroupdata['term'])){
              //le terme a été modifié entre temps, on n'importe pas la ligne
              $tblErrors[] = "SUBJID=$SubjectKey, SEQ=$SEQVAL : TERM has changed during the coding elapsed time import=$TERMVAL eCRF={$itemgroupdata['term']}. Importation of the current line is canceled";  
            }else{
              //On lance l'importation de la ligne en cours
              $tblFilledVar = array();
              $FormRepeatKey = $itemgroupdata['formRepeatKey'];
              $ItemGroupRepeatKey = $itemgroupdata['itemGroupRepeatKey'];
              for($i=5;$i<count($line);$i++){
                $tblFilledVar[$ItemGroupOID.".".strtoupper($headLine[$i])] = utf8_encode($line[$i]);
              }
              //$this->dumpPre($tblFilledVar);
              $hasModif = $this->m_ctrl->bocdiscoo()->saveItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$tblFilledVar,"Coding Import","BDLS","","",true);     
              if($hasModif){
                $nbUpdatedRecords++;  
              }
            }
          }
        }
      }
    }
    
    $importDate = date("c");
    $importUser = $this->m_ctrl->boacl()->getUserId();  
    $lstErrors = "";

    echo "importDate = " . $importDate . "<br/>";
    echo "importUser = " . $importUser . "<br/>";
    echo "nbOfBlockingErrors = " . count($tblErrors) . "<br/>";
    echo "nbUpdatedRecords = $nbUpdatedRecords <br/>";

    foreach($tblErrors as $errors){
      $lstErrors .= $errors . "\line ";
    }
    
    $nbOfBlockingErrors = count($tblErrors);
    if($nbOfBlockingErrors==0){
      $importStatus = "SUCCESS";
    }else{
      $importStatus = "ERRORS";
    }
    
    //Ecriture du report
    $reportContent = file_get_contents($this->m_tblConfig['IMPORT_BASE_PATH'] . "coding_report_template.rtf");

    $search = array("DATE_IMPORT_CODING","USER_IMPORT_CODING","ITEMGROUPOID","STATUS_IMPORT_CODING","NB_ERRORS","NB_UPDATED_RECORDS","BLOCKING_ERRORS");
    $replace = array($importDate,$importUser,$ItemGroupOID,$importStatus,$nbOfBlockingErrors,$nbUpdatedRecords,$lstErrors);
    $reportContent = str_replace($search,$replace,$reportContent);
    
    $filename = $this->m_tblConfig['IMPORT_BASE_PATH'] . "coding_report_".$ItemGroupOID."_".date("Ymd_Hi").".rtf";
    file_put_contents($filename,$reportContent);
     
    $sql = "INSERT INTO egw_alix_import(DATE_IMPORT_FILE,USER,STATUS,ERROR_COUNT,IMPORT_FILE,REPORT_FILE,IMPORT_TYPE,DATE_IMPORT,currentapp,importpath)
            VALUES('$ecgFileCreationDate','$importUser','$importStatus','$nbOfBlockingErrors','".basename($codingFile)."','".basename($filename)."','Coding',now(),'".$GLOBALS['egw_info']['flags']['currentapp']."','".$this->m_tblConfig['IMPORT_BASE_PATH']."')";
  
    $GLOBALS['egw']->db->query($sql);
  }

/*
@desc import le fichier ECG, en executant plusieurs étapes de vérification
@author wlt
*/   
  function importECG($ecgFile)
  {
    global $configEtude;   
    $configEtude["LOG_LONG_EXECUTION"] = false;

    $xml = new DOMDocument();
    $xml->load($ecgFile);
    
    ob_end_flush();
    ob_flush(); 
    flush();
    
    echo "cleaning...";
    flush();
        
    // Enable user error handling
    libxml_use_internal_errors(true);
    
    //On gère deux tableaux d'erreurs :
    //Les erreurs globales bloquant l'import complet
    //Les erreurs au niveau patient, bloquant l'import d'un patient
    $tblBlockingErrors = array();
    $tblSubjBlockingErrors = array();
    
    //Etape preliminaire : on gère les valeurs vides, en modifiant les balise ItemDataXXX en ItemDataAny
    $xPath = new DOMXPath($xml);
    $xPath->registerNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3");

    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:ItemDataInteger");        
    foreach($result as $itemData){
      if($itemData->nodeValue==""){
        $newNode = $itemData->ownerDocument->createElementNS("http://www.cdisc.org/ns/odm/v1.3",'ItemDataAny');
        if ($itemData->attributes->length) {
          foreach ($itemData->attributes as $attribute) {
              $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
          }
        }
        $itemData->parentNode->replaceChild($newNode,$itemData);        
      }     
    }

    //Etape preliminaire : on gère les valeurs vides ItemDataDate, en les supprimant
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:ItemDataDate");        
    foreach($result as $itemData){
      if($itemData->nodeValue==""){
        $itemData->parentNode->removeChild($itemData);     
      }   
    }
    
    //Enregistrement des modifications ItemDataXXX en ItemDataAny (si valeur vide)
    $xml->save($ecgFile);
    echo "ok<br/>";flush();
    
    echo "checking schema...";flush();
    //Première étape : vérification de la conformité par rapport au schéma ODM
    if(!$xml->schemaValidate($this->m_tblConfig["ODM_1_3_SCHEMA"]))
    {
      $errors = libxml_get_errors();
      foreach ($errors as $error) {
        $errorLib = "";
        switch ($error->level){
            case LIBXML_ERR_WARNING:
                $errorLib = "Warning $error->code : ";
                break;
            case LIBXML_ERR_ERROR:
                $errorLib = "Error $error->code : ";
                break;
            case LIBXML_ERR_FATAL:
                $errorLib = "Fatal Error $error->code : ";
                break;
        }
        $errorLib .= trim($error->message) . " on line $error->line";
        $tblBlockingErrors[] = $errorLib;
      }
      libxml_clear_errors();
    }
    echo "ok<br/>";flush();
    
    echo "checking keys...";flush();    
    //2eme étape : vérification des clés (présence et valeur)
    $xml = simplexml_load_file($ecgFile);
    $xml->registerXPathNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3");
    
    $clinicalData = $xml->ClinicalData;
    
    //Recuperation des codelists depuis les meetadatas
    $clTests = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$EGTEST','en');
    $clUnits = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$EGUNIT','en');
    
    //Liste des ItemData devant impérativement être présent dans l'import, pour chaque itemgroupdata
    $tblItemDataMandatory = array("EG.BRTHDTC","EG.EGSEQ","EG.EGORRES","EG.EGTEST","EG.EGORRESU","EG.EGSTAT","EG.EGREASND","EG.EGDTC","EG.EGTIM","EG.EGORNRLO","EG.EGORNRHI","EG.EGNRIND","EG.EGCLSIG","EG.EGLOC");
       
    //Boucle sur les patients     
    foreach($clinicalData->SubjectData as $subjectData){
      $subjectKey = (string)$subjectData["SubjectKey"];
      
      set_time_limit(120);
      
      //We only need to lock one patient
      $this->m_ctrl->socdiscoo($subjectKey,true);
      
      //extraction du patient de la base
      $subj = $this->m_ctrl->bocdiscoo()->getSubjectList("","\$SubjectData/@SubjectKey='$subjectKey'");
      if(!isset($subj[0]->subj['colBRTHDTC'])){ //Patient non trouvé
        $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey not found in eCRF Database";
        //On passe au patient suivant 
        continue;             
      }else{
        $subjBRTHDTC = (string)$subj[0]->subj['colBRTHDTC'];              
      }
            
      //Boucle sur les Visites
      foreach($subjectData->StudyEventData as $studyEventData){
        $studyEventOID = (string)$studyEventData["StudyEventOID"];
        $studyEventRepeatKey = (string)$studyEventData["StudyEventRepeatKey"];
        
        //StudyEventOID autorisé : de 1 à 30
        $tblSEOID = range(1,30);
        if( !in_array($studyEventOID,$tblSEOID) ){
          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID not in range [0;30]";    
        }
        
        //StudyEventRepeatKey autorisé = 0
        if(!$studyEventRepeatKey=="0"){
          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey <> 0";
        } 
      
        //Boucle sur les formulaires
        foreach($studyEventData->FormData as $formData){
          $formOID = (string)$formData['FormOID'];
          $formRepeatKey = (string)$formData['FormRepeatKey'];
          
          //FormOID autorisé = FORM.EG
          if($formOID!="FORM.EG"){
            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID <> FORM.EG";              
          }
          
          //FormRepeatKey autorisé = 0
          if($formRepeatKey!="0"){
            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID : FormRepeatKey $formRepeatKey <> 0";              
          }
          
          //On attend 13 lignes d'ItemGroupData par formulaire
          if(count($formData->ItemGroupData)!=13){
            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID : 13 ItemgroupData excepted; ".count($formData->ItemGroupData)." found";                
          }else{                    
            foreach($formData->ItemGroupData as $itemGroupData){
              $itemGroupOID = (string)$itemGroupData["ItemGroupOID"];
              $itemGroupRepeatKey = (integer)$itemGroupData["ItemGroupRepeatKey"];
              
              //ItemGroupOID autorisé = "EG"
              if($itemGroupOID!="EG"){
                $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID : FormRepeatKey $formRepeatKey : ItemGroupOID $itemGroupOID <> EG";                            
              }              
              
              //ItemGroupRepeatKey autorisé = [1;100]
              $tblIGRK = range(1,100);
              if( !in_array($itemGroupRepeatKey,$tblIGRK) ){
                $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey not in range [1;100]";                            
              }
              
              //Boucle sur les ItemData
              $tblItemDataImport = array();
              foreach($itemGroupData->children() as $itemData){
                $tblItemDataImport[] = (string)$itemData['ItemOID'];
                if($itemData['ItemOID']=="EG.EGTEST"){
                  if(!isset($clTests[(string)$itemData]) && (string)$itemData!=""){
                    $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGTEST='$itemData' not in CL.EGTEST codelist";                            
                  }                  
                }else{
                  if($itemData['ItemOID']=="EG.EGORRESU"){
                    if(!isset($clUnits[(string)$itemData]) && (string)$itemData!=""){
                      $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGORRESU='$itemData' not in CL.EGUNIT codelist";                            
                    }                  
                  }else{
                    if($itemData['ItemOID']=="EG.BRTHDTC"){
                      if($subjBRTHDTC!=(string)$itemData){
                        $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : Inconsistent Date of Birth (import=$itemData eCRF=$subjBRTHDTC )";                                                  
                      }
                    }
                  }              
                }    
              }
              //Avons-nous tous les itemdatas dans l'import ?
              $tblDiff = array_diff($tblItemDataMandatory,$tblItemDataImport);
              if(count($tblDiff)>0){
                foreach($tblDiff as $missingItemData){
                  $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData $missingItemData is missing";                                          
                }
              }            
            }
          }                    
        }
      }     
    }
    
    echo "ok<br/>";flush();
    
    echo "checking missing data...";flush();
    //3eme étape : vérification des données manquantes et déjà présentes dans un export précédent
    
    $subjectCol = $this->m_ctrl->socdiscoo("",true)->getClinicalDataCollection();
    set_time_limit(120);
    //On va chercher toutes les records d'ECG en base eCRF (itemgroupdata)
    $query = "  
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$v := '' (:car il nous faut un let :)
        return \$ItemData[last()]/string()
      };

      for \$ItemGroupDataEG in $subjectCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK']/odm:StudyEventData/odm:FormData[@FormOID='FORM.EG']/odm:ItemGroupData[@ItemGroupOID='EG' and @TransactionType!='Remove']
      let \$StudyEventData := \$ItemGroupDataEG/../..
      let \$SubjectData := \$ItemGroupDataEG/../../../../odm:SubjectData
      let \$MetaDataVersion := collection('MetaDataVersion.dbxml')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      let \$ItemGroupDataENROL := \$SubjectData/odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']

      let \$SiteId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITEID'])                
      let \$SiteName := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITENAME'])
      let \$SubjId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SUBJID'])
			
			let \$egtest := local:getLastValue(\$ItemGroupDataEG/odm:ItemDataString[@ItemOID='EG.EGTEST'])
			let \$egres :=  local:getLastValue(\$ItemGroupDataEG/odm:ItemDataString[@ItemOID='EG.EGORRES'])
			let \$egdt := local:getLastValue(\$ItemGroupDataEG/odm:ItemDataString[@ItemOID='EG.EGDTC'])
      return
        <eg   siteId='{\$SiteId}'
              siteName='{\$SiteName}'
              subjId='{\$SubjId}'
              subjectKey='{\$SubjectData/@SubjectKey}'
              studyEventOID='{\$StudyEventData/@StudyEventOID}'
              itemGroupRepeatKey='{\$ItemGroupDataEG/@ItemGroupRepeatKey}'
              egtest='{\$egtest}'
              egres='{\$egres}'
              egdt='{\$egdt}'
        /> 
      "; 

    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html>";
      $this->addLog($str,FATAL);
      die($str);
    }    
    
    //Boucle sur les données EG (itemgroupdata) déjà dans la base eCRF
    
    foreach($result as $eg){
      //On doit retrouver l'enregistrement dans les données envoyées
      //on ne test que les enregistement ayant une valeur en base eCRF, pour ne pas prendre en compte les data du BLANK

      $subjectKey = (string)$eg['subjectKey'];
      $studyEventOID = (string)$eg['studyEventOID'];
      $itemGroupRepeatKey = (string)$eg['itemGroupRepeatKey'];
      $egTest = (string)$eg['egtest'];
      $egRes = (string)$eg['egres'];
      $egDt = (string)$eg['egdt'];
      
      if($egRes!="" || $egDt!=""){           
        //Avous-nous l'enregistrement dans les données reçues ?
        //Car cela devrait être le cas ! Sinon, erreur bloquante
        $query = "/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey='$subjectKey']/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']/odm:FormData[@FormOID='FORM.EG' and @FormRepeatKey='0']/odm:ItemGroupData[@ItemGroupOID='EG' and @ItemGroupRepeatKey='$itemGroupRepeatKey']";
        $result = $xml->xpath($query);        
        if($result==false){
          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : record not found in incoming ecg file";                             
        }
      } 
    }
    
    if(count($tblBlockingErrors>0)){
      $importStatus = "FAIL";
    }else{
      $importStatus = "SUCCESS";
    }
    
    echo "ok<br/>";flush();
    
    $ecgFileCreationDate = $xml['CreationDateTime'];
    $importDate = date("c");
    $importUser = $this->m_ctrl->boacl()->getUserId();
    $nbOfBlockingErrors = count($tblBlockingErrors);
    $nbInsertedRecords = 0;
    $nbUpdatedRecords = 0;
    $nbInsertedValues = 0;
    $nbUpdatedValues = 0;
    
    $nbSubjectInsertedRecords = array();
    $nbSubjectUpdatedRecords = array();

    $nbSubjectInsertedValues = array();
    $nbSubjectUpdatedValues = array();
        
    if($nbOfBlockingErrors==0)
    {
      //Tout est ok, on  peut insérer les nouvelles lignes en base eCRF
      //On procéde patient par patient
      //Boucle sur les enregistrements en provenance de l'import
      foreach($clinicalData->SubjectData as $subjectData){
        echo "inserting data for subject " . $subjectData["SubjectKey"] . " ...";flush();
        set_time_limit(120);
        
        $subjectKey = (string)$subjectData["SubjectKey"];
        $nbSubjErrors = count($tblSubjBlockingErrors["$subjectKey"]);
        $nbOfBlockingErrors += $nbSubjErrors;
        $nbSubjectInsertedRecords["$subjectKey"] = 0;
        $nbSubjectUpdatedRecords["$subjectKey"] = 0; 
        if($nbSubjErrors==0)
        {        
          //extraction du patient de la base
          try{
            $subj = $this->m_ctrl->socdiscoo($subjectKey,true)->getDocument($subjectKey.".dbxml",$subjectKey,false);
            $xPath = new DOMXPath($subj);
            $xPath->registerNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3");
          }catch(exception $e){
            //Vérification du SubjectKey
            if($e->getCode()==11){ //Document non trouvé
              $tblBlockingErrors[] = "Subject $subjectKey not found in eCRF Database";
            }               
          }
  
          //Préparation de l'AuditRecord - ne servira pas de maj necessaire
          $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords");
          if($result->length==1){
            $AuditRecords = $result->item(0);
          }else{
            $str = "Pb d'AuditRecords, Patient $SubjectKey result->length={$result->length} (".__METHOD__.")";
            $this->addLog($str,FATAL);
            die($str);
          }
    
          //Ajout d'un AuditRecord
          $AuditRecord = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","AuditRecord");
          //Calcul du nouvel ID sous la form Audit-XXXXXX
          $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords/odm:AuditRecord");
          $AuditRecordID = sprintf("Audit-%06s",$result->length+1);
          $AuditRecord->setAttribute("ID",$AuditRecordID);
          
          $bModif = false;
          $result = $xml->xpath("/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey='$subjectKey']/odm:StudyEventData/odm:FormData[@FormOID='FORM.EG' and @FormRepeatKey='0']/odm:ItemGroupData[@ItemGroupOID='EG']");        
          foreach($result as $eg){
            //Extraction des clés identifiant mon enregistrement
            $studyEventData = $eg->xpath('../..');
            $studyEventOID = $studyEventData[0]['StudyEventOID'];
            $itemGroupRepeatKey = (string)$eg['ItemGroupRepeatKey'];
            
            //Ajout de StudyEventData si besoin
            $resultSE = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']");
            if($resultSE->length==0){
              $resultSD = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData");
              if($resultSD->length==1){
                $StudyEventData = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","StudyEventData");
                $StudyEventData->setAttribute("StudyEventOID","$studyEventOID");
                $StudyEventData->setAttribute("StudyEventRepeatKey","0");
                $resultSD->item(0)->appendChild($StudyEventData); //On l'ajoute
              }else{
                $str = "Erreur : Insertion StudyEventData[@StudyEventOID='$studyEventOID'] (". __METHOD__ .")";
                $this->addLog($str,FATAL);
                die($str);
              }
            }       
  
            //Ajout de FormData si besoin
            $resultFormData = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']/odm:FormData[@FormOID='FORM.EG' and @FormRepeatKey='0']");
            if($resultFormData->length==0){
              $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']");
              if($result->length==1){
                $FormData = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","FormData");
                $FormData->setAttribute("FormOID","FORM.EG");
                $FormData->setAttribute("FormRepeatKey","0");
        
                //Création de l'élement annotation, qui va contenir les flags de statuts
                $Annotation = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","Annotation");
                $Flag = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","Flag");
                $FlagValue = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","FlagValue","EMPTY");
                $FlagType = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","FlagType","STATUS");
        
                $Annotation->setAttribute("SeqNum","1");
                $FlagValue->setAttribute("CodeListOID","CL.IGSTATUS");
                $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");
        
                $Flag->appendChild($FlagValue);
                $Flag->appendChild($FlagType);
                $Annotation->appendChild($Flag);
                $FormData->appendChild($Annotation);
        
                $result->item(0)->appendChild($FormData); //On l'ajoute
              }else{
                $str = "Erreur : Insertion FormData[@FormOID='$FormOID' @FormRepeatKey='0'] : result->length={$result->length} (".__METHOD__.")";
                $this->addLog($str,FATAL);
                die($str);
              }
            }else{
              $FormData = $resultFormData->item(0);
            }
    
            //Recherche de l'itemgroupdata
            $query = "/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']/odm:FormData[@FormOID='FORM.EG' and @FormRepeatKey='0']";
            $query .= "/odm:ItemGroupData[@ItemGroupOID='EG' and @ItemGroupRepeatKey='$itemGroupRepeatKey']";
            $resultItemGroupData = $xPath->query($query);
            $bRecordHasBeenUpdated = false;
            $bRecordHasBeenInserted = false;
            if($resultItemGroupData->length==0){ 
              //Création de l'itemgroupdata
              $bModif = true;
              
              //MAJ AuditRecordID au niveau ItemData
              foreach($eg->children() as $itemData){
                //Nous sommes sur une insertion
                $itemData['TransactionType'] = "Insert";
                $itemData['AuditRecordID'] = $AuditRecordID;   
              }
              
              $eg['TransactionType'] = "Insert";
              
              $dom_eg = dom_import_simplexml($eg);
              $dom_eg = $subj->importNode($dom_eg, true);
        
              $FormData->appendChild($dom_eg);
              $bRecordHasBeenInserted = true;              
            }else{
              if($resultItemGroupData->length==1){
                $itemGroupData = $resultItemGroupData->item(0); 
                //Mise à jour de l'itemgroupdata
                foreach($eg as $itemData){
                  $queryItemData = $query . "/odm:*[@ItemOID='".$itemData['ItemOID']."']";
                  $resultItemData = $xPath->query($queryItemData); 
                  $bNeedUpdate = false;
                  $bNeedInsert = false;
                  if($resultItemData->length==0){
                    $bNeedInsert = true;
                  }else{
                    $oldValue = $resultItemData->item($resultItemData->length-1);
                    if((string)$oldValue->nodeValue!=(string)$itemData){
                      $bNeedUpdate = true;
                    }
                  }
                  if($bNeedUpdate || $bNeedInsert){
                    $bModif = true;
                    //Nous sommes sur une mise à jour
                    if($bNeedUpdate){
                      $itemData['TransactionType'] = "Update";
                      $bRecordHasBeenUpdated = true;
                      if($itemData['ItemOID']=="EG.EGORRES"){
                        $nbUpdatedValues++;
                        $nbSubjectUpdatedValues["$subjectKey"]++;
                      }
                    }
                    if($bNeedInsert){
                      $itemData['TransactionType'] = "Insert";
                      $bRecordHasBeenInserted = true;
                      if($itemData['ItemOID']=="EG.EGORRES"){
                        $nbInsertedValues++;
                        $nbSubjectInsertedValues["$subjectKey"]++;
                      }
                    }
                    $itemData['AuditRecordID'] = $AuditRecordID;
                    $dom_itemData = dom_import_simplexml($itemData);
                    $dom_itemData = $subj->importNode($dom_itemData, true);
                    $itemGroupData->appendChild($dom_itemData);                                         
                  }
                }    
              }else{
                $str = "Erreur : too much itemgroupdata (".$formData->length.") found with query $query";
                $this->addLog($str,FATAL);
                die($str);               
              }          
            }
            if($bRecordHasBeenUpdated){
              $nbUpdatedRecords++;
              $nbSubjectUpdatedRecords["$subjectKey"]++;
            }              
            if($bRecordHasBeenInserted){
              $nbInsertedRecords++;
              $nbSubjectInsertedRecords["$subjectKey"]++;
            }      
          }
          
          //Au moins une modif, ajout de l'AuditRecord
          if($nbUpdatedRecords>0 || $nbInsertedRecords>0){
            //Qui
            $UserRef = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","UserRef");
            $UserRef->setAttribute("UserOID",$importUser);
            $AuditRecord->appendChild($UserRef);
            //Ou
            $LocationRef = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","LocationRef");
            $LocationRef->setAttribute("LocationOID","CARDIABASE ECG");
            $AuditRecord->appendChild($LocationRef);
            //Quand
            $DateTimeStamp = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","DateTimeStamp",date('c'));
            $AuditRecord->appendChild($DateTimeStamp);
            //Pourquoi
            $ReasonForChange = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","ReasonForChange","");
            $AuditRecord->appendChild($ReasonForChange);
        
            $AuditRecords->appendChild($AuditRecord);         
          
            //Mise à jour du patient en base
            $subj->save("/tmp/importECG_$subjectKey.xml");
            $this->m_ctrl->socdiscoo()->replaceDocument($subj);
            
            echo "inserted = {$nbSubjectInsertedRecords["$subjectKey"]} updated={$nbSubjectUpdatedRecords["$subjectKey"]}<br/>";flush();
          }else{
            echo "no new data<br/>";flush();
          }          
        }else{
          echo"errors<br/>";flush();
        }
      }
    }
    
    echo "ecgFileCreationDate = " . $ecgFileCreationDate . "<br/>";
    echo "importDate = " . $importDate . "<br/>";
    echo "importUser = " . $importUser . "<br/>";
    echo "nbOfBlockingErrors = " . $nbOfBlockingErrors . "<br/>";
    echo "nbInsertedRecords = $nbInsertedRecords <br/>";
    echo "nbUpdatedRecords = $nbUpdatedRecords <br/>";
    echo "nbInsertedValues (EG.EGORRES) = $nbInsertedValues <br/>";
    echo "nbUpdatedValues (EG.EGORRES) = $nbUpdatedValues <br/>";
    
    //Gestion du statut de l'import, et de la liste des erreurs
    $lstErrors = "";
    if($nbOfBlockingErrors==0){
      $importStatus = "OK";         
    }else{
      $importStatus = "ERRORS";
      foreach($tblBlockingErrors as $errors){
        $lstErrors .= $errors . "\line "; 
      }
    }

    foreach($clinicalData->SubjectData as $subjectData){
      $subjectKey = (string)$subjectData["SubjectKey"];
      $lstErrors .= "\line *************************************Subject $subjectKey*********************************** \line ";
      if($tblSubjBlockingErrors["$subjectKey"]==0){
        $lstErrors .= "ECG Data successfully imported : inserted records=".$nbSubjectInsertedRecords["$subjectKey"]." updated values=".$nbSubjectUpdatedRecords["$subjectKey"] . "\line ";  
      }else{
        foreach($tblSubjBlockingErrors["$subjectKey"] as $errors){
          $lstErrors .= $errors . "\line ";
        }
      }
    }
    
    //Ecriture du report
    $reportContent = file_get_contents($this->m_tblConfig['IMPORT_BASE_PATH'] . "ecg_report_template.rtf");
    $search = array("DATE_CREATION_ECG","DATE_IMPORT_ECG","USER_IMPORT_ECG","STATUS_IMPORT_ECG","NB_BLOCKING_ERRORS","NB_INSERTED_RECORDS","NB_UPDATED_RECORDS","BLOCKING_ERRORS");
    $replace = array($ecgFileCreationDate,$importDate,$importUser,$importStatus,$nbOfBlockingErrors,$nbInsertedRecords,$nbUpdatedRecords,$lstErrors);
    $reportContent = str_replace($search,$replace,$reportContent);
    
    $filename = $this->m_tblConfig['IMPORT_BASE_PATH'] . "ecg_report_".date("Ymd_Hi").".rtf";
    file_put_contents($filename,$reportContent);
     
    $sql = "INSERT INTO egw_alix_import(DATE_IMPORT_FILE,USER,STATUS,ERROR_COUNT,IMPORT_FILE,REPORT_FILE,IMPORT_TYPE,DATE_IMPORT,currentapp,importpath)
            VALUES('$ecgFileCreationDate','$importUser','$importStatus','$nbOfBlockingErrors','".basename($ecgFile)."','".basename($filename)."','ECG',now(),'".$GLOBALS['egw_info']['flags']['currentapp']."','".$this->m_tblConfig['IMPORT_BASE_PATH']."')";
  
    $GLOBALS['egw']->db->query($sql);
  }

/*
@desc import le fichier LAB, en executant plusieurs étapes de vérification
@author wlt
*/   
  function importLAB($labFile)
  {
    global $configEtude;   
    $configEtude["LOG_LONG_EXECUTION"] = false;
    
    $xml = new DOMDocument();
    $xml->load($labFile);
    
    set_time_limit(240);
    
    // Enable user error handling
    libxml_use_internal_errors(true);
    
    //On gère deux tableaux d'erreurs :
    //Les erreurs globales bloquant l'import complet
    //Les erreurs au niveau patient, bloquant l'import d'un patient
    $tblBlockingErrors = array();
    $tblSubjBlockingErrors = array();
      
    $xPath = new DOMXPath($xml);
    $xPath->registerNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3");

    ob_end_flush();
    ob_flush(); 
    flush();
    
    echo "cleaning...";
    flush();

    //Etape preliminaire : on gère les valeurs vides, en modifiant les balise ItemDataInteger en ItemDataAny
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:ItemDataInteger");        
    foreach($result as $itemData){
      if($itemData->nodeValue==""){
        $newNode = $itemData->ownerDocument->createElementNS("http://www.cdisc.org/ns/odm/v1.3",'ItemDataAny');
        if ($itemData->attributes->length) {
          foreach ($itemData->attributes as $attribute) {
              $newNode->setAttribute($attribute->nodeName, $attribute->nodeValue);
          }
        }
        $itemData->parentNode->replaceChild($newNode,$itemData);        
      }   
    }

    //Deuxième étape : extraction des symbole < et > pour les mettre dans une autre variable
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:ItemDataString[@ItemOID='LB.LBORNRLO' or @ItemOID='LB.LBORNRHI']");        
    foreach($result as $itemData){
      $signArr = array(">","<","<=",">=");
      foreach($signArr as $sign){ 
        $startSign = strpos($itemData->nodeValue,$sign);
        if($startSign!==false){
          //Extraction du caractères < ou >
          $itemData->nodeValue = trim(substr($itemData->nodeValue,$startSign+strlen($sign)));
          //Ajout de l'itemData contenant le signe < ou > ou <= ou >=
          switch($sign){
            case '>' : $codedValue="1"; break;
            case '<' : $codedValue="2"; break;
            case '>=' : $codedValue="3"; break;
            case '<=' : $codedValue="4"; break;
            default : die("sign $sign inconnu");
          }
          $signNode = $itemData->ownerDocument->createElementNS("http://www.cdisc.org/ns/odm/v1.3",'ItemDataInteger'); 
          if($itemData->getAttribute("ItemOID")=="LB.LBORNRLO"){
            $signNode->setAttribute("ItemOID", "LB.LBORNRCL");  
          }else{
            if($itemData->getAttribute("ItemOID")=="LB.LBORNRHI"){
              $signNode->setAttribute("ItemOID", "LB.LBORNRCH");  
            }
          }
          
          $signNode->nodeValue = $codedValue;
          $itemData->parentNode->appendChild($signNode);           
        }
      }
    }
    
    //Enregistrement des modifications ItemDataXXX en ItemDataAny (si valeur vide)
    $xml->save($labFile);
    echo "ok<br/>";flush();
    
    echo "checking schema...";flush();    
    //Première étape : vérification de la conformité par rapport au schéma ODM
    if(!$xml->schemaValidate($this->m_tblConfig["ODM_1_3_SCHEMA"]))
    {
      $errors = libxml_get_errors();
      foreach ($errors as $error) {
        $errorLib = "";
        switch ($error->level){
            case LIBXML_ERR_WARNING:
                $errorLib = "Warning $error->code : ";
                break;
            case LIBXML_ERR_ERROR:
                $errorLib = "Error $error->code : ";
                break;
            case LIBXML_ERR_FATAL:
                $errorLib = "Fatal Error $error->code : ";
                break;
        }
        $errorLib .= trim($error->message) . " on line $error->line";
        $tblBlockingErrors[] = $errorLib;
      }
      libxml_clear_errors();
    }
    echo "ok<br/>";flush();
    
    echo "checking keys...";flush();        
    //2eme étape : vérification des clés (présence et valeur)
    $xml = simplexml_load_file($labFile);
    $xml->registerXPathNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3");
    
    $clinicalData = $xml->ClinicalData;
    
    //Recuperation des codelists depuis les meetadatas
    $clTests = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$LBTEST','en');
    $clInter = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$INT','en');
    $clSex = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$SEX','en');
    $clFast = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$YN','en');
    $clSymb = $this->m_ctrl->bocdiscoo()->getCodelist('1.0.0','CL.$SYMB','en');
    
    //Liste des ItemData devant impérativement être présent dans l'import, pour chaque itemgroupdata
    $tblItemDataMandatory = array("LB.BRTHDTC","LB.SEX","LB.LBCAT","LB.LBREFID","LB.LBSEQ","LB.LBTEST","LB.LBORRES","LB.LBORRESU","LB.LBSTAT","LB.LBREASND","LB.LBORNRLO","LB.LBORNRHI","LB.LBNRIND","LB.LBFAST","LB.LBDTC");
      
    //Boucle sur les patients     
    foreach($clinicalData->SubjectData as $subjectData){
      $subjectKey = (string)$subjectData["SubjectKey"];

      //We only need to lock one patient
      $this->m_ctrl->socdiscoo($subjectKey,true);
      
      //extraction du patient de la base
      $subj = $this->m_ctrl->bocdiscoo()->getSubjectList("","\$SubjectData/@SubjectKey='$subjectKey'");
      if(!isset($subj[0]->subj['colBRTHDTC'])){ //Patient non trouvé
        $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey not found in eCRF Database";
        //On passe au patient suivant 
        continue;             
      }else{
        $subjBRTHDTC = (string)$subj[0]->subj['colBRTHDTC'];              
      }
            
      //Boucle sur les Visites
      foreach($subjectData->StudyEventData as $studyEventData){
        $studyEventOID = (string)$studyEventData["StudyEventOID"];
        $studyEventRepeatKey = (string)$studyEventData["StudyEventRepeatKey"];
        
        //StudyEventOID autorisé : de 1 à 30
        $tblSEOID = range(1,30);
        if( !in_array($studyEventOID,$tblSEOID) ){
          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID not in range [0;30]";    
        }
        
        //StudyEventRepeatKey autorisé = 0
        if(!$studyEventRepeatKey=="0"){
          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey <> 0";
        } 
      
        //Boucle sur les formulaires
        foreach($studyEventData->FormData as $formData){
          $formOID = (string)$formData['FormOID'];
          $formRepeatKey = (string)$formData['FormRepeatKey'];
          
          //FormOID autorisé = FORM.EG
          if($formOID!="FORM.LBBIO" && $formOID!="FORM.LBHAE"){
            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID <> FORM.EG";              
          }
          
          //FormRepeatKey autorisé = 0
          if($formRepeatKey!="0"){
            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID : FormRepeatKey $formRepeatKey <> 0";              
          }
          
          //On attend 16 ou 17(LBBIO) ou 18 lignes(LBHAE) d'ItemGroupData par formulaire
          if(count($formData->ItemGroupData)!=18 && $formOID=="FORM.LBHAE" ||
             count($formData->ItemGroupData)!=17 && count($formData->ItemGroupData)!=16 && $formOID=="FORM.LBBIO"){
            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID : ".($formOID=="FORM.LBBIO"?"16 or 17":"18")." ItemgroupData excepted; ".count($formData->ItemGroupData)." found";                
          }else{                    
            foreach($formData->ItemGroupData as $itemGroupData){
              $itemGroupOID = (string)$itemGroupData["ItemGroupOID"];
              $itemGroupRepeatKey = (integer)$itemGroupData["ItemGroupRepeatKey"];
              
              //ItemGroupOID autorisé = "LBBIO" ou "LBHAE"
              if($itemGroupOID!="LBBIO" && $itemGroupOID!="LBHAE"){
                $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : StudyEventRepeatKey $studyEventRepeatKey : FormOID $formOID : FormRepeatKey $formRepeatKey : ItemGroupOID $itemGroupOID <> EG";                            
              }              
              
              //ItemGroupRepeatKey autorisé = [1;100]
              $tblIGRK = range(1,100);
              if( !in_array($itemGroupRepeatKey,$tblIGRK) ){
                $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey not in range [1;100]";                            
              }
             
              //Boucle sur les ItemData / Check des codelists
              $tblItemDataImport = array();
              foreach($itemGroupData->children() as $itemData){
                $tblItemDataImport[] = (string)$itemData['ItemOID'];
                if($itemData['ItemOID']=="LB.LBTEST"){
                  if(!isset($clTests[(string)$itemData]) && (string)$itemData!=""){
                    $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGTEST='$itemData' not in CL.EGTEST codelist";                            
                  }                  
                }else{
                  if($itemData['ItemOID']=="LB.LBCLSIG"){
                    if(!isset($clInter[(string)$itemData]) && (string)$itemData!=""){
                      $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGORRESU='$itemData' not in CL.EGUNIT codelist";                            
                    }                  
                  }else{
                    if($itemData['ItemOID']=="LB.BRTHDTC"){
                      if($subjBRTHDTC!=(string)$itemData){
                        $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : Inconsistent Date of Birth (import=$itemData eCRF=$subjBRTHDTC )";                                                  
                      }
                    }else{
                      if($itemData['ItemOID']=="LB.SEX"){
                        if(!isset($clSex[(string)$itemData]) && (string)$itemData!=""){
                          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGORRESU='$itemData' not in CL.EGUNIT codelist";                            
                        }                  
                      }else{
                        if($itemData['ItemOID']=="LB.LBFAST"){
                          if(!isset($clFast[(string)$itemData]) && (string)$itemData!=""){
                            $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGORRESU='$itemData' not in CL.EGUNIT codelist";                            
                          }                  
                        }else{
                          if($itemData['ItemOID']=="LB.LBORNRCL" || $itemData['ItemOID']=="LB.LBORNRCH"){
                            if(!isset($clSymb[(string)$itemData]) && (string)$itemData!=""){
                              $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData EG.EGORRESU='$itemData' not in CL.EGUNIT codelist";                            
                            }                  
                          }
                        }                      
                      }
                    }
                  }              
                }    
              }
              //Avons-nous tous les itemdatas dans l'import ?
              $tblDiff = array_diff($tblItemDataMandatory,$tblItemDataImport);
              if(count($tblDiff)>0){
                foreach($tblDiff as $missingItemData){
                  $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : ItemData $missingItemData is missing";                                          
                }
              }            
            }
          }                    
        }
      }     
    }

    echo "ok<br/>";flush();
    
    echo "checking missing data...";flush();    
    //3eme étape : vérification des données manquantes et déjà présentes dans un export précédent

    $subjectCol = $this->m_ctrl->socdiscoo("",true)->getClinicalDataCollection();
    set_time_limit(120);    
    //On va chercher toutes les records LAB en base eCRF (itemgroupdata)
    $query = "  
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$v := '' (:car il nous faut un let :)
        return \$ItemData[last()]/string()
      };

      for \$ItemGroupDataLAB in $subjectCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK']/odm:StudyEventData/odm:FormData/odm:ItemGroupData[(@ItemGroupOID='LBBIO' or @ItemGroupOID='LBHAE') and @TransactionType!='Remove']
      let \$StudyEventData := \$ItemGroupDataLAB/../..
      let \$SubjectData := \$ItemGroupDataLAB/../../../../odm:SubjectData
      let \$MetaDataVersion := collection('MetaDataVersion.dbxml')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      let \$ItemGroupDataENROL := \$SubjectData/odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']

      let \$SiteId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITEID'])                
      let \$SiteName := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITENAME'])
      let \$SubjId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SUBJID'])
			
			let \$labtest := local:getLastValue(\$ItemGroupDataLAB/odm:ItemDataString[@ItemOID='LB.LBTEST'])
			let \$labres :=  local:getLastValue(\$ItemGroupDataLAB/odm:ItemDataString[@ItemOID='LB.LBORRES'])
			let \$labdt := local:getLastValue(\$ItemGroupDataLAB/odm:ItemDataString[@ItemOID='LB.LBDTC'])
			let \$labnam := local:getLastValue(\$ItemGroupDataLAB/odm:ItemDataString[@ItemOID='LB.LBNAM'])
      return
        <lab  siteId='{\$SiteId}'
              siteName='{\$SiteName}'
              subjId='{\$SubjId}'
              subjectKey='{\$SubjectData/@SubjectKey}'
              studyEventOID='{\$StudyEventData/@StudyEventOID}'
              itemGroupRepeatKey='{\$ItemGroupDataLAB/@ItemGroupRepeatKey}'
              labtest='{\$labtest}'
              labres='{\$labres}'
              labdt='{\$labdt}'
              labnam='{\$labnam}'
        /> 
      "; 

    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html>";
      $this->addLog($str,FATAL);
      die($str);
    }    
    
    //Boucle sur les données EG (itemgroupdata) déjà dans la base eCRF
    
    foreach($result as $lab){
      //On doit retrouver l'enregistrement dans les données envoyées
      //on ne test que les enregistement ayant une valeur en base eCRF, pour ne pas prendre en compte les data du BLANK

      $subjectKey = (string)$lab['subjectKey'];
      $studyEventOID = (string)$lab['studyEventOID'];
      $itemGroupRepeatKey = (string)$lab['itemGroupRepeatKey'];
      $labTest = (string)$lab['egtest'];
      $labRes = (string)$lab['egres'];
      $labDt = (string)$lab['egdt'];
      $labNam = (string)$lab['labnam'];
      
      if(($labRes!="" || $labDt!="") && $labNam!="L"){ //labNam!="N" => on ne prend pas en compte les valeurs locales           
        //Avous-nous l'enregistrement dans les données reçues ?
        //Car cela devrait être le cas ! Sinon, erreur bloquante
        $query = "/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey='$subjectKey']/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']/odm:FormData/odm:ItemGroupData[(@ItemGroupOID='LBBIO' or @ItemGroupOID='LBHAE') and @ItemGroupRepeatKey='$itemGroupRepeatKey']";
        $result = $xml->xpath($query);        
        if($result==false){
          $tblSubjBlockingErrors["$subjectKey"][] = "Subject $subjectKey : StudyEventOID $studyEventOID : ItemGroupRepeatKey $itemGroupRepeatKey : record not found in incoming lab file";                             
        }
      } 
    }
    
    if(count($tblBlockingErrors>0)){
      $importStatus = "FAIL";
    }else{
      $importStatus = "SUCCESS";
    }
    
    echo "ok<br/>";flush();
    
    $labFileCreationDate = $xml['CreationDateTime'];
    $importDate = date("c");
    $importUser = $this->m_ctrl->boacl()->getUserId();
    $nbOfBlockingErrors = count($tblBlockingErrors);
    $nbInsertedRecords = 0;
    $nbUpdatedRecords = 0;
    $nbInsertedValues = 0;
    $nbUpdatedValues = 0;
    
    $nbSubjectInsertedRecords = array();
    $nbSubjectUpdatedRecords = array();

    $nbSubjectInsertedValues = array();
    $nbSubjectUpdatedValues = array();
        
    if($nbOfBlockingErrors==0)
    {
      //Tout est ok, on  peut insérer les nouvelles lignes en base eCRF
      //On procéde patient par patient
      //Boucle sur les enregistrements en provenance de l'import
      foreach($clinicalData->SubjectData as $subjectData){
        echo "inserting data for subject " . $subjectData["SubjectKey"] . " ...";flush();
        set_time_limit(120);
        
        $subjectKey = (string)$subjectData["SubjectKey"];
        $nbSubjErrors = count($tblSubjBlockingErrors["$subjectKey"]);
        $nbOfBlockingErrors += $nbSubjErrors;
        $nbSubjectInsertedRecords["$subjectKey"] = 0;
        $nbSubjectUpdatedRecords["$subjectKey"] = 0; 
        if($nbSubjErrors==0)
        {        
          //extraction du patient de la base
          try{
            $subj = $this->m_ctrl->socdiscoo($subjectKey,true)->getDocument($subjectKey.".dbxml",$subjectKey,false);
            $xPath = new DOMXPath($subj);
            $xPath->registerNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3");
          }catch(exception $e){
            //Vérification du SubjectKey
            if($e->getCode()==11){ //Document non trouvé
              $tblBlockingErrors[] = "Subject $subjectKey not found in eCRF Database";
            }               
          }
  
          //Préparation de l'AuditRecord - ne servira qu'en cas de maj necessaire
          $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords");
          if($result->length==1){
            $AuditRecords = $result->item(0);
          }else{
            $str = "Pb d'AuditRecords, Patient $SubjectKey result->length={$result->length} (".__METHOD__.")";
            $this->addLog($str,FATAL);
            die($str);
          }
    
          //Ajout d'un AuditRecord
          $AuditRecord = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","AuditRecord");
          //Calcul du nouvel ID sous la form Audit-XXXXXX
          $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords/odm:AuditRecord");
          $AuditRecordID = sprintf("Audit-%06s",$result->length+1);
          $AuditRecord->setAttribute("ID",$AuditRecordID);
          
          $bModif = false;
          $result = $xml->xpath("/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey='$subjectKey']/odm:StudyEventData/odm:FormData/odm:ItemGroupData");        
          foreach($result as $lab){
            //Extraction des clés identifiant mon enregistrement
            $studyEventData = $lab->xpath('../..');
            $studyEventOID = $studyEventData[0]['StudyEventOID'];
            
            $formData = $lab->xpath("..");
            $formOID  = $formData[0]['FormOID'];

            $itemGroupOID = (string)$lab['ItemGroupOID'];
            $itemGroupRepeatKey = (string)$lab['ItemGroupRepeatKey'];
            
            //Ajout de StudyEventData si besoin
            $resultSE = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']");
            if($resultSE->length==0){
              $resultSD = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData");
              if($resultSD->length==1){
                $StudyEventData = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","StudyEventData");
                $StudyEventData->setAttribute("StudyEventOID","$studyEventOID");
                $StudyEventData->setAttribute("StudyEventRepeatKey","0");
                $resultSD->item(0)->appendChild($StudyEventData); //On l'ajoute
              }else{
                $str = "Erreur : Insertion StudyEventData[@StudyEventOID='$studyEventOID'] (". __METHOD__ .")";
                $this->addLog($str,FATAL);
                die($str);
              }
            }       
  
            //Ajout de FormData si besoin
            $resultFormData = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']/odm:FormData[@FormOID='$formOID' and @FormRepeatKey='0']");
            if($resultFormData->length==0){
              $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']");
              if($result->length==1){
                $FormData = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","FormData");
                $FormData->setAttribute("FormOID",$formOID);
                $FormData->setAttribute("FormRepeatKey","0");
        
                //Création de l'élement annotation, qui va contenir les flags de statuts
                $Annotation = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","Annotation");
                $Flag = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","Flag");
                $FlagValue = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","FlagValue","EMPTY");
                $FlagType = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","FlagType","STATUS");
        
                $Annotation->setAttribute("SeqNum","1");
                $FlagValue->setAttribute("CodeListOID","CL.IGSTATUS");
                $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");
        
                $Flag->appendChild($FlagValue);
                $Flag->appendChild($FlagType);
                $Annotation->appendChild($Flag);
                $FormData->appendChild($Annotation);
        
                $result->item(0)->appendChild($FormData); //On l'ajoute
              }else{
                $str = "Erreur : Insertion FormData[@FormOID='$formOID' @FormRepeatKey='0'] : result->length={$result->length} (".__METHOD__.")";
                $this->addLog($str,FATAL);
                die($str);
              }
            }else{
              $FormData = $resultFormData->item(0);
            }
    
            //Recherche de l'itemgroupdata
            $query = "/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$studyEventOID' and @StudyEventRepeatKey='0']/odm:FormData[@FormOID='$formOID' and @FormRepeatKey='0']";
            $query .= "/odm:ItemGroupData[@ItemGroupOID='$itemGroupOID' and @ItemGroupRepeatKey='$itemGroupRepeatKey']";
            $resultItemGroupData = $xPath->query($query);
            $bRecordHasBeenUpdated = false;
            $bRecordHasBeenInserted = false;
            if($resultItemGroupData->length==0){ 
              //Création de l'itemgroupdata
              $bModif = true;
              
              //MAJ AuditRecordID au niveau ItemData
              foreach($lab->children() as $itemData){
                //Nous sommes sur une insertion
                $itemData['TransactionType'] = "Insert";
                $itemData['AuditRecordID'] = $AuditRecordID;   
              }
              
              $lab['TransactionType'] = "Insert";
              
              $dom_lab = dom_import_simplexml($lab);
              $dom_lab = $subj->importNode($dom_lab, true);
        
              $FormData->appendChild($dom_lab);
              $bRecordHasBeenInserted = true;              
            }else{
              if($resultItemGroupData->length==1){
                $itemGroupData = $resultItemGroupData->item(0);
                
                $bLocalValueDetected = false;
                
                //Lecture du type de lab : central ou local. Si local, on n'insère pas la valeur de l'import
                $queryItemDataLabNam = $query . "/odm:*[@ItemOID='LB.LBNAM']";
                $resultItemDataLabNam = $xPath->query($queryItemDataLabNam); 
                if($resultItemDataLabNam->length>0){
                  $labNam = $resultItemDataLabNam->item($resultItemDataLabNam->length-1);
                  if((string)$labNam->nodeValue=="L"){
                    $bLocalValueDetected = true;
                  }
                }                  
                 
                //Mise à jour de l'itemgroupdata, uniquement si il n'y a pas de données locales
                if($bLocalValueDetected==false){
                  foreach($lab as $itemData){
                    $queryItemData = $query . "/odm:*[@ItemOID='".$itemData['ItemOID']."']";
                    $resultItemData = $xPath->query($queryItemData); 
                    $bNeedUpdate = false;
                    $bNeedInsert = false;
                    if($resultItemData->length==0){
                      $bNeedInsert = true;
                    }else{
                      $oldValue = $resultItemData->item($resultItemData->length-1);
                      if((string)$oldValue->nodeValue!=(string)$itemData){
                        $bNeedUpdate = true;
                      }
                    }
                    if($bNeedUpdate || $bNeedInsert){
                      $bModif = true;
                      //Nous sommes sur une mise à jour
                      if($bNeedUpdate){
                        $itemData['TransactionType'] = "Update";
                        $bRecordHasBeenUpdated = true;
                        if($itemData['ItemOID']=="LB.LBORRES"){
                          $nbUpdatedValues++;
                          $nbSubjectUpdatedValues["$subjectKey"]++;
                        }
                      }
                      if($bNeedInsert){
                        $itemData['TransactionType'] = "Insert";
                        $bRecordHasBeenInserted = true;
                        if($itemData['ItemOID']=="LB.LBORRES"){
                          $nbInsertedValues++;
                          $nbSubjectInsertedValues["$subjectKey"]++;
                        }
                      }
                      $itemData['AuditRecordID'] = $AuditRecordID;
                      $dom_itemData = dom_import_simplexml($itemData);
                      $dom_itemData = $subj->importNode($dom_itemData, true);
                      $itemGroupData->appendChild($dom_itemData);                                         
                    }
                  }
                }    
              }else{
                $str = "Erreur : too much itemgroupdata (".$resultItemGroupData->length.") found with query $query";
                $this->addLog($str,FATAL);
                die($str);               
              }          
            }
            if($bRecordHasBeenUpdated){
              $nbUpdatedRecords++;
              $nbSubjectUpdatedRecords["$subjectKey"]++;
            }              
            if($bRecordHasBeenInserted){
              $nbInsertedRecords++;
              $nbSubjectInsertedRecords["$subjectKey"]++;
            }      
          }
          
          //Au moins une modif, ajout de l'AuditRecord
          if($bRecordHasBeenUpdated || $bRecordHasBeenInserted){
            //Qui
            $UserRef = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","UserRef");
            $UserRef->setAttribute("UserOID",$importUser);
            $AuditRecord->appendChild($UserRef);
            //Ou
            $LocationRef = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","LocationRef");
            $LocationRef->setAttribute("LocationOID","EUROFINS LAB");
            $AuditRecord->appendChild($LocationRef);
            //Quand
            $DateTimeStamp = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","DateTimeStamp",date('c'));
            $AuditRecord->appendChild($DateTimeStamp);
            //Pourquoi
            $ReasonForChange = $subj->createElementNS("http://www.cdisc.org/ns/odm/v1.3","ReasonForChange","");
            $AuditRecord->appendChild($ReasonForChange);
        
            $AuditRecords->appendChild($AuditRecord);         
          
            //Mise à jour du patient en base
            $subj->save("/tmp/importLAB_$subjectKey.xml");
            $this->m_ctrl->socdiscoo()->replaceDocument($subj);
            echo "inserted = {$nbSubjectInsertedRecords["$subjectKey"]} updated={$nbSubjectUpdatedRecords["$subjectKey"]}<br/>";flush();
          }else{
            echo "no new data<br/>";flush();
          }
        }else{
          echo"errors<br/>";flush();
        }
      }
    }
    
    echo "labFileCreationDate = " . $labFileCreationDate . "<br/>";
    echo "importDate = " . $importDate . "<br/>";
    echo "importUser = " . $importUser . "<br/>";
    echo "nbOfBlockingErrors = " . $nbOfBlockingErrors . "<br/>";
    echo "nbInsertedRecords = $nbInsertedRecords <br/>";
    echo "nbUpdatedRecords = $nbUpdatedRecords <br/>";
    echo "nbInsertedValues (LB.LBORRES) = $nbInsertedValues <br/>";
    echo "nbUpdatedValues (LB.LBORRES) = $nbUpdatedValues <br/>";
    
    //Gestion du statut de l'import, et de la liste des erreurs
    $lstErrors = "";
    if($nbOfBlockingErrors==0){
      $importStatus = "OK";         
    }else{
      $importStatus = "ERRORS";
      foreach($tblBlockingErrors as $errors){
        $lstErrors .= $errors . "\line "; 
        echo $errors;
      }
    }

    foreach($clinicalData->SubjectData as $subjectData){
      $subjectKey = (string)$subjectData["SubjectKey"];
      $lstErrors .= "\line *************************************Subject $subjectKey*********************************** \line ";
      if($tblSubjBlockingErrors["$subjectKey"]==0){
        $lstErrors .= "LAB Data successfully imported : inserted records=".$nbSubjectInsertedRecords["$subjectKey"]." updated values=".$nbSubjectUpdatedRecords["$subjectKey"] . "\line ";  
      }else{
        foreach($tblSubjBlockingErrors["$subjectKey"] as $errors){
          $lstErrors .= $errors . "\line ";
        }
      }
    }
    
    //Ecriture du report
    $reportContent = file_get_contents($this->m_tblConfig['IMPORT_BASE_PATH'] . "lab_report_template.rtf");
    $search = array("DATE_CREATION_LAB","DATE_IMPORT_LAB","USER_IMPORT_LAB","STATUS_IMPORT_LAB","NB_BLOCKING_ERRORS","NB_INSERTED_RECORDS","NB_UPDATED_RECORDS","BLOCKING_ERRORS");
    $replace = array($labFileCreationDate,$importDate,$importUser,$importStatus,$nbOfBlockingErrors,$nbInsertedRecords,$nbUpdatedRecords,$lstErrors);
    $reportContent = str_replace($search,$replace,$reportContent);
    
    $filename = $this->m_tblConfig['IMPORT_BASE_PATH'] . "lab_report_".date("Ymd_Hi").".rtf";
    file_put_contents($filename,$reportContent);
     
    $sql = "INSERT INTO egw_alix_import(DATE_IMPORT_FILE,USER,STATUS,ERROR_COUNT,IMPORT_FILE,REPORT_FILE,IMPORT_TYPE,DATE_IMPORT,currentapp,importpath)
            VALUES('$labFileCreationDate','$importUser','$importStatus','$nbOfBlockingErrors','".basename($labFile)."','".basename($filename)."','LAB',now(),'".$GLOBALS['egw_info']['flags']['currentapp']."','".$this->m_tblConfig['IMPORT_BASE_PATH']."')";
  
    $GLOBALS['egw']->db->query($sql);
  }

  public function getImportList($exportType){
    $sql = "SELECT IMPORTID,DATE_IMPORT_FILE,USER,STATUS,ERROR_COUNT,IMPORT_FILE,REPORT_FILE,IMPORT_TYPE,DATE_IMPORT
            FROM egw_alix_import
            WHERE currentapp='".$GLOBALS['egw_info']['flags']['currentapp']."' AND
                  IMPORT_TYPE='$exportType'
            ORDER BY DATE_IMPORT DESC";
            
    $GLOBALS['egw']->db->query($sql);        

    $tblExport = array();
    while($GLOBALS['egw']->db->next_record()){
      $tblExport[] = array('IMPORTID' => $GLOBALS['egw']->db->f('IMPORTID'),
                           'DATE_IMPORT_FILE' => $GLOBALS['egw']->db->f('DATE_IMPORT_FILE'), 
                           'USER' => $GLOBALS['egw']->db->f('USER'),
                           'STATUS' => $GLOBALS['egw']->db->f('STATUS'),
                           'ERROR_COUNT' => $GLOBALS['egw']->db->f('ERROR_COUNT'),
                           'IMPORT_FILE' => $GLOBALS['egw']->db->f('IMPORT_FILE'),
                           'REPORT_FILE' => $GLOBALS['egw']->db->f('REPORT_FILE'),
                           'DATE_IMPORT' => $GLOBALS['egw']->db->f('ERROR_COUNT'),                          
                           );  
    }
    return $tblExport;
  }

/*
@param filetype string IMPORT_FILE or REPORT_FILE
*/  
  public function getImportFile($importId,$fileType){    
        
    //Recuperation des informations sur le fichier demandé
    $sql = "SELECT $fileType,importpath
            FROM egw_alix_import
            WHERE IMPORTID='$importId'";
            
    $GLOBALS['egw']->db->query($sql);    
    
    if($GLOBALS['egw']->db->next_record()){
      $filename = $GLOBALS['egw']->db->f($fileType);
      $filepath = $GLOBALS['egw']->db->f('importpath') . $filename;
    }
    
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    
    readfile($filepath);
  }  

  /*
  @desc recuperation des erreurs de schéma XML
  @author http://php.net/manual/fr/domdocument.schemavalidate.php
  */   
  private function libxml_display_error($error)
  {
    $return = "<br/>\n";

    $return .= trim($error->message);
    if ($error->file) {
        $return .=    " in <b>$error->file</b>";
    }
    $return .= " on line <b>$error->line</b>\n";

    return $return;
  }
}