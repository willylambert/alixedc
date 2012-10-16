<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *
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

class bolockdb extends CommonFunctions
{  

  //Constructeur
  function __construct(&$tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }               

  /**
   *Add or Update a lock type
   *@param id string if 0, we add a new lock type. Otherwise we are updating the lock type $id
   *@param name,description,share string details on lock type
   *@return nothing - raise an exception in case of errors
   *@author wlt - 06/12/2011   
   **/              
  function defineLockDB(&$id,$name,$description,$share)
  {
    $this->addLog("bolockdb::defineLockDB($id,$name,$description,$share)",INFO);
    
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
        }
      }
    }
   
    //Parameters are OK
    $id = mysql_real_escape_string($id);
    $name = mysql_real_escape_string($name);
    $description = mysql_real_escape_string($description);
    $share = mysql_real_escape_string($share);
    
    if($id=="0"){
      //Insertion
      $sql = "INSERT INTO egw_alix_lockdb(name,description,user,creationDate,share,currentapp)
                                   VALUES('$name','$description','".$GLOBALS['egw_info']['user']['userid']."',now(),'$share','".$this->getCurrentApp(true)."')";
      $GLOBALS['egw']->db->query($sql);
      $id = mysql_insert_id();
    }else{
      //Update - only owner of lock can modify it
      $sql = "UPDATE egw_alix_lockdb set name='$name',description='$description',share='$share' 
              WHERE id='$id' and user='".$GLOBALS['egw_info']['user']['userid']."'";      
      $GLOBALS['egw']->db->query($sql);
    }
  }

  /**
  Produce a new data lock:
      - set any other lock definition as inactive (MySQL)
      - set the lock definition as active (MySQL)
      - produce a PDF to have a log of what is locked
  @author TPI - 09/10/2012
  **/   
  public function activateLockDB($id)
  {
    //set any other lock definition as inactive (MySQL)
    //$sql = "UPDATE egw_alix_lockdb set active='N', lastactivation='".date('Y-m-d H:i:s')."'";
    //$GLOBALS['egw']->db->query($sql);
    
    //set the lock definition as active (MySQL)
    $sql = "UPDATE egw_alix_lockdb set active='Y'
            WHERE id='$id'";
    $GLOBALS['egw']->db->query($sql);
    
    //Generation of the MetaLocks XML required for xQueries
    $this->generateMetaLocks();
    
    //create log
    $this->addLogLockDB($id,"activate");
    
  }
  
  /**
   * Create a PDF file to store the lock definition, and add a reference to this file in the database
   * @param id : lockid
   * @param action : activate or inactivate
   */   
  private function addLogLockDB($id,$action){
    
    //produce a PDF to have a log of what is locked
    $sql = "SELECT studyeventoid,studyeventrepeatkey,formoid,formrepeatkey,itemgroupoid,itemgrouprepeatkey,fields
            FROM egw_alix_lockdb_def, egw_alix_lockdb
            WHERE egw_alix_lockdb.active='Y'
                  AND egw_alix_lockdb_def.lockid=egw_alix_lockdb.id
                  AND egw_alix_lockdb.currentapp='".$this->getCurrentApp(true)."'
            ORDER BY studyeventoid, studyeventrepeatkey, formoid, formrepeatkey, itemgroupoid, itemgrouprepeatkey";
    $GLOBALS['egw']->db->query($sql);
    $html = " <h1>Lock of database: list of locked fields</h1>
              <br />Date: ".date('Y-m-d H:i')."
              <br />User: ".$this->m_user."
              <br />
              <table width='700px' border='1'>
                <tr><td>Visit OID</td><td>Visit RK</td><td>Form OID</td><td>Form RK</td><td>Section OID</td><td>Section RK</td><td>Fields</td></tr>";
    
    
    //Generate CSV file. Generate HTML for PDF
    $lockFileName = sprintf("%02d",$id)."_".date('Y_m_d_H_i')."_$action";
    $fp = fopen($this->m_tblConfig["LOCKDB_LOG_PATH"] . $lockFileName.".csv", 'w');
    $line = array("Visit OID", "Visit RK", "Form OID", "Form RK", "Section OID", "Section RK", "Fields");
    fputcsv($fp, $line,';');
    
    while($GLOBALS['egw']->db->next_record()){
      $line = array($GLOBALS['egw']->db->f('studyeventoid'), implode(',',(array)json_decode($GLOBALS['egw']->db->f('studyeventrepeatkey'))), $GLOBALS['egw']->db->f('formoid'), implode(',',(array)json_decode($GLOBALS['egw']->db->f('formrepeatkey'))), $GLOBALS['egw']->db->f('itemgroupoid'), implode(',',(array)json_decode($GLOBALS['egw']->db->f('itemgrouprepeatkey'))), implode(",",(array)json_decode($GLOBALS['egw']->db->f('fields'))));
      fputcsv($fp, $line,';');
      
      $html .= "<tr>";
      $html .= "<td valign='top'>". $GLOBALS['egw']->db->f('studyeventoid') ."&nbsp;</td>";
      $html .= "<td valign='top'>". implode('<br />',(array)json_decode($GLOBALS['egw']->db->f('studyeventrepeatkey'))) ."&nbsp;</td>";
      $html .= "<td valign='top'>". $GLOBALS['egw']->db->f('formoid') ."&nbsp;</td>";
      $html .= "<td valign='top'>". implode('<br />',(array)json_decode($GLOBALS['egw']->db->f('formrepeatkey'))) ."&nbsp;</td>";
      $html .= "<td valign='top'>". $GLOBALS['egw']->db->f('itemgroupoid') ."&nbsp;</td>";
      $html .= "<td valign='top'>". implode('<br />',(array)json_decode($GLOBALS['egw']->db->f('itemgrouprepeatkey'))) ."&nbsp;</td>";
      $html .= "<td valign='top'>". implode("<br />",(array)json_decode($GLOBALS['egw']->db->f('fields'))) ."&nbsp;</td>";
      $html .= "</tr>";
    }
    $html .= "</table>";
    
    //PDF Generation
    $htmlTemp = tempnam("/tmp","htmlDoc");
    $tmpHandle = fopen($htmlTemp,"w");
    fwrite($tmpHandle,$html);
    fclose($tmpHandle);
    
    # Tell HTMLDOC not to run in CGI mode...
    putenv("HTMLDOC_NOCGI=1");
    //Generation
    $cmd = "htmldoc -t pdf --quiet --color --webpage --jpeg  --left 30 --top 20 --bottom 20 --right 20 --footer c.: --fontsize 10 --textfont {helvetica}";
    ob_start();
    $err = passthru("$cmd '$htmlTemp'");
    $content = ob_get_contents();
    ob_end_clean();
    $tmpHandle = fopen($this->m_tblConfig["LOCKDB_LOG_PATH"] . $lockFileName.".pdf","w");
    fwrite($tmpHandle,$content);
    fclose($tmpHandle);
    unlink($htmlTemp);
    
    //add log in database
    $sql = "INSERT INTO egw_alix_lockdb_log(lockid,lockfilename,lockpath,lockdate,lockuser,currentapp)
            VALUES('$id','$lockFileName','{$this->m_tblConfig["LOCKDB_LOG_PATH"]}',now(),'{$this->m_user}','{$this->getCurrentApp(true)}')";
  
    $GLOBALS['egw']->db->query($sql);
  }
  
  /**
  Disable a data lock:
      - set the lock definition as inactive (MySQL)
      - produce a PDF to have a log of what is locked/unlocked
  @author TPI - 09/10/2012
  **/   
  public function inactivateLockDB($id)
  {
    //set the lock definition as inactive (MySQL)
    $sql = "UPDATE egw_alix_lockdb set active='N'
            WHERE id='$id'";      
    $GLOBALS['egw']->db->query($sql);
    
    //Generation of the MetaLocks XML required for xQueries
    $this->generateMetaLocks();
    
    //create log
    $this->addLogLockDB($id,"inactivate");
    
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
   *LockDB edit interface submit data to this function while saving
   *@param integer $id id of the lock to save
   *@param array $ItemGroups array of Itemgroup checked for lock
   *@param array $Items array of ItemDef checked for lock           
   **/  
  function saveLockDB($id,$ItemGroups,$Items){
    $this->addLog("bolockdb::saveLockDB($id)",INFO);
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
      
      //Here we extract the filters on StudyEventRepeatKey, FormRepeatKey and ItemGroupRepeatKey
      $StudyEventRepeatKey = "";
      $FormRepeatKey = "";
      $ItemGroupRepeatKey = "";
      $postStudyEventOID = str_replace('.','@',$StudyEventOID);
      $postFormOID = str_replace('.','@',$FormOID);
      $postItemGroupOID = str_replace('.','@',$ItemGroupOID);
      if(isset($_POST["StudyEventRepeatKey_{$postStudyEventOID}"])){
        $StudyEventRepeatKey = $_POST["StudyEventRepeatKey_{$postStudyEventOID}"];
      }
      if(isset($_POST["FormRepeatKey_{$postStudyEventOID}_{$postFormOID}"])){
        $FormRepeatKey = $_POST["FormRepeatKey_{$postStudyEventOID}_{$postFormOID}"];
      }
      if(isset($_POST["ItemGroupRepeatKey_{$postStudyEventOID}_{$postFormOID}_{$postItemGroupOID}"])){
        $ItemGroupRepeatKey = $_POST["ItemGroupRepeatKey_{$postStudyEventOID}_{$postFormOID}_{$postItemGroupOID}"];
      }

      $sql = "REPLACE INTO egw_alix_lockdb_def(lockid,studyeventoid,studyeventrepeatkey,formoid,formrepeatkey,itemgroupoid,itemgrouprepeatkey,fields,updateDT) 
                    VALUES('$id','$StudyEventOID','".json_encode(explode(',',$StudyEventRepeatKey))."','$FormOID','".json_encode(explode(',',$FormRepeatKey))."','$ItemGroupOID','".json_encode(explode(',',$ItemGroupRepeatKey))."','".json_encode($tblItem)."','$updateDT')";

      $GLOBALS['egw']->db->query($sql);
    }
    
    //We delete old definition for this lock 
    $sql = "DELETE FROM egw_alix_lockdb_def WHERE lockid='$id' AND updateDT<>'$updateDT'";                 
    $GLOBALS['egw']->db->query($sql);
    
    //If the lock is already activated => log this modification as a new activation
    $sql = "SELECT id,active
            FROM egw_alix_lockdb
            WHERE egw_alix_lockdb.currentapp='".$this->getCurrentApp(true)."'
                  AND id='$id'";
    $GLOBALS['egw']->db->query($sql);
    $GLOBALS['egw']->db->next_record();  
    if($GLOBALS['egw']->db->f('active')=="Y"){
      //activate new lock definition
      $this->activateLockDB($id);
    }
    
  }
  
  /**
   * Retrieve the Definition (i.e. Selected Items) of an lock
   * @param $id string id of the lock to retrieve   
   **/     
  public function getLockDBDef($id){
    $sql = "SELECT studyeventoid,studyeventrepeatkey,formoid,formrepeatkey,itemgroupoid,itemgrouprepeatkey,fields
            FROM egw_alix_lockdb_def
            WHERE lockid='$id'";
    $GLOBALS['egw']->db->query($sql);            
    $tblLockDB = array();
    while($GLOBALS['egw']->db->next_record()){
      $tblLockDB[(string)$GLOBALS['egw']->db->f('studyeventoid')]
                [(string)$GLOBALS['egw']->db->f('formoid')]
                [(string)$GLOBALS['egw']->db->f('itemgroupoid')] = json_decode($GLOBALS['egw']->db->f('fields'));
      $tblLockDB["studyeventrepeatkey_" . (string)$GLOBALS['egw']->db->f('studyeventoid')] = implode(',',json_decode($GLOBALS['egw']->db->f('studyeventrepeatkey')));
      $tblLockDB["formrepeatkey_" . (string)$GLOBALS['egw']->db->f('studyeventoid') . "_" . (string)$GLOBALS['egw']->db->f('formoid')] = implode(',',json_decode($GLOBALS['egw']->db->f('formrepeatkey')));
      $tblLockDB["itemgrouprepeatkey_" . (string)$GLOBALS['egw']->db->f('studyeventoid') . "_" . (string)$GLOBALS['egw']->db->f('formoid') . "_" . (string)$GLOBALS['egw']->db->f('itemgroupoid')] = implode(',',json_decode($GLOBALS['egw']->db->f('itemgrouprepeatkey')));
    }
    return $tblLockDB;
  }
  
  /**
   *Retrieve lock list
   *@param int $id optional : if specified filter the lock list to retrieve only one lock type
   *@return array         
   **/  
  public function getLockDBList($id=""){
    $sql = "SELECT id,name,description,user,creationDate,share,active,
                   (SELECT MAX(lockdate) FROM egw_alix_lockdb_log WHERE egw_alix_lockdb_log.lockid=egw_alix_lockdb.id AND egw_alix_lockdb_log.currentapp='".$this->getCurrentApp(true)."') as lastactivation
            FROM egw_alix_lockdb
            WHERE egw_alix_lockdb.currentapp='".$this->getCurrentApp(true)."'";    
    if($id!=""){
      $sql .= " AND id='$id'";
    }
            
    $GLOBALS['egw']->db->query($sql);        

    $tblLockDB = array();
    while($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw_info']['user']['apps']['admin'] ||
         $GLOBALS['egw_info']['user']['userid']==$GLOBALS['egw']->db->f('user') ||
         $GLOBALS['egw']->db->f('share')=="Y")
      {
        $tblLockDB[] = array('id' => $GLOBALS['egw']->db->f('id'),
                             'name' => $GLOBALS['egw']->db->f('name'),
                             'description' => $GLOBALS['egw']->db->f('description'), 
                             'user' => $GLOBALS['egw']->db->f('user'),
                             'creationDate' => $GLOBALS['egw']->db->f('creationDate'),
                             'share' => $GLOBALS['egw']->db->f('share'),
                             'active' => $GLOBALS['egw']->db->f('active'),
                             'lastactivation' => $GLOBALS['egw']->db->f('lastactivation'),
                            );
      }                        
    }
    return $tblLockDB;
  
  }
  
  public function getLogLockDB(){
    $sql = "SELECT logid,name,lockfilename,lockpath,lockdate,lockuser,lockid
            FROM egw_alix_lockdb_log LEFT JOIN egw_alix_lockdb
            ON egw_alix_lockdb_log.lockid=egw_alix_lockdb.id
            WHERE egw_alix_lockdb_log.currentapp='".$this->getCurrentApp(true)."'
            ORDER BY lockdate DESC";
            
    $GLOBALS['egw']->db->query($sql);        

    $tblLockDB = array();
    while($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw_info']['user']['apps']['admin'] ||
         $GLOBALS['egw_info']['user']['userid']==$GLOBALS['egw']->db->f('lockuser'))
      {
        $tblLockDB[] = array('logid' => $GLOBALS['egw']->db->f('logid'),
                             'lockid' => $GLOBALS['egw']->db->f('lockid'),
                             'lockname' => $GLOBALS['egw']->db->f('name'),                           
                             'lockfilename' => $GLOBALS['egw']->db->f('lockfilename'), 
                             'lockpath' => $GLOBALS['egw']->db->f('lockpath'),
                             'lockdate' => $GLOBALS['egw']->db->f('lockdate'),
                             'lockuser' => $GLOBALS['egw']->db->f('lockuser'));  
      }
    }
    return $tblLockDB;
  }
  
  public function getLockDBFile($logId,$format='pdf'){    
    
    //Recuperation des informations sur le fichier demandé
    $sql = "SELECT lockfilename,lockpath
            FROM egw_alix_lockdb_log
            WHERE logid='$logId'";
            
    $GLOBALS['egw']->db->query($sql);    
    
    if($GLOBALS['egw']->db->next_record()){
      $filename = $GLOBALS['egw']->db->f('lockfilename') . "." . $format;
      $filepath = $GLOBALS['egw']->db->f('lockpath') . $filename;
    }
         
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    if($format=="csv"){
      header("Content-Type: text/csv");
    }else{
      header("Content-Type: application/pdf");
    }
    header("Content-Transfer-Encoding: binary");
    
    readfile($filepath);
  } 
  
  /**
   * Get list of locked items
   * return array of ItemOID
   * @author TPI   
   * @use bocdiscoo::addItemData
   */
  public function getLockedItems($StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey){
    $tblRet = array();
    
    if($StudyEventOID=="" || $FormOID=="" || $ItemGroupOID==""){
      $this->addLog(__METHOD__."($StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey) Missing parameters.",FATAL);
    }
    
    //request base
    $sql = "SELECT fields
            FROM egw_alix_lockdb_def, egw_alix_lockdb
            WHERE egw_alix_lockdb.active='Y'
                  AND egw_alix_lockdb_def.lockid=egw_alix_lockdb.id
                  AND egw_alix_lockdb.currentapp='".$this->getCurrentApp(true)."'
                  AND studyeventoid='$StudyEventOID'
                  AND (studyeventrepeatkey='[\"\"]' OR studyeventrepeatkey LIKE '%\"$StudyEventRepeatKey\"%')
                  AND formoid='$FormOID'
                  AND (formrepeatkey='[\"\"]' OR formrepeatkey LIKE '%\"$FormRepeatKey\"%')
                  AND itemgroupoid='$ItemGroupOID'
                  AND (itemgrouprepeatkey='[\"\"]' OR itemgrouprepeatkey LIKE '%\"$ItemGroupRepeatKey\"%')";
      
    $GLOBALS['egw']->db->query($sql);
    while($GLOBALS['egw']->db->next_record()){
      $fields = (array)json_decode($GLOBALS['egw']->db->f('fields'));
      $tblRet = array_merge($tblRet, $fields);
    }
    
    return $tblRet;
  }
  
  /**
   * Check if the specified element contains (or is) locked items
   * return boolean
   * @author TPI
   * @use etudemenu::getMenu 
   */        
  public function isLocked($StudyEventOID,$StudyEventRepeatKey="",$FormOID="",$FormRepeatKey="",$ItemGroupOID="",$ItemGroupRepeatKey="",$ItemOID=""){
    
    //request base
    $sql = "SELECT 1
            FROM egw_alix_lockdb_def, egw_alix_lockdb
            WHERE egw_alix_lockdb.active='Y'
                  AND egw_alix_lockdb_def.lockid=egw_alix_lockdb.id
                  AND egw_alix_lockdb.currentapp='".$this->getCurrentApp(true)."'";
    
    //the visit is locked ?
    if($StudyEventOID!=""){
      $sql .= "
              AND studyeventoid='$StudyEventOID'
              AND (studyeventrepeatkey='[\"\"]' OR studyeventrepeatkey LIKE '%\"$StudyEventRepeatKey\"%')";
      
      //the form is locked ?
      if($FormOID!=""){
        $sql .= "
                AND formoid='$FormOID'
                AND (formrepeatkey='[\"\"]' OR formrepeatkey LIKE '%\"$FormRepeatKey\"%')";
      
        //the itemgroup is locked ?
        if($ItemGroupOID!=""){
          $sql .= "
                  AND itemgroupoid='$ItemGroupOID'
                  AND (itemgrouprepeatkey='[\"\"]' OR itemgrouprepeatkey LIKE '%\"$ItemGroupRepeatKey\"%')";
      
          //the item is locked ?
          if($ItemOID!=""){
            $sql .= "
                    AND fields LIKE '%\"$ItemOID\"%'";
          }
        }
      }
      
      $GLOBALS['egw']->db->query($sql);
      if($GLOBALS['egw']->db->next_record()){
        //if we find a record for these condition then the item is locked
        return true;
      }
    }else{
      $this->addLog(__METHOD__." StudyEventOID cannot be empty",FATAL);
    }
    
    return false;
  }
  
  /**
   * Generation of the XML document MetaLocks needed for xQuery requests
   * @author: TPI
   */
  private function generateMetaLocks(){
    
    //Initialize the MetaLocks document
    $doc = new DOMDocument();
    /*
    $root = $doc->createElement("XML");
    $FileOID = $doc->createAttribute('FileOID');
    $FileOID->value = "MetaLocks";
    $root->appendChild($FileOID);
    $doc->appendChild($root);
    */
    $doc->loadXml('<?xml version="1.0" standalone="yes"?><ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" ODMVersion="1.3" FileOID="MetaLocks" FileType="Transactional" Description="ALIX EDC : MetaLocks" CreationDateTime="2012-10-11T18:27:00" Originator="Business &amp; Decision Life Sciences"></ODM>');
    ?><?
    $SubjectData = $doc->createElement("SubjectData");
    $attr = $doc->createAttribute('SubjectKey');
    $attr->value = "*";
    $SubjectData->appendChild($attr);
    
    //get locks list from MySQL
    $sql = "SELECT studyeventoid,studyeventrepeatkey,formoid,formrepeatkey,itemgroupoid,itemgrouprepeatkey,fields
            FROM egw_alix_lockdb_def, egw_alix_lockdb
            WHERE egw_alix_lockdb.active='Y'
                  AND egw_alix_lockdb_def.lockid=egw_alix_lockdb.id
                  AND egw_alix_lockdb.currentapp='".$this->getCurrentApp(true)."'
            ORDER BY studyeventoid, studyeventrepeatkey, formoid, formrepeatkey, itemgroupoid, itemgrouprepeatkey";
    $GLOBALS['egw']->db->query($sql);
    
    //for each record create the corresponding "ODM" nodes with their oid and repeatkey
    while($GLOBALS['egw']->db->next_record()){
      //simplify access to variables (coding convenience)
      $SEOID = $GLOBALS['egw']->db->f('studyeventoid');
      $SERKs = (array)json_decode($GLOBALS['egw']->db->f('studyeventrepeatkey'));
      $FRMOID = $GLOBALS['egw']->db->f('formoid');
      $FRMRKs = (array)json_decode($GLOBALS['egw']->db->f('formrepeatkey'));
      $IGOID = $GLOBALS['egw']->db->f('itemgroupoid');
      $IGRKs = (array)json_decode($GLOBALS['egw']->db->f('itemgrouprepeatkey'));
      $ItemOIDs = (array)json_decode($GLOBALS['egw']->db->f('fields'));
      
      //create studyevent(s)
      foreach($SERKs as $SERK){
        $SE = $doc->createElement("StudyEventData");
        $attr = $doc->createAttribute('StudyEventOID');
        $attr->value = $SEOID;
        $SE->appendChild($attr);
        $attr = $doc->createAttribute('StudyEventRepeatKey');
        $attr->value = ($SERK!=''?$SERK:'0');
        $SE->appendChild($attr);
      
        //create form(s)
        foreach($FRMRKs as $FRMRK){
          $FRM = $doc->createElement("FormData");
          $attr = $doc->createAttribute('FormOID');
          $attr->value = $FRMOID;
          $FRM->appendChild($attr);
          $attr = $doc->createAttribute('FormRepeatKey');
          $attr->value = ($FRMRK!=''?$FRMRK:'0');
          $FRM->appendChild($attr);
          
          //create itemgroup(s)
          foreach($IGRKs as $IGRK){
            $IG = $doc->createElement("ItemGroupData");
            $attr = $doc->createAttribute('ItemGroupOID');
            $attr->value = $IGOID;
            $IG->appendChild($attr);
            $attr = $doc->createAttribute('ItemGroupRepeatKey');
            $attr->value = ($IGRK!=''?$IGRK:'0');
            $IG->appendChild($attr);
            
            //create item(s)
            foreach($ItemOIDs as $ItemOID){
              $Item = $doc->createElement("ItemData");
              $attr = $doc->createAttribute('ItemOID');
              $attr->value = $ItemOID;
              $Item->appendChild($attr);
              
              //set item Locked attribute
              /*
              $attr = $doc->createAttribute('Locked');
              $attr->value = "Y";
              $Item->appendChild($attr);
              */
              
              //append the element to the parent element
              $IG->appendChild($Item);
            }
            
            //append the element to the parent element
            $FRM->appendChild($IG);
          }
          
          //append the element to the parent element
          $SE->appendChild($FRM);
        }
        
        //append the element to the parent element
        $SubjectData->appendChild($SE);
      }
      
    }
    
    //append to document
    $ClinicalData = $doc->createElement("ClinicalData");
    $attr = $doc->createAttribute('StudyOID');
    $attr->value = "*";
    $ClinicalData->appendChild($attr);
    $attr = $doc->createAttribute('MetaDataVersionOID');
    $attr->value = "*";
    $ClinicalData->appendChild($attr);
    $ClinicalData->appendChild($SubjectData);
    $doc->documentElement->appendChild($ClinicalData);
    
    //add or replace the document in the MetaLocks collection
    try{
      $fileOID = $this->m_ctrl->socdiscoo()->addDocument($doc,false,"MetaLocks",false);
    }catch(Exception $e){
      //maybe the document already existing, we will try to replace it
      try{
        $fileOID = $this->m_ctrl->socdiscoo()->replaceDocument($doc,false,"MetaLocks",false);
      }catch(Exception $e){
        $this->addLog(__METHOD__." ".$e->getMessage(), FATAL);
      }
    }
  }           
}