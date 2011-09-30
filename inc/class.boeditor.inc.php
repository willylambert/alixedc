<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2011 Business & Decision Life Sciences                         *
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

define("ODM_NAMESPACE","http://www.cdisc.org/ns/odm/v1.3");

/*
@desc edition management class
@author TPI
*/
class boeditor extends CommonFunctions
{

  //Constructor
  function boeditor($tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }

/*
@desc return the content of a file for the editor
@param string filename
@return string text
@author TPI
*/
  public function getFileContent($file){
    $content = "";
    
    $filename = dirname(__FILE__) ."/". substr($file, 6);
    $content = file_get_contents($filename);
    
    return $content;   
  }

/*
@desc return the content of an xml file for the editor
@param string containerName
@param string fileOID
@return string text
@author TPI
*/
  public function getDbxmlFileContent($containerName, $fileOID){
    $content = "";
    
    if($containerName=="ClinicalData"){
      $document = $this->m_ctrl->socdiscoo($fileOID)->getDocument("$fileOID.dbxml",$fileOID,false);
      $document->formatOutput = true;
      $document->normalizeDocument();
      $content = $document->saveXML();
    }elseif($containerName=="MetaDataVersion"){
      $document = $this->m_ctrl->socdiscoo()->getDocument("$containerName.dbxml",$fileOID,false);
      $document->formatOutput = true;
      $document->normalizeDocument();
      $content = $document->saveXML();
    }else{
      $content = "Error file not found : unknown container '$containerName'";
    }
    
    return $content;   
  }

/*
@desc create an html FULL list of folders which can be selected
@param string root
@return string res
@author TPI
*/
  public function getSelectableFolderTree($root){
    $res = "";
    
    $dir = dirname(__FILE__) ."/". substr($root, 6);
    
    $res = $this->getSelectableFolderTreeEx($dir,0);
    
    return $res;   
  }

/*
@desc create an html list of folders which can be selected
@param string dir
@param integer niv
@return string res
@author TPI
*/
  private function getSelectableFolderTreeEx($dir, $niv, $root=""){
    $res = "";
    
    $files = scandir($dir);
    natcasesort($files);
    if( count($files) > 2 ) { /* The 2 accounts for . and .. */
    	// All dirs
    	foreach( $files as $file ) {
    		if( file_exists($dir . $file) && $file != '.' && $file != '..' && is_dir($dir . $file) ) {
    		  $margin = 20*$niv;
    		  //$value = htmlentities($file);
    		  $value = $root . $file;
    			$res .= "<div style='margin-left: ". $margin ."px'><input name='editor_new_selectedFolder' type='checkbox' value='". $value ."' />". htmlentities($file) ."</div>";
          $res .= $this->getSelectableFolderTreeEx($dir . $file ."/", $niv+1, $value."/");
    		}
    	}
    }
    
    return $res;   
  }

/*
@desc save the content of a file
@param string filename
@param string file content
@return string res
@author TPI
*/
  public function setFileContent($file, $content){
    $res = "";
    
    $filename = dirname(__FILE__) ."/". substr($file, 6);
    $h = fopen($filename, "w");
    if($h){
      fwrite($h, $content);
      
      $res = $file;
      
    }else{
      $res = "Error, the file could not be edited for writing.";
    }
    fclose($h);
    
    return $res;   
  }

/*
@desc save the content of a dbxml file
@param string containerName
@param string filename
@param string file content
@return string res
@author TPI
*/
  public function setDbxmlFileContent($container, $fileOID, $content){
    $res = "";
    
    if($container=="ClinicalData"){
      $containerName = "";
    }elseif($container!=""){
      $containerName = $container .".dbxml";
    }
    
    try{
      //let's check if the FileOID has not been modified. If so we have to change the filename
      $document = new SimpleXMLElement($content);
      if($fileOID != $document['FileOID']){
        $newFileOID = $document['FileOID'];
        $this->renameDbxmlFile($container, $fileOID, $newFileOID);
        $fileOID = $newFileOID;
        $this->setXmlFiles();
      }
      
      $this->m_ctrl->socdiscoo()->replaceDocument($content,true,$containerName);
        
      $res = "../../../custom/dbxml/". $container ."/". $fileOID .".xml";
      
    }catch(Exception $e){
      $res = $e->getMessage();
    }
    
    return $res;   
  }

/*
@desc create a file
@param string root
@param string folder
@param string filename
@return string res = filename on success
@author TPI
*/
  public function createFile($root,$folder,$filename){
    $res = false;
    
    $file = dirname(__FILE__) ."/". substr($root, 6) . $folder ."/". $filename;
    $h = fopen($file, 'a');
    if($h){
      fwrite($h, "");
      $res = $root . $folder ."/". $filename;
    }
    fclose($h);
    
    return $res;
  }

/*
@desc create a dbxml file
@param string $container
@param string $fileOID
@return string res = $fileOID on success
@author TPI
*/
  public function createDbxmlFile($container, $fileOID){
    $res = false;
    
    if($container=="ClinicalData"){
      $newClinicalXml = '<?xml version="1.0" encoding="UTF-8"?><ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ODMVersion="1.3" FileOID="'. $fileOID .'" FileType="Transactional" Description="ClinicalData of a patient" CreationDateTime="'. date('Y-m-d\TH:i:s') .'" Originator="http://www.alix-edc.com/"><ClinicalData StudyOID="ALIXSTUDYXXX" MetaDataVersionOID="1.0.0"><SubjectData SubjectKey="'. $fileOID .'" TransactionType="Insert"><Annotation SeqNum="1"><Flag><FlagValue CodeListOID="CL.SSTATUS">EMPTY</FlagValue><FlagType CodeListOID="CL.FLAGTYPE">STATUS</FlagType></Flag></Annotation><!-- Screening Visit --><StudyEventData StudyEventOID="1" StudyEventRepeatKey="0"><FormData FormOID="FORM.ENROL" FormRepeatKey="0"><ItemGroupData ItemGroupOID="ENROL" ItemGroupRepeatKey="0"><Annotation SeqNum="1"><Flag><FlagValue CodeListOID="CL.IGSTATUS">EMPTY</FlagValue><FlagType CodeListOID="CL.FLAGTYPE">STATUS</FlagType></Flag></Annotation><ItemDataString ItemOID="ENROL.STUDYID" AuditRecordID="Audit-00000" TransactionType="Insert">ALIX EDC</ItemDataString></ItemGroupData></FormData></StudyEventData></SubjectData></ClinicalData></ODM>';
      ?><?
      try{
        $this->m_ctrl->socdiscoo($fileOID)->addDocument($newClinicalXml,true);
        $res = "../../../custom/dbxml/". $container ."/". $fileOID .".xml";
        $this->setXmlFiles();
      }catch(Exception $e){
        $res = $e->getMessage();
      }
    }elseif($container=="MetaDataVersion"){
      $newMetaDataXml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?><ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" ODMVersion="1.3" FileOID="'. $fileOID .'" FileType="Transactional" Description="ALIX EDC Study" CreationDateTime="'. date('Y-m-d\TH:i:s') .'" Originator="http://www.alix-edc.com/"><Study OID="ALIXSTUDYXXX"><!-- Study definition --><GlobalVariables><StudyName>ALIX EDC Study</StudyName><StudyDescription>ALIX EDC Study</StudyDescription><ProtocolName>ALIX XXX</ProtocolName></GlobalVariables><BasicDefinitions></BasicDefinitions><MetaDataVersion OID="'. $fileOID .'" Name="Version 1.0.0"><Protocol><!-- List of StudyEvents in the study --><StudyEventRef StudyEventOID="1" OrderNumber="1" Mandatory="Yes"/></Protocol><!-- Definition of StudyEvents and list of forms linked --><StudyEventDef OID="1" Name="1" Repeating="No" Type="Common"><Description><TranslatedText xml:lang="en">Screening Visit</TranslatedText></Description><FormRef FormOID="FORM.ENROL" OrderNumber="1" Mandatory="Yes"/></StudyEventDef><!-- Definition of Forms and ItemGroups linked --><FormDef OID="FORM.ENROL" Name="ENROL" Repeating="No"><Description><TranslatedText xml:lang="en">Enrolment</TranslatedText></Description><ItemGroupRef ItemGroupOID="ENROL" Mandatory="Yes"/></FormDef><ItemGroupDef OID="ENROL" Name="ENROL" SASDatasetName="ENROL" Repeating="No" IsReferenceData="No"><Description><TranslatedText xml:lang="en">Enrolment</TranslatedText></Description><ItemRef ItemOID="ENROL.COUNTID" OrderNumber="1" Mandatory="Yes" Role="NOAN"/><ItemRef ItemOID="ENROL.SITEID" OrderNumber="2" Mandatory="Yes" Role="NOAN"/><ItemRef ItemOID="ENROL.PATID" OrderNumber="3" Mandatory="Yes" Role="NOAN"/><ItemRef ItemOID="ENROL.SUBJINIT" OrderNumber="4" Mandatory="No" Role="NOAN"/><ItemRef ItemOID="ENROL.SITENAME" OrderNumber="5" Mandatory="Yes" Role="NOAN"/><ItemRef ItemOID="ENROL.SUBJID" OrderNumber="6" Mandatory="Yes" Role="NOAN"/><ItemRef ItemOID="ENROL.STUDYID" OrderNumber="7" Mandatory="Yes" Role="NOAN"/></ItemGroupDef><!-- Definition of Items --><ItemDef OID="ENROL.SUBJINIT" Name="SUBJINIT" SASFieldName="SUBJINIT" DataType="string" Length="2"><Question><TranslatedText xml:lang="en">Patient Initials</TranslatedText></Question></ItemDef><ItemDef OID="ENROL.COUNTID" Name="COUNTID" SASFieldName="COUNTID" DataType="string" Length="3"><Question><TranslatedText xml:lang="en">Country</TranslatedText></Question><CodeListRef CodeListOID="CL.$COUNT"/></ItemDef><ItemDef OID="ENROL.SITEID" Name="SITEID" SASFieldName="SITEID" DataType="string" Length="2"><Question><TranslatedText xml:lang="en">Site number</TranslatedText></Question></ItemDef><ItemDef OID="ENROL.SITENAME" Name="SITENAME" SASFieldName="SITENAME" DataType="string" Length="50"><Question><TranslatedText xml:lang="en">Site name</TranslatedText></Question></ItemDef><ItemDef OID="ENROL.SUBJID" Name="SUBJID" SASFieldName="SUBJID" DataType="string" Length="32"><Question><TranslatedText xml:lang="en">Subject ID</TranslatedText></Question></ItemDef><ItemDef OID="ENROL.STUDYID" Name="STUDYID" SASFieldName="STUDYID" DataType="string" Length="22"><Question><TranslatedText xml:lang="en">Study ID</TranslatedText></Question></ItemDef><ItemDef OID="ENROL.PATID" Name="PATID" SASFieldName="PATID" DataType="string" Length="3"><Question><TranslatedText xml:lang="en">Patient number</TranslatedText></Question></ItemDef></MetaDataVersion></Study></ODM>';
      ?><?  
      try{
        $this->m_ctrl->socdiscoo()->addDocument($newMetaDataXml,true,"$container.dbxml");
        $res = "../../../custom/dbxml/". $container ."/". $fileOID .".xml";
        $this->setXmlFiles();
      }catch(Exception $e){
        $res = $e->getMessage();
      }
    }else{
      $res = "Error file not created : unknown container '$container'";
    }
    
    return $res;
  }

/*
@desc delete a file
@param string filename
@return string res = filename on success
@author TPI
*/
  public function deleteFile($file){
    $res = false;
    
    $filename = dirname(__FILE__) ."/". substr($file, 6);
    if(unlink($filename)){
      $res = $file;
    }
    
    return $res;
  }

/*
@desc delete a dbxml file
@param string $containerName
@param string $fileOID
@return string res = filename on success
@author TPI
*/
  public function deleteDbxmlFile($container, $fileOID){
    $res = false;
    
    if($container == "ClinicalData"){
      $containerName = $fileOID .".dbxml";
    }elseif($container == "MetaDataVersion"){
      $containerName = $container .".dbxml";
    }else{
      throw new Exception("Error file not deleted : unknown container '$container'");
    }
    
    try{
      $this->m_ctrl->socdiscoo()->deleteDocument($containerName,$fileOID);
      $res = "../../../custom/dbxml/". $container ."/". $fileOID .".xml";
      $this->setXmlFiles();
    }catch(Exception $e){
        $res = $e->getMessage();
    }
    
    return $res;
  }

/*
@desc rename a file
@param string filename
@param string new filename
@return string res
@author TPI
*/
  public function renameFile($file, $newName){
    $res = false;
    
    $filename = dirname(__FILE__) ."/". substr($file, 6);
    $newfilename = substr($filename,0,strrpos($filename,'/')+1) . $newName;
    if(rename($filename, $newfilename)){
      $res = $newName;
    }
    
    return $res;   
  }
/*
@desc rename a file
@param string $container
@param string $fileOID
@param string $newFileOID
@return string res
@author TPI
*/
  public function renameDbxmlFile($container, $fileOID, $newFileOID){
    $res = false;
    
    if($container=="ClinicalData"){
      $oldContainerName = $fileOID .".dbxml";
      $newContainerName = $newFileOID .".dbxml";
    }elseif($container=="MetaDataVersion"){
      $oldContainerName = $container .".dbxml";
      $newContainerName = $container .".dbxml";
    }else{
      throw new Exception("Error file not renamed : unknown container '$container'");
    }
    
    try{
      $document = $this->m_ctrl->socdiscoo()->getDocument($oldContainerName,$fileOID);
      $document['FileOID'] = $newFileOID;
      $this->m_ctrl->socdiscoo()->addDocument($document->asXML(), true, $newContainerName);
      $this->m_ctrl->socdiscoo()->deleteDocument($oldContainerName,$fileOID);
      $res = $newFileOID .".xml";
      $this->setXmlFiles();
    }catch(Exception $e){
        $res = $e->getMessage();
    }
    
    return $res;   
  }

/*
@desc store preferences => theme, fontsize
@param string theme
@param string fontsize
@return array preferences
@author TPI
*/
  public function storePreferences($theme="", $fontsize=""){
    $preferences = array();
    
    if(!isset($_SESSION['editor'])){
      $_SESSION['editor'] = array("preferences" =>array("theme" => "", "fontsize" => ""));
    }
    if($theme!=""){
      $_SESSION['editor']["preferences"]["theme"] = $theme;
    }
    if($fontsize!=""){
      $_SESSION['editor']["preferences"]["fontsize"] = $fontsize;
    }
    
    $preferences = $_SESSION['editor']["preferences"];
    
    return $preferences;   
  }

/*
@desc create empty files from dbxml to simulate their existence in a directory => accessible to jqueryFileTree
*/  
  public function setXmlFiles(){
    //base path
    $root = dirname(__FILE__) ."/../custom";
    //creating directories (deleting previous files)
    if(!is_dir($root."/dbxml")){
      mkdir($root."/dbxml");
      chmod($root."/dbxml", 0775);
    }
    if(!is_dir($root."/dbxml/ClinicalData")){
      mkdir($root."/dbxml/ClinicalData");
      chmod($root."/dbxml/ClinicalData", 0775);
    }
    if(!is_dir($root."/dbxml/MetaDataVersion")){
      mkdir($root."/dbxml/MetaDataVersion");
      chmod($root."/dbxml/MetaDataVersion", 0775);
    }
    $files = scandir($root."/dbxml/ClinicalData");
    foreach($files as $file){
      if($file!="." && $file!=".."){
        unlink($root."/dbxml/ClinicalData/".$file);
      }
    }
    $files = scandir($root."/dbxml/MetaDataVersion");
    foreach($files as $file){
      if($file!="." && $file!=".."){
        unlink($root."/dbxml/MetaDataVersion/".$file);
      }
    }
    
    //creating xml files for ClinicalData
    $subjectContainers = $this->m_ctrl->socdiscoo()->getSubjectsContainers();
    $subjectContainers[] = "BLANK.dbxml"; //special file : BLANK.xml
    foreach($subjectContainers as $subjectContainer){
      $filename = $root."/dbxml/ClinicalData/".substr($subjectContainer, 0, -6).".xml";
      $h = fopen($filename, 'w');
      fwrite($h, "This is a virtual file only.");
      fclose($h);
      chmod($filename, 0664);
    }
    
    //creating xml files for MetaDataVersion
    $query = "<docs>
                {
                let \$Col := collection('MetaDataVersion.dbxml')
                for \$doc in \$Col 
                return 
                <doc>{dbxml:metadata('dbxml:name', \$doc)}</doc>
                }
                </docs>
                "; 

    try{
      $docs = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "xQuery error : " . $e->getMessage() . "<br/><br/>" . $query . "</html>";
      die($str);
    }
    foreach($docs[0] as $doc)
    {
      $filename = $root."/dbxml/MetaDataVersion/".$doc.".xml";
      $h = fopen($filename, 'w');
      fwrite($h, "This is a virtual file only.");
      fclose($h);
      chmod($filename, 0664);
    }
  }
  
}