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
require(dirname(__FILE__) . "/../lib/nanodicom/nanodicom.php");

class boimportdicom extends CommonFunctions
{  
  //Constructor
  function __construct(&$tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }               

  /**
  *get file not already processed
  *@return array of files (key=filename) : array('PATIENTNAME'=>'','PATIENTSEX'=>'','PATIENTBIRTHDATE'=>'','ACQUISITIONDATE'=>'')
  *@author wlt        
  **/  
  public function getFilesToImport(){
  
    $importPath = $this->m_tblConfig["IMPORT_BASE_PATH"] . "dicom";
    
    $tblRet = array();
    
    $files = array();
    //Populate $files array
    if ($handle = opendir($importPath)){
      while( $file = readdir($handle) ) {
        if ($file != "." && $file != ".." && !is_dir($importPath."/".$file)){
    			$files[$file] = $file;
    		}
    	}
      closedir($handle);
    }
    ksort($files);
    
    //Extraction of DICOM information
    foreach ($files as $file)
    {
    	$filename = $importPath."/".$file;    
    	try
    	{
    		$dicom = Nanodicom::factory($filename, 'simple');
    		$dicom->parse(array('PatientName','PatientSex','PatientBirthDate','AcquisitionDate','PixelSpacing'));
    		$tblRet["$file"] = array("PATIENTNAME"=>$dicom->PatientName,
                                 "PATIENTSEX"=>$dicom->PatientSex,
                                 "PATIENTBIRTHDATE"=>$dicom->PatientBirthDate,
                                 "ACQUISITIONDATE"=>$dicom->AcquisitionDate,
                                 "PIXELSPACING"=>$dicom->PixelSpacing);
        
        unset($dicom);
    	}
    	catch (Nanodicom_Exception $e)
    	{
    		$tblRet["$file"] = array("ERROR"=>$e->getMessage());
    	}    
    }
    return $tblRet;
  }

  /**
  *Extract image from DICOM file and save it as PNG
  *Saved image are stored in the data directory, dicom_images folder
  *Naming convention $SubjectKey_$StudyEventOID_$StudyEventRepeatKey_$FormOID_$FormRepeatKey_$imageSuffix_UID.png
  *       allow the identification of the image     
  *@author wlt        
  **/  
  public function saveImage($file,$SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID="",$FormRepeatKey="",$imageSuffix=""){
    $this->addLog(__METHOD__ . "($file,$SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$imageSuffix)",TRACE);  
    
    $importPath = $this->m_tblConfig["IMPORT_BASE_PATH"] . "dicom";
    $exportPath = $this->m_tblConfig["PATH_TO_AJAXZOOM_PICT"];
    
    $filename = $importPath . "/" . $file;

    //Checking filename parameter
    if(!file_exists($filename)){
      $this->addLog(__METHOD__ . " File $filename does not exists",FATAL);
    }
    
    //We need at least a StudyEventOID and a StudyEventRepeatKey
    if($StudyEventOID=="" || $StudyEventRepeatKey==""){
      $this->addLog(__METHOD__ . " StudyEventOID and StudyEventRepeatKey must be set",FATAL);
    }

    //Handle of Exception must be done at the upper level, to be added to the import report
    $dicom = Nanodicom::factory($filename, 'pixeler');
    $imageFilename =  $SubjectKey . "_" . $StudyEventOID . "_" . $StudyEventRepeatKey . "_" . $FormOID . "_" . $FormRepeatKey . "_" . $imageSuffix . "_" . $file; 
    
    if(!file_exists($imageFilename.'.0.jpg')){
      $images = $dicom->get_images();

      if ($images !== FALSE){
        foreach ($images as $index => $image){
          $dicom->write_image($image, $exportPath . "/" . $imageFilename.'.'.$index);
        }
      }else{
        throw new Exception("For file $filename there are no DICOM images or transfer syntax not supported yet");
      }
      $images = NULL;

  		$dicom = Nanodicom::factory($filename, 'anonymizer');
  		file_put_contents(dirname($filename) . "/anonymizedDICOM/$imageFilename", $dicom->anonymize());
  		unset($dicom);
    }else{
      throw new Exception("File $imageFilename.0.jpg is already here");
    }
    unset($dicom); 
  } 

 
  public function getReportFile($importId){    
        
    //Recuperation des informations sur le fichier demandé
    $sql = "SELECT REPORT_FILE,importpath
            FROM egw_alix_import
            WHERE IMPORTID='$importId'";
            
    $GLOBALS['egw']->db->query($sql);    
    
    if($GLOBALS['egw']->db->next_record()){
      $filename = $this->m_tblConfig['IMPORT_BASE_PATH'] . "/" . $GLOBALS['egw']->db->f('REPORT_FILE');
    }
    
    readfile($filename);
  } 
}