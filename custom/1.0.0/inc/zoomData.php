<?php

function getZoomData($uisubject,$SubjectKey,$StudyEventOID,$StudyEventRepeatKey)
{
  $imageDir = $uisubject->m_tblConfig["PATH_TO_AJAXZOOM_PICT"];// . "/dicom";

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
  
  //Turn the array $zoomData into a string that can be passed over query string in one variable
  $zoomData = strtr(base64_encode(addslashes(gzcompress(serialize($zoomData),9))), '+/=', '-_,');
  
  $zoomDatas = array($zoomData,$i-1);
  
  return $zoomDatas;
}