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
management of sites
@author tpi,wlt
*/
class bosites extends CommonFunctions
{
  //Constructeur
  function __construct(&$tblConfig,$ctrlRef)
  {
      parent::__construct($tblConfig,$ctrlRef);
  }

/**************************************************** Accesseurs Site ****************************************************/

  /*
  @desc return the name of a site
  @param $siteId site id
  @return string siteName or false
  @author wlt
  */ 
  public function getSiteName($siteId){
    $siteName = "";
    
    //Request MySQL for the Site
    $sql = "SELECT SITENAME
            FROM egw_alix_sites
            WHERE CURRENTAPP='".$this->getCurrentApp(false)."'
            AND SITEID='".$siteId."'";
    
    $GLOBALS['egw']->db->query($sql); 
    if($GLOBALS['egw']->db->next_record()){
      $siteName = (string)$GLOBALS['egw']->db->f('SITENAME');
    }else{
      return false;
    }
    
    return $siteName;
  }
  
  /*
  @desc return the profileId of a site
  @param $siteId site id
  @return string profileId or false
  @author tpi
  */ 
  public function getSiteProfileId($siteId){
    $siteProfileId = "";
    
    //Request MySQL for the Site
    $sql = "SELECT SITEPROFILEID
            FROM egw_alix_sites
            WHERE CURRENTAPP='".$this->getCurrentApp(false)."'
            AND SITEID='".$siteId."'";
    
    $GLOBALS['egw']->db->query($sql); 
    if($GLOBALS['egw']->db->next_record()){
      $siteProfileId = (string)$GLOBALS['egw']->db->f('SITEPROFILEID');
    }else{
      return false;
    }
    
    return $siteProfileId;
  }
  
  /*
  @desc retourne la liste de tous les centres (de l'application en cours)
  @return array(siteId,sitename)
  @author wlt
  */ 
  public function getSites(){
    $tblRet = array();
    //Recuperation de la liste des centres
    $sql = "SELECT SITEID,SITENAME,SITEPROFILEID,COUNTRY,CHECKONSAVE
            FROM egw_alix_sites
            WHERE CURRENTAPP='".$this->getCurrentApp(false)."'
            ORDER BY SITEID";
    
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $siteId = (string)$GLOBALS['egw']->db->f('SITEID');
      $tblRet["site$siteId"] = array('siteId'=>$siteId,
                        'siteName'=>$GLOBALS['egw']->db->f('SITENAME'),
                        'siteProfileId'=>$GLOBALS['egw']->db->f('SITEPROFILEID'),
                        'siteCountry'=>$GLOBALS['egw']->db->f('COUNTRY'),
                        'checkOnSave'=>$GLOBALS['egw']->db->f('CHECKONSAVE'));
    }
    return $tblRet;
  }
  

/**************************************************** Modificateurs Site ****************************************************/
  
  public function addSite($siteId,$siteName,$siteProfileId,$siteCountry,$checkOnSave){
    $sql = "INSERT INTO egw_alix_sites(CURRENTAPP,SITEID,SITENAME,SITEPROFILEID,COUNTRY,CHECKONSAVE) 
          VALUES('".$this->getCurrentApp(false)."','$siteId','$siteName','$siteProfileId','$siteCountry','$checkOnSave');";
    $GLOBALS['egw']->db->query($sql); 
  }
}
