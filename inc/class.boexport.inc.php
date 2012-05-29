<?php
require_once("class.CommonFunctions.php");

class boexport extends CommonFunctions
{  

  //Constructeur
  function __construct(&$tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }               

  /**
   *Add or Update a export type
   *@param id string if 0, we add a new export type. Otherwise we are updating the export type $id
   *@param name,description,share string details on export type
   *@return nothing - raise an exception in case of errors
   *@author wlt - 06/12/2011   
   **/              
  function defineExport(&$id,$name,$description,$share,$raw)
  {
    $this->addLog("boexport::defineExport($id,$name,$description,$share,$raw)",INFO);
    
    //Checking parameters
    if($name==""){
      throw new Exception("Name cannot be empty");
      return;
    }else{
      if($description==""){
        throw new Exception("Description cannot be empty");
        return;
      }else{
        if($share!='Y' and $share!='N'){
          throw new Exception("Share must be 'Y' or 'N'");
          return;
        }else{
          if($raw!='Y' and $raw!='N'){
            throw new Exception("Raw must be 'Y' or 'N'");
            return;
          }
        }
      }
    }
   
    //Parameters are OK
    $id = mysql_real_escape_string($id);
    $name = mysql_real_escape_string($name);
    $description = mysql_real_escape_string($description);
    $share = mysql_real_escape_string($share); 
    $raw = mysql_real_escape_string($raw); 
    
    if($id=="0"){
      //Insertion
      $sql = "INSERT INTO egw_alix_export(name,description,user,creationDate,share,raw,currentapp)
                                   VALUES('$name','$description','".$GLOBALS['egw_info']['user']['userid']."',now(),'$share','$raw','".$this->getCurrentApp(true)."')";
      $GLOBALS['egw']->db->query($sql);
      $id = mysql_insert_id();
    }else{
      //Update - only owner of export can modify it
      $sql = "UPDATE egw_alix_export set name='$name',description='$description',share='$share',raw='$raw' 
              WHERE id='$id' and user='".$GLOBALS['egw_info']['user']['userid']."'";      
      $GLOBALS['egw']->db->query($sql);
    }
  }

  /**
  Produce a new data export. Rules are as follows :
      1 file is produced per ItemGroue
      Variables to be exported could be defined in the UI or/and the config file config.export.inc.php
      CSV files are zipped and password protected in 1 file which is avaiable for download from UI
  @param string $rawValue 'Y' or 'N' Do we extract raws values or decoded values ?
  @author WLT - 09/12/2011
  **/   
  public function runExport($id,$type,$rawValue='Y')
  { 	        
    $this->addLog("boexport::runExport($id,$type,$rawValue)",INFO);
    //Checking parameters
    if($type!="db" && $type!="config_file"){
      throw new Exception("Type must be db or config_file cannot be empty");
      return;
    }else{
      if($id==""){
        throw new Exception("ID cannot be empty");
        return;
      }
    }   
    
    if($type=="config_file"){
      if(isset($this->m_tblConfig['EXPORT']['TYPE'][$id]['index'])){
        $exportIndex = $this->m_tblConfig['EXPORT']['TYPE'][$id]['index'];
        $exportDef = $this->m_tblConfig[$exportIndex];
        $contextVars = $this->m_tblConfig['EXPORT']['TYPE'][$id]['contextVars']; 
      }else{
        $this->addLog("export : unknown type $id",FATAL);
      }
    }else{
      if($type=="db"){
        $contextVars = $this->m_tblConfig['EXPORT']["DEFAULT_CONTEXT"];
        $exportDef = array();
        $sql = "SELECT studyeventoid,formoid,itemgroupoid,fields
                FROM egw_alix_export_def
                WHERE exportid='$id'";
                
        $GLOBALS['egw']->db->query($sql);        
        while($GLOBALS['egw']->db->next_record()){
          $studyeventoid = array($GLOBALS['egw']->db->f('studyeventoid'));
          $formoid = array($GLOBALS['egw']->db->f('formoid'));
          $itemgroupoid = $GLOBALS['egw']->db->f('itemgroupoid');
          $fields = json_decode($GLOBALS['egw']->db->f('fields'));
          //If we have multiple line for one IG, we have to merge fields and keys
          if(isset($exportDef[$itemgroupoid])){
            $studyeventoid = array_merge($studyeventoid,$exportDef[$itemgroupoid]['SEOID']);
            $formoid = array_merge($formoid,$exportDef[$itemgroupoid]['FRMOID']);    
          }
          $exportIGdef = array('FILEDEST'=>$itemgroupoid,'FIELDLIST'=>$fields,'SEOID'=>$studyeventoid,'FRMOID'=>$formoid);
          $exportDef[$itemgroupoid] = $exportIGdef;
        }    
      }
    }        
    
    //temporary storage of files, before zipping
    $tmp = sys_get_temp_dir();
  	$uid = uniqid($type);
  	mkdir($tmp.'/'.$uid);    
    
    ob_end_flush();
    ob_flush(); 
    flush();
    
    //Retrieve metadatas
    $query = "
             let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='{$this->m_tblConfig["METADATAVERSION"]}']
                
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
    $ItemGroupDefs = $this->m_ctrl->socdiscoo()->query($query);
    
    $filename = $tmp.'/'.$uid."/Structure.csv";
    $fp = fopen($filename, 'w');
    
    $line = array('TABLE','FIELD','QUESTION','DATATYPE','LENGTH','CODELIST','UNIT');
    fputcsv($fp, $line,';');
    
    $tblCodeList = array('CL.$COUNT');
   
    foreach($ItemGroupDefs as $ItemGroupDef){
      $ItemGroupOID = (string)$ItemGroupDef['OID'];
      
      if(isset($exportDef[$ItemGroupOID])){

        foreach($ItemGroupDef as $ItemDef){
          $ItemOID = (string)$ItemDef['OID'];
          if(!isset($exportDef[$ItemGroupOID]['FIELDLIST']) || 
             in_array($ItemOID,$exportDef[$ItemGroupOID]['FIELDLIST'])){
            //Requested field
            if($ItemDef['Length']!="" && $ItemDef['SignificantDigits']!=""){
              $length = $ItemDef['Length'] . "." . $ItemDef['SignificantDigits']; 
            }else{
              $length = $ItemDef['Length'];
            }
            $line = array($exportDef[$ItemGroupOID]['FILEDEST'],
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
    
    //Codelist file
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
/** Program : importFormat.sas                                                      **/
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
/** Date     | Author | Descritption                                                **/
/**                                                                                 **/
/**                                                                                 **/
/*************************************************************************************/
/*************************************************************************************/"; 
   
    $query = "
             let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='{$this->m_tblConfig["METADATAVERSION"]}']
                
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
    $Codelists = $this->m_ctrl->socdiscoo()->query($query);

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
    
    //For each patient, context keys values are extracted, to be inserted in every tables
    $sitesFilter = $this->m_ctrl->bosubjects()->getUserSites();

    $queryCol = array();
    foreach($sitesFilter as $siteId){
      $queryCol[] = "index-scan('SiteRef','$siteId','EQ')";  
    }
   
    $SubjectDatasSelect = implode(" union ",$queryCol);
   
    $query = "let \$SubjectDatas := $SubjectDatasSelect
              for \$SubjectData in \$SubjectDatas
              ";

    foreach($contextVars as $key=>$col){
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
    
    foreach($contextVars as $key=>$col){
      if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
        $query .= "$key='{\$col$key}' ";
      }
    }                     
    $query .= "/>";    
    
    $subjs = $this->m_ctrl->socdiscoo()->query($query);

    $tblSubj = array();
    foreach($subjs as $subj){
      $subjKey = (string)$subj['SubjectKey'];
      foreach($contextVars as $key=>$col){
        if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME"))){
          $tblSubj[$subjKey]["$key"] = (string)$subj["$key"];
        }
      }
    }

    //Setup of the SAS Import file
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
/** Date     | Author | Description                                                 **/
/**                                                                                 **/
/**                                                                                 **/
/*************************************************************************************/
/*************************************************************************************/

%let PATH_TO_CSV=PATH_TO_CSV_WITHOUT_ENDING_SLASH;

%include \"&PATH_TO_CSV\\importFormat.sas\";
";
    //Loop through itemgroup = tables with vertical pool 
    //tables with horizontal pool are stored in array TblPoolH, to be used later 
    $tblPoolH = array();
    $tblHeadLine = array(); //Header - field match - offset
    foreach($exportDef as $ItemGroupOID => $ItemGroupDest){
      $tblFields = $ItemGroupDest['FIELDLIST'];
      
      if(isset($ItemGroupDest['SEOID'])){
        $tblStudyEventOID = $ItemGroupDest['SEOID'];
      }else{
        $tblStudyEventOID = array();
      }
      
      if(isset($ItemGroupDest['FRMOID'])){
        $tblFormOID = $ItemGroupDest['FRMOID'];
      }else{
        $tblFormOID = array();
      }
      
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
          //360 seconds per ItemGroup
          set_time_limit(360);
          
          echo "<br/>Exporting $ItemGroupOID...";
          flush();
          
          //read metadata - to get fields informations
          $query = "let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='{$this->m_tblConfig["METADATAVERSION"]}']
                    let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID='$ItemGroupOID']
                    for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                    let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                    
                    return
                      <ItemRef ItemOID='{\$ItemRef/@ItemOID}' 
                               Name='{\$ItemDef/@SASFieldName}' 
                               DataType='{\$ItemDef/@DataType}' 
                               CodeListOID='{\$ItemDef/odm:CodeListRef/@CodeListOID}'
                               Label='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()}'
                               Length='{\$ItemDef/@Length}'
                               SignificantDigits='{\$ItemDef/@SignificantDigits}'/>";
          $tblItemRef = $this->m_ctrl->socdiscoo()->query($query);
                          
          //Creation of dest csv file
          $filename = $tmp.'/'.$uid."/$destFile.csv";
          $fexists = file_exists($filename);
          $fp = fopen($filename, 'a+');
          
          $procSAS .= " 

  data $destFile;
  infile \"&PATH_TO_CSV\\$destFile.csv\" 
  delimiter = ';' MISSOVER DSD lrecl=32767 firstobs=2;";
  
          $procSASinput = "
    
    INPUT";
          
          $procSASformat = "
                              
    Format ";
          
          $procSASlabel = "
                   
    Label ";
              
          //header line - fields name
          if($fexists==false){
            $tblHeadLine = array();
            
            $line = array();
            foreach($contextVars as $contextVar => $infos){
              $line[] = $contextVar;  
              $procSASinput .= "
       $contextVar";
                  
              if($infos['type']=="string" || $infos['type']==="text"){
                $procSASinput .= " \$ ";
              }
                             
              if($infos['codelist']!=""){
                //We remove the $ character
                $CodelistOID = str_replace(".\$","_",$infos['codelist']);
                if($infos['type']=="string" || $infos['type']==="text"){
                  $CodelistOID = "\$" . $CodelistOID;
                }
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
                    $procSASinput .= "
       $col \$";
                  }else{
                    $procSASinput .= "
       $col";
                  }
                  
                  if($CodeListOID!=""){
                    //We remove the $ character
                    $CodeListOID = str_replace(".\$","_",$CodeListOID);
                    if($DataType=="string" || $DataType=="text" || $DataType=="partialDate" || $DataType=="partialTime"){
                    $CodeListOID = "\$".$CodeListOID;
                    }
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
          
          $procSAS .= $procSASformat. "\n;" . $procSASinput . "\n;" . $procSASlabel . "\n; \nrun;";
          
          //Data extraction
          $query = "import module namespace alix = 'http://www.alix-edc.com/alix';
              
                    let \$SubjectDatas := $SubjectDatasSelect
                    let \$BLANKSubjectData := index-scan('SubjectData','BLANK','EQ')
                    let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='{$this->m_tblConfig["METADATAVERSION"]}']
                    let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID='$ItemGroupOID']
                    for \$ItemGroupData in \$SubjectDatas/odm:StudyEventData/
                                                         odm:FormData[@TransactionType!='Remove']/
                                                         odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @TransactionType!='Remove']
                    let \$SubjectKey := \$ItemGroupData/../../../@SubjectKey
                    let \$StudyEventOID := \$ItemGroupData/../../@StudyEventOID
                    let \$StudyEventRepeatKey := \$ItemGroupData/../../@StudyEventRepeatKey
                    let \$FormOID := \$ItemGroupData/../@FormOID
                    let \$FormRepeatKey := \$ItemGroupData/../@FormRepeatKey
                    let \$StudyEventName := \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventOID]/odm:Description/odm:TranslatedText               
                    where count(\$ItemGroupData/odm:*) gt count(\$BLANKSubjectData/odm:StudyEventData[@StudyEventOID=\$StudyEventOID and @StudyEventRepeatKey=\$StudyEventRepeatKey]/odm:FormData[@FormOID=\$FormOID and @FormRepeatKey=\$FormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey=\$ItemGroupData/@ItemGroupRepeatKey]/odm:*)
                          and (\$ItemGroupDef/@Repeating='No' or \$ItemGroupDef/@Repeating='Yes' and \$ItemGroupData/@ItemGroupRepeatKey!='0')   
                    return
                      <ItemGroupData SubjectKey='{\$SubjectKey}' 
                                     StudyEventOID='{\$StudyEventOID}'
                                     StudyEventName='{\$StudyEventName}'
                                     FormOID='{\$FormOID}'
                                     ItemGroupRepeatKey='{\$ItemGroupData/@ItemGroupRepeatKey}'
                                     >
                      {
                        for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                        let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                        let \$ItemData := (\$ItemGroupData/odm:*[@ItemOID=\$ItemRef/@ItemOID])[last()]
                        let \$DecodedValue := alix:getDecode(\$ItemData,\$MetaDataVersion)
                        return
                          <ItemData OID='{\$ItemRef/@ItemOID}' 
                                    Name='{\$ItemDef/@SASFieldName}' 
                                    Value='{\$ItemData/string()}'
                                    DecodedValue='{\$DecodedValue}'/>
                      }
                      </ItemGroupData>  
                   ";
      
          $ItemGroupDatas = $this->m_ctrl->socdiscoo()->query($query);
          
          //Loop through lines
          $rowNum = 0;
          foreach($ItemGroupDatas as $ItemGroupData){
            $subjKey = (string)$ItemGroupData['SubjectKey'];
            $IGstudyEventOID = (string)$ItemGroupData['StudyEventOID'];
            $IGformOID = (string)$ItemGroupData['FormOID'];
            
            //We filter result - if specified
            if( (count($tblStudyEventOID)==0 || in_array($IGstudyEventOID,$tblStudyEventOID)) && 
                (count($tblFormOID)==0 || in_array($IGformOID,$tblFormOID)) )
            {
              $line = array_fill(0,count($tblHeadLine),"");
  
              //Fill the context vars
              foreach($contextVars as $key=>$col){
                if(!in_array($key,array("SUBJID","VISITNUM","VISITNAME","IGRK"))){
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
                    case 'IGRK' :
                              $line[$tblHeadLine["IGRK"]] = (string)$ItemGroupData['ItemGroupRepeatKey'];
                              break;
                  }  
                }
              }
                          
             foreach($ItemGroupData as $ItemData){
                $itemName = (string)$ItemData['Name'];
                $OID = (string)$ItemData['OID'];
                if(!is_array($tblFields) || in_array($OID,$tblFields)){
                  if($rawValue=="Y"){
                    $value = utf8_decode($ItemData['Value']);
                  }else{
                    $value = utf8_decode($ItemData['DecodedValue']);
                  }
                  $line[$tblHeadLine[$itemName]] = $value;
                }
              }
              $rowNum++;
              //Add line of data to csv file
              fputcsv($fp, $line,';');
            }
          }
          fclose($fp); 
          
          echo "$rowNum lines exported to $destFile.csv";
          flush();                          
        }
      }
    }

    //Loop through table, handle of itemgroup pooled horizontaly
    //Horizontal pool implies only 1 itemgroupdata of each itemgroup per visit
    $tblFields = array();
    foreach($tblPoolH as  $destFile => $ItemGroupOIDList){
      //360 seconds per ItemGroup
      set_time_limit(360);
      
      $xQuerySelect = "";
      $xQuerySelectIGOID = "";
      $lstIGOID = "";  //Only for display
      for($i=0;$i<count($ItemGroupOIDList);$i++){
        $ItemGroupOID = $ItemGroupOIDList[$i];
        $xQuerySelect .= "@OID='$ItemGroupOID'";
        $xQuerySelectIGOID .= "@ItemGroupOID='$ItemGroupOID'";
        $lstIGOID .= "$ItemGroupOID ,";
        if( $i<count($ItemGroupOIDList)-1 ){
          $xQuerySelect .= " or ";
          $xQuerySelectIGOID .= " or ";  
        }
        if(isset($exportDef[$ItemGroupOID]['FIELDLIST'])){
          $tblFields = array_merge($tblFields,$exportDef[$ItemGroupOID]['FIELDLIST']);
        }
      }   
        
      //Retrieve metadatas - get fields informations
      $query = "let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='{$this->m_tblConfig["METADATAVERSION"]}']                    
                let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[$xQuerySelect]
                for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                return
                  <ItemRef ItemOID='{\$ItemRef/@ItemOID}' 
                           Name='{\$ItemDef/@SASFieldName}'
                           DataType='{\$ItemDef/@DataType}' 
                           CodeListOID='{\$ItemDef/odm:CodeListRef/@CodeListOID}'
                           Label='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()}'
                           Length='{\$ItemDef/@Length}'
                           SignificantDigits='{\$ItemDef/@SignificantDigits}'/>";
      $tblItemRef = $this->m_ctrl->socdiscoo()->query($query);
      
      $procSAS .= " 

data $destFile;
infile \"&PATH_TO_CSV\\$destFile.csv\" 
delimiter = ';' MISSOVER DSD lrecl=32767 firstobs=2;";

      $procSASformat = "
                              
    Format ";
    
      $procSASinput = "

    INPUT ";
          
      $procSASlabel = "
                   
    Label ";                      
      //Creation of the dest csv file
      $filename = $tmp.'/'.$uid."/$destFile.csv";
      $fexists = file_exists($filename);
      $fp = fopen($filename, 'a+');
      
      //header line - fields name
      if($fexists==false){
        $colNum = 0;
        $line = array();
        foreach($contextVars as $contextVar => $infos){
          $line[] = $contextVar;  
          $procSASinput .= "
       $contextVar";
              
          if($infos['type']=="string" || $infos['type']==="text"){
            $procSASinput .= " \$ ";
          }
                         
          if($infos['codelist']!=""){
            //We remove the $ character
            $CodelistOID = str_replace(".\$","_",$infos['codelist']);    
            if($infos['type']=="string" || $infos['type']=="text" || $infos['type']=="partialDate" || $infos['type']=="partialTime"){
              $CodelistOID = "\$".$CodelistOID;
            }
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
            
            $procSASinput .= "
       ".(string)$ItemRef['Name'];
            
            $bItemFind = false;
            $i=0;              
            while($bItemFind==false && $i<count($tblItemRef)){
              if((string)($tblItemRef[$i]['Name'])==(string)$ItemRef['Name']){
                $DataType = (string)($tblItemRef[$i]['DataType']);
                $CodeListOID = (string)($tblItemRef[$i]['CodeListOID']);
                $SASName = (string)$ItemRef['Name']; 
                if($DataType=="string" || $DataType=="text" || $DataType=="partialDate" || $DataType=="partialTime"){
                  $procSASinput .= " \$ ";
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

      $procSAS .= $procSASformat . "\n;" . $procSASinput . "\n;" . $procSASlabel . "\n; \nrun;";
 
      $query = "let \$SubjectDatas := $SubjectDatasSelect
                let \$BLANKSubjectData := index-scan('SubjectData','BLANK','EQ')
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='{$this->m_tblConfig["METADATAVERSION"]}']                    
                let \$ItemGroupDef := \$MetaDataVersion/odm:ODM/odm:Study/odm:MetaDataVersion/odm:ItemGroupDef[$xQuerySelect]
                for \$StudyEventData in \$SubjectDatas/odm:StudyEventData[odm:FormData/odm:ItemGroupData[$xQuerySelectIGOID and @TransactionType!='Remove']]
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
                    let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                    let \$ItemData := (\$StudyEventData/odm:FormData/odm:ItemGroupData/odm:*[@ItemOID=\$ItemRef/@ItemOID])[last()]
                    return
                      <ItemData OID='{\$ItemRef/@ItemOID}' 
                                Name='{\$ItemDef/@SASFieldName}' 
                                Value='{\$ItemData/string()}'/>
                  }
                  </StudyEventData>  
               ";
      $StudyEventDatas = $this->m_ctrl->socdiscoo()->query($query);
        
      $rowNum = 0;
      foreach($StudyEventDatas as $ItemGroupData){
        //Preliminary check : we expect only one ItemGroupData of each Itemgroup listed per StudyEventData
        if($ItemGroupData['NbIG']>count($ItemGroupOIDList)){
          $str = "<html>Export / Subject {$ItemGroupData['SubjectKey']} / Visit {$ItemGroupData['StudyEventOID']}-{$ItemGroupData['StudyEventName']} : Expecting ".count($ItemGroupOIDList)." ItemGroupData, but {$ItemGroupData['NbIG']} found. ($xQuerySelectIGOID)</html> (". __METHOD__ .")";
          $this->addLog("Error : exportDSMB() => $str",FATAL);
        }
        $colNum = 0;
        $line = array();
        $subjKey = (string)$ItemGroupData['SubjectKey'];

        foreach($contextVars as $key=>$col){
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
        //Add line to csv file
        fputcsv($fp, $line,';');
      }
      fclose($fp); 
      
      echo "<br/>$rowNum lines exported to $destFile.csv from itemgroups $lstIGOID";
      flush();  
    }
    
    file_put_contents($tmp.'/'.$uid . '/import.sas',$procSAS);
    file_put_contents($tmp.'/'.$uid . '/importFormat.sas',$procSasFormat);


    //Annotations - Creation of the dest csv file
    $filename = $tmp.'/'.$uid."/Annotations.csv";
    $fexists = file_exists($filename);
    $fp = fopen($filename, 'a+');
          
    //Processing of Annotation - Here we add all annonations into one file
    $query = "let \$SubjectDatas := $SubjectDatasSelect             
              for \$SubjectData in \$SubjectDatas
              let \$SubjectKey := \$SubjectData/@SubjectKey 
              for \$Annotation in \$SubjectData/../odm:Annotations/odm:Annotation                
                let \$AnnotationID := \$Annotation/@ID
                let \$ItemDatas := \$SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:*[@AnnotationID=\$AnnotationID]
                for \$ItemData in \$ItemDatas
                  let \$StudyEventOID := \$ItemData/../../../@StudyEventOID
                  let \$StudyEventRepeatKey := \$ItemData/../../../@StudyEventRepeatKey
                  let \$FormOID := \$ItemData/../../@FormOID
                  let \$FormRepeatKey := \$ItemData/../../@FormRepeatKey
                  let \$ItemGroupOID := \$ItemData/../@ItemGroupOID
                  let \$ItemGroupRepeatKey := \$ItemData/../@ItemGroupRepeatKey
                  return
                    <AnnotedItem SubjectKey='{\$SubjectKey}'
                                 StudyEventOID='{\$StudyEventOID}'
                                 StudyEventRepeatKey='{\$StudyEventRepeatKey}'
                                 FormOID='{\$FormOID}'
                                 FormRepeatKey='{\$FormRepeatKey}'
                                 ItemGroupOID='{\$ItemGroupOID}'
                                 ItemGroupRepeatKey='{\$ItemGroupRepeatKey}'
                                 ItemOID='{\$ItemData/@ItemOID}'>
                      <FlagValue>{\$Annotation/odm:Flag/odm:FlagValue/text()}</FlagValue>
                      <Comment>{\$Annotation/odm:Comment/text()}</Comment>  
                    </AnnotedItem>      
              ";   
    $AnnotedItems = $this->m_ctrl->socdiscoo()->query($query);
    fputcsv($fp, array("SubjectKey","StudyEventOID","StudyEventRepeatKey","FormOID","FormRepeatKey","ItemGroupOID","ItemGroupRepeatKey","ItemOID","Flag","Comment"),';');                              
    
    $tblAnnot = array();
    foreach($AnnotedItems as $AnnotedItem){
      $line = array();
      $key = (string)$AnnotedItem['SubjectKey'] . "_" . 
             (string)$AnnotedItem['StudyEventOID'] . "_" .  
             (string)$AnnotedItem['StudyEventRepeatKey'] . "_" .
             (string)$AnnotedItem['FormOID'] . "_" .
             (string)$AnnotedItem['FormRepeatKey'] . "_" .
             (string)$AnnotedItem['ItemGroupOID'] . "_" .
             (string)$AnnotedItem['ItemGroupRepeatKey'] . "_" .
             (string)$AnnotedItem['ItemOID'];
             
      $line[] = (string)$AnnotedItem['SubjectKey'];
      $line[] = (string)$AnnotedItem['StudyEventOID'];  
      $line[] = (string)$AnnotedItem['StudyEventRepeatKey'];
      $line[] = (string)$AnnotedItem['FormOID'];
      $line[] = (string)$AnnotedItem['FormRepeatKey'];
      $line[] = (string)$AnnotedItem['ItemGroupOID'];
      $line[] = (string)$AnnotedItem['ItemGroupRepeatKey'];
      $line[] = (string)$AnnotedItem['ItemOID'];
      $line[] = utf8_decode((string)($AnnotedItem->FlagValue));
      $line[] = utf8_decode((string)($AnnotedItem->Comment));
      //We keep only the last annotation
      $tblAnnot[$key] = $line;                                    
    }
    foreach($tblAnnot as $annot){
      fputcsv($fp,$annot,";");
    }
    fclose($fp);
          
    $dsmbFileName = $id."_".date('Y_m_d_H_i').".zip";
    $dsmbFile = $this->m_tblConfig["EXPORT_BASE_PATH"] . $dsmbFileName;
    
    shell_exec('cd '.$tmp.'/'.$uid.';zip -P '.$uid.' -r '.escapeshellarg($dsmbFile).' ./');
    
    $sql = "INSERT INTO egw_alix_export_log(exportid,exportfilename,exportpath,exporttype,exportdate,exportpassword,exportuser,currentapp)
            VALUES('$id','$dsmbFileName','{$this->m_tblConfig["EXPORT_BASE_PATH"]}','$type',now(),'$uid','{$GLOBALS['egw_info']['user']['userid']}','{$GLOBALS['egw_info']['flags']['currentapp']}')";
  
    $GLOBALS['egw']->db->query($sql);
  }
  
  public function getMetaDataStructure(){
    $query = "
      let \$SubjectData := index-scan('SubjectData','BLANK','EQ')
      let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      
      for \$StudyEventDef in \$MetaDataVersion/odm:StudyEventDef
        return
          <StudyEvent StudyEventOID='{\$StudyEventDef/@OID}'
                      StudyEventTitle='{\$StudyEventDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
          {        
            for \$FormRef in \$StudyEventDef/odm:FormRef
            let \$FormDef := \$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]
            return
              <Form FormOID='{\$FormDef/@OID}'
                    FormTitle='{\$FormDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
              {
                for \$ItemGroupRef in \$FormDef/odm:ItemGroupRef
                let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupRef/@ItemGroupOID]
                return
                  <ItemGroup ItemGroupOID='{\$ItemGroupDef/@OID}' 
                             ItemGroupTitle='{\$ItemGroupDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                  {
                    for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                    let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                    return 
                      <Item ItemOID='{\$ItemDef/@OID}'
                            Question='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}' />                        
                  }
                  </ItemGroup>
              }
              </Form>
          }
          </StudyEvent>    
    ";
    $doc = $this->m_ctrl->socdiscoo("BLANK")->query($query);
    return $doc;    
  }

  /**
   *Export edit interface submit data to this function while saving
   *@param integer $id id of the export to save
   *@param array $ItemGroups array of Itemgroup checked for export
   *@param array $Items array of ItemDef checked for export           
   **/  
  function saveExport($id,$ItemGroups,$Items){
    $this->addLog("boexport::saveExport($id)",INFO);
    //Checking parameters
    if($id==""){
      throw new Exception("ID cannot be empty");
      return;
    }else{
      if(is_array($ItemGroups)==false or is_array($ItemGroups) and count($ItemGroups)==0){
        throw new Exception("One Itemgroup must be selected at least");
        return;
      }else{
        if(is_array($Items)==false or is_array($Items) and count($Items)==0){
          throw new Exception("One Item must be selected at least");
          return;
        }
      }
    }
    
    $id = mysql_real_escape_string($id);
    $updateDT = date("c");
          
    foreach($ItemGroups as $ItemGroupOIDInfos){
      $ItemGroupInfo = explode('_',$ItemGroupOIDInfos);
      $StudyEventOID = $ItemGroupInfo[0];
      $FormOID = $ItemGroupInfo[1];
      $ItemGroupOID = $ItemGroupInfo[2]; 
      
      //Here we extract the Item list corresponding to the current ItemGroup
      $tblItem = array();
      foreach($Items as $Item){
        $ItemInfo = explode("_",$Item);
        if($ItemInfo[0]==$StudyEventOID && 
           $ItemInfo[1]==$FormOID && 
           $ItemInfo[2]==$ItemGroupOID){
          $tblItem[] = $ItemInfo[3];
        }
      }

      $sql = "REPLACE INTO egw_alix_export_def(exportid,studyeventoid,formoid,itemgroupoid,fields,updateDT) 
                    VALUES('$id','$StudyEventOID','$FormOID','$ItemGroupOID','".json_encode($tblItem)."','$updateDT')";

      $GLOBALS['egw']->db->query($sql);
    }
    
    //We delete old definition for this export 
    $sql = "DELETE FROM egw_alix_export_def WHERE exportid='$id' AND updateDT<>'$updateDT'";                 
    $GLOBALS['egw']->db->query($sql);
    
  }
  
  /**
   * Retrieve the Definition (i.e. Selected Items) of an export
   * @param $id string id of the export to retrieve   
   **/     
  public function getExportDef($id){
    $sql = "SELECT studyeventoid,formoid,itemgroupoid,fields
            FROM egw_alix_export_def
            WHERE exportid='$id'";
    $GLOBALS['egw']->db->query($sql);            
    $tblExport = array();
    while($GLOBALS['egw']->db->next_record()){
      $tblExport[(string)$GLOBALS['egw']->db->f('studyeventoid')]
                [(string)$GLOBALS['egw']->db->f('formoid')]
                [(string)$GLOBALS['egw']->db->f('itemgroupoid')] = json_decode($GLOBALS['egw']->db->f('fields'));
    }
    return $tblExport;    
  }
  
  /**
   *Retrieve export list
   *@param int $id optional : if specified filter the export list to retrieve only one export type
   *@return array         
   **/  
  public function getExportList($id=""){
    $sql = "SELECT id,name,description,user,creationDate,share,raw,MAX(exportdate) as lastrun
            FROM egw_alix_export LEFT JOIN egw_alix_export_log 
              ON egw_alix_export.id=egw_alix_export_log.exportid
            WHERE egw_alix_export.currentapp='".$this->getCurrentApp(true)."'";
 
    $sql = "SELECT id,name,description,user,creationDate,share,raw,
                   (SELECT MAX(exportdate) FROM egw_alix_export_log WHERE egw_alix_export_log.exportid=egw_alix_export.id AND egw_alix_export_log.currentapp='".$this->getCurrentApp(true)."') as lastrun
            FROM egw_alix_export
            WHERE egw_alix_export.currentapp='".$this->getCurrentApp(true)."'";    
    if($id!=""){
      $sql .= " AND id='$id'";
    }
            
    $GLOBALS['egw']->db->query($sql);        

    $tblExport = array();
    while($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw_info']['user']['apps']['admin'] ||
         $GLOBALS['egw_info']['user']['userid']==$GLOBALS['egw']->db->f('user') ||
         $GLOBALS['egw']->db->f('share')=="Y")
      {
        $tblExport[] = array('id' => $GLOBALS['egw']->db->f('id'),
                             'name' => $GLOBALS['egw']->db->f('name'),
                             'description' => $GLOBALS['egw']->db->f('description'), 
                             'user' => $GLOBALS['egw']->db->f('user'),
                             'creationDate' => $GLOBALS['egw']->db->f('creationDate'),
                             'share' => $GLOBALS['egw']->db->f('share'),
                             'raw' => $GLOBALS['egw']->db->f('raw'),
                             'lastrun' => $GLOBALS['egw']->db->f('lastrun'),
                            );
      }                        
    }
    return $tblExport;
  
  }
  
  public function getLogExport(){
    $sql = "SELECT logid,name,exportfilename,exportpath,exportdate,exportpassword,exportuser,exporttype,exportid
            FROM egw_alix_export_log LEFT JOIN egw_alix_export
            ON egw_alix_export_log.exportid=egw_alix_export.id
            WHERE egw_alix_export_log.currentapp='".$this->getCurrentApp(true)."'
            ORDER BY exportdate DESC";
            
    $GLOBALS['egw']->db->query($sql);        

    $tblExport = array();
    while($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw_info']['user']['apps']['admin'] ||
         $GLOBALS['egw_info']['user']['userid']==$GLOBALS['egw']->db->f('exportuser'))
      {
        $tblExport[] = array('logid' => $GLOBALS['egw']->db->f('logid'),
                             'exporttype' => $GLOBALS['egw']->db->f('exporttype'),
                             'exportid' => $GLOBALS['egw']->db->f('exportid'),
                             'exportname' => $GLOBALS['egw']->db->f('name'),                           
                             'exportfilename' => $GLOBALS['egw']->db->f('exportfilename'), 
                             'exportpath' => $GLOBALS['egw']->db->f('exportpath'),
                             'exportdate' => $GLOBALS['egw']->db->f('exportdate'),
                             'exportpassword' => $GLOBALS['egw']->db->f('exportpassword'),
                             'exportuser' => $GLOBALS['egw']->db->f('exportuser'));  
      }
    }
    return $tblExport;
  }
  
  public function getExportFile($logId){    
    
    //Recuperation des informations sur le fichier demandé
    $sql = "SELECT exportfilename,exportpath
            FROM egw_alix_export_log
            WHERE logid='$logId'";
            
    $GLOBALS['egw']->db->query($sql);    
    
    if($GLOBALS['egw']->db->next_record()){
      $filename = $GLOBALS['egw']->db->f('exportfilename');
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
        $sasFormat = $Length . "." . $SignificantDigits;
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