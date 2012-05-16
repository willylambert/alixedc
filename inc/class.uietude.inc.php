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
require_once("class.instanciation.inc.php");

require_once(EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/config.inc.php");

/*@desc joue le role de controlleur pour notre application. C'est ici que les sont centralisés les instanciations à la volée des classe uiXXXXX et boXXXX
*/
class uietude extends CommonFunctions
{
	var $public_functions = array(
	  'auditTrailInterface' => True,
		'changePasswordInterface' => True,
		'dashboardInterface'	=> True,
		'dbadminInterface'	=> True,
		'deviationsInterface' => True,
		'documentsInterface'	=> True,
		'editorInterface' => True,
		'exportInterface' => True,
		'lockInterface' => True,
		'logout' => True,
		'preferencesInterface' => True,
		'queriesInterface' => True,
    'sitesInterface' => True,
		'startupInterface'	=> True,
		'subjectPDF' => True,
		'subjectInterface' => True,
		'subjectListInterface' => True,
		'usersInterface' => True
		);

  public function __construct()
  {
    global $configEtude;

    CommonFunctions::__construct($configEtude,null);
        
    $GLOBALS['egw_info']['flags']['app_header'] = $this->m_tblConfig['APP_NAME'];
    
    //Controleur d'instanciation
    $this->m_ctrl = new instanciation();
    
    //If the user quit a CRF page, we need to update subject info in the subject list
    if(isset($_GET['updateSubjectEntry'])){
      $SubjectKey = $_GET['updateSubjectEntry'];
      $this->m_ctrl->bosubjects()->updateSubjectsList($SubjectKey);
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
 
		if($_SESSION[$this->getCurrentApp(false)]['testmode']){
      echo "<div style='width:100%;text-align:center;color:white;background-color:red;'><strong><blink>WARNING</blink> : Test mode is activated !</strong>
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
       
        //Only accessible to admin
        if(!$GLOBALS['egw_info']['user']['apps']['admin']){
          $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
        }
        
        $ui = new uidbadmin();
        $ui->setCtrl($this->m_ctrl);        
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();  
   }

   public function editorInterface()
   {
        require_once('class.uieditor.inc.php');
        
        //Only accessible to admin
        if(!$GLOBALS['egw_info']['user']['apps']['admin']){
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
        
        $GLOBALS['egw_info']['flags']['app_header'];
		    $this->create_header();
        echo $ui->getInterface();
		    $this->create_footer();  
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
        
        //On gère ici la demande d'activation / désactivation du mode test
        $testMode = false;
        if(isset($_GET['testmode']) && $_GET['testmode']=='true'){
          $testmode = true;
        }
        $_SESSION[$this->getCurrentApp(false)]['testmode'] = $testmode;
        require_once('class.uipassword.inc.php');
        $uiPassword = new uipassword($configEtude,$this->m_ctrl);
        if($uiPassword->passwordNeedChange()){
          $this->create_header();
          echo $uiPassword->getChangeInterface();
          $this->create_footer();
        }else{ 
          require_once('class.uidashboard.inc.php');
          
          $ui = new uidashboard($configEtude,$this->m_ctrl);
                  
          $this->create_header();
          echo $ui->getInterface();
          $this->create_footer();
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
        $subjId = sprintf($this->config("SUBJID_FORMAT"),$SubjectKey);
        $filename = $this->config("APP_NAME") ."_Site_". $siteId ."_Patient_". $subjId .".pdf";
        
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
     
   public function lockInterface()
   {
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
