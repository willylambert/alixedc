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
  function subjects($tblConfig,$ctrlRef)
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
    $subjectsContainers = $this->m_ctrl->socdiscoo()->getSubjectsContainers();
    $isNewList = true;
    foreach($subjectsContainers as $subjectContainer){
      set_time_limit(30);
      $SubjectKey = substr($subjectContainer, 0, -6);
      try{
        $this->addLog(__METHOD__."() Adding Subject $SubjectKey",TRACE);
        $xquery = "let \$FileOID := collection('$SubjectKey.dbxml')/odm:ODM/@FileOID
                   return
                         <result FileOID='{\$FileOID}' />";
        $results = $this->m_ctrl->socdiscoo($SubjectKey,true)->query($xquery);
        $this->addLog(__METHOD__."() Query OK $SubjectKey",TRACE);
        if((string)$results[0]['FileOID'] == '') throw new Exception("Cannot find collection('$SubjectKey.dbxml')/odm:ODM/@FileOID");
      
        $this->addLog(__METHOD__."() > updateSubjectInList",TRACE);
        $this->updateSubjectInList($SubjectKey, $DOMList, $isNewList);
        $isNewList = false;
        $DOMList = false;
        $i++;
      }catch(xmlexception $e){
        $this->addLog($e->getMessage(),WARN);
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
      $SubjectsList = $this->m_ctrl->socdiscoo("SubjectsList")->getDocument("SubjectsList.dbxml","SubjectsList",true);
    }catch(Exception $e){
      $this->addLog($e->getMessage(),INFO);
      $this->initSubjectsList();
      $SubjectsList = $this->m_ctrl->socdiscoo("SubjectsList")->getDocument("SubjectsList.dbxml","SubjectsList",true);
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
        $SubjectsList = $this->m_ctrl->socdiscoo($SubjectKey)->getDocument("SubjectsList.dbxml","SubjectsList",false);
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
      $this->m_ctrl->socdiscoo("SubjectsList")->addDocument($SubjectsList,false,"",false);
    }else{
      $this->m_ctrl->socdiscoo("SubjectsList")->replaceDocument($SubjectsList,false,"",false);
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
                let \$SubjectsCol := collection('$SubjectKey.dbxml')
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
        $query .= "let \$col$key := " . $col['Value'];
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
      $doc = $this->m_ctrl->socdiscoo($SubjectKey)->query($query);
      $this->addLog(__METHOD__."() Query OK",TRACE);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    return $doc;
  }
  
}
