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
    
    //Any link which involves exiting current subject may conduct to an update of the subject entry in the subjectlist file
    if(isset($_GET['SubjectKey'])){
      $updateSubjectEntryLink = "&updateSubjectEntry=" . $_GET['SubjectKey']; 
    }else{
      $updateSubjectEntryLink = "";
    }
    
    //Seuls les investgateurs peuvent inclure
    $enroll = "";
    if($this->m_ctrl->boacl()->existUserProfileId("INV")){
      $enroll = '<li style="'.$jqabStyle.'" altbox="Create a new subject and start filling in its CRF."><a id="addSubj" href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.subjectInterface',
                                                                         'MetaDataVersionOID' => $this->m_tblConfig["METADATAVERSION"],
                                                                         'SubjectKey' => $this->m_tblConfig['BLANK_OID'],
                                                                         'StudyEventOID' => $this->m_tblConfig['ENROL_SEOID'],
                                                                         'StudyEventRepeatKey' => $this->m_tblConfig['ENROL_SERK'],
                                                                         'FormOID' => $this->m_tblConfig['ENROL_FORMOID'],
                                                                         'FormRepeatKey' => $this->m_tblConfig['ENROL_FORMRK'])).$updateSubjectEntryLink.'"><img src="'.$this->getCurrentApp(false).'/templates/default/images/user_add.png" alt="" />Enrol Subject</a></li>';
    }
       
    $adminButtons = "";
    if($GLOBALS['egw_info']['user']['apps']['admin'])
    {
      $adminButtons .= '
                <li id="adminMenu" style="'.$jqabStyle.'" altbox="Access to the administration board and manage:<ul><li>Users : create users and manage their rights</li><li>Sites : create and manage sites</li><li>ClinicalData : consult the list of subjects\' documents (XML CDISC)</li><li>MetaData : Access to the study design\'s document (XML CDISC)</li><li>Subjects List : a list of the subjects (XML)</li><li>Import Metadata/ClinicalData : import and replace documents (XML CDISC)</li><li>Import Clinical Data</li><li>Data export : export data for statistical purposes</li><li>Editor : edit CDSIC documents and modify specifical scripts (user interface, behavior of pages)</li></ul>"><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface')).$updateSubjectEntryLink.'"><img src="'.$GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/notification_warning.png" alt=""/>Admin</a></li>';
    }
    
    $dashboard = '<li style="'.$jqabStyle.'" altbox="Figures of the study : number of subjects, distribution, etc."><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface')).$updateSubjectEntryLink.'"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/piechart2.png" alt="" />Dashboard</a></li>';
    
    //Module de gestion des queries
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM"))){
      $queries = '<li style="'.$jqabStyle.'" altbox="Consult and manage queries : suggest corrections, close resolved queries, open new ones."><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.queriesInterface')).$updateSubjectEntryLink.'"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/file_notification_warning.png" alt=""/>Queries</a></li>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $queries = '<li class="inactiveButton"><a href="#"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_notification_warning.png" alt=""/>Queries</a></li>';
    }
    
    //Module de gestion des deviations
    if($this->m_ctrl->boacl()->existUserProfileId(array("CRA","DM","SPO"))){
      $deviations = '<li style="'.$jqabStyle.'" altbox="Consult and manage protocol deviations"><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.deviationsInterface')).$updateSubjectEntryLink.'"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_warning.png" alt=""/>Deviations</a></li>';
    }elseif($this->m_ctrl->boacl()->existUserProfileId("SPO")){
      $deviations = '<li class="inactiveButton"><a href="#"><img src="'. $this->getCurrentApp(false).'/templates/default/images/file_warning.png" alt=""/>Deviations</a></li>';
    }
    
    $testmode = $_SESSION[$this->getCurrentApp(false)]['testmode'];
  
    $menu = '<!--
             <SCRIPT LANGUAGE="JavaScript" SRC="' . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.jqAltBox.js') . '"></SCRIPT>
             -->
             <div id="mysite" class="divSideboxHeader" align="center"><span>ALIX EDC Demo</span></div>
             <div id="toolbar_ico">         
              <ul>
                '.$enroll.'
                <li altbox="{'.$jqabStyle.'}List of subjects : select an existing subject and access to its CRF."><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.subjectListInterface')).$updateSubjectEntryLink.'"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/user_manage.png" alt=""/>Subjects list</a></li>
                '.$dashboard.'
                <li altbox="{'.$jqabStyle.'}List of documents you can download (i.e. protocol, etc)"><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.documentsInterface')).$updateSubjectEntryLink.'"><img src="'. $GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/folder.png" alt=""/>Documents</a></li>
                <li id="testModeMenu" altbox="{'.$jqabStyle . '}' . ($testmode?'Exit demo CRF : manage actual subjects.':'Switch to test mode : create virtual subjects and start discovering the CRF.') .'"><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.startupInterface','testmode'=>($testmode?'false':'true'))).$updateSubjectEntryLink.'"><img src="'. $this->getCurrentApp(false).'/templates/default/images/application_warning.png" alt=""/>
                  '. ($testmode?'Exit test mode':'Test Mode') .'
                  </a></li>
                '.$queries.'
                '.$deviations.'
                '.$adminButtons.'
                <li altbox="'.$jqabStyle.'Close your session."><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.logout')).$updateSubjectEntryLink.'"><img src="'.$this->getCurrentApp(false).'/templates/default/images/logout2.png" alt=""/>Logout</a></li>
              </ul>
            </div>';
/*

            <div id="userProfileInfo" style="display:block;">
             <div id="userInfo">
              ['.$user['login'].'] '.$user['fullname'].' Last login : '.date('r',$user['lastlogin']).'
             </div>
             <div id="profileInfo">';
      $menu .= '
              ['.$profile['siteId'].']'. $profile['siteName'].' =&gt; '.$profile['profileId'].'
             ';         
      $menu .= '
              </div>
              <div><a href="'.$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.preferencesInterface')).$updateSubjectEntryLink.'">My preferences</a>
              </div>
            </div>
*/
    return $menu;
	}	
}
