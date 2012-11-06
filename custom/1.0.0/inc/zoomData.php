<?php

function getZoomData($uisubject,$SubjectKey,$StudyEventOID,$StudyEventRepeatKey)
{
  require(dirname(__FILE__) . "/../../../lib/nanodicom/nanodicom.php");
  $importPathAnon = $uisubject->m_tblConfig["IMPORT_BASE_PATH"] . "dicom/anonymizedDICOM/";
  
  $imageDir = $uisubject->m_tblConfig["PATH_TO_AJAXZOOM_PICT"];

  $files = array();
  //Populate $files array
  if ($handle = opendir($imageDir)) {
    while( $file = readdir($handle) ) {
      if ($file != "." && $file != "..") {
  			$fileinfo = explode("_",$file);
  			$file_SubjectKey = $fileinfo[0];
  			$file_StudyEventOID = $fileinfo[1];
  			$file_StudyEventRepeatKey = $fileinfo[2];
        if($file_SubjectKey==$SubjectKey && 
           $file_StudyEventOID==$StudyEventOID && 
           $file_StudyEventRepeatKey==$StudyEventRepeatKey){
          $files[$file] = $file;
        }
  		}
  	}
    closedir($handle);
  }
  
  ksort($files);
    
  $zoomData = array();

  $i = 1;
  foreach($files as $file){
    $zoomData[$i]['f'] = $file; // File
    $zoomData[$i]['p'] = "/pic/zoom/dicom/"; // Path
    $i++;
  }

  $dicomFilename = $importPathAnon . substr($zoomData[1]['f'],0,strpos($zoomData[1]['f'],"."));
  $dicom = Nanodicom::factory($dicomFilename, 'simple');
  $dicom->parse(array('PixelSpacing'));
  $pixArr = explode("\\",$dicom->PixelSpacing);
  $pixelSpacingH = $pixArr[0];
  $pixelSpacingV = $pixArr[1]; 

  //Turn the array $zoomData into a string that can be passed over query string in one variable
  $zoomData = strtr(base64_encode(addslashes(gzcompress(serialize($zoomData),9))), '+/=', '-_,');
  
  $zoomDatas = array($zoomData,$i-1,$pixelSpacingH,$pixelSpacingV);
  
  return $zoomDatas;
}