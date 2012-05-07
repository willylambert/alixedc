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
  * @param $uisubject instance of uisubject
  * @return nothing
  * @author WLT
  **/  
function uisubject_getInterface_start($FormOID,$uisubject){  
}

  /**
  * @desc Definition des paramètres passés à l'XSL de rendu du formulaire
  * @param XSLTProcessor $xslProc Processeur XSL  
  * @return XSLTProcessor HTML à afficher
  * @author WLT
  **/  
function uisubject_getInterface_xslParameters($FormOID,$xslProc,$uisubject){
  if($FormOID==""){  
    //Here we force the display of a select instead of radios buttons - multiple codelist could be used - separe them with white space
    $xslProc->setParameter('','CodeListForceSelect','CL.$YN CL.$SYMB');    
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

    case 'FORM.HE' : 
      $dob = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"1","0","FORM.IC","0","DM","0","DM.BRTHDTC");
      $xslProc->setParameter('','BRTHDTC',$dob);
      break;
      
    case 'FORM.ELIG' :
    case 'FORM.STDD' :      
      $siteId = substr($_GET['SubjectKey'],0,2);
      $subjId = substr($_GET['SubjectKey'],2,2);

      $country = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"1","0","FORM.ENROL","0","ENROL","0","ENROL.COUNTID");  
      $subjInit = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"1","0","FORM.ENROL","0","ENROL","0","ENROL.SUBJINIT");
      $subjWeight = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],$_GET['StudyEventOID'],$_GET['StudyEventRepeatKey'],$_GET['FormOID'],$_GET['FormRepeatKey'],"DA","0","DA.WEIGHT");
      $poso = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],$_GET['StudyEventOID'],$_GET['StudyEventRepeatKey'],$_GET['FormOID'],$_GET['FormRepeatKey'],"DA","0","DA.DAPOS");
    
      switch($_GET['StudyEventOID']){
        case '2' : $visit = "D0"; break;
        case '3' : $visit = "W4"; break;
        case '5' : $visit = "W13"; break;
        case '6' : $visit = "W26"; break;
        case '7' : $visit = "W39"; break;
        case '8' : $visit = "W52"; break;
        case '9' : $visit = "W66"; break;
        case '10' : $visit = "W78"; break;
        case '11' : $visit = "W91"; break;
      }
      
      $randoId = "9999";
      $nbUT = 3;
      $tblUTdisp1 = array();
      for($i=0;$i<$nbUT;$i++){
        $tblUTdisp1[$i] = 9000 + $i;    
      }

      //Passage des listes d'UT à l'XSL
      for($i=1;$i<=count($tblUTdisp1);$i++){
        $xslProc->setParameter('',"tblUTdisp1_$i",$tblUTdisp1[$i-1]);        
      }
      
      $xslProc->setParameter('',"RANDOID",$randoId);
      $xslProc->setParameter('',"currentApp",$GLOBALS['egw_info']['flags']['currentapp']);
      $xslProc->setParameter('',"country",$country);
      $xslProc->setParameter('',"subjWeight",$subjWeight);
      $xslProc->setParameter('',"poso",$poso);
      $xslProc->setParameter('',"siteId",$siteId);
      $xslProc->setParameter('',"subjId",$subjId);
      $xslProc->setParameter('',"subjInit",$subjInit);
      $xslProc->setParameter('',"visit",$visit); 
      $xslProc->setParameter('',"nbUTtotal",count($tblUTdisp1));
      
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
  //Extraction of dates for insertion into menu
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

function uisubject_getMenu_beforeRendering($SubjectKey,&$tblForm,$uisubject){
}

function bocdiscoo_getNewPatientID_customSubjId($bocdiscoo){
  //Return the new SubjectKey, incremented by site in this example

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