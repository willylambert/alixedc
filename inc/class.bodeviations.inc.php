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
@desc classe de gestion des droits utilisateurs
@author wlt
*/
class bodeviations extends CommonFunctions
{

  var $statuses = array(
      "O" => "Added",
      "U" => "Updated",
      "C" => "Deleted",
    );
    
  //Constructeur
  function bodeviations(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }
  
  
/*
@desc Savoir si une déviation peut être saisie sur un formulaire donné
@return boolean
*/  
  public function formCanHaveDeviation($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey){
    if(isset($this->m_tblConfig['FORM_DEVIATIONS'])){
      foreach($this->m_tblConfig['FORM_DEVIATIONS'] as $keys){
        if($keys['SEOID']==$StudyEventOID && $keys['SERK']==$StudyEventRepeatKey && $keys['FRMOID']==$FormOID && $keys['FRMRK']==$FormRepeatKey){
          return true;
        }
      }
    }
    return false;
  }
  
/*
@desc Envoi une déviation par email
*/  
  private function mailDeviation($DeviationId){
    if(isset($this->m_tblConfig["EMAIL_DEVIATION"]) && $this->m_tblConfig["EMAIL_DEVIATION"]!=""){
      $sql = "SELECT *
              FROM egw_alix_deviations
              WHERE DEVIATIONID='".$DeviationId."'";
      $GLOBALS['egw']->db->query($sql);
      if($GLOBALS['egw']->db->next_record()){
        $statesToAction = array("O" => "added", "U" => "updated", "C" => "deleted");
        $action = $statesToAction[$GLOBALS['egw']->db->f('STATUS')];
        
        $message = "Deviation $action for subject ".$GLOBALS['egw']->db->f('SUBJKEY')." :";
        $message .= "\n\n";
        $message .= "Item : ". $GLOBALS['egw']->db->f('ITEMTITLE');
        $message .= "\n\n";
        $message .= "« ". $GLOBALS['egw']->db->f('DESCRIPTION') ." »";
        $message .= "\n\n";
        $message .= "User : ". $GLOBALS['egw']->db->f('BYWHO');
        $message .= "\n";
        $message .= "Date : ". $GLOBALS['egw']->db->f('UPDATEDT');
        $message = utf8_decode($message);
        $subject = "TROPHOS SMA : Deviation $action, Site ".$GLOBALS['egw']->db->f('SITEID').", Subject ".$GLOBALS['egw']->db->f('SUBJKEY');
        $subject = utf8_decode($subject);
        mail($this->m_tblConfig["EMAIL_DEVIATION"], $subject, $message);
      }
    }
  }


/*
@desc ajoute ou met à jour la deviation $deviation
@param array $deviation : représente une deviation, avec les champs suivants : array("ItemOID" => '',
                                                                             "ItemGroupOID" => '',
                                                                             "ItemGroupRepeatKey" => '',
                                                                             "Description" => '',
                                                                             "Title" => ''
@return false or array(
                        'DEVIATIONID'=>$GLOBALS['egw']->db->f('DEVIATIONID'),
                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                        'DESCRIPTION'=>$GLOBALS['egw']->db->f('DESCRIPTION'),
                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                       );
@author wlt,tpi
*/
  function updateDeviation($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $deviation, $status='O')
  {
    $this->addLog(__METHOD__."($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, {$deviation['ItemGroupOID']}/{$deviation['ItemGroupRepeatKey']}/{$deviation['ItemOID']}, $status)",INFO);
    
    $userId = $this->m_ctrl->boacl()->getUserId(); 
    $siteId = substr($SubjectKey, 0, 2);
    $profileId = $this->m_ctrl->boacl()->getUserProfileId("",$siteId);

    //Y a t il eu des changements ?
    $sql = "SELECT DESCRIPTION,ITEMTITLE
              FROM egw_alix_deviations
              WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                            SUBJKEY='$SubjectKey' AND
                            SEOID='$StudyEventOID' AND
                            SERK='$StudyEventRepeatKey' AND
                            FRMOID='$FormOID' AND
                            FRMRK='$FormRepeatKey' AND
                            IGOID='{$deviation['ItemGroupOID']}' AND
                            IGRK='{$deviation['ItemGroupRepeatKey']}' AND
                            ITEMOID='{$deviation['ItemOID']}' AND
                            ISLAST='Y'";
    $this->addLog(__METHOD__." : sql = ".$sql,TRACE);
    $GLOBALS['egw']->db->query($sql);
    $hasModif = false;
    
    if($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw']->db->f('DESCRIPTION') != $deviation['Description'] ||
        $GLOBALS['egw']->db->f('ITEMTITLE') != $deviation['Title'] ||
        $GLOBALS['egw']->db->f('STATUS') != $status){
          $hasModif = true;
      }
    }else{
      //Pas d'ancien enregistrement, on va insérer la deviation
      $hasModif = true;
    }
    
    if($hasModif){    
      $sql = "UPDATE egw_alix_deviations 
              SET ISLAST='N'
              WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                    SUBJKEY='$SubjectKey' AND
                    SEOID='$StudyEventOID' AND
                    SERK='$StudyEventRepeatKey' AND
                    FRMOID='$FormOID' AND
                    FRMRK='$FormRepeatKey' AND
                    IGOID='".$deviation['ItemGroupOID']."' AND
                    IGRK='".$deviation['ItemGroupRepeatKey']."' AND
                    ITEMOID='".$deviation['ItemOID']."'";
      $this->addLog(__METHOD__." : sql = ".$sql,TRACE);
      $this->addLog(__METHOD__." : hasModif = true => mise à jour de la deviation  {$deviation['ItemGroupOID']}/{$deviation['ItemGroupRepeatKey']}/{$deviation['ItemOID']} ",INFO);
      $GLOBALS['egw']->db->query($sql);
  
      $sql = "INSERT INTO egw_alix_deviations(CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,ITEMOID,
                                            DESCRIPTION,ITEMTITLE,BYWHO,BYWHOGROUP,UPDATEDT,STATUS,
                                            ISLAST)
             VALUES('".$this->getCurrentApp(true)."',
                    '$siteId','$SubjectKey','$StudyEventOID','$StudyEventRepeatKey','$FormOID','$FormRepeatKey','".$deviation['ItemGroupOID']."','".$deviation['ItemGroupRepeatKey']."',
                    '".$deviation['ItemOID']."','".addslashes($deviation['Description'])."','".addslashes($deviation['Title'])."','$userId','$profileId',now(),'".$status."','Y')";
      $this->addLog(__METHOD__." : sql = ".$sql,TRACE);
      $GLOBALS['egw']->db->query($sql);
      
      //send mail
      $DeviationId = $GLOBALS['egw']->db->get_last_insert_id("egw_alix_deviations", "DEVIATIONID");
      $this->mailDeviation($DeviationId);
      
      return array(
                    'DEVIATIONID'=>$DeviationId,
                    'SITEID'=>$siteId,
                    'SUBJKEY'=>$SubjectKey,
                    'SEOID'=>$StudyEventOID,
                    'SERK'=>$StudyEventRepeatKey,
                    'FRMOID'=>$FormOID,
                    'FRMRK'=>$FormRepeatKey,
                    'IGOID'=>$deviation['ItemGroupOID'],
                    'IGRK'=>$deviation['ItemGroupRepeatKey'],
                    'ITEMOID'=>$deviation['ItemOID'],
                    'DESCRIPTION'=>$deviation['Description'],
                    'ITEMTITLE'=>$deviation['Title'],
                    'BYWHO'=>$userId,
                    'BYWHOGROUP'=>$profileId,
                    'UPDATEDT'=>date("Y-m-d H:i:s"),
                    'STATUS'=>$status,
                    'ISLAST'=>'Y'
                   );
    }
    else{
      return false;
    }            
  }

/*
@desc retourne la liste des deviations
@param identifiants de la deviation et paramètes SQL
@return false or array(
                        'DEVIATIONID'=>$GLOBALS['egw']->db->f('DEVIATIONID'),
                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                        'DESCRIPTION'=>$GLOBALS['egw']->db->f('DESCRIPTION'),
                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                        'ISLAST'=>$GLOBALS['egw']->db->f('ISLAST')
                       );
@author wlt,tpi
*/
  function getDeviationsList($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupKey="", $ItemOID="", $status="", $isLast="", $where="", $orderBy="", $limit=""){
    $this->addLog("bodeviations->getDeviationsList($SubjectKey,$StudyEventOID,$StudyEventRepeatKey, $FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupKey,$ItemOID,$status,$isLast,$where,$orderBy,$limit)",INFO);
    $tblDeviations = array();
    //Recuperation de la liste des deviations
    $sql = "SELECT DEVIATIONID, SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,ITEMOID,
                   DESCRIPTION,ITEMTITLE,BYWHO,BYWHOGROUP,UPDATEDT,STATUS
            FROM egw_alix_deviations
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";
    
    //Liste des centres autorisés pour l'utilisateur courant
    $userProfiles = $this->m_ctrl->boacl()->getUserProfiles();
    $sql .= " AND SITEID IN (";
    $iProfile = 0;
    foreach($userProfiles as $userProfile){
      if($iProfile>0) $sql .= ",";
      $sql .= "'". $userProfile['siteId'] ."'";
      $iProfile++;
    }
    $sql .= ")";

    if($where!=""){
      $sql .= " AND ($where)";
    }

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

    if($status!=""){
      $statuses = explode(",", $status);
      $sqlStatus .= "";
      foreach($statuses as $qS){
        if($sqlStatus!="") $sqlStatus .= " OR ";
        $sqlStatus .= "STATUS='$qS'";
      }
      $sql .= " AND (". $sqlStatus .")";
    }

    if($isLast!=""){
      $sql .= " AND ISLAST='$isLast'";
    }
    
    if($orderBy!=""){
      $sql .= " ORDER BY $orderBy";
    }
    
    if($limit!=""){
      $sql .= " LIMIT $limit";
    }
    
    //echo $sql;
    $this->addLog(__METHOD__." : $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblDeviations[] = array(
                            'DEVIATIONID'=>$GLOBALS['egw']->db->f('DEVIATIONID'),
                            'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'DESCRIPTION'=>$GLOBALS['egw']->db->f('DESCRIPTION'),
                            'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                            'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                            'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                            'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                            'STATUS'=>$GLOBALS['egw']->db->f('STATUS'),
                            'ISLAST'=>$GLOBALS['egw']->db->f('ISLAST')
                           );
    }
    return $tblDeviations;
  }

/*
@desc retourne la deviation d'identifiant DEVIATIONID
@param int DEVIATIONID
@return false or array(
                        'DEVIATIONID'=>$GLOBALS['egw']->db->f('DEVIATIONID'),
                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                        'DESCRIPTION'=>$GLOBALS['egw']->db->f('DESCRIPTION'),
                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                        'ISLAST'=>$GLOBALS['egw']->db->f('ISLAST')
                       );
@author wlt,tpi
*/
  function getDeviation($DeviationId){
    $tblRes = false;
    //Recuperation de la deviation
    $sql = "SELECT DEVIATIONID, SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,ITEMOID,
                   DESCRIPTION,ITEMTITLE,STATUS,BYWHO,BYWHOGROUP,UPDATEDT,ISLAST
            FROM egw_alix_deviations
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                  DEVIATIONID='".$DeviationId."'
            LIMIT 1";
    
    $this->addLog(__METHOD__." : $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblRes = array(
                            'DEVIATIONID'=>$GLOBALS['egw']->db->f('DEVIATIONID'),
                            'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'DESCRIPTION'=>$GLOBALS['egw']->db->f('DESCRIPTION'),
                            'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                            'STATUS'=>$GLOBALS['egw']->db->f('STATUS'),
                            'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                            'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                            'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                            'ISLAST'=>$GLOBALS['egw']->db->f('ISLAST')
                           );
    }
    return $tblRes;
  }


/*
@desc retourne un tableau contenant les identifiants de visites et formulaires avec des déviations ouvertes (non supprimées)
@author tpi
*/
  function getDeviationsFormList($SubjectKey=""){
    $this->addLog(__METHOD__."($SubjectKey)",INFO);
    
    $tblRes = array();
    //Recuperation de la liste des déviations
    $sql = "SELECT DISTINCT SUBJKEY,SEOID,SERK,FRMOID,FRMRK
            FROM egw_alix_deviations
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                  ISLAST='Y' AND
                  STATUS!='C'";

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

  //Retourne le nombre de deviations correspondant aux paramètres spécifiés
  function getDeviationsCount($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupKey="", $ItemOID="", $status="", $isLast="", $where=""){
     $tblQueries = array();
    //Recuperation de la liste des déviations
    $sql = "SELECT COUNT(*) as NBRES
            FROM egw_alix_deviations
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";
    
    //Liste des centres autorisés pour l'utilisateur courant
    $userProfiles = $this->m_ctrl->boacl()->getUserProfiles();
    $sql .= " AND SITEID IN (";
    $iProfile = 0;
    foreach($userProfiles as $userProfile){
      if($iProfile>0) $sql .= ",";
      $sql .= "'". $userProfile['siteId'] ."'";
      $iProfile++;
    }
    $sql .= ")";

    if($where!=""){
      $sql .= " AND ($where)";
    }

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

    if($status!=""){
      $statuses = explode(",", $status);
      $sqlStatus .= "";
      foreach($statuses as $qS){
        if($sqlStatus!="") $sqlStatus .= " OR ";
        $sqlStatus .= "STATUS='$qS'";
      }
      $sql .= " AND (". $sqlStatus .")";
    }

    if($isLast!=""){
      $sql .= " AND ISLAST='$isLast'";
    }
    
    $this->addLog(__METHOD__." : $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    $res = false;
    if($GLOBALS['egw']->db->next_record()){
      $res = $GLOBALS['egw']->db->f('NBRES');
    }
    return $res;
  }
  
/*
@desc retourne le libellé de statut de deviation
@author tpi
*/
  public function getStatusLabel($status){
    return $this->statuses[$status];
  }
  
/*
@desc retourne lun tableau de statuts de deviation
@author tpi
*/
  public function getStatuses(){
    return $this->statuses;
  }
  
}
