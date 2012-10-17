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

/**
* management of users rights
* @author wlt
**/
class boacl extends CommonFunctions
{
  function __construct(&$tblConfig,$ctrlRef)
  {
      parent::__construct($tblConfig,$ctrlRef);
  }

/**************************************************** Accessors User/Profile ****************************************************/

  /**
  * Test if the user has the specified Profile (CRT, INV, CRA, DM, ...) for the specified Site 
  * @param profileIds to test, could be an array of profileId or a comma separated list of profileId
  * @param optional $userId 
  * @param optional $siteId
  * @return boolean
  * @author tpi
  **/
  public function existUserProfileId($profileIds, $userId="", $siteId=""){
    $userProfiles = $this->getUserProfiles($userId,$siteId);
    if(!is_array($profileIds)){
      $profileIds = explode(",",$profileIds);
    }
    foreach($profileIds as $profileId){
      foreach($userProfiles as $userProfile){
        if($userProfile['profileId'] == $profileId){
          return true;
        }
      }
    }
    return false;
  }
  
  /**
  * Retrieve infos on connected user
  * @return array(login,fullname,lastlogin)
  * @author wlt
  **/
  public function getUserInfo(){
    $tblRet = array();
    $tblRet['login'] = $this->getUserId();
    $tblRet['fullname'] = $GLOBALS['egw']->accounts->data['account_fullname'];
    $tblRet['lastlogin'] = $GLOBALS['egw']->accounts->data['account_lastlogin'];
    return $tblRet;   
  }

  /**
  * Get the default profileId for the specified user and siteId
  * @param optional $userId if empty, get default profile of the current user
  * @param optional $siteId if empty, get default profile of the user
  * @return string profileId (CRT, INV, ARC, DM) or false if no profile found
  * @author tpi
  **/ 
  public function getUserProfileId($userId="", $siteId=""){
    $userProfile = $this->getUserProfile($userId,$siteId);
    if($userProfile===false){
      if($this->m_user=="CLI"){
        return "DM";
      }else{
        return false; //No profile found
      }
    }else{
      return $userProfile['profileId'];
    }
  }
  
  /**
  * Get the default profile array for the specified user and siteId
  * @param optional $userId if empty, get default profile of the current user
  * @param optional $siteId if empty, get default profile of the user
  * @return array(siteId,sitename,siteCountry,profileId,defaultProfile) or false if no profile found
  * @author tpi
  **/ 
  public function getUserProfile($userId="", $siteId=""){
    $bDefault = false;
    if($siteId=="") $bDefault = true;
    $userProfiles = $this->getUserProfiles($userId,$siteId,$bDefault);
    if(count($userProfiles)>0){
      return $userProfiles[0]; //We should have only one line in the array !
    }else{
      return false;
    }
  }
  
  /**
  * Get the profiles list of specified user for the specified site
  * @param optional $userId if empty, get default profile of the current user
  * @param optional $siteId if empty, get default profile of the user
  * @param optional $bDefault get only the default profile if true  
  * @return array(array(siteId,sitename,siteCountry,profileId,defaultProfile))
  * @author wlt
  **/ 
  public function getUserProfiles($userId="",$siteId="",$bDefault=false){
    $tblRet = array();
    
    if($userId==""){
      $userId = $this->getUserId();
    }
    
    $sql = "SELECT egw_alix_acl.SITEID,PROFILEID,SITENAME,COUNTRY,CHECKONSAVE,DEFAULTPROFILE
            FROM egw_alix_acl,egw_alix_sites
            WHERE USERID='".$userId."' AND 
                  egw_alix_acl.SITEID=egw_alix_sites.SITEID AND
                  egw_alix_acl.CURRENTAPP=egw_alix_sites.CURRENTAPP AND
                  egw_alix_acl.CURRENTAPP='".$this->getCurrentApp(false)."'";
    
    if($siteId!=""){
      $sql .= " AND egw_alix_acl.SITEID='".$siteId."'";
    }
    
    if($bDefault){
      $sql .= " AND DEFAULTPROFILE='Y'";
    }

    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblRet[] = array('siteId'=>$GLOBALS['egw']->db->f('SITEID'),
                        'siteName'=>$GLOBALS['egw']->db->f('SITENAME'),
                        'siteCountry'=>$GLOBALS['egw']->db->f('COUNTRY'),
                        'checkOnSave'=>$GLOBALS['egw']->db->f('CHECKONSAVE'),
                        'profileId'=>$GLOBALS['egw']->db->f('PROFILEID'),
                        'defaultProfile'=>$GLOBALS['egw']->db->f('DEFAULTPROFILE'),
                       );
    } 
    
    return $tblRet;
  }

  /**
  * Add a profile to the ACL table
  * @author wlt
  **/
  public function addProfile($egwUserId,$userId,$siteId,$profileId,$isDefault=false){
    $default = "";
    if($isDefault){
      $default = "Y";
    }else{
      $default = "N";
    }
    $sql = "REPLACE INTO egw_alix_acl(CURRENTAPP,SITEID,EGWUSERID,USERID,PROFILEID,DEFAULTPROFILE) 
          VALUES('".$this->getCurrentApp(false)."','$siteId','$egwUserId','$userId','$profileId','$default');";
    $GLOBALS['egw']->db->query($sql); 
  }

 /**
 * Check if current user has access to the module (or one of the modules listed and separated by a double-pipe)
 * @return boolean true if module is enable for current user
 * @author wlt        
 **/  
  public function checkModuleAccess($moduleName){
    $access = false;
    
    $moduleNames = explode("||", $moduleName);
    foreach($moduleNames as $moduleName){
      switch($moduleName)
      {
        case "importDoc" : //importing documents such as the BLANK, Subjects and Metadata into the database
        case "deleteDoc" : //deleting documents such as the BLANK, Subjects and Metadata from the database
        case "viewDocs" : //viewing the content of documents such as the BLANK, Subjects and Metadata from the database
        case "ManageUsers" : //adding, modifying and deleting users and profiles
        case "ManageSites" : //adding, modifying and deleting sites
        case "LockDB" : //locking and unlocking database modifications for everyone
        case "Configuration" : //access to Alix configuration : maintenance, etc
           if( $GLOBALS['egw_info']['user']['apps']['admin']){
             $access = true; 
           }
           break;
        case "EditDocs" : //editing and modifying content of the database documents and the custom scripts
           if( $GLOBALS['egw_info']['user']['apps']['admin'] || 
              $this->m_ctrl->boacl()->existUserProfileId(array("DM"))){
             $access = true; 
           }
           break;
        case "ExportData" : //exporting clinical data
           if($GLOBALS['egw_info']['user']['apps']['admin'] || 
              $this->m_ctrl->boacl()->existUserProfileId(array("DM","SPO"))){
             $access = true; 
           }
           break;    
      }
    }
    return $access;
  }
}