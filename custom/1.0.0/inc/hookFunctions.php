<?
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
  
  
  /**
  * @desc Hook appellé avant la restitution de l'html d'un formulaire
  * @param $FormOID OID du Form 
  * @param $uisubject instance uisubject appelante
  * @return rien
  * @author WLT
  **/  
function uisubject_getInterface_start($FormOID,$uisubject){
 
  $siteId = substr($_GET['SubjectKey'],0,2);
  $subjId = substr($_GET['SubjectKey'],2,2);
  
}

  /**
  * @desc Definition des paramètres passés à l'XSL de rendu du formulaire
  * @param XSLTProcessor $xslProc Processeur XSL  
  * @return XSLTProcessor HTML à afficher
  * @author WLT
  * @history le 17/02/2011 par WLT : suppression du filtre investigateur pour la liste des centres  
  **/  
function uisubject_getInterface_xslParameters($FormOID,$xslProc,$uisubject){
  //Paramètres passés au StudyEventForm pour permettre d'afficher les codelistes en liste déroulantes
  if($FormOID==""){  
    $xslProc->setParameter('','CodeListForceSelect','CL.$YN CL.$SYMB'); //valeurs séparées par un espace
      
  }
  
  switch($FormOID){
    case 'FORM.ENROL' : 
      //Liste des centres où l'utilisateur connecté est investigateur
      $profiles = $uisubject->m_ctrl->boacl()->getUserProfiles();
      
      $sitesList = "<select name='text_string_ENROL@SITEID_0'>";
      foreach($profiles as $profile){
          $sitesList .= "<option value='{$profile['siteId']}'>{$profile['siteName']}</option>";  
      }
      $sitesList .= "</select>";
      
      $xslProc->setParameter('','sitesList',$sitesList);
      $xslProc->setParameter('','SubjectKey',$_GET['SubjectKey']);
      
      break;
      
      case 'FORM.SV' : 
      $xslProc->setParameter('','StudyEventOID',$_GET['StudyEventOID']);
      break;
      
      case 'FORM.HE' : 
      $xslProc->setParameter('','BRTHDTC',$uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"1","0","FORM.IC","0","DM","0","DM.BRTHDTC"));
      break;
      
      case 'FORM.ASS' : 
      $xslProc->setParameter('','StudyEventOID',$_GET['StudyEventOID']);
      break;

      case 'FORM.AE' : 
      $xslProc->setParameter('','FormRepeatKey',$_GET['FormRepeatKey']);
      break;                     
  }
}

function ajax_saveItemGroupData_afterSave($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ajax){

}

/**
* Hook called just after the FORM XSL transformation. Could be used to add another XSL transformation step
* @param $FormOID
* @param $uisubject instance uisubject appelante
* @param $html HTML generated 
* @return DOMDocument
* @author WLT
**/  
function uisubject_getInterface_afterXSLT($MetaDataVersionOID,$FormOID,$uisubject,$xmlDoc){
  return $xmlDoc;
}

function uisubject_getMenu_xslParameters($xslProc,$uisubject){
  
  //on va chercher les dates de visites
  $selDT = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"1","0","FORM.SV","0","SV","0","SV.SVSTDTC");  
  $incDT =  $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"2","0","FORM.SV","0","SV","0","SV.SVSTDTC");
  $dmAge = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"1","0","FORM.IC","0","DM","0","DM.AGE");
  $xslProc->setParameter('','SelDT',$selDT);
  $xslProc->setParameter('','IncDT',$incDT);
  $xslProc->setParameter('','DMAGE',$dmAge);
}

/**
 *wrapper to uisubject_getMenu_beforeRendering hook
 *@author wlt 
 */
function bosubjects_updateSubjectInList_customVisitStatus($SubjectKey,$tblForm,$uisubject){
  uisubject_getMenu_beforeRendering($SubjectKey,$tblForm,$uisubject);
}

/**
 * @desc Calcul et enregistrement du statut du CRF du patient
 * @author tpi 
 */ 
function uisubject_getMenu_beforeRendering($SubjectKey,&$tblForm,$uisubject){
  $SubjectStatus = "";
  $endOfStudyStatus = "";
  $EMPTY = 0;
  $PARTIAL = 0;
  $INCONSISTENT = 0;
  $FILLED = 0;
  $StudyEventDatas = $tblForm->firstChild->childNodes;
  
  //Mise à jour du statut de la visite pour la visite Inclusion Visit (VO) => cas particulier, un formulaire est caché
  $V0_StudyEventOID = "2";
  $V0_StudyEventRepeatKey = "0";
  $V0_forms = 0;
  $V0_filledForms = 0;
  foreach($StudyEventDatas as $StudyEventData){
    if((string)$StudyEventData->getAttribute('StudyEventOID') == $V0_StudyEventOID &&(string)$StudyEventData->getAttribute('StudyEventRepeatKey') == $V0_StudyEventRepeatKey ){
      foreach($StudyEventData->childNodes as $FormData){
        $V0_forms++;
        foreach($FormData->childNodes as $FormDataChild){
          if($FormDataChild->nodeName=="status"){
            if($FormDataChild->nodeValue=="FILLED" || $FormDataChild->nodeValue=="FROZEN"){
              $V0_filledForms++;
            }
          }
        }
      }
      break;
    }
  }
  if($V0_filledForms == ($V0_forms-1)){
    //seul le formulaire caché est empty => la visite est FILLED
    $xPath = new DOMXPath($tblForm);
    $result = $xPath->query("StudyEventData[@StudyEventOID='$V0_StudyEventOID' and @StudyEventRepeatKey='$V0_StudyEventRepeatKey']");
    if($result->length==1){
      $StudyEventData = $result->item(0);
      $StudyEventData->setAttribute("Status","FILLED");
    }
  }
  
  //Calcul du statut du patient
  
  $notEmpty = 0;
  foreach($StudyEventDatas as $StudyEventData){
    $FormDatas = $StudyEventData->childNodes;
    foreach($FormDatas as $FormData){
      $FormDataChilds = $FormData->childNodes;
      foreach($FormDataChilds as $FormDataChild){
        if($FormDataChild->nodeName=="status"){
          if($FormDataChild->nodeValue!="EMPTY" && $FormDataChild->nodeValue!=""){
            $notEmpty++;
          }
        }
      }
    }
  }
  if($notEmpty<=1){ //considéré encore vide si uniquement le formulaire de la fiche signalétique a été saisi
    $SubjectStatus = "EMPTY";
  }else{
    foreach($StudyEventDatas as $StudyEventData){
      $status = $StudyEventData->getAttribute('Status');
      eval("\$". strtoupper($status) ."++;" );
      
      if($StudyEventData->getAttribute('StudyEventOID')=="13"){
        $endOfStudyStatus = $status;
      }
    }
    if($INCONSISTENT>0){
      $SubjectStatus = "INCONSISTENT";
    }else{
      if($PARTIAL>0 || $endOfStudyStatus!="FILLED"){
        $SubjectStatus = "PARTIAL";
      }else{
        if($EMPTY==0 && $PARTIAL==0 && $INCONSISTENT==0 && $endOfStudyStatus=="FILLED"){
        $SubjectStatus = "FILLED";
        }
      }
    }
  }
  $uisubject->m_ctrl->bocdiscoo()->setSubjectStatus($SubjectKey,$SubjectStatus);
}

function bocdiscoo_getNewPatientID_customSubjId($bocdiscoo){
  //Récupération du nouveau subjectKey, incrémenté par centre

  //Extraction de la variable $_POST du numéro de centre
  $siteId = $_POST['text_string_ENROL@SITEID_0'];

  $clinicalCollection = $bocdiscoo->m_ctrl->socdiscoo()->getClinicalDataCollection();

  $query = "let \$SubjectsCol := $clinicalCollection
            let \$maxSubjId := max(\$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK' and odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']/odm:ItemDataString[@ItemOID='ENROL.SITEID']='$siteId']/@SubjectKey)   
            return <MaxSubjId>{\$maxSubjId}</MaxSubjId>";  
  try
  {
    $Result = $bocdiscoo->m_ctrl->socdiscoo()->query($query, true);
  }
  catch(xmlexception $e)
  {
    $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query . " (". __METHOD__ .")";
    $bocdiscoo->addLog("bocdiscoo->getNewPatientID() Erreur : $str",FATAL);
    die($str);
  }
  
  if((string)$Result[0]!="")
  {
    $subjKey = (string)$Result[0] + 1;
  }
  else
  {
      $subjKey = $siteId."01";
  }
  
  $bocdiscoo->addLog("bocdiscoo_getNewPatientID_customSubjId() : New SubjId = $subjKey",INFO);
  
  return $subjKey;
   
}

  /**
  * @desc Traitement spécifique des queries pour chaque formulaire à la mise à jour de queries
  * @param string $FormOID identifiant du formulaire
  * @return array $queries
  * @author TPI
  **/  
function boqueries_updateQueries_form($FormOID, $FormRepeatKey, $queryType, $queries){
  //ne créer aucune query à l'enrollement
  if($FormOID=="FORM.ENROL"){
    $queries = array();
  }
}
