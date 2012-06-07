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
  
class etudemenu extends CommonFunctions
{
  
  function __construct($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }
/*
@param string $SiteId Centre du patient - utiliser pour l'affichage des droits de l'utilisateur connecté
*/	
  public function getMenu($siteId=""){
    
    $user = $this->m_ctrl->boacl()->getUserInfo();
    $profile = $this->m_ctrl->boacl()->getUserProfile("",$siteId);
    
    $jqabStyle = "padding: 10px; border-radius: 15px;"; //the css personal style for info-bubbles
    $jqabStyle = "";
    
    //Inclusion (investigators only)
    $enroll = "";
    if($this->m_ctrl->boacl()->existUserProfileId("INV")){
      $enroll = '<a id="addSubj" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.subjectInterface',
                                                                         'MetaDataVersionOID' => $this->m_tblConfig["METADATAVERSION"],
                                                                         'SubjectKey' => $this->m_tblConfig['BLANK_OID'],
                                                                         'StudyEventOID' => $this->m_tblConfig['ENROL_SEOID'],
                                                                         'StudyEventRepeatKey' => $this->m_tblConfig['ENROL_SERK'],
                                                                         'FormOID' => $this->m_tblConfig['ENROL_FORMOID'],
                                                                         'FormRepeatKey' => $this->m_tblConfig['ENROL_FORMRK'])).'">
                <li class="ui-state-default" style="'.$jqabStyle.'" ><img src="'.$this->getCurrentApp(false).'/templates/default/images/user_add.png" alt="" /><div><p>Enrol Subject</p></div></li></a>';
    }
       
    $toolsButtons = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface')).'"><li class="ui-state-default" id="adminMenu" style="'.$jqabStyle.'" ><img src="'.$GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/notification_warning.png" alt="" /><div><p>Tools</p></div></li></a>';
    
    $dashboard = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface')).'"><li class="ui-state-default" style="'.$jqabStyle.'" ><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/piechart2.png" alt="" /><div><p>Dashboard</p></div></li></a>';
    
    //Queries
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM"))){
      $queries = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.queriesInterface')).'"><li class="ui-state-default" style="'.$jqabStyle.'" ><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/file_notification_warning.png" alt="" /><div><p>Queries</p></div></li></a>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $queries = '<a href="#"><li class="ui-state-default" class="inactiveButton"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_notification_warning.png" alt=""/><div><p>Queries</p></div></li></a>';
    }
    
    //Test mode
    $testmode = "";
    if(!$_SESSION[$this->getCurrentApp(false)]['forcetestmode']){
      if($_SESSION[$this->getCurrentApp(false)]['testmode']){
        $testmode = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface','testmode'=>'false')).'"><li class="ui-state-default" id="testModeMenu" ><img src="'. $this->getCurrentApp(false).'/templates/default/images/application_warning.png" alt="" /><div><p>Exit test mode</p></div></li></a>';
      }else{
        $testmode = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface','testmode'=>'true')) .'"><li class="ui-state-default" id="testModeMenu" ><img src="'. $this->getCurrentApp(false).'/templates/default/images/application_warning.png" alt="" /><div><p>Test Mode</p></div></li></a>';
      }
    }
    
    //Deviations
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM","SPO"))){
      $deviations = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.deviationsInterface')).'"><li class="ui-state-default" style="'.$jqabStyle.'" ><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_warning.png" alt="" /><div><p>Deviations</p></div></li></a>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $deviations = '<a href="#"><li class="ui-state-default" class="inactiveButton"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_warning.png" alt=""/><div><p>Deviations</p></div></li></a>';
    }

    //Audit Trail
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM","SPO"))){
      $auditTrail = '<a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.auditTrailInterface')).'"><li class="ui-state-default" style="'.$jqabStyle.'" ><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_notification_warning.png" alt="" /><div><p>Audit Trail</p></div></li></a>';
    }
  
    $menu = '<div id="mysite" class="divSideboxHeader" align="center"><span>ALIX EDC Demo</span></div>
             <div id="toolbar_ico">         
              <ul>
                '.$enroll.'
                <a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.subjectListInterface')).'"><li class="ui-state-default"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/user_manage.png" alt="" /><div><p>Subjects list</p></div></li></a>
                '.$dashboard.'
                <a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.documentsInterface')).'"><li class="ui-state-default"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/folder.png" alt="" /><div><p>Documents</p></div></li></a>
                '.$testmode.'
                '.$queries.'
                '.$deviations.'
                '.$auditTrail.'
                '.$toolsButtons.'
                <a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.logout')).'"><li class="ui-state-default"><img src="'.$this->getCurrentApp(false).'/templates/default/images/logout2.png" alt="" /><div><p>Logout</p></div></li></a>
              </ul>
            </div>';
    
    return $menu;
	}	
}
