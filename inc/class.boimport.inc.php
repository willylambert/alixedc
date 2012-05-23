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

/**
 * Detect if the xml file is a MetaData or a ClinicalData file
 * @param string $file full path to the file to test 
 * @return string MetaData or ClinicalData
 * @author wlt  
 **/ 
  public function getContainer($file){
    $xml = simplexml_load_file($file);
    if(isset($xml->ClinicalData)){
      $containerName = "ClinicalData";  
    }else{
      if(isset($xml->Study->MetaDataVersion)){
        $containerName = "MetaDataVersion";      
      }else{
        $this->addLog("uidbadmin->getContainer : unrecognized document type for file $file",FATAL);
      }
    }
    return $containerName;   
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