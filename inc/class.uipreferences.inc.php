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
    
class uipreferences extends CommonFunctions
{
  function uipreferences($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }
  
  function getInterface()
  {
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    $user = $this->m_ctrl->boacl()->getUserInfo();
    $tblProfile = $this->m_ctrl->boacl()->getUserProfiles($user['login']);
    //Construction de la liste des profils-centre
    $htmlProfiles = "
      <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
  	    <div class='ui-grid-header ui-widget-header ui-corner-top'>Site / profile</div>
        <table id='tblProfiles' class='ui-grid-content ui-widget-content'>
  			<thead>
  				<tr>
  					<th class='ui-state-default'> Site Id</th>
  					<th class='ui-state-default'> Site name</th>
  					<th class='ui-state-default'> Profile Id</th>
  					<th class='ui-state-default'> Default</th>
  				</tr>
  			</thead>
        <tbody>";

      foreach($tblProfile as $profile)
			{
			   $htmlProfiles .= "<tr>
                					<td class='ui-widget-content' name='siteId'>".$profile['siteId']."</td>
                					<td class='ui-widget-content'>".$profile['siteName']."</td>
                					<td class='ui-widget-content' name='profileId'>".$profile['profileId']."</td>
                					<td class='ui-widget-content' name='default'>".$profile['defaultProfile']."</td>
                				</tr>";

			}
			$htmlProfiles .= "</tbody></table>
                  	</div>";
    
    $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.4.2.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.4.custom.min.js') . "'></SCRIPT>
                $menu
                  <div id='mainForm' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                    <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-grid-header ui-widget-header ui-corner-top'>Preferences</span>
                    </div>
                    <p>Welcome {$user['fullname']}. Here is the information of your account.</p>
                    <div id='frmPreferences'>
                      <div><span><strong>Login : </strong></span><span>{$user['login']}</span></div>
                      <div><span><strong>Last login : </strong></span><span>".date('r',$user['lastlogin'])."</span></div>
                      <div><a href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.changePasswordInterface'))."'>Change account password</a></div>
                    </div>
                    $htmlProfiles
                  </div>";
    return $htmlRet;  
  }
}