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
    
class uipassword extends CommonFunctions
{
  function uipassword($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
   *Determine rules for suggest password change to the user
   *@return string reason for need to change
   *@author wlt        
   **/    
  public function passwordNeedChange(){
    //Password must be changed ?
    $password = $GLOBALS['egw_info']['user']['passwd'];
    $sReasonForChange = "";
        
    //By default, passwords are 6 low case letters long.
    //In this case, password change is suggested to the user
    if(strlen($password)==6 && ctype_lower($password)){
      $sReasonForChange = "Your password has never been changed";
    }
    
    //If password change is older than tblConfig['PASSWORD']['CHANGE_AFTER'] (see config.inc.php)
    $nbDaysSinceLastChange = (time() - $GLOBALS['egw_info']['user']['account_lastpwd_change'])/86400; 
    if($nbDaysSinceLastChange >= $this->m_tblConfig['PASSWORD']['CHANGE_AFTER']){
      $sReasonForChange = "Your password is older than ". $this->m_tblConfig['PASSWORD']['CHANGE_AFTER'] ." days";    
    }
    
    return $sReasonForChange;
  }
  
  /**
   *@desc interface de modification du mot de passe
   *@return string html à afficher
   *@author wlt        
   */  
  public function getChangeInterface($sReasonForChange="")
  {
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    //Demande de modification soumise
    if(isset($_GET['action']) && $_GET['action']=='changePassword'){
      //Vérification d'usage
			if($GLOBALS['egw']->acl->check('nopasswordchange', 1) || $_POST['cancel']){
				$GLOBALS['egw']->redirect_link('/'.$this->getCurrentApp(false).'/index.php');
				$GLOBALS['egw']->common->egw_exit();
			}else{              
      	$n_passwd   = $_POST['n_passwd'];
			  $n_passwd_2 = $_POST['n_passwd_2'];
			  $o_passwd_2 = $_POST['o_passwd_2'];
			
        $o_passwd = $GLOBALS['egw_info']['user']['passwd'];
  
  			if($o_passwd != $o_passwd_2){
  				$errors[] = lang('The old password is not correct');
  			}
  
  			if($n_passwd != $n_passwd_2){
  				$errors[] = lang('The two passwords are not the same');
  			}
  
  			if(!$n_passwd){
  				$errors[] = lang('You must enter a password');
  			}
  			
        if($GLOBALS['egw_info']['server']['check_save_passwd'] && $error_msg = $GLOBALS['egw']->auth->crackcheck($n_passwd)){
  				$errors[] = $error_msg;
  			}
  			
  			//Password min length
  			if(strlen($n_passwd)<$this->m_tblConfig['PASSWORD']['MIN_LENGTH']){
          $errors[] = lang('The min length of the password is 6 characters');
        }

        //Password needs at least one upper case letter
  			if($this->m_tblConfig['PASSWORD']['UPPER_LOWER_CASE']){
          if(strtolower($n_passwd) == $n_passwd){
    			  $errors[] = lang('The password needs at least one upper case letter');      
          }
          //Password needs at least one lower case letter
    			if(strtoupper($n_passwd) == $n_passwd){
    			  $errors[] = lang('The password needs at least one lower case letter');      
          }
        }
  			
  			if(is_array($errors)==false){ //Pas d'erreur
  			  $bopassword = & CreateObject('preferences.bopassword');
  			  $passwd_changed = $bopassword->changepass($o_passwd, $n_passwd);
  				if($passwd_changed===false)
  				{
  					$errors[] = lang('Failed to change password.  Please contact your administrator.');
    			}else{
            $GLOBALS['egw']->session->appsession('password','phpgwapi',base64_encode($n_passwd));
					  $GLOBALS['egw_info']['user']['passwd'] = $n_passwd;
					  
					  $htmlRet = "<div style='margin: 50px; border: 1px solid #ccc;'>Password successfully updated. <a href='".$GLOBALS['egw']->link('/logout.php')."'>Click here to logout</a></div>";
					  
          }
        }
        if(is_array($errors)){
          $htmlErrors = $GLOBALS['egw']->common->error_list($errors);
    		}
			}
    }
    
    //A message : reason to change the password
    $message = "";
    if($sReasonForChange!=""){
      $message = "<div style='font-weight: bold; color: #740101; margin: 10px; text-align: left;'>". $sReasonForChange .". You should change your password now.</div>";
    }

    if($htmlRet==""){
      $htmlRet = "$menu
                  <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                    <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Change your password</span>
                    </div>
                    $message
                    <div style='color: #505001; margin: 10px; padding: 10px; text-align: left; background-color: #fefef5; border: 1px solid #383801; width: 350px;'>Your password must :<br />- be at least 6 characters long<br />- have at least one upper case letter<br />- have at least one lower case letter</div>
                    <div style='color: #740101;'>$htmlErrors</div>
                    <div class='ui-dialog-content ui-widget-content'>
                      <div id='divFrmPasswd' class='ui-widget'>
                        <form name='formu' action='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.changePasswordInterface','action'=>'changePassword'))."' method='post'>
                          <p>
                            <label for='o_passwd_2' style='display: inline-block; width: 250px;'>Please enter your old password :</label>
                            <input type='password' name='o_passwd_2' size='10'/>
                          </p>
                          <p>
                            <label for='n_passwd' style='display: inline-block; width: 250px;'>Please enter your new password :</label>
                            <input type='password' name='n_passwd' size='10'/>
                          </p>
                          <p>
                            <label for='n_passwd_confirmed' style='display: inline-block; width: 250px;'>Please confirm your new password :</label>
                            <input type='password' name='n_passwd_2' size='10'/>                      
                          </p>
                          <button onClick='document.formu.submit()'>Save</button>
                        </form>
                      </div>
                    </div>
                  </div>";
    }
    return $htmlRet;
  }
  
    
}