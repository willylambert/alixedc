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
		'dashboardInterface'	=> True,
		'dbadminInterface'	=> True,
		'changePasswordInterface' => True,
		'deviationsInterface' => True,
		'documentsInterface'	=> True,
		'editorInterface' => True,
		'queriesInterface' => True,
    'sitesInterface' => True,
		'startupInterface'	=> True,
		'changePasswordInterface' => True,
		'subjectInterface' => True,
		'subjectListInterface' => True,
		'lockInterface' => True,
		'usersInterface' => True,
		'preferencesInterface' => True,
		'logout' => True
		);

  public function __construct()
  {
    global $configEtude;

    CommonFunctions::__construct($configEtude,null);

    $this->addLog("******************************NEW REQUEST******************************",INFO);
    $this->addLog($_SERVER['HTTP_USER_AGENT'],INFO);
    $this->addLog("uietude->uietude() : user=".$GLOBALS['egw_info']['user']['userid'],TRACE);
        
    $GLOBALS['egw_info']['flags']['app_header'] = $this->m_tblConfig['APP_NAME'];
    
    //Controleur d'instanciation
    $this->m_ctrl = new instanciation();
    
    //If the user quit a CRF page, we need to update subject info in the subject list
    if(isset($_GET['updateSubjectEntry'])){
      $SubjectKey = $_GET['updateSubjectEntry'];
      //Update only if there is no current lock on SubjectList - Otherwise the update will me made later (nightly at worse)
      if($this->m_ctrl->socdiscoo($SubjectKey)->getLockStatus("SubjectsList.dbxml")===false){
        $this->m_ctrl->bosubjects()->updateSubjectsList($SubjectKey);
      }
    }
  }
 
	public function create_header ()
	{
    ob_start();
		$GLOBALS['egw']->common->egw_header();
		parse_navbar();
    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    echo "<link rel='stylesheet' type='text/css' href='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/'.$this->m_tblConfig['APP_CSS'].'/jquery-ui-1.8.16.custom.css')."' />";
    echo "<link rel='stylesheet' type='text/css' href='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/'.$this->m_tblConfig['APP_CSS'].'/app-custom.css?'.$jsVersion)."' />";
    echo "<link rel='stylesheet' type='text/css' href='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/ui.jqgrid.css')."' />";
    echo "<link rel='stylesheet' type='text/css' href='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/ui.jqgridex.css')."' />";
      
		if($_SESSION[$this->getCurrentApp(false)]['testmode']){
      echo "<div style='width:100%;text-align:center;color:white;background-color:red;'><strong><blink>WARNING</blink> : Test mode is activated !</strong>
              <a style='color:white;' href=".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface',
  				                                                                              'testmode' => 'false', 
                                                                                           'title' => urlencode(lang('testmode')))).">Click here to exit test mode</a></div>";
    }
	}
	
	public function create_footer ()
	{	
		$GLOBALS['egw']->common->egw_footer();
		$this->addLog("***********************************************************************",INFO);
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
        global $configEtude;
        $ui = new uieditor($configEtude,$this->m_ctrl);
        
        $this->create_header();
        echo $ui->getInterface();
        $this->create_footer();
   }

   public function deviationsInterface()
   {
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
        require_once('class.uiqueries.inc.php');
        global $configEtude;
        $ui = new uiqueries($configEtude,$this->m_ctrl);
        
        $GLOBALS['egw_info']['flags']['app_header'];
		    $this->create_header();
        echo $ui->getInterface();
		    $this->create_footer();  
   } 

   public function sitesInterface()
   {
        require_once('class.uisites.inc.php');
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
          require_once('class.uiaccueil.inc.php');
          
          $ui = new uiaccueil($configEtude,$this->m_ctrl);
                  
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
