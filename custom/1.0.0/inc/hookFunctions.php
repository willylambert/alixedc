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
  $profile = $uisubject->m_ctrl->boacl()->getUserProfile();

  switch($FormOID){
    case 'FORM.ENROL' : 
      $xslProc->setParameter('','SiteId',$profile['siteId']);
      $xslProc->setParameter('','SiteName',$profile['siteName']);
      $xslProc->setParameter('','SubjectKey',$_GET['SubjectKey']);      
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
