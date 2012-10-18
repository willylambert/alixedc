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
require_once("class.instanciation.inc.php");

require_once(EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/config.inc.php");

class uiimportdicom extends CommonFunctions{
 
  function __construct()
  {	
    global $configEtude;
    CommonFunctions::__construct($configEtude,null);
    
    $this->m_ctrl = new instanciation();
  }
  
  function getInterface(){
    $this->addLog("uiimportdicom->getInterface()",INFO);
    
    if(!$this->m_ctrl->boacl()->checkModuleAccess("ImportDICOM")){
      $this->addLog("Unauthorized Access to Import DICOM Module - Administrator has been notified",FATAL);
    }

    if(!isset($_GET['action'])){                                            
      $htmlRet = $this->getImportDicomInterface();
    }else{
      if($_GET['action']=="importDICOM"){
        $this->runImport();
        $htmlRet = $this->getImportDicomInterface();
      }
    }
    return $htmlRet;       
  }
  
  function getImportDicomInterface(){
    //Get list of DICOM files waiting to be processed
    $tblFiles = $this->m_ctrl->boimportdicom()->getFilesToImport();
    
    //retrieving subjects list
    $tblSubjectKeys = $this->m_ctrl->bosubjects()->getSubjectsList(false);
    $SubjectSelectOptions = "<option value=''>..</option>";
    foreach($tblSubjectKeys as $SubjectKey){
      $SubjectSelectOptions .= "<option value='$SubjectKey'>$SubjectKey</option>";  
    }
     
    //Retrieve list of visits from the BLANK Subject
    $SubjectTblForm = $this->m_ctrl->bocdiscoo()->getSubjectsTblForm("BLANK");
    $xPath = new DOMXPath($SubjectTblForm);
    $xResult = $xPath->query("/SubjectData/StudyEventData");
    $VisitSelectOptions = "<option value=''>..</option>";
    foreach($xResult as $StudyEventData){
      $StudyEventOID = $StudyEventData->getAttribute("StudyEventOID");
      $StudyEventRepeatKey = $StudyEventData->getAttribute("StudyEventRepeatKey");
      $StudyEventTitle = $StudyEventData->getAttribute("Title");
      $optionValue = $StudyEventOID . "_" . $StudyEventRepeatKey; 
      $VisitSelectOptions .= "<option value='$optionValue'>$StudyEventTitle</option>";  
    }
    
    //Build interface for selecting subject and visit
    $htmlSubjs = "<table>
                      <thead>
                          <th>&nbsp;</th>
                          <th>File</th>
                          <th>Patient Name</th>
                          <th>Sex</th>
                          <th>Birth Date</th>
                          <th>Acquisition Date</th>
                          <th>eCRF Subject ID</th>
                          <th>eCRF Visit</th>
                     </thead>";
    foreach($tblFiles as $filename => $infos){
      $htmlSubjs .= "<tr>
                          <td><input type='checkbox' name='{$filename}_chk' /></td>
                          <td>$filename</td>
                          <td>{$infos['PATIENTNAME']}</td>
                          <td>{$infos['PATIENTSEX']}</td>
                          <td>{$infos['PATIENTBIRTHDATE']}</td>
                          <td>{$infos['ACQUISITIONDATE']}</td>
                          <td><select name='{$filename}_subjectKey'>$SubjectSelectOptions</select></td>
                          <td><select name='{$filename}_visitKeys'>$VisitSelectOptions</select></td>
                     </tr>";    
    }
    $htmlSubjs .= "</table>";
    
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    $html = "
      $menu
      <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>  
    
        <div id='DICOMimport' class='ui-grid ui-widget ui-widget-content ui-corner-all'>
  		    <div class='ui-grid-header ui-widget-header ui-corner-top'>Import DICOM File</div>
  		      <div><br/>Please select the DICOM files you want to import, then select Subject and Visit for each file.</div>
            <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.importDicomInterface','action'=>'importDICOM')) . "' method='post'>
              <br/>
              $htmlSubjs
              <input type='submit' value='Import selected files' />  
            </form>          
  		  </div>
      </div>";
          
    return $html;     
  }
  
  function runImport()
  {  
    //Extraction of selected files
    $tblFilesToImport = array();
    foreach($_POST as $key => $value){
      if(substr($key,-3)=="chk"){
        $tblChk = explode("_",$key);
        $filename = $tblChk[0];
        $SubjectKey = $_POST[$filename."_subjectKey"];
        $visitKeys = $_POST[$filename."_visitKeys"];
        $tblVisitKeys = explode("_",$visitKeys);
        $StudyEventOID = $tblVisitKeys[0];
        $StudyEventRepeatKey = $tblVisitKeys[1];
        if($StudyEventOID!="" && $StudyEventRepeatKey!="" && $SubjectKey!=""){
          $tblFilesToImport["$filename"] = array("SUBJECTKEY"=>$SubjectKey,
                                                 "STUDYEVENTOID"=>$StudyEventOID,
                                                 "STUDYEVENTREPEATKEY"=>$StudyEventRepeatKey);  
        }
      }
    }
    
    $tblFilesToDelete = array();    
    foreach($tblFilesToImport as $file => $keys){
      try{
        $this->m_ctrl->boimportdicom()->saveImage($file,$keys['SUBJECTKEY'],$keys['STUDYEVENTOID'],$keys['STUDYEVENTREPEATKEY']);
        //Import OK, (Not Excception raised for this image), store file for deletion
        $tblFilesToDelete[] = $file;
      }
      catch(Exception $e){
        $this->addLog($e->getMessage(),FATAL);  
      }
    }
    
    //Delete successfully imported DICOM files
    foreach($tblFilesToDelete as $file){
      $filename = $this->m_tblConfig["IMPORT_BASE_PATH"] . "dicom/$file";
      unlink($filename);
    }
  }   
}