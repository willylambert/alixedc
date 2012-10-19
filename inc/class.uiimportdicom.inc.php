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
        $errors = $this->runImport();
        $htmlRet = $this->getImportDicomInterface($errors);
      }
    }
    return $htmlRet;       
  }
  
  function getImportDicomInterface($errors=""){
    //Get list of DICOM files waiting to be processed
    $tblFiles = $this->m_ctrl->boimportdicom()->getFilesToImport();
    
    //retrieving subjects list
    $tblSubjectKeys = $this->m_ctrl->bosubjects()->getSubjectsList(false);
    $SubjectSelectOptions = "<select name='selSubject'><option value=''>..</option>";
    foreach($tblSubjectKeys as $SubjectKey){
      $SubjectSelectOptions .= "<option value='$SubjectKey'>$SubjectKey</option>";  
    }
    $SubjectSelectOptions .= "</select>";
     
    //Retrieve list of visits from the BLANK Subject
    $SubjectTblForm = $this->m_ctrl->bocdiscoo()->getSubjectsTblForm("BLANK");
    $xPath = new DOMXPath($SubjectTblForm);
    $xResult = $xPath->query("/SubjectData/StudyEventData");
    $VisitSelectOptions = "<select name='selVisit'><option value=''>..</option>";
    foreach($xResult as $StudyEventData){
      $StudyEventOID = $StudyEventData->getAttribute("StudyEventOID");
      $StudyEventRepeatKey = $StudyEventData->getAttribute("StudyEventRepeatKey");
      $StudyEventTitle = $StudyEventData->getAttribute("Title");
      $optionValue = $StudyEventOID . "_" . $StudyEventRepeatKey; 
      $VisitSelectOptions .= "<option value='$optionValue'>$StudyEventTitle</option>";  
    }
    $VisitSelectOptions .= "</select>";
    
    //Build interface for selecting subject and visit
    $htmlSubjs = "<table>
                      <thead>
                          <th>&nbsp;</th>
                          <th>File</th>
                          <th>Patient Name</th>
                          <th>Sex</th>
                          <th>Birth Date</th>
                          <th>Acquisition Date</th>
                     </thead>";
    foreach($tblFiles as $filename => $infos){
      $htmlSubjs .= "<tr>
                          <td><input type='checkbox' name='{$filename}_chk' /></td>
                          <td>$filename</td>
                          <td><input type='hidden' name='{$filename}_name' value='{$infos['PATIENTNAME']}'/>{$infos['PATIENTNAME']}</td>
                          <td><input type='hidden' name='{$filename}_sex' value='{$infos['PATIENTSEX']}'/>{$infos['PATIENTSEX']}</td>
                          <td><input type='hidden' name='{$filename}_dob' value='{$infos['PATIENTBIRTHDATE']}'/>{$infos['PATIENTBIRTHDATE']}</td>
                          <td><input type='hidden' name='{$filename}_acqdate' value='{$infos['ACQUISITIONDATE']}'/>{$infos['ACQUISITIONDATE']}</td>
                     </tr>";    
    }
    $htmlSubjs .= "</table>";
    
    $menu = $this->m_ctrl->etudemenu()->getMenu();

    //Ouptut import already done
    $tblImport = $this->m_ctrl->boimport()->getImportList("DICOM");
    
    $htmlImportLog = "<br/><br/><table><tr><th>User</th><th>Status</th><th>Errors</th><th>Report File</th></tr>";
    
    foreach($tblImport as $import){
      $htmlImportLog .= "
                   <tr>
                    <td>{$import['USER']}</td>
                    <td>{$import['STATUS']}</td>
                    <td>{$import['ERROR_COUNT']}</td>
                    <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                             'importid' => $import['IMPORTID'],
                                                                             'importFileType' => 'REPORT_FILE',
                                                                             )) ."'>{$import['REPORT_FILE']}</a></td>                   
                   </tr>";
    }
    $htmlImportLog .= "</table>"; 

    if($errors!=""){
      $htmlErrors = "<div class='QueryTypeCM'>$errors</div>";
    }else{
      $htmlErrors = "";
    }

    $html = "
      $menu
      <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>  
        $htmlErrors
        <div id='DICOMimport' class='ui-grid ui-widget ui-widget-content ui-corner-all'>
  		    <div class='ui-grid-header ui-widget-header ui-corner-top'>Import DICOM File</div>
  		      <div class='step'><strong>Step 1 : </strong>Please select the DICOM files to import</div>
            <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.importDicomInterface','action'=>'importDICOM')) . "' method='post'>
              <br/>
              $htmlSubjs
              <div class='step'><strong>Step 2 :</strong> Select targetted Subject and Visit</div>
              <div>Subject : $SubjectSelectOptions Visit : $VisitSelectOptions</div>           
              <div class='step'><strong>Step 3 :</strong> Run import</div>
              <input type='submit' value='Import selected files' />  
            </form>
            $htmlImportLog          
  		  </div>
      </div>";
          
    return $html;     
  }
  
  function runImport()
  {  
    //Check of parameter : we must have a Subject and a visit selected
    if(isset($_POST['selSubject']) && $_POST['selSubject']!="" &&
       isset($_POST['selVisit']) && $_POST['selVisit']!="")
    {
      $SubjectKey = $_POST["selSubject"];
      $visitKeys = $_POST["selVisit"];
      $tblVisitKeys = explode("_",$visitKeys);
      $StudyEventOID = $tblVisitKeys[0];
      $StudyEventRepeatKey = $tblVisitKeys[1];

      ob_end_flush();
      ob_flush(); 
      flush();
      echo "start of processing...<br/>";
      flush();

      //Extraction of selected files
      $tblFilesToImport = array();
      foreach($_POST as $key => $value){
        if(substr($key,-3)=="chk"){
          $tblChk = explode("_",$key);
          $filename = $tblChk[0];
          if($StudyEventOID!="" && $StudyEventRepeatKey!="" && $SubjectKey!=""){
            $tblFilesToImport["$filename"] = array("SUBJECTKEY"=>$SubjectKey,
                                                   "NAME"=>$_POST["{$filename}_name"],
                                                   "SEX"=>$_POST["{$filename}_sex"],
                                                   "DOB"=>$_POST["{$filename}_dob"],
                                                   "ACQDATE"=>$_POST["{$filename}_acqdate"]);
          }
        }
      }
      
      $htmlReport = "<H1>DICOM file import report</H1><br/><br/><br/>
                     <table>
                      <tr><th>Filename</th><th>Patient Name</th><th>Sex</th><th>DOB</th><th>Acquisition date</th><th>Subject ID</th><th>Visit Name</th><th>Status</th></tr>";
      $errorsCount = 0;                         
      $tblFilesToDelete = array();    
      foreach($tblFilesToImport as $file => $keys){
        set_time_limit(90);        
        echo "Processing $file...<br/>";
        flush();
        $htmlReport .= "<tr><td>$file</td>
                            <td>{$keys['NAME']}</td>
                            <td>{$keys['SEX']}</td>
                            <td>{$keys['DOB']}</td>
                            <td>{$keys['ACQDATE']}</td>
                            <td>$SubjectKey</td>
                            <td>$StudyEventOID / $StudyEventRepeatKey</td>";
        try{
          $this->m_ctrl->boimportdicom()->saveImage($file,$keys['SUBJECTKEY'],$StudyEventOID,$StudyEventRepeatKey);
          //Import OK, (Not Exception raised for this image), store file for deletion
          $tblFilesToDelete[] = $file;
          $htmlReport .= "<td>OK</td>"; 
        }
        catch(Exception $e){
          $htmlReport .= "<td>" . $e->getMessage() . "</td>";
          $errorsCount++; 
        }
        $htmlReport .= "</tr>";
      }
      $htmlReport .= "</table>";
      
      //Delete successfully imported DICOM files
      foreach($tblFilesToDelete as $file){
        $filename = $this->m_tblConfig["IMPORT_BASE_PATH"] . "dicom/$file";
        unlink($filename);
      }

      $filename = $this->m_tblConfig['IMPORT_BASE_PATH'] . "dicom_report_".date("Ymd_Hi").".html";
      file_put_contents($filename,$htmlReport);
       
      $sql = "INSERT INTO egw_alix_import(USER,STATUS,ERROR_COUNT,REPORT_FILE,IMPORT_TYPE,DATE_IMPORT,currentapp)
              VALUES('$importUser','".($errorsCount==0?'OK':'KO')."','$errorsCount','".basename($filename)."','DICOM',now(),'".$GLOBALS['egw_info']['flags']['currentapp']."')";
    
      $GLOBALS['egw']->db->query($sql);

    }else{
      $errors = "Subject and/or Visit is not set. Please verify.";
    }
  }   
}