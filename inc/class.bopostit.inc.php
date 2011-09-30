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

define("ODM_NAMESPACE","http://www.cdisc.org/ns/odm/v1.3");

/*
@desc classe de gestion des post-it
@author tpi
*/
class bopostit extends CommonFunctions
{

  //Constructeur
  function bopostit($tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }

/*
@desc ajoute/enregistre un post-it
@author tpi
*/
  function savePostIt($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID,$txt){
    $this->addLog("bopostit->savePostIt($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID,$txt)",INFO);
    
    $userId = $this->m_ctrl->boacl()->getUserId();
    $siteId = substr($SubjectKey, 0, 2);
    $profileId = $this->m_ctrl->boacl()->getUserProfileId("",$siteId);
    
    $sql = "REPLACE INTO egw_alix_postit
              (
                CURRENTAPP,
                SITEID,
                SUBJKEY,
                SEOID,
                SERK,
                FRMOID,
                FRMRK,
                IGOID,
                IGRK,
                ITEMOID,
                TXT,
                BYWHO,
                BYWHOGROUP,
                DT,
                ISREAD)
            VALUES
              (
                '".$this->getCurrentApp(true)."',
                '".$siteId."',
                '".$SubjectKey."',
                '".$StudyEventOID."',
                '".$StudyEventRepeatKey."',
                '".$FormOID."',
                '".$FormRepeatKey."',
                '".$ItemGroupOID."',
                '".$ItemGroupRepeatKey."',
                '".$ItemOID."',
                '".addslashes($txt)."',
                '".$userId."',
                '".$profileId."',
                now(),
                'N'
              )";                              
                                         
    $GLOBALS['egw']->db->query($sql);   
    
    return true; 
  }

/*
@desc supprime un post-it
@author tpi
*/
  function deletePostIt($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID){
    $this->addLog("bopostit->deletePostIt($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)",INFO);
    
    $sql = "DELETE FROM egw_alix_postit
              WHERE
                CURRENTAPP = '".$this->getCurrentApp(true)."' AND
                SUBJKEY = '".$SubjectKey."' AND
                SEOID = '".$StudyEventOID."' AND
                SERK = '".$StudyEventRepeatKey."' AND
                FRMOID = '".$FormOID."' AND
                FRMRK = '".$FormRepeatKey."' AND
                IGOID = '".$ItemGroupOID."' AND
                IGRK = '".$ItemGroupRepeatKey."' AND
                ITEMOID = '".$ItemOID."'
          ";                              
                                         
    $GLOBALS['egw']->db->query($sql);
    
    return true; 
  }


/*
@desc retourne un tableau de post-it
@author tpi
*/
  function getPostItList($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupKey="", $ItemOID=""){
    $this->addLog("bopostit->getPostItList($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)",INFO);
    
    $tblRes = array();
    //Recuperation de la liste des post-it
    $sql = "SELECT SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,ITEMOID,TXT,ISREAD
            FROM egw_alix_postit
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";

    if($SubjectKey!=""){
      $sql .= " AND SUBJKEY='$SubjectKey'";
    }

    if($StudyEventOID!=""){
      $sql .= " AND SEOID='$StudyEventOID'";
    }

    if($StudyEventRepeatKey!=""){
      $sql .= " AND SERK='$StudyEventRepeatKey'";
    }

    if($FormOID!=""){
      $sql .= " AND FRMOID='$FormOID'";
    }

    if($FormRepeatKey!=""){
      $sql .= " AND FRMRK='$FormRepeatKey'";
    }

    if($ItemGroupOID!=""){
      $sql .= " AND IGOID='$ItemGroupOID'";
    }

    if($ItemGroupKey!=""){
      $sql .= " AND IGRK='$ItemGroupKey'";
    }

    if($ItemOID!=""){
      $sql .= " AND ITEMOID='$ItemOID'";
    }
    
    $this->addLog("err $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblRes[] = array(
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'TXT'=>$GLOBALS['egw']->db->f('TXT'),
                            'ISREAD'=>$GLOBALS['egw']->db->f('ISREAD')
                           );
    }
    return $tblRes;
  }  

/*
@return the number of post-it for specified filters
@return int number of post-it
        false in case of error 
@author <lt
*/
  function getPostItCount($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupKey="", $ItemOID=""){
    $this->addLog("bopostit->getPostItCount($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)",TRACE);
    
    $sql = "SELECT COUNT(POSTITID) as NBPOSTIT
            FROM egw_alix_postit
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";

    if($SubjectKey!=""){
      $sql .= " AND SUBJKEY='$SubjectKey'";
    }

    if($StudyEventOID!=""){
      $sql .= " AND SEOID='$StudyEventOID'";
    }

    if($StudyEventRepeatKey!=""){
      $sql .= " AND SERK='$StudyEventRepeatKey'";
    }

    if($FormOID!=""){
      $sql .= " AND FRMOID='$FormOID'";
    }

    if($FormRepeatKey!=""){
      $sql .= " AND FRMRK='$FormRepeatKey'";
    }

    if($ItemGroupOID!=""){
      $sql .= " AND IGOID='$ItemGroupOID'";
    }

    if($ItemGroupKey!=""){
      $sql .= " AND IGRK='$ItemGroupKey'";
    }

    if($ItemOID!=""){
      $sql .= " AND ITEMOID='$ItemOID'";
    }
        
    $GLOBALS['egw']->db->query($sql);
    
    if($GLOBALS['egw']->db->next_record()){
      $nbPostIt = $GLOBALS['egw']->db->f('NBPOSTIT'); 
    }else{
      $nbPostIt = false;
    }
    
    return $nbPostIt; 
  }  

/*
@desc retourne un tableau contenant les identifiants de visites et formulaires avec des post-its
@author tpi
*/
  function getPostItFormList($SubjectKey=""){
    $this->addLog("bopostit->getPostItFormList($SubjectKey)",INFO);
    
    $tblRes = array();
    //Recuperation de la liste des post-it
    $sql = "SELECT DISTINCT SUBJKEY,SEOID,SERK,FRMOID,FRMRK
            FROM egw_alix_postit
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                  ISREAD='N'";

    if($SubjectKey!=""){
      $sql .= " AND SUBJKEY='$SubjectKey'";
    }
    
    $this->addLog("err $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblRes[] = array(
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK')
                           );
    }
    return $tblRes;
  }
  
}
