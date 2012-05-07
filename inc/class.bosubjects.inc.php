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
@author tpi
*/
class bosubjects extends CommonFunctions
{

  //Constructeur
  function bosubjects(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }

  
  /**
   * @description Retourne le statut du patient (Screened, Randomized, etc)
   * @todo à déplacer dans custom avec un hook 
   * @author tpi
   */  
  public function getSubjectStatus($subj){
    $this->addLog(__METHOD__."($subj)",INFO);
    
    if($subj['colCONT']=="1" && $subj['colDSTERMN']==""){
      return "Completed";
    }elseif($subj['colCONT']!="1" && $subj['colDSTERMN']!=""){
      return "Withdrawal";
    }elseif($subj['colIEYN']=="1" && $subj['colRDNUM']!=""){
      return "Randomized";
    }elseif($subj['colIEYN']=="2" && $subj['colRDNUM']==""){
      return "Randomization Failure";
    }elseif($subj['colIEELIG']=="2"){
      return "Screening Failure";
    }elseif($subj['colIEELIG']=="1"){
      return "Screened";
    }
    return "";
  }

  
  /**
   * @description update subject(s) summary in SubjectsList.dbxml
   * @param optional $SubjectKey => update a specific subject, otherwise update every subjects
   * @author tpi
   */  
  public function updateSubjectsList($SubjectKey=false){
    $this->addLog(__METHOD__."($SubjectKey)",INFO);
    
    if($SubjectKey!==false){
      $this->updateSubjectInList($SubjectKey);
    }else{
      $this->initSubjectsList();
    }
  }

  
  /**
   * @description create the subjects summary list in SubjectsList.dbxml
   * @author tpi
   */  
  public function initSubjectsList(){
    $this->addLog(__METHOD__."()",INFO);
    
    //We do not log long execution time
    $this->m_tblConfig['LOG_LONG_EXECUTION'] = false;
    
    $DOMList = new DOMDocument();
    $DOMList->loadXML("<subjects FileOID='SubjectsList'></subjects>");
    $SubjectKeys = $this->m_ctrl->socdiscoo()->getDocumentsList('ClinicalData');
    $isNewList = true;
    foreach($SubjectKeys as $SubjectKey){
      set_time_limit(30);
      try{
        $this->addLog(__METHOD__."() Adding Subject $SubjectKey",TRACE);
        
        $this->updateSubjectInList($SubjectKey, $DOMList, $isNewList);
        $isNewList = false;
        $DOMList = false;
        $i++;
      }catch(xmlexception $e){
        $this->addLog($e->getMessage(),FATAL);
      }
    }
  }


  
  /**
   * @description return Subjects List
   * @author tpi
   */  
  public function getSubjectsList(){
    $this->addLog(__METHOD__."()",INFO);
    
    try{
      $SubjectsList = $this->m_ctrl->socdiscoo()->getDocument("","SubjectsList");
    }catch(Exception $e){
      $this->addLog($e->getMessage(),INFO);
      $this->initSubjectsList();
      $SubjectsList = $this->m_ctrl->socdiscoo()->getDocument("","SubjectsList");
    }
    return $SubjectsList;
  }
  
  /**
   * @description update a subject summary in SubjectsList.dbxml
   * @param $SubjectKey => update a specific subject
   * @param optional $SubjectsList => DOM object
   * @author tpi
   */  
  public function updateSubjectInList($SubjectKey,$SubjectsList=false, $isNewList=false){
    $this->addLog(__METHOD__."($SubjectKey,".($SubjectsList?1:0).",$isNewList)",INFO);
    
    if($SubjectsList===false){
      try{
        $SubjectsList = $this->m_ctrl->socdiscoo()->getDocument("","SubjectsList",false);
      }catch(Exception $e){
        $this->addLog($e->getMessage(),INFO);
        $this->initSubjectsList();
        return true;
      }
    }
    
    $xPath = new DOMXPath($SubjectsList);
    
    //accessing subject, setting SubjectKey if necessary
    $query = "/subjects/subject[./SubjectKey='$SubjectKey']";
    $result = $xPath->query($query);
    if($result->length==0){
      $result = $xPath->query("/subjects");
      if($result->length==0){
        $element = $SubjectsList->createElement("subjects");
        $result->appendChild($element);
        $result = $xPath->query("/subjects");
      }
      $element = $SubjectsList->createElement("subject");
      $element2 = $SubjectsList->createElement("SubjectKey", $SubjectKey);
      $element->appendChild($element2);
      $result->item(0)->appendChild($element);
      
      $result = $xPath->query($query);
    }
    
    //Subject Parameters
    $subject = $this->getSubjectParams($SubjectKey);
    $subject[0]->subj->addAttribute("SUBJECTSTATUS", $this->getSubjectStatus($subject[0]->subj)); //adding SubjectStatus
    $subject[0]->subj->addAttribute("CRFSTATUS", $this->m_ctrl->bocdiscoo()->getSubjectStatus($SubjectKey)); //adding CRFStatus
    $subjectParams = $subject[0]->subj->attributes();
    foreach($subjectParams as $col=>$val){
      $query = "/subjects/subject[./SubjectKey='$SubjectKey']/$col";
      $domcol = $xPath->query($query);
      if($domcol->length!=0 && $val!=$domcol->item(0)->nodeValue){
        $domcol->item(0)->nodeValue = $val;
      }elseif($domcol->length==0){
        $element = $SubjectsList->createElement("$col", $val);
        $result->item(0)->appendChild($element);
      }
    }
    
    //Visits Status
    $formList = $this->m_ctrl->bocdiscoo()->getSubjectTblForm($SubjectKey);
    
    //HOOK => bosubject_updateSubjectInList_customVisitStatus
    $this->callHook(__FUNCTION__,"customVisitStatus",array($SubjectKey,&$formList,$this));

    $formListNode = $SubjectsList->importNode($formList->documentElement,true);
    
    $query = "/subjects/subject[./SubjectKey='$SubjectKey']/formList/SubjectData";
    $domFormList = $xPath->query($query);
    
    if($domFormList->length==0){
      $element = $SubjectsList->createElement("formList", "");
      $element->appendChild($formListNode);
      $result->item(0)->appendChild($element);      
    }else{
      $domFormList->item(0)->parentNode->replaceChild($formListNode,$domFormList->item(0));
    } 
          
    //update SubjectsList.dbxml
    if($isNewList){
      $this->m_ctrl->socdiscoo()->addDocument($SubjectsList,false,"",false);
    }else{
      $this->m_ctrl->socdiscoo()->replaceDocument($SubjectsList,false,"",false);
    }
  }

  //Retourne une liste de paramètres pour un patient
  private function getSubjectParams($SubjectKey)
  {
    $this->addLog(__METHOD__."($SubjectKey)",INFO);
    
    //L'audit trail engendre plusieurs ItemData avec le même ItemOID, ce qui nous oblige
    //pour chaque item à rechercher le dernier en regardant l'attribut AuditRecordID qui est le plus grand, et ce pour chaque item
    $query = "
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$v := ''
        return \$ItemData[last()]/string()
      };

          <subjs>
              {
                let \$SubjectsCol := collection('ClinicalData')[/odm:ODM/@FileOID='$SubjectKey']
                for \$SubjectData in \$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData
				        let \$FileOID := \$SubjectData/../../@FileOID
                ";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      if(is_array($col['Value'])){
        $query .= "let \$col$key := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['Value']['SEOID']}' and @StudyEventRepeatKey='{$col['Value']['SERK']}']/
                                                          odm:FormData[@FormOID='{$col['Value']['FRMOID']}' and @FormRepeatKey='{$col['Value']['FRMRK']}']/
                                                          odm:ItemGroupData[@ItemGroupOID='{$col['Value']['IGOID']}' and @ItemGroupRepeatKey='{$col['Value']['IGRK']}']/
                                                          odm:*[@ItemOID='{$col['Value']['ITEMOID']}'])
                  ";
      }else{
        if($key=="SUBJID"){
          $query .= "let \$col$key := \$FileOID \n";
        }else{
          $query .= "let \$col$key := " . $col['Value'];
        }
      }
    }            

    $query .= " return 
                  <subj fileOID='{\$FileOID}'";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      $query .= " col$key ='{\$col$key}' ";
    } 
  
    $query .= "/>";
      
    $query .= "}
          </subjs>";
    
    try{
      $this->addLog(__METHOD__."() Run query",TRACE);
      $doc = $this->m_ctrl->socdiscoo()->query($query);
      $this->addLog(__METHOD__."() Query OK",TRACE);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    return $doc;
  }
  
  /**
   * @description returns a specified parameter for a subject. The paramter as to be defined in config.inc.phph into $configEtude['SUBJECT_LIST']['COLS']
   * @param $SubjectKey => the subject
   * @param $key => the parameter for which a value is requested
   * @return the value   
   * @author tpi
   */ 
  public function getSubjectColValue($SubjectKey,$key){
    $this->addLog(__METHOD__."($SubjectKey)",INFO);
    if(!$SubjectKey) throw new Exception("Error: SubjectKey is empty (". __METHOD__ .")");
    if(!$key) throw new Exception("Error: Requested Key is not specified (". __METHOD__ .")");
    if(!isset($this->m_tblConfig['SUBJECT_LIST']['COLS'][$key])) throw new Exception("Error: Requested Key is not defined in config.inc.php (". __METHOD__ .")");
    
    //We will find the Key in the CRF
    $col = $this->m_tblConfig['SUBJECT_LIST']['COLS'][$key];
    
    //L'audit trail engendre plusieurs ItemData avec le même ItemOID, ce qui nous oblige
    //pour chaque item à rechercher le dernier en regardant l'attribut AuditRecordID qui est le plus grand, et ce pour chaque item
    $query = "
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$v := ''
        return \$ItemData[last()]/string()
      };

          <subjs>
              {
                let \$SubjectsCol := collection('ClinicalData')[/odm:ODM/@FileOID='$SubjectKey']
                for \$SubjectData in \$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData
				        let \$FileOID := \$SubjectData/../../@FileOID
                let \$col$key := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['Value']['SEOID']}' and @StudyEventRepeatKey='{$col['Value']['SERK']}']/
                                                        odm:FormData[@FormOID='{$col['Value']['FRMOID']}' and @FormRepeatKey='{$col['Value']['FRMRK']}']/
                                                        odm:ItemGroupData[@ItemGroupOID='{$col['Value']['IGOID']}' and @ItemGroupRepeatKey='{$col['Value']['IGRK']}']/
                                                        odm:*[@ItemOID='{$col['Value']['ITEMOID']}'])
                return 
                  <subj col$key ='{\$col$key}' />
              }
          </subjs>";
    
    try{
      $this->addLog(__METHOD__."() Run query",TRACE);
      $doc = $this->m_ctrl->socdiscoo()->query($query);
      $this->addLog(__METHOD__."() Query OK",TRACE);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    
    $value = (string)$doc[0]->subj["col$key"];
    
    //HOOK => bosubjects_getSubjectColValue_customValue
    $this->callHook(__FUNCTION__,"customValue",array($SubjectKey,$key,&$value,$this));
    
    return $value;
  }
  
}
