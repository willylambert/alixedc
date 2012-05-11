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

/*
management of users rights
@author wlt
*/
class boacl extends CommonFunctions
{
  //Constructeur
  function __construct(&$tblConfig,$ctrlRef)
  {
      parent::__construct($tblConfig,$ctrlRef);
  }

/**************************************************** Accesseurs User/Profile ****************************************************/

/*
@desc teste si l'utilisateur prossède un profile du profile spécifié (INV, CRA, DM, etc) (ou des profiles spécifiés (tableau) : retourne true au premier profile rencontré)
@params le profileId à tester (ou un tableau de profileId, ou une liste de profiles séparés par une virgule), userId et siteId optionnels
@return boolean
@author tpi
*/
  public function existUserProfileId($profileIds, $userId="", $siteId=""){
    $userProfiles = $this->getUserProfiles($userId,$siteId);
    if(!is_array($profileIds)){
      //$profileIds = array($profileIds);
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
  
/*
@desc retourne les infos de l'utilisateur connecté
@return array(login,fullname,lastlogin)
@author wlt
*/
  public function getUserInfo(){
    $tblRet = array();
    $tblRet['login'] = $this->getUserId();
    $tblRet['fullname'] = $GLOBALS['egw']->accounts->data['account_fullname'];
    $tblRet['lastlogin'] = $GLOBALS['egw']->accounts->data['account_lastlogin'];
    return $tblRet;   
  }

/*
@desc retourne le profileId par défaut de l'utlisateur spécifié (utilisateur connecté si non spécifié) dans le site spécifié (profile par défaut si le siteId n'est pas précisé)
@return string profileId (ARC, INV, DM)  or false if no profile found
@author tpi
*/ 
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
  
/*
@desc retourne le profile de l'utlisateur spécifié (utilisateur connecté si non spécifié) dans le site spécifié (profile par défaut si le siteId n'est pas précisé)
@return array(siteId,sitename,siteCountry,profileId,defaultProfile) or false if no profile found
@author tpi
*/ 
  public function getUserProfile($userId="", $siteId=""){
    $bDefault = false;
    if($siteId=="") $bDefault = true;
    $userProfiles = $this->getUserProfiles($userId,$siteId,$bDefault);
    if(count($userProfiles)>0){
      return $userProfiles[0]; //normalement une seule ligne dans le tableau ! soit c'est le profil par défaut (un seul autorisé en base), soit c'est le profile sur le siteId spécifié (un seul autorisé en base)
    }else{
      return false;
    }
  }
  
/*
@desc retourne la liste des profiles de l'utlisateur spécifié (utilisateur connecté si non spécifié)
@return array(array(siteId,sitename,siteCountry,profileId,defaultProfile))
@author wlt
*/ 
  public function getUserProfiles($userId="",$siteId="",$bDefault=false){
    $tblRet = array();
    
    if($userId==""){
      $userId = $this->getUserId();
    }
    
    //Recuperation de la liste des centres de l'utilisateur
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


/**************************************************** Modificateurs Profile ****************************************************/

/*
@desc ajoute un profile utilisateur à la base ACL
@author wlt
*/
  public function addProfile($userId,$siteId,$profileId,$isDefault=false){
    $default = "";
    if($isDefault){
      $default = "Y";
    }
    $sql = "REPLACE INTO egw_alix_acl(CURRENTAPP,SITEID,USERID,PROFILEID,DEFAULTPROFILE) 
          VALUES('".$this->getCurrentApp(false)."','$siteId','$userId','$profileId','$default');";
    $GLOBALS['egw']->db->query($sql); 
  }
  

/**************************************************** Others ****************************************************/

  /**
 *Check if current user has access to the module (or one of the modules listed and separated by a double-pipe)
 *@return boolean true if module is enable for current user
 *@author wlt        
 **/  
  public function checkModuleAccess($moduleName){
    $access = false;
    
    $moduleNames = explode("||", $moduleName);
    foreach($moduleNames as $moduleName){
      switch($moduleName)
      {
        case "importDoc" :
        case "deleteDoc" :
        case "viewDocs" :
        case "EditDocs" :
           if( $GLOBALS['egw_info']['user']['apps']['admin']){
            $access = true; 
           }
           break;    
        case "ManageUsers" :
        case "ManageSites" :
        case "ExportData" :
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
