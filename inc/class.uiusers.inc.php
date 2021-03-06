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
    
/**
* UI Class dedicated to users management
* @author WLT
**/ 
class uiusers extends CommonFunctions
{
  /**
  * Class constructor
  * @param array $configEtude array of config values    
  * @param uietude $ctrlRef reference to instanciation object 
  * @author WLT
  **/ 
  function uiusers($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * Get the main interface, called from uietude
  * @return string HTML
  * @author WLT
  **/     
  public function getInterface()
  {
    $this->addLog("uiusers->getInterface()",TRACE);
    $htmlRet = "";
    
    if(!isset($_GET['action'])){
      if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers")){
        $htmlRet = $this->getInterfaceUserList();
      }else{
        $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
      }
    }else{
      if($_GET['action']=='addProfile'){
        if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers")){
          $htmlRet = $this->getInterfaceProfil();
        }else{
          $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
        }
      }
      if($_GET['action']=='addUser'){
        if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers")){
          $htmlRet = $this->getInterfaceProfil();
        }else{
          $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
        }
      }
      if($_GET['action']=='viewUser'){
        if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers") ||
          $_GET['login']==$this->getUserId()){
          $htmlRet = $this->getInterfaceProfil();
        }else{
          $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
        }
      }
    }      			
		return $htmlRet;
  }

  /**
  * Default interface - users list
  * @author wlt
  **/ 
  private function getInterfaceUserList(){
    if(isset($_GET['sort'])){
      $sort = $_GET['sort'];
    }else{
      $sort = "ASC";
    } 

    if(isset($_GET['order'])){
      $order = $_GET['order'];
    }else{
      $order = "account_lid";
    } 

    if(isset($_GET['start'])){
      $start = $_GET['start'];
    }else{
      $start = 0;
    } 
    
    //Get eGroupware Users lists - cf egroupware/admin/inc/class.uiaccounts.inc.php - list_users
		$search_param = array(
			'type' => 'accounts',
			'start' => $start,
			'sort' => $sort,
			'order' => $order,
		);
    $account_info = $GLOBALS['egw']->accounts->search($search_param);
    $total = $GLOBALS['egw']->accounts->total;
    
    if($sort=="ASC"){
      $nextSort = "DESC";
    }else{
      $nextSort = "ASC";
    }
    
    $nextStart = $start + count($account_info);
    $prevStart = $start - count($account_info);
    if($prevStart<0){$prevStart = 0;}
    
    //Build users list
    $htmlUsers = "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
	    <div class='ui-grid-header ui-widget-header ui-corner-top'>Users list</div>
      <table id='tblUsers' class='ui-grid-content ui-widget-content'>
			<thead>
				<tr>
					<th class='ui-state-default'>
            <a href='?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&order=account_lid&sort=$nextSort'><span class='ui-icon ui-icon-triangle-1-s' title='sort ascending'></span> Login</a>
          </th>
					<th class='ui-state-default'>
            <a href='?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&order=account_firstname&sort=$nextSort'><span class='ui-icon ui-icon-triangle-1-s' title='sort ascending'></span> Firstname</a>
          </th>
					<th class='ui-state-default'>
            <a href='?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&order=account_lastname&sort=$nextSort'><span class='ui-icon ui-icon-triangle-1-s' title='sort ascending'></span> Lastname</a>
          </th>
					<th class='ui-state-default'>
            <a href='?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&order=account_email&sort=$nextSort'><span class='ui-icon ui-icon-triangle-1-s' title='sort ascending'></span> e-mail address</a></th>
				</tr>
			</thead>
      <tbody>";
		
    foreach($account_info as $account)
		{
		   $htmlUsers .= "<tr id='".$account['account_lid']."' egwUserId='".$account['account_id']."'>
              					<td class='ui-widget-content'>".$account['account_lid']."</td>
              					<td class='ui-widget-content'>".$account['account_firstname']."</td>
              					<td class='ui-widget-content'>".$account['account_lastname']."</td>
              					<td class='ui-widget-content'>".$account['account_email']."</td>
              				</tr>";

		}
		$htmlUsers .= "<tbody></table>
                		<div class='ui-grid-footer ui-widget-header ui-corner-bottom ui-helper-clearfix'>
                			<div class='ui-grid-paging ui-helper-clearfix'>
                				<a href='?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&start=$prevStart' class='ui-grid-paging-prev ui-state-default ui-corner-left'>
                          <span class='ui-icon ui-icon-triangle-1-w' title='previous set of results'></span></a>
                				<a href='?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&start=$nextStart' class='ui-grid-paging-next ui-state-default ui-corner-right'>
                          <span class='ui-icon ui-icon-triangle-1-e' title='next set of results'></span></a>
                 			</div>
                			<div class='ui-grid-results'>Showing results $start-$nextStart / $total</div>
                		</div>
                	</div>";
    
    $htmlUsers .= "<div id='dialog-form' title='Add user'>
                  	<p class='validateTips'>All form fields are required.</p>
              
                  	<form id='addUser' action='index.php?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&action=addUser' method='post'>
                      <fieldset>
                    		<label for='user-login'>Login</label>  
                    		<input id='user-login' name='user-login' type='text' value='' />
                    		<label for='user-firstname'>First name</label>  
                    		<input id='user-firstname' name='user-firstname' type='text' value='' />
                    		<label for='user-lastname'>Last name</label>  
                    		<input id='user-lastname' name='user-lastname' type='text' value='' />
                    		<label for='user-password'>Password</label>  
                    		<input id='user-password' name='user-password' type='password' value='' />
                    		<label for='user-email'>Email</label>  
                    		<input id='user-email' name='user-email' type='email' value='' />
                    		
                        <input type='submit' id='submitButton' style='display: none' />
                      </fieldset>
                  	</form>
                  
                  </div>
                  <button id='create-user' class='ui-state-default ui-corner-all'>Add a user</button>";
    
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.users.js') . "'></SCRIPT>

                $menu

                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Admin</span>
                  </div>
                  <div class='ui-grid-users ui-dialog-content ui-widget-content'>
                    $htmlUsers
                  </div>
                </div>
                
                <script>loadAlixCRFusersJS('".$this->getCurrentApp(false)."');</script>";
                
    return $htmlRet;
  }

  /**
  * for a given user, define sites profiles
  * @author wlt
  **/  
  private function getInterfaceProfil(){
      $message = ""; //a message to display
      
      $userId = "";
      if(isset($_GET["userId"])) $userId = $_GET["userId"];
      $egwUserId = "";
      if(isset($_GET["egwUserId"])) $egwUserId = $_GET["egwUserId"];
      
        //Check if the username in alix is still the same as the LoginID in egroupware
      if(isset($_GET["action"]) && $_GET["action"]=="viewUser" && isset($_GET["egwUserId"])){
        //let's check if the username in alix is still the same as the LoginID in egroupware
        $userIds = $this->m_ctrl->bousers()->checkUserId($egwUserId, $userId);
        if(!$userIds){
          $this->addLog("An error occured while checking username consistency between Alix and eGroupware for user '$alixUserId'.", ERROR);
        }else{
          if($userIds[0]!=$userIds[1]){
            $message = "The login of user '". $userIds[0] ."' has been changed to '". $userIds[1] ."' to keep his Alix account consistent with his eGroupware account.";
            $userId = $userIds[1];
          }
        }
      }
 
      //Request for creation of a profile
      if(isset($_GET['action']) && $_GET['action']=='addProfile'){
        $bDefault = false;
        if(isset($_POST['default']) && $_POST['default']=="Y") $bDefault = true;
        $this->m_ctrl->boacl()->addProfile($_POST['egwUserId'],$_POST['userId'],$_POST['siteId'],$_POST['profileId'],$bDefault);
        $egwUserId = $_POST['egwUserId'];
        $userId = $_POST['userId'];
      } 
 
      //Request for creation of a user
      if(isset($_GET['action']) && $_GET['action']=='addUser'){
        $this->m_ctrl->bousers()->addUser($_POST['user-login'],$_POST['user-password'],$_POST['user-firstname'],$_POST['user-lastname'],$_POST['user-email']);
        $userId = $_POST['user-login'];
      } 
                
      $tblSite = $this->m_ctrl->bosites()->getSites();
      $selSite = "<select name='siteId'><option value=''>";
      foreach($tblSite as $site){
        $selSite .= "<option value='".$site['siteId']."'>".$site['siteId']."-".$site['siteName']."</option>";
      }
      $selSite .= "</select>";
    
      //Build of profiles-site lists
      $htmlUser = "
      <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
		    <div class='ui-grid-header ui-widget-header ui-corner-top'>User <span style='color: #dd0000;'>$userId</span> profiles list</div>
        <div class='action_message'>$message</div>
		    <div style='text-align: left;'><a href='index.php?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&title=users'>&lt;&lt; back to user list</a></div>
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
			
			$tblProfile = $this->m_ctrl->boacl()->getUserProfiles($userId);
      foreach($tblProfile as $profile)
			{
			   $htmlUser .= "<tr>
                					<td class='ui-widget-content' name='siteId'>".$profile['siteId']."</td>
                					<td class='ui-widget-content'>".$profile['siteName']."</td>
                					<td class='ui-widget-content' name='profileId'>".$profile['profileId']."</td>
                					<td class='ui-widget-content' name='default'>".$profile['defaultProfile']."</td>
                				</tr>";

			}
			$htmlUser .= "</tbody></table>
                  	</div>";

      $menu = $this->m_ctrl->etudemenu()->getMenu();

      $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.6.2.min.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.16.custom.min.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.users.js') . "'></SCRIPT>
                  
                  $menu
                                   
                  <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                    <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Admin</span>
                    </div>
                    <div class='ui-dialog-content ui-widget-content'>
                      $htmlUser
                    </div>
                  </div>";  
      if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers")){
        $htmlRet .=                  
                  "<div id='dialog-form' title='Add/Edit profile for $userId'>
                  	<p class='validateTips'>All form fields are required.</p>
              
                  	<form id='addProfile' action='index.php?menuaction=".$this->getCurrentApp(false).".uietude.usersInterface&action=addProfile' method='post'>
                    	<input type='hidden' name='egwUserId' value='$egwUserId'/> 
                    	<input type='hidden' name='userId' value='$userId'/> 
                      <fieldset>
                    		<label for='siteId'>Site Identifiant</label>  
                    		$selSite
                    		<label for='siteName'>Profile</label>
                    		<select name='profileId'>
                    		  <option value=''/>
                    		  <option value='CRT'>Technician</option>
                    		  <option value='INV'>Investigator</option>
                    		  <option value='CRA'>CRA</option>
                    		  <option value='DM'>Data Manager</option>
                          <option value='SPO'>Sponsor</option>
                        </select>  
                    		<select name='default'>
                    		  <option value='Y'>Y</option>
                    		  <option value='N' selected='selected'>N</option>
                        </select> 
                      </fieldset>
                  	</form>
                  
                  </div>
                  <button id='create-profile' class='ui-state-default ui-corner-all'>Add new profile to user $userId</button>

                  <script>loadAlixCRFprofilesJS();</script>";
      }
                  	
      return $htmlRet;            	
                        
  }
} 
