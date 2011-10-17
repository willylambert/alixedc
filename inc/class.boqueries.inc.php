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

class boqueries extends CommonFunctions
{
   
  var $statuses = array(
      "O" => "Open",
      "A" => "Value confirmed",
      "P" => "Resolution proposed",
      "R" => "Resolved",
      "C" => "Closed",
    );
  var $types = array(
      "CM" => "Missing or badformat",
      "HC" => "Inconsistency",
      "SC" => "Information",
    );

  //Constructeur
  function boqueries($tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }

/*
@desc ferme la query queryid, gère également l'audit trail
@author wlt
*/
function closeQuery($queryId,$userId,$profileId){
  $this->addLog(__METHOD__."($queryId,$userId,$profileId)",INFO);
  //Cloture automatique de la query
  $sql = "UPDATE egw_alix_queries 
          SET ISLAST='N'
          WHERE QUERYID='$queryId'";
  $GLOBALS['egw']->db->query($sql);
  
  //récupération de la nouvelle valeur enregistrée pour insertion en base de queries
  $value = "?";
  $decodedValue = "?";
  $sql = "SELECT * FROM egw_alix_queries
          WHERE QUERYID='$queryId'";
  $GLOBALS['egw']->db->query($sql);
  if($GLOBALS['egw']->db->next_record()){
    $value = $this->m_ctrl->bocdiscoo()->getValue($GLOBALS['egw']->db->f('SUBJKEY'),$GLOBALS['egw']->db->f('SEOID'),$GLOBALS['egw']->db->f('SERK'),$GLOBALS['egw']->db->f('FRMOID'),$GLOBALS['egw']->db->f('FRMRK'),$GLOBALS['egw']->db->f('IGOID'),$GLOBALS['egw']->db->f('IGRK'),$GLOBALS['egw']->db->f('ITEMOID'));
    $decodedValue = $this->m_ctrl->bocdiscoo()->getDecodedValue($GLOBALS['egw']->db->f('SUBJKEY'),$GLOBALS['egw']->db->f('SEOID'),$GLOBALS['egw']->db->f('SERK'),$GLOBALS['egw']->db->f('FRMOID'),$GLOBALS['egw']->db->f('FRMRK'),$GLOBALS['egw']->db->f('IGOID'),$GLOBALS['egw']->db->f('IGRK'),$GLOBALS['egw']->db->f('ITEMOID'));
  }
  $sql = "INSERT INTO egw_alix_queries(CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                                       LABEL,ITEMTITLE,ISMANUAL,QUERYTYPE,QUERYSTATUS,ANSWER,BYWHO,BYWHOGROUP,UPDATEDT,ISLAST,VALUE,DECODE)
            (
             SELECT CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                    LABEL,ITEMTITLE,ISMANUAL,QUERYTYPE,'C',ANSWER,'$userId','$profileId',now(),'Y','". $value ."','". addslashes($decodedValue) ."'
             FROM egw_alix_queries 
             WHERE QUERYID=$queryId
            )";                              
                                       
  $GLOBALS['egw']->db->query($sql);    
}

/*
@desc retourne la liste des queries
@param identifiants de la query et paramètes SQL
@return false or array(
                        'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                        'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                        'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                        'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                        'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                        'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                        'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'),
                        'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                        'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                        'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                       );
@author wlt,tpi
*/
  function getQueriesList($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupKey="", $ItemOID="", $position="", $queryStatus="", $isLast="", $where="", $orderBy="", $limit=""){
     $tblQueries = array();
    //Recuperation de la liste des queries
    $sql = "SELECT QUERYID, SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                   LABEL,ITEMTITLE,ISMANUAL,BYWHO,BYWHOGROUP,UPDATEDT,
                   QUERYTYPE,QUERYSTATUS,ANSWER,VALUE,DECODE
            FROM egw_alix_queries
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

    if($position!=""){
      $sql .= " AND POSITION='$position'";
    }

    if($queryStatus!=""){
      $queryStatuses = explode(",", $queryStatus);
      $sqlStatus .= "";
      foreach($queryStatuses as $qS){
        if($sqlStatus!="") $sqlStatus .= " OR ";
        $sqlStatus .= "QUERYSTATUS='$qS'";
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
      $tblQueries[] = array(
                            'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                            'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                            'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                            'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                            'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                            'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                            'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                            'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                            'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                            'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'), //champ absent de la base. M : query OPEN de façon manuelle (ARC), A : query OPEN de façon automatique (CRF)
                            'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                            'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                            'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                           );
    }
    return $tblQueries;
  }

/*
@desc retourne la query d'identifiant QUERYID
@param int QUERYID
@return false or array(
                        'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                        'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                        'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                        'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                        'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                        'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                        'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'),
                        'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                        'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                        'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                       );
@author wlt,tpi
*/
  function getQuery($QueryId){
    $tblQuery = false;
    //Recuperation de la query
    $sql = "SELECT QUERYID, SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                   LABEL,ITEMTITLE,ISMANUAL,BYWHO,BYWHOGROUP,UPDATEDT,
                   QUERYTYPE,QUERYSTATUS,ANSWER,VALUE,DECODE
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                  QUERYID='".$QueryId."'
            LIMIT 1";
    
    $this->addLog(__METHOD__." : $sql",TRACE);
    $GLOBALS['egw']->db->query($sql); 
    while($GLOBALS['egw']->db->next_record()){
      $tblQuery = array(
                            'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                            'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                            'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                            'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                            'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                            'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                            'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                            'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                            'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                            'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                            'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                            'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                            'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                            'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                            'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                            'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                            'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                            'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                            'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                            'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'), //champ absent de la base. M : query OPEN de façon manuelle (ARC), A : query OPEN de façon automatique (CRF)
                            'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                            'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                            'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                           );
    }
    return $tblQuery;
  }

  //Retourne le nombre de queries correspondant aux paramètres spécifiés
  function getQueriesCount($SubjectKey="", $StudyEventOID="", $StudyEventRepeatKey="", $FormOID="", $FormRepeatKey="", $ItemGroupOID="", $ItemGroupKey="", $ItemOID="", $position="", $queryStatus="", $isLast="", $where=""){
     $tblQueries = array();
    //Recuperation de la liste des queries
    $sql = "SELECT COUNT(*) as NBQUERIES
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."'";
    
    //Liste des centres autorisés pour l'utilisateur courant
    if($this->m_ctrl->boacl()->existUserProfileId("SPO")==false && $SubjectKey==""){
      $userProfiles = $this->m_ctrl->boacl()->getUserProfiles();
      $sql .= " AND SITEID IN (";
      $iProfile = 0;
      foreach($userProfiles as $userProfile){
        if($iProfile>0) $sql .= ",";
        $sql .= "'". $userProfile['siteId'] ."'";
        $iProfile++;
      }
      $sql .= ")";
    }
    
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

    if($position!=""){
      $sql .= " AND POSITION='$position'";
    }

    if($queryStatus!=""){
      $queryStatuses = explode(",", $queryStatus);
      $sqlStatus = "";
      foreach($queryStatuses as $qS){
        if($sqlStatus!="") $sqlStatus .= " OR ";
        $sqlStatus .= "QUERYSTATUS='$qS'";
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
      $res = $GLOBALS['egw']->db->f('NBQUERIES');
    }
    return $res;
  }
  
/*
@desc retourne le libellé de statut de query
@author tpi
*/
  public function getStatusLabel($status){
    return $this->statuses[$status];
  }
  
/*
@desc retourne le libellé de type de query
@author tpi
*/
  public function getTypeLabel($type){
    return $this->types[$type];
  }
  
/*
@desc retourne lun tableau de statuts de query
@author tpi
*/
  public function getStatuses(){
    return $this->statuses;
  }
  
/*
@desc retourne lun tableau de types de query
@author tpi
*/
  public function getTypes(){
    return $this->types;
  }

  /*
  @param string $queryType : 'M' => Mandatory 'C' => Consistency (Hard ou Soft)
  */
  function updateQueries($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $queryType, $queries)
  {  
    $this->addLog(__METHOD__."($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $queryType, $queries)",INFO);
      
    //HOOK => boqueries_updateQueries_form
    $this->callHook(__FUNCTION__,"form",array($FormOID, $FormRepeatKey, $queryType, &$queries));

    $userId = $this->m_ctrl->boacl()->getUserId(); 
    $siteId = substr($SubjectKey, 0, 2);
    $profileId = $this->m_ctrl->boacl()->getUserProfileId("",$siteId);
    
    if($queryType=="C"){
      $where = "(QUERYTYPE='SC' or QUERYTYPE='HC')";
    }else{
      $where = "QUERYTYPE='CM'";
    }

    //Recuperation des queries non fermées et non manuelles pour le formulaire demandé
    $sql = "SELECT QUERYID,IGOID,IGRK,ITEMOID,POSITION,LABEL,ITEMTITLE,QUERYTYPE,VALUE,DECODE,QUERYSTATUS
            FROM egw_alix_queries
            WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                          SUBJKEY='$SubjectKey' AND
                          SEOID='$StudyEventOID' AND
                          SERK='$StudyEventRepeatKey' AND
                          FRMOID='$FormOID' AND
                          FRMRK='$FormRepeatKey' AND
                          ISLAST='Y' AND
                          ISMANUAL='N' AND
                          QUERYSTATUS<>'C' AND
                          $where";
    $GLOBALS['egw']->db->query($sql);
    $tblQueryFromDB = array();
    while($GLOBALS['egw']->db->next_record()){
      $tblQueryFromDB[] = array('ItemGroupOID' => $GLOBALS['egw']->db->f('IGOID'), 
                                'ItemGroupRepeatKey' => $GLOBALS['egw']->db->f('IGRK'),
                                'ItemOID' => $GLOBALS['egw']->db->f('ITEMOID'),
                                'Position' => $GLOBALS['egw']->db->f('POSITION'),
                                'QueryId' => $GLOBALS['egw']->db->f('QUERYID'),
                                'Description' => $GLOBALS['egw']->db->f('LABEL'),
                                'Title' => $GLOBALS['egw']->db->f('ITEMTITLE'),
                                'Type' => $GLOBALS['egw']->db->f('QUERYTYPE'),
                                'QueryStatus' => $GLOBALS['egw']->db->f('QUERYSTATUS'));  
    }
    
    foreach($tblQueryFromDB as $queryDB){  
      $this->addLog(__METHOD__." : query {$queryDB['QueryId']} est ouverte",INFO);
      //La query est-elle toujours présente ?
      if($queryDB['Position']<0){ //on ne ferme pas automatiquement les queries manuelles (on fait un updateQuery pour les passer à RESOLVED si la valeur est modifiée)
        $queryDB['Value'] = $this->m_ctrl->bocdiscoo()->getValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$queryDB['ItemGroupOID'],$queryDB['ItemGroupRepeatKey'],$queryDB['ItemOID']);
        $queryDB['Decode'] = $this->m_ctrl->bocdiscoo()->getDecodedValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$queryDB['ItemGroupOID'],$queryDB['ItemGroupRepeatKey'],$queryDB['ItemOID']);
        $this->updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, false, 'Value changed', $queryDB);
      }else{
        $i=0;
        $bFind = false;
        while($i<count($queries) && $bFind == false){
          $query = $queries[$i];
          $i++;
          if( $query['ItemGroupOID']==$queryDB['ItemGroupOID'] && 
              $query['ItemGroupRepeatKey']==$queryDB['ItemGroupRepeatKey'] &&
              $query['ItemOID']==$queryDB['ItemOID'] &&
              $query['Position']==$queryDB['Position']
              ){
                //Oui !
                $bFind = true;
                $this->addLog(__METHOD__." : query {$queryDB['QueryId']} est toujours la",INFO);
              }
        }
        if($bFind==false){
          //Fermeture de la query
          if($queryDB['QueryStatus']!="A"){ //le CRF n'a pas le droit de fermer automatiquement les queries CONFIRMED
            $this->closeQuery($queryDB['QueryId'],$userId,$profileId);
          }
        }    
       } 
    }
    
    foreach($queries as $query){
      //Mise à jour ou creation
      $this->updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, false, '', $query);
    }
   
  }

/*
@desc ajoute ou met à jour la query $query
@param array $query : représente une query, avec les champs suivants : array("ItemOID" => '',
                                                                             "ItemGroupOID" => '',
                                                                             "ItemGroupRepeatKey" => '',
                                                                             "Description" => '',
                                                                             "Title" => ''
                                                                             'Position' => '' (Position du ranheCheck dans L'itemDef)
                                                                             'Type' =>  'CM' (CheckMandatory),
                                                                                        'CS' (CheckSoft)
                                                                                        'CH' (CheckHard),
                                                                             'Value' => '',
                                                                             'Decode' => ''
@return false or array(
                        'QUERYID'=>$GLOBALS['egw']->db->f('QUERYID'),
                        'SITEID'=>$GLOBALS['egw']->db->f('SITEID'),
                        'SUBJKEY'=>$GLOBALS['egw']->db->f('SUBJKEY'),
                        'SEOID'=>$GLOBALS['egw']->db->f('SEOID'),
                        'SERK'=>$GLOBALS['egw']->db->f('SERK'),
                        'FRMOID'=>$GLOBALS['egw']->db->f('FRMOID'),
                        'FRMRK'=>$GLOBALS['egw']->db->f('FRMRK'),
                        'IGOID'=>$GLOBALS['egw']->db->f('IGOID'),
                        'IGRK'=>$GLOBALS['egw']->db->f('IGRK'),
                        'POSITION'=>$GLOBALS['egw']->db->f('POSITION'),
                        'ITEMOID'=>$GLOBALS['egw']->db->f('ITEMOID'),
                        'LABEL'=>$GLOBALS['egw']->db->f('LABEL'),
                        'ITEMTITLE'=>$GLOBALS['egw']->db->f('ITEMTITLE'),
                        'ISMANUAL'=>$GLOBALS['egw']->db->f('ISMANUAL'),
                        'BYWHO'=>$GLOBALS['egw']->db->f('BYWHO'),
                        'BYWHOGROUP'=>$GLOBALS['egw']->db->f('BYWHOGROUP'),
                        'UPDATEDT'=>$GLOBALS['egw']->db->f('UPDATEDT'),
                        'QUERYTYPE'=>$GLOBALS['egw']->db->f('QUERYTYPE'),
                        'QUERYSTATUS'=>$GLOBALS['egw']->db->f('QUERYSTATUS'),
                        'QUERYORIGIN'=> ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'),
                        'ANSWER'=>$GLOBALS['egw']->db->f('ANSWER'),
                        'VALUE'=>$GLOBALS['egw']->db->f('VALUE'),
                        'DECODE'=>$GLOBALS['egw']->db->f('DECODE')
                       );
@author wlt,tpi
*/
  function updateQuery($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $isManual, $answer, $query, $queryStatus='O')
  {
    $this->addLog(__METHOD__."($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey, $isManual, $answer, {$query['ItemGroupOID']}/{$query['ItemGroupRepeatKey']}/{$query['Position']}/{$query['ItemOID']}, $queryStatus)",INFO);
    
    $userId = $this->m_ctrl->boacl()->getUserId(); 
    $siteId = substr($SubjectKey, 0, 2);

    if($isManual){
      $cIsManual = "Y";
    }else{
      $cIsManual = "N";
    }

    //Y a t il eu des changements ?
    $sql = "SELECT LABEL,ITEMTITLE,ISMANUAL,ANSWER,QUERYSTATUS,QUERYTYPE,POSITION,VALUE,CONTEXTKEY
              FROM egw_alix_queries
              WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                            SUBJKEY='$SubjectKey' AND
                            SEOID='$StudyEventOID' AND
                            SERK='$StudyEventRepeatKey' AND
                            FRMOID='$FormOID' AND
                            FRMRK='$FormRepeatKey' AND
                            IGOID='{$query['ItemGroupOID']}' AND
                            IGRK='{$query['ItemGroupRepeatKey']}' AND
                            POSITION='{$query['Position']}' AND
                            ITEMOID='{$query['ItemOID']}' AND
                            /*QUERYSTATUS<>'C' AND*/
                            ISLAST='Y'";
    $this->addLog(__METHOD__." : sql = ".$sql,TRACE);
    $GLOBALS['egw']->db->query($sql);
    $hasModif = false;
    
    if($GLOBALS['egw']->db->next_record()){
      if($GLOBALS['egw']->db->f('LABEL') != $query['Description'] ||
        $GLOBALS['egw']->db->f('ITEMTITLE') != $query['Title'] ||
        $GLOBALS['egw']->db->f('ANSWER') != $answer ||
        $GLOBALS['egw']->db->f('QUERYSTATUS') != $queryStatus ||
        $GLOBALS['egw']->db->f('VALUE') != $query['Value'] ||
        $GLOBALS['egw']->db->f('CONTEXTKEY') != $query['ContextKey']){
        
          //On ne peut modifier la querie que sous certaines conditions de profil et de changement de statut
          /*
          if($isManual){
            //TODO ? vérifier les droits ici ?
            $hasModif = true;
          }else{
            if($GLOBALS['egw']->db->f('VALUE') != $query['Value']){ //le CRF peut (ré)ouvrir, fermer ou mettre à jour (la description par exemple) une querie quelque soit sont statut actuel dès que la valeur est modifiée
              $hasModif = true;
            }elseif($queryStatus=='C'){ //le CRF peut fermer automatiquement les queries quelque soit leur statut
              $hasModif = true;
            }elseif($queryStatus=='O' && $GLOBALS['egw']->db->f('QUERYSTATUS')=='C'){ //le CRF peut (ré)ouvrir automatiquement les queries en statut Closed
              if($GLOBALS['egw']->db->f('ISMANUAL')!='Y' //si non fermées manuellement (ARC)
                || ($GLOBALS['egw']->db->f('ISMANUAL')=='Y' && $GLOBALS['egw']->db->f('VALUE')!=$query['Value']) //ou si elles ont été fermées manuellement mais que la valeur a été modifiée (par l'INV) 
                ){
                $hasModif = true;
              }
            }elseif($queryStatus=='O' && $GLOBALS['egw']->db->f('QUERYSTATUS')=='A' && $GLOBALS['egw']->db->f('VALUE')!=$query['Value']){ //le CRF peut (ré)ouvrir les queries Acknowledge si l'investigateur en a modifié la valeur
              $hasModif = true;
            }
          }
          */
          
          $QUERYORIGIN = ($GLOBALS['egw']->db->f('POSITION')<0 ? 'M' : 'A'); //champ absent de la base. M : query OPEN de façon manuelle (ARC), A : query OPEN de façon automatique (CRF)
          
          if($isManual){ //Tout modifications demandée manuellement est autorisée par l'interface
            //TODO ? vérifier les droits ici ? (Les actions possibles sont déjà limitées dans l'interface via le code JavaScript.)
            $hasModif = true;
          }else{ //On vérifie plus finement ici pour les modifications automatiques (CRF)
            if($GLOBALS['egw']->db->f('VALUE') != $query['Value'] || $GLOBALS['egw']->db->f('CONTEXTKEY') != $query['ContextKey']){ //le CRF ne peut modifier une querie que si la valeur a été modifiée
              /*
              * le CRF peut donc modifier :
              *              les queries OPEN
              *               - et uniquement les passer en statut RESOLVED si elles ont été créées manuellement
              *              les queries RESOLUTION PROPOSED
              *               - si le nouveau statut est CLOSED
              *               - la description uniquement si celle-ci a changé (valeur modifiée qui peut être reprise dans la description)              
              *              les queries CONFIRMED si le nouveau staut est OPEN
              *              les queries CLOSED
              *               - qui ont été fermées automatiquement
              *               - qui ont été ouvertes automatiquement
              */
              if($GLOBALS['egw']->db->f('QUERYSTATUS') == "O"){
                $hasModif = true;
                if($QUERYORIGIN == "M"){
                  $queryStatus = "R";
                }
              }elseif($GLOBALS['egw']->db->f('QUERYSTATUS') == "P"){
                if($queryStatus == "C"){
                  $hasModif = true;
                }elseif($GLOBALS['egw']->db->f('LABEL') != $query['Description']){
                  $isManual = $GLOBALS['egw']->db->f('ISMANUAL');
                  $answer = $GLOBALS['egw']->db->f('ANSWER');
                  $query['Type'] = $GLOBALS['egw']->db->f('QUERYTYPE');
                  $queryStatus = $GLOBALS['egw']->db->f('QUERYSTATUS');
                  $hasModif = true;
                }
              }elseif($GLOBALS['egw']->db->f('QUERYSTATUS') == "A" && $queryStatus == "O"){
                $hasModif = true;
              }elseif($GLOBALS['egw']->db->f('QUERYSTATUS') == "C"){
                if($GLOBALS['egw']->db->f('ISMANUAL') != "Y"){
                  $hasModif = true;
                }elseif($QUERYORIGIN == "A"){
                  $hasModif = true;
                }
              }
            }
          }
      }
    }else{
      //Pas d'ancien enregistrement, on va insérer la query
      $hasModif = true;
    }

    if($hasModif){    
      $sql = "UPDATE egw_alix_queries 
              SET ISLAST='N'
              WHERE CURRENTAPP='".$this->getCurrentApp(true)."' AND
                    SUBJKEY='$SubjectKey' AND
                    SEOID='$StudyEventOID' AND
                    SERK='$StudyEventRepeatKey' AND
                    FRMOID='$FormOID' AND
                    FRMRK='$FormRepeatKey' AND
                    IGOID='".$query['ItemGroupOID']."' AND
                    IGRK='".$query['ItemGroupRepeatKey']."' AND
                    POSITION='".$query['Position']."' AND
                    ITEMOID='".$query['ItemOID']."'";
      $this->addLog(__METHOD__." : sql = ".$sql,TRACE);
      $this->addLog(__METHOD__." : hasModif = true => mise à jour de la query  {$query['ItemGroupOID']}/{$query['ItemGroupRepeatKey']}/{$query['Position']}/{$query['ItemOID']} ",INFO);
      $GLOBALS['egw']->db->query($sql);
  
      $sql = "INSERT INTO egw_alix_queries(CURRENTAPP,SITEID,SUBJKEY,SEOID,SERK,FRMOID,FRMRK,IGOID,IGRK,POSITION,ITEMOID,
                                            LABEL,ITEMTITLE,ISMANUAL,BYWHO,UPDATEDT,
                                            QUERYTYPE,QUERYSTATUS,ANSWER,ISLAST,VALUE,DECODE,CONTEXTKEY)
             VALUES('".$this->getCurrentApp(true)."',
                    '$siteId','$SubjectKey','$StudyEventOID','$StudyEventRepeatKey','$FormOID','$FormRepeatKey','".$query['ItemGroupOID']."','".$query['ItemGroupRepeatKey']."',
                    '".$query['Position']."','".$query['ItemOID']."','".addslashes($query['Description'])."','".addslashes($query['Title'])."','$cIsManual','$userId',now(),'".$query['Type']."','".$queryStatus."','".addslashes($answer)."','Y','".$query['Value']."','".addslashes($query['Decode'])."','".$query['ContextKey']."')";
      $this->addLog(__METHOD__." : sql = ".$sql,TRACE);
      $GLOBALS['egw']->db->query($sql);
      
      return array(
                    'QUERYID'=>$GLOBALS['egw']->db->get_last_insert_id("egw_alix_queries", "QUERYID"),
                    'SITEID'=>$siteId,
                    'SUBJKEY'=>$SubjectKey,
                    'SEOID'=>$StudyEventOID,
                    'SERK'=>$StudyEventRepeatKey,
                    'FRMOID'=>$FormOID,
                    'FRMRK'=>$FormRepeatKey,
                    'IGOID'=>$query['ItemGroupOID'],
                    'IGRK'=>$query['ItemGroupRepeatKey'],
                    'POSITION'=>$query['Position'],
                    'ITEMOID'=>$query['ItemOID'],
                    'LABEL'=>$query['Description'],
                    'ITEMTITLE'=>$query['Title'],
                    'ISMANUAL'=>$cIsManual,
                    'BYWHO'=>$userId,
                    'UPDATEDT'=>date("Y-m-d H:i:s"),
                    'QUERYTYPE'=>$query['Type'],
                    'QUERYSTATUS'=>$queryStatus,
                    'QUERYORIGIN'=> ($query['Position']<0 ? 'M' : 'A'), //champ absent de la base. M : query OPEN de façon manuelle (ARC), A : query OPEN de façon automatique (CRF)
                    'ANSWER'=>$answer,
                    'VALUE'=>$query['Value'],
                    'DECODE'=>$query['Decode']
                   );
    }
    else{
      return false;
    }            
  }
  
}
