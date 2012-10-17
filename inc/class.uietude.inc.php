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

/*@desc joue le role de controlleur pour notre application. C'est ici que les sont centralisés les instanciations à la volée des classe uiXXXXX et boXXXX
*/
class uietude extends CommonFunctions
{
	var $public_functions = array(
	  'annotatedCRF' => True,
	  'auditTrailInterface' => True,
		'changePasswordInterface' => True,
		'configInterface' => True,
		'dashboardInterface'	=> True,
		'dbadminInterface'	=> True,
		'deviationsInterface' => True,
		'documentsInterface'	=> True,
		'editorInterface' => True,
		'exportInterface' => True,
		'lockInterface' => True,
		'lockdbInterface' => True,
		'importDicomInterface' => True,
		'logout' => True,
		'preferencesInterface' => True,
		'queriesInterface' => True,
    'sitesInterface' => True,
		'startupInterface'	=> True,
		'subjectInterface' => True,
		'subjectListInterface' => True,
		'subjectPDF' => True,
		'subjectProfile' => True,
		'usersInterface' => True,
		);

  public function __construct()
  {
    global $configEtude;

    CommonFunctions::__construct($configEtude,null);
        
    $GLOBALS['egw_info']['flags']['app_header'] = $this->m_tblConfig['APP_NAME'];
    
    //Controler for instanciation
    $this->m_ctrl = new instanciation();
    
    //Blocking access if maintenance
    if($this->getConfig("maintenance")=="Y"
      && !$GLOBALS['egw_info']['user']['apps']['admin']
      ){
      session_destroy();
      die("<html><div style='text-align: center; margin-top: 150px;'><img src='phpgwapi/templates/idots/images/alix/alix_logo.png'></div><div style='text-align: left; font: 24px calibri bold; width: 450px; margin: 10px auto;'>The site is currently down for maintenance.<div style=' text-align: left; font: 16px calibri; color: #aaa; margin: 10px auto;'>We expect to be back in about an hour.<br />We apologize for the inconvenience and appreciate your patience.</div></div></html>");
    }
  }

	/**
	 *@param boolean $bBuffering false to disable buffering of output allowing post treament of output
	 *                           useful if a script uses flush()     	
	 **/	 
	public function create_header($bBuffering=true)
	{
    if($bBuffering){
      ob_start();
		}
    $GLOBALS['egw']->common->egw_header();
		//parse_navbar();
    
    //Notification : Test Mode
		if($_SESSION[$this->getCurrentApp(false)]['testmode']){
      echo "<div style='width:100%;text-align:center;color:white;background-color:red;'><strong>WARNING : Test mode is activated !</strong>
              <a style='color:white;' href=".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface',
  				                                                                              'testmode' => 'false', 
                                                                                           'title' => urlencode(lang('testmode')))).">Click here to exit test mode</a></div>";
    }
	}
	
	/**
	 *@param boolean $bBuffering false to disable buffering of output allowing post treament of output
	 *                           useful if a script uses flush()     	
	 **/	
	public function create_footer($bBuffering=true)
	{	
		//$GLOBALS['egw']->common->egw_footer();
		echo '<div id="divPoweredBy"><a href="http://www.alix-edc.com/" target="_blank">ALIX EDC '.$GLOBALS['egw']->applications->data[$this->m_tblConfig["MODULE_NAME"]]["version"].'</a> by <a href="http://www.businessdecision-lifesciences.com/" target="_blank">Business &amp; Decision</a></div>';
		if($bBuffering)
		{
  		$htmlRet = ob_get_clean();
  		$htmlRet = str_replace("&","&amp;",$htmlRet);
      
      //die($htmlRet);
          
      $stdDoc = new DOMDocument();
      $stdDoc->loadHTML($htmlRet);
      
      $xsl = new DOMDocument;
      $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/baseBrowser.xsl");    
  
      $user = $this->m_ctrl->boacl()->getUserInfo();
  
      $viewUserProfileLink = $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.usersInterface','action'=>'viewUser','userId'=>$user['login']));
  
      $proc = new XSLTProcessor;     
      $proc->importStyleSheet($xsl);
      $proc->setParameter('',"UserInfo",$user['fullname'].' Last login : '.date('r',$user['lastlogin']));
      $proc->setParameter('',"UserId",$user['login']);
      $proc->setParameter('',"METADATAVERSION",$this->m_tblConfig['METADATAVERSION']);      
      $proc->setParameter('',"CurrentApp",$this->getCurrentApp(false));
      $stdDoc = $proc->transformToDoc($stdDoc);
              
      if($this->isIpad()){
        $xsl = new DOMDocument;
        $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/ipad.xsl");
        
        $proc = new XSLTProcessor;     
        $proc->importStyleSheet($xsl);
        
        $proc->setParameter('',"CurrentApp",$this->getCurrentApp(false));
        if(isset($_GET['SubjectKey'])){
          $proc->setParameter('',"SubjectKey",$_GET['SubjectKey']);
        }
        if(isset($_GET['OnlyLoadForm'])){
          $proc->setParameter('',"OnlyLoadForm",$_GET['OnlyLoadForm']);
        }
        
        $stdDoc = $proc->transformToDoc($stdDoc);
      } 		
      $htmlRet = $stdDoc->saveHTML();
    }          
      
    $htmlRet = str_replace("&amp;","&",$htmlRet);
    //We add the html DOC Type for HTML 5 support - safely ignored by older browser
    echo "<!DOCTYPE html>";
    echo $htmlRet;		
	}

   public function exportInterface()
   {
        require_once('class.uiexport.inc.php');
        global $configEtude;
        $ui = new uiexport($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
		    
		    if(isset($_GET['action']) && $_GET['action']=="run"){
          $bBuffer = false;
        }else{
          $bBuffer = true;
        }
        
        $this->create_header($bBuffer);
        echo $ui->getInterface();
		    $this->create_footer($bBuffer);  
   }

   public function lockdbInterface()
   {
        require_once('class.uilockdb.inc.php');
        global $configEtude;
        $ui = new uilockdb($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
        
        $this->create_header();
        echo $ui->getInterface();
		    $this->create_footer();  
   }

   public function importDicomInterface()
   {
        require_once('class.uiimportdicom.inc.php');
        global $configEtude;
        $ui = new uiimportdicom($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
        
        $this->create_header();
        echo $ui->getInterface();
		    $this->create_footer();  
   }
   
   public function dashboardInterface()
   {
        require_once('class.uidashboard.inc.php');
        global $configEtude;
        $ui = new uidashboard($configEtude,$this->m_ctrl);
        $GLOBALS['egw_info']['flags']['app_header'];
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   } 

   public function dbadminInterface()
   {
        require_once('class.uidbadmin.inc.php');
        
        $ui = new uidbadmin();
        $ui->setCtrl($this->m_ctrl);        
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();  
   }

   public function editorInterface()
   {
        require_once('class.uieditor.inc.php');
        
        //Only accessible to admin and DM
        if(!$GLOBALS['egw_info']['user']['apps']['admin'] &&
           !$this->m_ctrl->boacl()->existUserProfileId(array("DM"))){
          $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
        }

        global $configEtude;
        $ui = new uieditor($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();
   }

   public function deviationsInterface()
   {
        //Only accessible to CRA, DM and SPONSOR
        if(!$this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM","SPO"))){
          $this->addLog("Unauthorized Access to Deviations Module - Administrator has been notified",FATAL);          
        }

        require_once('class.uideviations.inc.php');
        global $configEtude;
        $ui = new uideviations($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
		    $this->create_header();
        echo $ui->getInterface();
		    $this->create_footer();  
   } 
   
   public function documentsInterface()
   {
        require_once('class.uidocuments.inc.php');
        
        global $configEtude;
        $ui = new uidocuments($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }

   public function queriesInterface()
   {
        //Only accessible to CRA and DM
        if(!$this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM"))){
          $this->addLog("Unauthorized Access to Queries Module - Administrator has been notified",FATAL);          
        }
        
        require_once('class.uiqueries.inc.php');
        global $configEtude;
        $ui = new uiqueries($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
		    $this->create_header();
        echo $ui->getInterface();
		    $this->create_footer();  
   } 

   public function auditTrailInterface()
   {
        require_once('class.uiaudittrail.inc.php');
        global $configEtude;
        $ui = new uiaudittrail($configEtude,$this->m_ctrl);
        
        if(isset($_POST['bExport']) && $_POST['bExport']=='true'){
          $bExport = true;  
        }else{
          $bExport = false;
        }
        
        if($bExport==false){
          $GLOBALS['egw_info']['flags']['app_header'];
  		    $this->create_header();
          echo $ui->getInterface($bExport);
  		    $this->create_footer();  
        }else{
          echo $ui->getInterface($bExport);  		    
        }
   } 

   public function sitesInterface()
   {
        require_once('class.uisites.inc.php');
        
        //Only accessible to admin
        if(!$GLOBALS['egw_info']['user']['apps']['admin']){
          $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
        }

        global $configEtude;
        $ui = new uisites($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }

   public function startupInterface () 
   {
        global $configEtude;
        
        //Management of activation and disactivation of the test mode
        $testMode = false;
        //In some cases the user can only use the test mode
        $forceTestMode = false;
    		//These cases are when the user profile is not the same as the site profile
    		$userProfile = $this->m_ctrl->boacl()->getUserProfile();
    		if($userProfile['profileId'] != $this->m_ctrl->bosites()->getSiteProfileId($userProfile['siteId'])){
    		  $forceTestMode = true;
    		  $testmode = true;
    		}else{
    		  //Did the user asked for activation of the test mode ?
          if(isset($_GET['testmode']) && $_GET['testmode']=='true'){
            $testmode = true;
          }
    		}
        $_SESSION[$this->getCurrentApp(false)]['testmode'] = $testmode;
    		$_SESSION[$this->getCurrentApp(false)]['forcetestmode'] = $forceTestMode;
    		
    		
    		//Configuration (first connection to currentapp)
        require_once('class.boconfig.inc.php');
        $boConfig = new boconfig($configEtude,$this->m_ctrl);
        $bConfigurationNeeded = $boConfig->configurationNeeded();
    		if($bConfigurationNeeded){
          $this->configInterface();
    		}else{
    		  //Password (need to change)
          require_once('class.uipassword.inc.php');
          $uiPassword = new uipassword($configEtude,$this->m_ctrl);
          $sReasonForChange = $uiPassword->passwordNeedChange();
          if($sReasonForChange!=""){
            $this->create_header();
            echo $uiPassword->getChangeInterface($sReasonForChange);
            $this->create_footer();
          }else{
            //Access to main apge (dashboard)
            require_once('class.uidashboard.inc.php');
            
            $ui = new uidashboard($configEtude,$this->m_ctrl);
                    
            $this->create_header();
            echo $ui->getInterface();
            $this->create_footer();
          }
        }
   }    
   
   public function changePasswordInterface()
   {
        global $configEtude;
        
        require_once('class.uipassword.inc.php');
        $ui = new uipassword($configEtude,$this->m_ctrl);
        
        //On procède en deux étapes affectation/affichage car getChangeInterface utilise une redirection
        $html = $ui->getChangeInterface();
        $this->create_header();
        echo $html;
        $this->create_footer();
   }
   
   public function subjectInterface()
   {
        require_once('class.uisubject.inc.php');
        global $configEtude;
        $ui = new uisubject($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }
   
   public function subjectPDF()
   {
        require_once('class.uisubject.inc.php');
        global $configEtude;
        $ui = new uisubject($configEtude,$this->m_ctrl);
        
        $content = ob_get_contents();
        ob_end_clean();
        
        $SubjectKey = $_GET['SubjectKey'];
        
        $siteId = $ui->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");
        $subjId = sprintf($this->m_tblConfig["SUBJID_FORMAT"],$SubjectKey);
        $filename = $this->m_tblConfig["APP_NAME"] ."_Site_". $siteId ."_Patient_". $subjId .".pdf";
        
        $pdf = $ui->getPDF($SubjectKey);
        
        if(!$pdf) $this->m_ctrl->addLog($pdf,FATAL);
        
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Pragma: public"); 
        header("Cache-Control: private, must-revalidate");
        header("Content-Type: application/pdf");
        
        echo $pdf;
        
        exit(0);  
   }
   
   public function annotatedCRF()
   {
        require_once('class.uisubject.inc.php');
        global $configEtude;
        $ui = new uisubject($configEtude,$this->m_ctrl);
        
        $content = ob_get_contents();
        ob_end_clean();
        
        $filename = $this->m_tblConfig["APP_NAME"] ."_Annotated_CRF.pdf";
        
        $pdf = $ui->getAnnotatedCRF();
        
        if(!$pdf) $this->m_ctrl->addLog($pdf,FATAL);
        
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Pragma: public"); 
        header("Cache-Control: private, must-revalidate");
        header("Content-Type: application/pdf");
        
        echo $pdf;
        
        exit(0);  
   }
   
   public function subjectProfile()
   {
        require_once('class.uisubject.inc.php');
        global $configEtude;
        $ui = new uisubject($configEtude,$this->m_ctrl);
        
        $content = ob_get_contents();
        ob_end_clean();
        
        $SubjectKey = $_GET['SubjectKey'];
        
        $siteId = $ui->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");
        $subjId = sprintf($this->m_tblConfig["SUBJID_FORMAT"],$SubjectKey);
        $filename = $this->m_tblConfig["APP_NAME"] ."_Site_". $siteId ."_Patient_". $subjId .".pdf";
        
        $pdf = $ui->getProfile($SubjectKey);
        
        if(!$pdf) $this->m_ctrl->addLog($pdf,FATAL);
        
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Pragma: public"); 
        header("Cache-Control: private, must-revalidate");
        header("Content-Type: application/pdf");
        
        echo $pdf;
        
        exit(0);  
   }
     
   public function lockInterface()
   {
        if( !isset($_GET['SubjectKey']) ||
            !isset($_GET['StudyEventOID']) ||
            !isset($_GET['StudyEventRepeatKey']) ||
            !isset($_GET['FormOID']) ||
            !isset($_GET['FormRepeatKey']) ||
            !isset($_GET['FormStatus'])) $this->m_ctrl->addLog(__METHOD__ ." Missing keys in ". print_r($_GET, true),FATAL);
        
        require_once('class.uisubject.inc.php');
        
        $SubjectKey = $_GET['SubjectKey'];
        $StudyEventOID = $_GET['StudyEventOID'];
        $StudyEventRepeatKey = $_GET['StudyEventRepeatKey'];
        $FormOID = $_GET['FormOID'];
        $FormRepeatKey = $_GET['FormRepeatKey'];
        $formStatus = $_GET['FormStatus'];
        if($formStatus=="FILLED"){
          $bLock = true; //freeze asked
        }else{
          $bLock = false; //unfreeze
        }
        
        //On commence par modifier les statuts des itemgroups demandés
        $this->m_ctrl->bocdiscoo()->setLock($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$bLock);
        
        //On retourne ensuite le formulaire demandé
        global $configEtude;
        $ui = new uisubject($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }

   public function subjectListInterface()
   {
        require_once('class.uisubjectlist.inc.php');
        global $configEtude;
        $ui = new uisubjectlist($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }

   public function usersInterface()
   {
        require_once('class.uiusers.inc.php');

        //Only accessible to admin
        if(!$GLOBALS['egw_info']['user']['apps']['admin']){
          $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
        }

        global $configEtude;
        $ui = new uiusers($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }

   public function configInterface()
   {
        require_once('class.uiconfig.inc.php');

        //Only accessible to admin
        if(!$GLOBALS['egw_info']['user']['apps']['admin']){
          $this->addLog("Unauthorized Access to Config Module - Administrator has been notified",FATAL);
        }

        global $configEtude;
        $ui = new uiconfig($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }

   public function preferencesInterface()
   {
        require_once('class.uipreferences.inc.php');
        global $configEtude;
        $ui = new uipreferences($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();   
   }
   
   public function logout()
   {
      header("Location:logout.php");
   }   
}
