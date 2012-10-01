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
  
  $incpath = "../../../inc/";
  $GLOBALS['egw_info']['user']['userid'] = "AEtoPDF";
  define("EGW_SERVER_ROOT","/var/www/alix/docs/demo");
  
  require_once($incpath ."class.CommonFunctions.php");
  require_once($incpath ."class.bocdiscoo.inc.php");
  require_once($incpath ."class.instanciation.inc.php");
  
  require_once(EGW_SERVER_ROOT . "/".$_GET['currentapp']."/config.inc.php");
  
  /*
  @desc this class handles and answers to Ajax queries, received from Javascript calls directly sent by the user's browser.
  */
  class AEtoPDF extends CommonFunctions
  {
    public function __construct()
    {
      global $configEtude;
  
      //Controleur d'instanciation
      $this->m_ctrl = new instanciation();
  
      CommonFunctions::__construct($configEtude,$this->m_ctrl);
      
    }	

    //Adverse Event PDF Generation
    public function generateAEPDF($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey){
      
      //Template
      $template = dirname(__FILE__)."/templates/AE.htm";
      if(!file_exists($template)){
        $str = "Template not found '$template'.";
        $this->addLog($str,ERROR);
      }
      $handle = fopen($template, "r");
      $htmlContent = fread($handle, filesize($template));
      fclose($handle);
      //Values
      $values = $this->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.ENROL","0","ENROL","0");
      $values += $this->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.IC","0","DS","0");    
      $values += $this->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.IC","0","DM","0");    
      $values += $this->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey);
  
      $code = array("{SITENAME}", "{SITEID}", "{INVID}", "{SUBJID}", "{BRTHDT}", "{SEX}", "{AERK}", "{DIAG}", "{STDT}", "{AESEV}", "{AECONTR}", "{AEACN}", "{AEOUT}", "{AEENDTC}", "{AESER}", "{AECOM}");
      $value = array($values['ENROL.SITENAME']
                    ,$values['ENROL.SITEID']
                    ,$values['DS.INVNAM']
                    ,$SubjectKey
                    ,$values['DM.BRTHDTC']
                    ,$values['DM.SEX']
                    ,$FormRepeatKey
                    ,utf8_decode($values['AE.AETERM'])
                    ,$values['AE.AESTDTC']
                    ,$values['AE.AESEV']
                    ,$values['AE.AECONTR']
                    ,$values['AE.AEACN']
                    ,$values['AE.AEOUT']
                    ,$values['AE.AEENDTC']
                    ,$values['AE.AESER']
                    ,$values['AE.AECOM']);
          
      $htmlContent = str_replace($code, $value, $htmlContent);
      
      $filename = $this->m_tblConfig["APP_NAME"].'_Adverse_Event_-_Patient_'. $SubjectKey .'_Site_'.$values['ENROL.SITEID'].'.pdf';
      
      //PDF Generation
      $htmlTemp = tempnam("/tmp","htmlDoc");
      $tmpHandle = fopen($htmlTemp,"w");
      fwrite($tmpHandle,$htmlContent);
      fclose($tmpHandle);
      
      
      # Tell HTMLDOC not to run in CGI mode...
      putenv("HTMLDOC_NOCGI=1");
      //Generation and display to std output
      $cmd = "htmldoc -t pdf --quiet --color --webpage --jpeg  --left 30 --top 20 --bottom 20 --right 20 --footer c.: --fontsize 10 --textfont {helvetica}";
      header("Content-Disposition: attachment; filename=\"$filename\"");
      header("Expires: 0");
      header("Pragma: public"); 
      header("Cache-Control: private, must-revalidate");
      header("Content-Type: application/pdf");
      passthru("$cmd '$htmlTemp'");
      
      unlink($htmlTemp);
    }
  }
  
  //Ask generation for this AE
  $aetopdf = new AEtoPDF();
  $aetopdf->generateAEPDF($_GET['SubjectKey'],"AE","0","FORM.AE",$_GET['FormRepeatKey'],"AE","0");