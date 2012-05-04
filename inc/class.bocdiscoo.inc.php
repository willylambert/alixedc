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

class bocdiscoo extends CommonFunctions
{
  var $m_lang; //lang de l'utilisateur : cf constructeur

  //Constructeur
  function bocdiscoo(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);

      $this->m_lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
  }

/*
@desc ajoute un form dans l'xml du patient
      Attention valable uniquement pour les form de type Repeating="Yes"
@return string nouveau FormRepeatKey assigné
@author wlt
*/  
  function addFormData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID)
  {
    $this->addLog("bocdiscoo->addFormData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID)",INFO);

    //Ouverture du patient
    try{
      $subj = $this->m_ctrl->socdiscoo($SubjectKey)->getDocument("$SubjectKey.dbxml",$SubjectKey,false);
      $subj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$SubjectKey,false);
    }catch(xmlexception $e){
      $str= "Patient $SubjectKey non trouvé dans la base : " . $e->getMessage() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    //Recuperation du dernier FormRepeatKey
    $query = "
          let \$FormRepeatKey := max(collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData/@FormRepeatKey)
          return
            <FormRepeatKey max='{\$FormRepeatKey}'/>
         ";

    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    $newRepeatKey = $result[0]['max'] + 1;

    $xPath = new DOMXPath($subj);
    $xPath->registerNamespace("odm", ODM_NAMESPACE);

    //Ajout de StudyEventData si besoin
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']");
    if($result->length==0){
      $this->addLog("bocdiscoo->addFormData() Ajout de StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']",INFO);
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData");
      if($result->length==1){
        $StudyEventData = $subj->createElementNS(ODM_NAMESPACE,"StudyEventData");
        $StudyEventData->setAttribute("StudyEventOID","$StudyEventOID");
        $StudyEventData->setAttribute("StudyEventRepeatKey",$StudyEventRepeatKey);
        $result->item(0)->appendChild($StudyEventData); //On l'ajoute
      }else{
        $str = "Erreur : Insertion StudyEventData[@StudyEventOID='$StudyEventOID'] (". __METHOD__ .")";
        $this->addLog($str,FATAL);
        die($str);
      }
    }else{
      if($result->length==1){
        //Il était déjà présent
      }else{
        $str = "Erreur : doublons de StudyEventData[@StudyEventOID='$StudyEventOID'] (". __METHOD__ .")";
        $this->addLog($str,FATAL);
        die($str);
      }
    }

    //Ajout de FormData si besoin
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$newRepeatKey']");
    if($result->length==0){
      $this->addLog("bocdiscoo()->addFormData() Ajout de FormData=$FormOID FormRepeatKey=$newRepeatKey",TRACE);
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']");
      if($result->length==1){
        $FormData = $subj->createElementNS(ODM_NAMESPACE,"FormData");
        $FormData->setAttribute("FormOID","$FormOID");
        $FormData->setAttribute("FormRepeatKey","$newRepeatKey");

        //Création de l'élement annotation, qui va contenir les flags de statuts
        $Annotation = $subj->createElementNS(ODM_NAMESPACE,"Annotation");
        $Flag = $subj->createElementNS(ODM_NAMESPACE,"Flag");
        $FlagValue = $subj->createElementNS(ODM_NAMESPACE,"FlagValue","EMPTY");
        $FlagType = $subj->createElementNS(ODM_NAMESPACE,"FlagType","STATUS");

        $Annotation->setAttribute("SeqNum","1");
        $FlagValue->setAttribute("CodeListOID","CL.IGSTATUS");
        $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");

        $Flag->appendChild($FlagValue);
        $Flag->appendChild($FlagType);
        $Annotation->appendChild($Flag);
        $FormData->appendChild($Annotation);

        $result->item(0)->appendChild($FormData); //On l'ajoute
      }else{
        $str = "Erreur : Insertion FormData[@FormOID='$FormOID' @FormRepeatKey='$newRepeatKey'] : result->length={$result->length} (".__METHOD__.")";
        $this->addLog($str,FATAL);
        die($str);
      }
    }else{
      if($result->length==1){
        //Il était déjà présent
        $FormData = $result->item(0);
      }else{
        $str = "Erreur : doublons de FormData[@FormOID='$FormOID' @FormRepeatKey='$newRepeatKey] (". __METHOD__ .")";
        $this->addLog($str,FATAL);
        die($str);
      }
    }

    //Mise à jour de notre document dans la base
    $this->m_ctrl->socdiscoo()->replaceDocument($subj,false,"ClinicalData");

    return $newRepeatKey;
  }

/*
@desc Ajoute les items (requetes spécifique - saveItemGroupData) au $IG (DOMDocument)
@param boolean $bEraseNotFoundItem met à "" les items non présent dans les données soumises. 
                utile pour mettre à blanc les champs disable
                En revanche en cas d'import (coding par ex.) on ne souhaite pas mettre à blanc les valeurs non présentes
@return boolean : Retourne true si la mise à jour est effective, false si elle n'etait pas nécessaire (donnée identique)
@author wlt
*/
  private function addItemData($IG,$ItemGroupRef,$formVars,&$tblFilledVar,$subj,$AuditRecordID,$bEraseNotFoundItem=true)
  {
    $this->addLog("bocdiscoo->addItemData() : tblFilledVar = " . $this->dumpRet($tblFilledVar),TRACE);
    $valRet = false;

    $ItemGroupRepeatKey = $IG->getAttribute("ItemGroupRepeatKey");

    //Ajout des itemdata à notre $IG
    foreach($ItemGroupRef as $Item)
    {
      //Gestion des annotations : RAS/ND/NSP + comment
      $AnnotationID = "";
      $bAnnotationModif = false;
      $flag = $formVars["annotation_flag_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      $previousflag = $formVars["annotation_previousflag_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      $comment = $formVars["annotation_comment_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      $previouscomment = $formVars["annotation_previouscomment_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      
      //Identification de l'annotation 
      // Création d'un nouvelle annotation si la précédente a été modifiée
      if($flag != $previousflag || $comment != $previouscomment)
      {
        $bAnnotationModif = true;
        $hasModif = true;
     
        $xPath = new DOMXPath($subj); /*A optimiser*/
        $xPath->registerNamespace("odm", ODM_NAMESPACE);
        //Ajout d'une Annotation
        $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:Annotations");
        if($result->length==0){
          $str = "Erreur : absence de Annotations";
          $this->addLog($str,FATAL);
          die($str);
        }
        else
        {
          $Annotations = $result->item(0);
          
          $Annotation = $subj->createElementNS(ODM_NAMESPACE,"Annotation");
          
          //Calcul du nouvel ID sous la forme d'un simple numéro :o)
          $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:Annotations/odm:Annotation");
          $AnnotationSeqNum = $result->length+1;
          $AnnotationID = sprintf("Annot-%06s",$result->length+1);
          $Annotation->setAttribute("SeqNum",$AnnotationSeqNum);
          $Annotation->setAttribute("ID",$AnnotationID);
           
          //Comment
          $comment = stripslashes($comment);
          $aComment = $subj->createElementNS(ODM_NAMESPACE,"Comment",$comment);
          $Annotation->appendChild($aComment);
           
          //Flag
          $aFlag = $subj->createElementNS(ODM_NAMESPACE,"Flag");
          //FlagValue
          $FlagValue = $subj->createElementNS(ODM_NAMESPACE,"FlagValue",$flag);
          $FlagValue->setAttribute("CodeListOID","ANNOTFLA");
          $aFlag->appendChild($FlagValue);
          $Annotation->appendChild($aFlag);
          $Annotations->appendChild($Annotation);
          
          $str = "bocdiscoo->addItemData() Ajout de l'annotation $AnnotationID : ". $flag ." / ". $comment;
          $this->addLog($str,TRACE);
        }
      }
      else
      {
        $AnnotationID = $Item['AnnotationID'];
      }

      //Gestion particulière pour les types Date et PartialDate
      switch($Item['DataType'])
      {
        case 'datetime' :
        case 'date' :
          $dd = $formVars["text_dd_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $mm = $formVars["text_mm_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $yy = $formVars["text_yy_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];

          if($dd=="" && $mm=="" && $yy==""){
            //Si la valeurs est null, alors on ne peut l'enregistrer sous la bannière "ItemDataDate", on est
            //obligé de passé en ItemDataAny
            $tblFilledVar["{$Item['ItemOID']}"] = "";
            $Item['DataType'] = "any";
          }else{
            $tblFilledVar["{$Item['ItemOID']}"] = date('Y-m-d',mktime(0,0,0,$mm,$dd,$yy));
            
            if($Item['DataType']=="datetime"){
              $hh = $formVars["text_hh_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
              if(strlen($hh)==1){
                $hh = "0" . $hh; 
              }
              $ii = $formVars["text_ii_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
              if(strlen($ii)==1){
                $ii = "0" . $ii; 
              }
              $tblFilledVar["{$Item['ItemOID']}"] .= "T$hh:$ii:00";  
            }
          }
          break;

        case 'partialDate' :
          $dd = $formVars["text_dd_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $mm = $formVars["text_mm_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $yy = $formVars["text_yy_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];

          if($dd !="" && $mm !="" && $yy != ""){
            $dt = date('Y-m-d',mktime(0,0,0,$mm,$dd,$yy));
          }else{
            if($mm !="" && $yy != ""){
              $dt = date('Y-m',mktime(0,0,0,$mm,1,$yy));
            }else{
              if($yy!=""){
                $dt = date('Y',mktime(0,0,0,1,1,$yy));
              }else{
                $dt = "";
              }
            }
          }
          if($dt!=""){
            $tblFilledVar["{$Item['ItemOID']}"] = $dt;
          }
          break;

        case 'float' :
          $int = $formVars["text_int_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $dec = $formVars["text_dec_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          
          if($int=="" && $dec==""){
            $tblFilledVar["{$Item['ItemOID']}"] = "";
            $Item['DataType'] = "any";
          }else{
            //Auto complete if one value (int or dec) is not filled
            if($int==""){
              $int = "0";
            }
            if($dec==""){
              $dec = "0";
            }
            $tblFilledVar["{$Item['ItemOID']}"] = "$int.$dec";
          }
          break;

        case 'integer' :
          //Si la valeurs est null, alors on ne peut l'enregistrer sous la bannière "ItemDataInteger", on est
          //obligé de passé en ItemDataAny
          if((string)$tblFilledVar["{$Item['ItemOID']}"]==""){ //ne pas confondre 0 et "" (cf http://www.php.net/manual/en/types.comparisons.php)
            $Item['DataType'] = "any";
          }
          break;

        case 'text' :
          $Item['DataType'] = "string";
          break;

        case 'partialTime' :
          $dt = $formVars["text_partialTime_" . str_replace(".","@",$Item['ItemOID'])  . "_$ItemGroupRepeatKey"]; 
          $tblDt = explode(":",$dt);
          if(strlen($tblDt[0])==1){
            $hh = "0" . $tblDt[0];
          }else{
            $hh = $tblDt[0];
          }
          if(strlen($tblDt[1])==1){
            $mm = "0" . $tblDt[1];
          }else{
            $mm = $tblDt[1];
          }
 
          $tblFilledVar["{$Item['ItemOID']}"] = "$hh:$mm";
          break;
      }

      if(isset($tblFilledVar["{$Item['ItemOID']}"]))
      {
        if((string)$tblFilledVar["{$Item['ItemOID']}"]==NULL){  //ne pas confondre la valeur 0 (zéro) et la valeur NULL (cf http://www.php.net/manual/en/types.comparisons.php)
          $tblFilledVar["{$Item['ItemOID']}"] = "";
        }
      }
      else
      {
        $this->addLog("Expected item (ItemGroup ".$IG->getAttribute("ItemGroupOID").") not found in POSTed vars .\nElement ".$Item['ItemOID'],TRACE);
      }
      
      //Nouvel ItemData uniquement si nous avons un nouvel Item ou une Mise à jour de la valeur
      //Et que la valeur est présente dans les données soumises, sauf si on force l'insertion avec $bEraseNotFoundItem (cas par défaut)
      if( isset($tblFilledVar["{$Item['ItemOID']}"]) && 
          (
           !isset($Item->PreviousItemValue) ||
           isset($Item->PreviousItemValue) && 
           (string)($Item->PreviousItemValue) != (string)($tblFilledVar["{$Item['ItemOID']}"]) || 
           $bAnnotationModif
          ) || 
          !isset($tblFilledVar["{$Item['ItemOID']}"]) && $bEraseNotFoundItem 
        )                                     
      {
        $this->addLog("bocdiscoo->addItemData() Ajout de ItemData={$Item['ItemOID']} PreviousItemValue=".$Item->PreviousItemValue." Value=".$tblFilledVar["{$Item['ItemOID']}"],INFO);

        //Value may contains & caracters
        $encodedValue = htmlspecialchars($tblFilledVar["{$Item['ItemOID']}"],ENT_NOQUOTES); 

        $valRet = true;
        $ItemData = $subj->createElementNS(ODM_NAMESPACE,"ItemData" . ucfirst($Item['DataType']),$encodedValue);
        $ItemData->setAttribute("ItemOID",$Item['ItemOID']);
        $ItemData->setAttribute("AuditRecordID",$AuditRecordID);
        if($AnnotationID != "") $ItemData->setAttribute("AnnotationID",$AnnotationID);
        if(isset($Item->PreviousItemValue)){
          $ItemData->setAttribute("TransactionType",'Update');
        }else{
          $ItemData->setAttribute("TransactionType",'Insert');
        }
        $IG->appendChild($ItemData);
      }
    }
    return $valRet;
  }

/*
@desc Ajout d'une Annotation à un ItemGroupData
@author tpi
*/
  private function addItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status="EMPTY",$SeqNum="1")
  {
    $this->addLog("bocdiscoo->addItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status,$SeqNum)",INFO);
    
    //Le document de note patient (c'est un DOMDocument)
    try{
      $subj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$SubjectKey,false);
    }catch(xmlexception $e){
      $str= "Patient $SubjectKey non trouvé dans la base : " . $e->getMessage() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    $xPath = new DOMXPath($subj);
    $xPath->registerNamespace("odm", ODM_NAMESPACE);
    
    $query = "/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey' and not(./Annotation[@SeqNum='$SeqNum'])]";
    $resultIGDT = $xPath->query($query);
    if($resultIGDT->length==0){
      $str = "bocdiscoo->addItemGroupStatus() : ItemGroup non trouvé ou ItemGroup avec Annotation(SeqNum='$SeqNum') déjà présente ! Requête : $query (". __METHOD__ .")";
      $this->addLog($str,INFO);
      //die($str);
    }else{
      $IGDT = $resultIGDT->item(0);
      $Annotation = $subj->createElementNS(ODM_NAMESPACE,"Annotation");
      $Flag = $subj->createElementNS(ODM_NAMESPACE,"Flag");
      $FlagValue = $subj->createElementNS(ODM_NAMESPACE,"FlagValue",$status);
      $FlagType = $subj->createElementNS(ODM_NAMESPACE,"FlagType","STATUS");

      $Annotation->setAttribute("SeqNum","1");
      $FlagValue->setAttribute("CodeListOID","CL.IGSTATUS");
      $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");

      $Flag->appendChild($FlagValue);
      $Flag->appendChild($FlagType);
      $Annotation->appendChild($Flag);
      
      //Les annotations doivent êtes le premier noeud de l'IGDT
      $firstChild = $IGDT->firstChild;
      if($firstChild)
      {
        $IGDT->insertBefore($Annotation, $firstChild);
      }
      else
      {
        $IGDT->appendChild($Annotation);
      }
      
      //Mise à jour de notre document dans la base
      $this->m_ctrl->socdiscoo()->replaceDocument($subj,false,"ClinicalData");
      
      $str = "bocdiscoo->addItemGroupStatus() Un FlagValue non trouvé a été créé/restauré sur un ItemGroupData (".__METHOD__.")";
      $this->addLog($str,INFO);
    }
  }

/*
@desc Ajout d'une Annotation à un SubjectData
@author tpi
*/
  private function addSubjectStatus($SubjectKey,$status="EMPTY",$SeqNum="1")
  {
    $this->addLog("bocdiscoo->addSubjectStatus($SubjectKey,$status,$SeqNum)",INFO);
    
    //Le document de note patient (c'est un DOMDocument)
    try{
      $subj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$SubjectKey,false);
    }catch(xmlexception $e){
      $str= "Patient $SubjectKey non trouvé dans la base : " . $e->getMessage() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    $xPath = new DOMXPath($subj);
    $xPath->registerNamespace("odm", ODM_NAMESPACE);
    
    $query = "/odm:ODM/odm:ClinicalData/odm:SubjectData[not(./Annotation[@SeqNum='$SeqNum'])]";
    $resultIGDT = $xPath->query($query);
    if($resultIGDT->length==0){
      $str = "bocdiscoo->addItemGroupStatus() : SubjectData non trouvé ou SubjectData avec Annotation(SeqNum='$SeqNum') déjà présente ! Requête : $query (". __METHOD__ .")";
      $this->addLog($str,INFO);
      //die($str);
    }else{
      $IGDT = $resultIGDT->item(0);
      $Annotation = $subj->createElementNS(ODM_NAMESPACE,"Annotation");
      $Flag = $subj->createElementNS(ODM_NAMESPACE,"Flag");
      $FlagValue = $subj->createElementNS(ODM_NAMESPACE,"FlagValue",$status);
      $FlagType = $subj->createElementNS(ODM_NAMESPACE,"FlagType","STATUS");

      $Annotation->setAttribute("SeqNum","1");
      $FlagValue->setAttribute("CodeListOID","CL.SSTATUS");
      $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");

      $Flag->appendChild($FlagValue);
      $Flag->appendChild($FlagType);
      $Annotation->appendChild($Flag);
      
      //Les annotations doivent êtes le premier noeud de l'IGDT
      $firstChild = $IGDT->firstChild;
      if($firstChild)
      {
        $IGDT->insertBefore($Annotation, $firstChild);
      }
      else
      {
        $IGDT->appendChild($Annotation);
      }
      
      //Mise à jour de notre document dans la base
      $this->m_ctrl->socdiscoo()->replaceDocument($subj,false,"ClinicalData");
      
      $str = "bocdiscoo->addSubjectStatus() Un FlagValue non trouvé a été créé/restauré sur un SubjectData (".__METHOD__.")";
      $this->addLog($str,INFO);
    }
  }

  private function checkFormConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)
  {
    $this->addLog("bocdiscoo->checkFormConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID)", TRACE);

    //Boucle sur les ItemDatas ayant un ItemDef contenant un FormalExpression
    $query = "
        let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        for \$ItemGroupData in \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                            /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey' and @TransactionType!='Remove']
                                            /odm:ItemGroupData[@TransactionType!='Remove']
        let \$ItemGroupOID := \$ItemGroupData/@ItemGroupOID
        let \$ItemGroupRepeatKey := \$ItemGroupData/@ItemGroupRepeatKey
        for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
          let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
          let \$ItemData := \$ItemDatas[last()]
          let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
          where exists(\$ItemDef/odm:RangeCheck) and \$ItemData/string()!=''
          return
            for \$RangeCheck at \$Position in \$ItemDef/odm:RangeCheck
            return
              <Control ItemOID='{\$ItemDef/@OID}'
                       ItemGroupRepeatKey='{\$ItemGroupRepeatKey}'
                       Position='{\$Position}'
                       ItemGroupOID='{\$ItemGroupOID}'
                       AuditRecordID='{\$ItemData/@AuditRecordID}'
                       Name='{\$ItemDef/@Name}'
                       SoftHard='{\$RangeCheck/@SoftHard}'
                       ErrorMessage='{\$RangeCheck/odm:ErrorMessage/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                       FormalExpression='{\$RangeCheck/odm:FormalExpression[@Context='XQuery']/string()}'
                       FormalExpressionDecode='{\$RangeCheck/odm:FormalExpression[@Context='XQueryDecode']/string()}'
                       Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/text()}'/>
        ";

    try{
      $ctrls = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    $errors = array();
    
    $macros = $this->getMacros($SubjectKey);
    
    foreach($ctrls as $ctrl)
    {
      $testXQuery = $macros . $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl);
      try{
        $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery);
      }catch(xmlexception $e){
        //L'erreur est probablement liée à l"ecriture du contrôle contenu dans les metadatas,
        //ainsi on présente cela d'une façon élégante à l'utilsateur. On conserve la notification par e-mail,
        //pour rectifier le tir.
        $str = "Consistency : Erreur du controle : " . $e->getMessage() . " " . $testXQuery;
        $this->addLog($str,ERROR);
      }

      $this->addLog("bocdiscoo->checkFormConsistency() Control[{$StudyEventOID}][{$FormOID}][{$ctrl['ItemGroupOID']}][{$ctrl['ItemGroupRepeatKey']}]['{$ctrl['ItemOID'] }'] => Result=" . $ctrlResult[0]->Result, INFO);
      if($ctrlResult[0]->Result=='false'){
        if($ctrl['SoftHard']=="Hard"){
          $type = 'HC';
        }else{
          $type = 'SC';
        }
        //On doit passer par un eval pour gérer les décodes multiples, que l'on passe en param de la func sprintf()
        $nbS = substr_count($ctrl['ErrorMessage'],"%s");
        if($nbS>0){
          $tblParams = explode(' ',$ctrlResult[0]->Decode . str_pad("",$nbS));
          $tblParams = str_replace("¤", " ", $tblParams); //restauration des espaces ' ' substitués (par des '¤', cf getXQueryConsistency())
          $ctrl['ErrorMessage'] = str_replace("\"","'",$ctrl['ErrorMessage']);
          $cmdEval = "\$desc = sprintf(\"".$ctrl['ErrorMessage']."\",\"".implode('","',$tblParams)."\");";
          eval($cmdEval);
        }else{
          //Ici, pas de decode, on affiche simplement le message d'erreur
          $desc = $ctrl['ErrorMessage'];
        }
        $this->addLog("message = $desc",INFO);  
        $errors[] = array( 
                           'Description' => $desc,
                           'Title' => $ctrl['Title'],
                           'Type' => $type,
								           'ItemOID' => $ctrl['ItemOID'],
								           'ItemGroupOID' => $ctrl['ItemGroupOID'],
								           'ItemGroupRepeatKey' => $ctrl['ItemGroupRepeatKey'],
								           'Position' => $ctrl['Position'],
								           'ContextKey' => $ctrlResult[0]->ContextKey,
								           'Value' => $ctrlResult[0]->Value,
								           'Decode' => $ctrlResult[0]->DecodedValue
                         );
      }
    }

    //Enregistrement des queries en base
    $this->m_ctrl->boqueries()->updateQueries($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey,'C',$errors);  

    return $errors;
  }
  
  
  /*
  * Just a method to test a xQuery code
  * Called by boalixws  
  * @author: tpi
  */
  public function RunXQuery($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemOID,$Value=false,$ErrorMessage,$FormalExpression,$FormalExpressionDecode,$SoftHard)
  {
    $this->addLog("bocdiscoo->checkFormConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemOID,$Value,\$ErrorMessage,\$FormalExpression,\$FormalExpressionDecode,$SoftHard)", TRACE);
    
    $whereItemData = "";
    if($Value==false){
      $whereItemData = "where \$ItemData/string()!=''";
    }
    
    //Boucle sur les ItemDatas ayant un ItemDef contenant un FormalExpression
    $query = "
        let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        for \$ItemGroupData in \$SubjectData/odm:StudyEventData[@StudyEventOID=\"$StudyEventOID\" and @StudyEventRepeatKey=\"$StudyEventRepeatKey\"]
                                            /odm:FormData[@FormOID=\"$FormOID\" and @FormRepeatKey=\"$FormRepeatKey\" and @TransactionType!=\"Remove\"]
                                            /odm:ItemGroupData[@TransactionType!=\"Remove\"]
        let \$ItemGroupOID := \$ItemGroupData/@ItemGroupOID
        let \$ItemGroupRepeatKey := \$ItemGroupData/@ItemGroupRepeatKey
          let \$ItemOID := \"$ItemOID\"
          let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
          let \$ItemData := \$ItemDatas[last()]
          let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
          $whereItemData
          return
              <Control ItemOID=\"{\$ItemDef/@OID}\"
                       ItemGroupRepeatKey=\"{\$ItemGroupRepeatKey}\"
                       Position=\"-1\"
                       ItemGroupOID=\"{\$ItemGroupOID}\"
                       AuditRecordID=\"{\$ItemData/@AuditRecordID}\"
                       Name=\"{\$ItemDef/@Name}\"
                       SoftHard=\"$SoftHard\"
                       ErrorMessage=\"$ErrorMessage\"
                       FormalExpression=\"$FormalExpression\"
                       FormalExpressionDecode=\"$FormalExpressionDecode\"
                       Title=\"{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang=\"{$this->m_lang}\"]/text()}\"/>
        ";
    //return $query;
    try{
      $ctrls = $this->m_ctrl->socdiscoo()->query($query);
      //return $ctrls;
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    $errors = array();
    
    $macros = $this->getMacros($SubjectKey);
    
    foreach($ctrls as $ctrl)
    {
      $testXQuery = $macros . $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl,$Value);
      //return $testXQuery;
      try{
        $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery);
        //return $ctrlResult;
      }catch(xmlexception $e){
        //L'erreur est probablement liée à l"ecriture du contrôle contenu dans les metadatas,
        //ainsi on présente cela d'une façon élégante à l'utilsateur. On conserve la notification par e-mail,
        //pour rectifier le tir.
        $str = "xQuery error : " . $e->getMessage() . " " . $testXQuery;
        $this->addLog($str,ERROR);
        //Have to return the message !
        return $str;
      }

      $this->addLog("bocdiscoo->checkFormConsistency() Control[{$StudyEventOID}][{$FormOID}][{$ctrl['ItemGroupOID']}][{$ctrl['ItemGroupRepeatKey']}]['{$ctrl['ItemOID'] }'] => Result=" . $ctrlResult[0]->Result, INFO);
      if($ctrlResult[0]->Result=='false'){
        if($ctrl['SoftHard']=="Hard"){
          $type = 'HC';
        }else{
          $type = 'SC';
        }
        //On doit passer par un eval pour gérer les décodes multiples, que l'on passe en param de la func sprintf()
        $nbS = substr_count($ctrl['ErrorMessage'],"%s");
        if($nbS>0){
          $tblParams = explode(' ',$ctrlResult[0]->Decode . str_pad("",$nbS));
          $tblParams = str_replace("¤", " ", $tblParams); //restauration des espaces ' ' substitués (par des '¤', cf getXQueryConsistency())
          $ctrl['ErrorMessage'] = str_replace("\"","'",$ctrl['ErrorMessage']);
          $cmdEval = "\$desc = sprintf(\"".$ctrl['ErrorMessage']."\",\"".implode('","',$tblParams)."\");";
          eval($cmdEval);
        }else{
          //Ici, pas de decode, on affiche simplement le message d'erreur
          $desc = $ctrl['ErrorMessage'];
        }
        $this->addLog("message = $desc",INFO);
        $errors[] = array( 
                           'Description' => $desc,
                           'Title' => $ctrl['Title'],
                           'Type' => $type,
								           'ItemOID' => $ctrl['ItemOID'],
								           'ItemGroupOID' => $ctrl['ItemGroupOID'],
								           'ItemGroupRepeatKey' => $ctrl['ItemGroupRepeatKey'],
								           'Position' => $ctrl['Position'],
								           'ContextKey' => $ctrlResult[0]->ContextKey,
								           'Value' => $ctrlResult[0]->Value,
								           'Decode' => $ctrlResult[0]->DecodedValue
                         );
      }
    }
    
    if(count($errors)==0 && $Value===false){
      //test if value to test is existing
      $value = $this->getValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,"","",$ItemOID,"");
      if($value=="") return "No error. Value not found or empty in the CRF.";
    }
    
    return $errors;
  }
  
/*
@desc Verification que les données de l'itemgroup respecte bien le format défini dans les metadata
@param string $MetaDataVersion version des MetaDatas à utiliser
@param string $ItemGroupOID ItemGroupOID de l'itemgroup
@param string $ItemGroupRepeatKey ItemGroupRepeatKey permet l'extraction des données du formulaire
@param array $formVars données recues en $_POST
@return array tableau des erreurs rencontrées
@author wlt 
*/
  function checkItemGroupDataSanity($SubjectKey,$MetaDataVersion,$ItemGroupOID,$ItemGroupRepeatKey,$formVars)
  {
    $this->addLog("bocdiscoo->checkItemGroupDataSanity($SubjectKey,$MetaDataVersion,$ItemGroupOID,$ItemGroupRepeatKey,formVars)",INFO);

    $Form = array();
    $errors = array();
	
    //on boucle sur les variables de notre formulaire soumis
    foreach($formVars as $key=>$value)
    {
		  //Extraction de l'oid
      $varParts = explode("_",$key);
      $ItemOID = str_replace("@",".",$varParts[count($varParts)-2]);
      //on ne garde que les variables ayant réellement une valeur, et pas les type dates que nous ajoutons plus loin, mais recomposées
      if($value!="" && !in_array($varParts[1],array('dd','mm','yy')) ){
        $Form["$ItemOID"] = $value;
      }
    }

    //on se base sur le patient $SubjectKey pour utiliser les metadatas correspondantes,
    //on requete ici les variables de notre FormOID
    $query = "
    let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='$MetaDataVersion']
    let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID='$ItemGroupOID']
    return
    <ItemGroupDef>
    {
      for \$ItemRef in \$ItemGroupDef/odm:ItemRef
      let \$ItemOID := \$ItemRef/@ItemOID
      let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
      return
        <Item ItemOID='{\$ItemOID}'
              DataType='{\$ItemDef/@DataType}'
              Mandatory='{\$ItemRef/@Mandatory}'
              Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
        />
    }
    </ItemGroupDef>
    ";

    try{
      $results = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str= "Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    foreach($results as $ItemGroupDef)
    {
      foreach($ItemGroupDef as $Item)
      {
        $ItemOID = (string)($Item['ItemOID']);
        switch($Item['DataType'])
        {          
          case 'partialTime' : //format HH:MM
            $isBadFormated = false;
            $dt = $formVars["text_partialTime_" . str_replace(".","@",$Item['ItemOID'])  . "_$ItemGroupRepeatKey"]; 
            $tblDt = explode(":",$dt);
            if(!is_numeric($tblDt[0]) || !is_numeric($tblDt[1])){
              $isBadFormated = true;
            }else{
              if($tblDt[0] >= 24 || $tblDt[0] >= 60){
                $isBadFormated = true;
              }
            }           

            if($isBadFormated){
              $desc = lang('bad_time_format',$Item['Title']);
              $errors[] = array('desc' => $desc,'type' => 'badformat','ItemOID' => $ItemOID,'ItemGroupRepeatKey'=>$ItemGroupRepeatKey);
            }
            
            break;
          //Gestion particulière pour les type Date et PartialDate
          case 'partialDate' :
            $isBadFormated = false;
            $dd = $formVars["text_dd_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $mm = $formVars["text_mm_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $yy = $formVars["text_yy_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            
            if(!$isBadFormated){
              //Le vrai premimer check, c'est que si quelquechose est saisi çà doit être un nombre !
              if(($dd!="" && !is_numeric($dd)) || ($mm!="" && !is_numeric($mm)) || ($yy!="" && !is_numeric($yy))){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //Si le mois est saisi, l'année doit être saisie (et si le jour est saisi, le mois et l'année doivent être saisis)
              if(($mm!="" && $yy=="") || ($dd!="" && ($mm=="" || $yy==""))){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //Check obligatoire dans tous les cas : l'année (pour des histoires de mktime qui ne supporte pas des dates < 1901)
              if($yy!=""){
                if($yy<=1901 || $yy>date('Y')+5){
                  $isBadFormated = true;
                }else{
                  $Form["{$Item['ItemOID']}"] = "$yy";
                }
              }
            }
            
            if(!$isBadFormated){
              //Si on a les trois => vérification normale
              if($dd!="" && $mm!="" && $yy!=""){
                //Construction de la date saisie
                if(!@checkdate($mm,$dd,$yy)){
                  $isBadFormated = true;
                }else{
                  $Form["{$Item['ItemOID']}"] = "$yy-$mm-$dd";
                }
              }else{
                //Seulement le mois et l'année
                //Pour les dates, on fixe arbitrairement les bornes 1901 <=> année en cours + 5 ans
                if($mm!="" && $yy!=""){
                  if($mm>12 || $yy<=1901 || $yy>date('Y')+5){
                    $isBadFormated = true;
                  }else{
                    $Form["{$Item['ItemOID']}"] = "$yy-$mm";
                  }
                }
              }
            }
            
            if($isBadFormated){
              $desc = lang('bad_date_format',$Item['Title']);
              $errors[] = array('desc' => $desc,'type' => 'badformat','ItemOID' => $ItemOID,'ItemGroupRepeatKey'=>$ItemGroupRepeatKey);
            }

            break;

          case 'date' :
            $isBadFormated = false;
            $dd = $formVars["text_dd_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $mm = $formVars["text_mm_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $yy = $formVars["text_yy_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];

            if(!$isBadFormated){
              //Le vrai premimer check, c'est que si quelquechose est saisi çà doit être un nombre !
              if(($dd!="" && !is_numeric($dd)) || ($mm!="" && !is_numeric($mm)) || ($yy!="" && !is_numeric($yy))){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //Si l'un des paramètres est saisi, alors tous les paramètres doivent être saisis (date complète)
              if(($dd!="" || $mm!="" || $yy!="") && ($dd=="" || $mm=="" || $yy=="")){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //Check obligatoire dans tous les cas : l'année (pour des histoires de mktime qui ne supporte pas des dates < 1901)
              if($yy!=""){
                if($yy<=1901 || $yy>date('Y')+5){
                  $isBadFormated = true;
                }
              }
            }
            
            if(!$isBadFormated){
            //Si on a les trois => vérification normale
              if($dd!="" && $mm!="" && $yy!=""){
                //Construction de la date saisie
                if(!@checkdate($mm,$dd,$yy)){
                  $isBadFormated = true;
                }else{
                  $Form["{$Item['ItemOID']}"] = "$yy-$mm-$dd";
                }
              }
            }
            
            if($isBadFormated){
              $desc = lang('bad_date_format',$Item['Title']);
              $errors[] = array('desc' => $desc,'type' => 'badformat','ItemOID' => $ItemOID,'ItemGroupRepeatKey'=>$ItemGroupRepeatKey);
            }
            
            break;

          case 'float' :
            $int = $formVars["text_int_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $dec = $formVars["text_dec_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $val = $int .".".$dec;
            if(!is_numeric($val) && $val!='.'){
              $desc = lang('bad_numeric_format',$Item['Title']);
              $errors[] = array('desc' => $desc,'type' => 'badformat','ItemOID' => $ItemOID);
            }else{
              if($val!='.'){
                $Form["{$Item['ItemOID']}"] = $val;
              }
            }
            break;

          case 'integer' :
            $val = $formVars["text_integer_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            if( (!is_numeric($val) || strpos($val,".")!==false) && $val!=''){
              $desc = lang('bad_integer_format',$Item['Title']);
              $errors[] = array('desc' => $desc,'type' => 'badformat','ItemOID' => $ItemOID,'ItemGroupRepeatKey'=>$ItemGroupRepeatKey);
            }
            break;
        }
      }
    }

    $this->addLog("bocdiscoo->checkItemGroupDataSanity() => " . count($errors) . " erreurs",INFO);
    return $errors;
  }

/*
@desc verifie la présence des données marquées mandatory dans les metadatas
@author wlt
*/
  function checkMandatoryData($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey)
  {
    $this->addLog("bocdiscoo->checkMandatoryData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)", TRACE);

    $query = "
        let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        return
            <errors>
            {       
            for \$ItemGroupData in \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                                /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                                /odm:ItemGroupData[@TransactionType!='Remove']   
              let \$ItemGroupOID := \$ItemGroupData/@ItemGroupOID
              let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupOID]
              let \$ItemGroupRepeatKey := \$ItemGroupData/@ItemGroupRepeatKey
              where \$ItemGroupRepeatKey!='0' and \$ItemGroupDef/@Repeating='Yes' or \$ItemGroupDef/@Repeating='No'
              return                  
                 for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
                  let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
                  let \$ItemData := \$ItemDatas[last()]
                  let \$FlagValue := \$SubjectData/../odm:Annotations/odm:Annotation[@ID=\$ItemData/@AnnotationID]/odm:Flag/odm:FlagValue/string()
                  let \$ItemRef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupOID]/odm:ItemRef[@ItemOID=\$ItemOID]
                  let \$CollectionException := \$MetaDataVersion/odm:ConditionDef[@OID=\$ItemRef/@CollectionExceptionConditionOID]
                    where \$ItemRef/@Mandatory='Yes' and 
                          (count(\$ItemData)=0 or \$ItemData/string()='') and 
                          (\$FlagValue='Ø' or \$FlagValue='' or not(\$FlagValue) or \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]/@DataType='date')
                    return
                        <error ItemOID=\"{\$ItemOID}\"
                               FlagValue=\"{\$FlagValue}\"
                               ItemGroupOID=\"{\$ItemGroupOID}\"
                               ItemGroupRepeatKey=\"{\$ItemGroupRepeatKey}\"
                               AuditRecordID=\"{\$ItemData/@AuditRecordID}\"
                               Mandatory=\"{\$ItemRef/@Mandatory}\"
                               FormalExpression=\"{\$CollectionException/odm:FormalExpression[@Context='XQuery']/string()}\"
                               FormalExpressionDecode=\"{\$CollectionException/odm:FormalExpression[@Context='XQueryDecode']/string()}\"
                               Description=\"{\$CollectionException/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/text()}\"
                               Title=\"{\$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/text()}\">
                        </error>       
            }
            </errors>
        ";
    try{
      $errors = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    
    $macros = $this->getMacros($SubjectKey);

    //On boucle sur les erreurs pour executer les CollectionConditionException, 
    //Qui peuvent conduire à la suppression de l'erreur
    $tblRet = array();
    foreach($errors[0] as $error){
      $this->addLog("bocdiscoo->checkMandatoryData() : error=".$this->dumpRet($error),TRACE);
      
      //Position = 1 Car une seule query de type Mandatory est possible par Item
      //Type = M Pour Mandatory
      $errorToAdd = array(
                           "ItemOID" => (string)($error['ItemOID']),
                           "ItemGroupOID" => (string)($error["ItemGroupOID"]),
                           "ItemGroupRepeatKey" => (string)($error["ItemGroupRepeatKey"]),
                           "Description" => (string)($error["Description"]),
                           "Title" => (string)($error["Title"]),
                           "Position" => "0",
                           "Type" => "CM",
								           'ContextKey' => "",
								           'Value' => "",
								           'Decode' => ""                       
                         );       
      
      if($error["Description"]==""){
        $errorToAdd["Description"] = ($error["ItemGroupRepeatKey"]!="0"?"[line #".$error["ItemGroupRepeatKey"] . "] ":"") . $error["Title"] . " " . lang("is mandatory");  
      }
      
      $testExpr = "";
      if($error['FormalExpression']!=""){
        $testXQuery = $macros . $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$error);//-
        try{
          $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery);
        }catch(xmlexception $e){
          //L'erreur est probablement liée à l"ecriture du contrôle contenu dans les metadatas,
          //ainsi on présente cela d'une façon élégante à l'utilsateur. On conserve la notification par e-mail,
          //pour rectifier le tir.
          $str = "Mandatory : Erreur du controle : " . $e->getMessage() . " " . $testXQuery;
          $this->addLog($str,ERROR);
          $desc = "Misformated control on the {$error['ItemOID']} value. Notification was sent to administrator.";
          $errors["$desc"] = array( 'desc' => $desc,
                                    'type' => 'badformat',
  								                  'itemOID' => $error['ItemOID']);
        }
  
        $this->addLog("bocdiscoo->checkFormMandatory() Control[{$StudyEventOID}][{$FormOID}][{$error['ItemGroupOID']}][{$error['ItemGroupRepeatKey']}]['{$error['ItemOID'] }'] => Result=" . $ctrlResult[0]->Result, INFO);
        
        if($ctrlResult[0]->Result=='true'){
          //La CollectionException a joué on supprime l'erreur
          $this->addLog("bocdiscoo->checkFormMandatory() : " . $error['ItemOID'] . " n'est plus obligatoire",INFO);
        }else{
          //Extraction de(s) decode(s)
          //On doit passer par un eval pour gérer les décodes multiples, que l'on passe en param de la func sprintf()
          if(substr_count($error["Description"],"%s")>0){
            $tblParams = explode(' ',$ctrlResult[0]->Decode);
            $tblParams = str_replace("¤", " ", $tblParams); //restauration des espaces ' ' substitués (par des '¤', cf getXQueryConsistency())
            $error["Description"] = str_replace("\"","'",$error["Description"]);
            $cmdEval = "\$desc = sprintf(\"".$error["Description"]."\",\"".implode('","',$tblParams)."\");";
            eval($cmdEval);
            $errorToAdd["Description"] = $desc;
          }
          
          $errorToAdd["ContextKey"] = $ctrlResult[0]->ContextKey;
          $errorToAdd["Value"] = $ctrlResult[0]->Value;
          $errorToAdd["Decode"] = $ctrlResult[0]->DecodedValue;

          $tblRet[] = $errorToAdd;
        }
      }else{
        //Pas de CollectionException
        $tblRet[] = $errorToAdd;
      }
    }
    
    //Enregistrement des queries en base
    $this->m_ctrl->boqueries()->updateQueries($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey,'M',$tblRet);  
    
    return $tblRet;
  }

  //Copie d'un StudyEvent d'un patient vers un autre,
  //Attention valable uniquement pour les visites de type Repeating="Yes"
  function copyStudyEventData($SubjectKeySource,$SubjectKeyDest,$StudyEventOID,$incrementSERK=1)
  {
    $this->addLog("bocdiscoo->copyStudyEventData($SubjectKeySource,$SubjectKeyDest,$StudyEventOID)",INFO);

    //Recuperation de la visite à inserer
    $query = "
          let \$StudyEventData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKeySource']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID']
          return
            <StudyEventData xmlns='" . ODM_NAMESPACE . "' StudyEventOID='$StudyEventOID'>
              {
                \$StudyEventData/node()
              }
            </StudyEventData>
         ";

    try{
      $StudyEventData = $this->m_ctrl->socdiscoo()->query($query,false);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    //Ouverture du patient cible
    try{
      $subj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$SubjectKeyDest,false);
    }catch(xmlexception $e){
      $str= "Patient $SubjectKey non trouvé dans la base : " . $e->getMessage() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    //Recuperation du dernier StudyEventRepeatKey
    $query = "
          let \$StudyEventRepeatKey := max(collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKeyDest']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID']/@StudyEventRepeatKey)
          return
            <StudyEventRepeatKey max='{\$StudyEventRepeatKey}'/>
         ";

    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    $newRepeatKey = $result[0]['max'] + $incrementSERK;
    $StudyEventDataNode = $subj->ImportNode($StudyEventData->documentElement,true);
    $StudyEventDataNode->setAttribute("StudyEventOID","$StudyEventOID");
    $StudyEventDataNode->setAttribute("StudyEventRepeatKey","$newRepeatKey");

    //Insertion
    $xPath = new DOMXPath($subj);
    $xPath->registerNamespace("odm", ODM_NAMESPACE);

    //Recuperation des noeuds StudyEventData
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData");
    if($result->length>0){
      //C'est tout bon, on ajoute notre noeud
      $result->item(0)->parentNode->insertBefore($StudyEventDataNode,$result->item($result->length-1)->nextSibling);
    }else{
      //C'est la première visite, on l'ajoute
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='AE']");
      $result->item(0)->parentNode->insertBefore($StudyEventDataNode,$result->item($result->length-1)->nextSibling);
    }

    //Mise à jour de notre document dans la base
    $this->m_ctrl->socdiscoo()->replaceDocument($subj,false,"ClinicalData");

    return $newRepeatKey;
  }

  
  /*
  @desc Retourne le nombre d'items dans le noeud de niveau spécifié (items qui sont les derniers ajoutés et qui ne sont pas dans un ItemGroupData[@TransactionType='Remove']) = nombre de champs du CRF qui sont saisis (avec ou sans valeur)
  @author tpi
  */
  function countItems($SubjectKey,$StudyEventOID="",$StudyEventRepeatKey="",$FormOID="",$FormRepeatKey="",$ItemGroupOID="",$ItemGroupRepeatKey="")
  {
    $path = "";
    
    if($StudyEventOID!=""){
      $andStudyEventRepeatKey = "";
      if($StudyEventRepeatKey!=""){
        $andStudyEventRepeatKey = "and @StudyEventRepeatKey='$StudyEventRepeatKey'";
      }
      $path .= "odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]";
    }
    
    if($FormOID!=""){
      $andFormRepeatKey = "";
      if($FormRepeatKey!=""){
        $andFormRepeatKey = "and @FormRepeatKey='$FormRepeatKey'";
      }
      if($path!="") $path .= "/";
      $path .= "odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]";
    }
    
    if($ItemGroupOID!=""){
      $andItemGroupRepeatKey = "";
      if($ItemGroupRepeatKey!=""){
        $andItemGroupRepeatKey = "and @ItemGroupRepeatKey='$ItemGroupRepeatKey'";
      }
      if($path!="") $path .= "/";
      $path .= "odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' $andItemGroupRepeatKey]";
    }
    
    $query = "
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$value := count(\$SubjectData/$path/odm:*[@ItemOID])
      return
        <result value='{\$value}' />
    ";
    
    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    
    return $doc[0]['value'];
  }

/*  
@desc Création d'un nouveau patient, on copie le patient BLANK
      Retourne le n° (le subjectKey) du nouveau patient
*/
  function enrolNewSubject()
  {
    $newSubj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$this->config('BLANK_OID'));

    //Calcul du nouveau numéro patient
    $subjKey = $this->getNewPatientID($site);
    //zero padding
    $subjKey = sprintf($this->m_tblConfig["SUBJID_FORMAT"],$subjKey);

    //Mise à jour du patient BLANK
    $newSubj['FileOID'] = $subjKey;
    $newSubj['Description'] = "";
    $newSubj->ClinicalData->SubjectData['SubjectKey'] = $subjKey;

    //Enregistrement de notre patient
    $this->m_ctrl->socdiscoo()->addDocument($newSubj->asXML(),true,"ClinicalData");

    return $newSubj['FileOID'];
  }

  //Retourne pour un formulaire donnée un tableau des Items le composant, avec toutes les infos (cohérences, ...)
  function getAnnotedCRF($FormOID)
  {
    $this->addLog("bocdiscoo->getAnnotedCRF($FormOID)",INFO);
    $query = "let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='BLANK']/odm:ClinicalData/odm:SubjectData
              let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
              for \$ItemGroupRef in \$MetaDataVersion/odm:FormDef[@OID='$FormOID']/odm:ItemGroupRef
                let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupRef/@ItemGroupOID]
                for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                  let \$ConditionDef := \$MetaDataVersion/odm:ConditionDef[@OID=\$ItemRef/@CollectionExceptionConditionOID]
                  let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                  return
                    <Item OID='{\$ItemRef/@ItemOID}'
                          Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                          ConditionDesc='{\$ConditionDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                          ConditionFE='{\$ConditionDef/odm:FormalExpression/string()}'
                          CheckDesc='{\$ItemDef/odm:RangeCheck/odm:ErrorMessage/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                          CheckFE='{\$ItemDef/odm:RangeCheck/odm:FormalExpression/string()}'/>

             ";
    try{
    $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    return $doc;
  }
  
  //Retourne l'audit trail d'une variable
  public function getAuditTrail($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)
  {
    $this->addLog(__METHOD__."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)",INFO);
    
    
    //$SubjectKey correspond au nom du document dans le container ClinicalData
    $query = "
              declare function local:getLastValue(\$ItemData as node()*) as xs:string?
              {
                let \$v := '' (:car il nous faut un let :)
                return \$ItemData[last()]/string()
              };

              declare function local:getDecode(\$ItemData as node()*) as xs:string?
              {
                let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
                let \$value := local:getLastValue(\$ItemData)
                let \$CodeListOID := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemData/@ItemOID]/odm:CodeListRef/@CodeListOID
                return
                  if(\$CodeListOID)
                  then \$MetaDataVersion/odm:CodeList[@OID=\$CodeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()
                  else \$value
              };
        
              let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              let \$StudyEventData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
              let \$ItemGroupData := \$StudyEventData/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
              let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID='$ItemOID']
              
              return
                    (: audit trail de l'item \$ItemOID :)
                    <ItemGroupDataAT>
              			{
                      for \$ItemData in \$ItemDatas
                      let \$AuditRecord := \$SubjectData/../odm:AuditRecords/odm:AuditRecord[@ID=\$ItemData/@AuditRecordID]
                      let \$Annotation := \$SubjectData/../odm:Annotations/odm:Annotation[@ID=\$ItemData/@AnnotationID]
              				order by \$ItemData/@ItemOID,\$ItemData/@AuditRecordID descending
                      return <ItemDataAT ItemOID='{\$ItemData/@ItemOID}'
                                         Value='{\$ItemData/string()}'
                                         AuditRecordID='{\$ItemData/@AuditRecordID}'
                                         TransactionType='{\$ItemData/@TransactionType}'>
                                    <AuditRecord User='{\$AuditRecord/odm:UserRef/@UserOID}'
                                                 Location='{\$AuditRecord/odm:LocationRef/@LocationOID}'
                                                 Date='{\$AuditRecord/odm:DateTimeStamp/string()}'
                                                 Reason='{\$AuditRecord/odm:ReasonForChange/string()}'/>
                                    <Annotation FlagValue='{\$Annotation/odm:Flag/odm:FlagValue/string()}'
                                                Comment='{\$Annotation/odm:Comment/string()}'/>
                             </ItemDataAT>
              			}</ItemGroupDataAT>
              ";

    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    }catch(xmlexception $e){
      $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    $this->addLog(__METHOD__." return ".$doc->saveXML(),TRACE);
    
    return $doc;
  }

  /*
  @desc retourne une codelist des metadata sous la forme d'un array php
        utilisé dans la classe boimport
  @author wlt
  */
  function getCodelist($metaDataVersionOID,$CodeListOID,$lang)
  {
    $query = "let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='$metaDataVersionOID']
              let \$CL := \$MetaDataVersion/odm:CodeList[@OID='$CodeListOID']
              for \$CLItem in \$CL/odm:CodeListItem
              return
                <CodeListItem CodedValue='{\$CLItem/@CodedValue}'> 
                  <Decode>{\$CLItem/odm:Decode/odm:TranslatedText[@xml:lang='$lang']/string()}</Decode>
                </CodeListItem>             
             ";  
    
    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    
    $tblCodeListItem = array();
    foreach($result as $clitem){
      $tblCodeListItem[(string)$clitem['CodedValue']] = (string)$clitem->Decode;  
    }
    
    return $tblCodeListItem;        
  }
  
  //Retourne le libellé de la valeur actuelle d'une variable donnée
  public function getDecodedValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)
  {
    $andStudyEventRepeatKey = "";
    if($StudyEventRepeatKey!=""){
      $andStudyEventRepeatKey = "and @StudyEventRepeatKey='$StudyEventRepeatKey'";
    }
    $andFormRepeatKey = "";
    if($FormRepeatKey!=""){
      $andFormRepeatKey = "and @FormRepeatKey='$FormRepeatKey'";
    }
    $andItemGroupRepeatKey = "";
    if($ItemGroupRepeatKey!=""){
      $andItemGroupRepeatKey = "and @ItemGroupRepeatKey='$ItemGroupRepeatKey'";
    }
    
    //L'audit trail engendre plusieurs ItemData avec le même ItemOID, ce qui nous oblige
    //pour chaque item à rechercher le dernier en regardant l'attribut AuditRecordID qui est le plus grand, et ce pour chaque item
    $query = "
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$v := ''
        return \$ItemData[last()]/string()
      };
      
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$value := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType))]/odm:*[@ItemOID='$ItemOID'])
      let \$MetaDataVersion := doc(concat('MetaDataVersion/',\$SubjectData/../@MetaDataVersionOID))/odm:ODM/odm:Study/odm:MetaDataVersion
      let \$codeListOID := \$MetaDataVersion/odm:ItemDef[@OID='$ItemOID']/odm:CodeListRef/@CodeListOID
      let \$decodedValue := \$MetaDataVersion/odm:CodeList[@OID=\$codeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()
      return
        <result value='{\$decodedValue}' />
    ";
    
    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    return (string)$doc[0]['value'];
  }
  
  /*
  *@desc Retourne un tableau associatif de descriptions des visites, formulaires et itemgroups définis dans les metadatas
  *@return  array(
  *            $MetaDataVersion => array(  
  *                                  "StudyEventDef" => array(
  *                                                        $OID => "Libellé de la visite", 
  *                                                        $OID => "Libellé de la visite", ... 
  *                                                     ), 
  *                                  "FormDef" => array(
  *                                                        $OID => "Libellé du formulaire", 
  *                                                        $OID => "Libellé du formulaire", ... 
  *                                                     ), 
  *                                  "ItemGroupDef" => array(
  *                                                        $OID => "Libellé de l'itemgroup", 
  *                                                        $OID => "Libellé de l'itemgroup", ... 
  *                                                     )
  *                               )  
  *           )
  * @author tpi  
  */        
  public function getDescriptions($MetaDataVersionOID){
  /*
    $descriptions = array(
                     $MetaDataVersionOID => array(
                                               "StudyEventDef" => array(),
                                               "FormDef" => array(),
                                               "ItemGroupDef" => array()
                                            )
                   );
    */
    
    $query = "
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='$MetaDataVersionOID']
                let \$StudyEventDefs := \$MetaDataVersion/odm:StudyEventDef
                let \$FormDefs := \$MetaDataVersion/odm:FormDef
                let \$ItemGroupDefs := \$MetaDataVersion/odm:ItemGroupDef
                return
                  <Descriptions>
                    <StudyEventDefs>
                      {
                        for \$StudyEventDef in \$StudyEventDefs
                        let \$OID := \$StudyEventDef/@OID
                        let \$TranslatedText := \$StudyEventDef/odm:Description/odm:TranslatedText[@xml:lang='". $this->m_lang ."']/string()
                        return
                          <StudyEventDef OID='{\$OID}'>{\$TranslatedText}</StudyEventDef>
                      }
                    </StudyEventDefs>
                    <FormDefs>
                      {
                        for \$FormDef in \$FormDefs
                        let \$OID := \$FormDef/@OID
                        let \$TranslatedText := \$FormDef/odm:Description/odm:TranslatedText[@xml:lang='". $this->m_lang ."']/string()
                        return
                          <FormDef OID='{\$OID}'>{\$TranslatedText}</FormDef>
                      }
                    </FormDefs>
                    <ItemGroupDefs>
                      {
                        for \$ItemGroupDef in \$ItemGroupDefs
                        let \$OID := \$ItemGroupDef/@OID
                        let \$TranslatedText := \$ItemGroupDef/odm:Description/odm:TranslatedText[@xml:lang='". $this->m_lang ."']/string()
                        return
                          <ItemGroupDef OID='{\$OID}'>{\$TranslatedText}</ItemGroupDef>
                      }
                    </ItemGroupDefs>
                  </Descriptions>
            ";

    try{
      $sxe = $this->m_ctrl->socdiscoo("BLANK")->query($query,true);
    }catch(xmlexception $e){
      $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    
    foreach($sxe[0] as $Defs){
      foreach($Defs as $Def){
        $attributes = $Def->attributes();
        $descriptions["{$MetaDataVersionOID}"]["{$Def->getName()}"]["{$attributes['OID']}"] = (string)$Def;
      }
    }
    
    return $descriptions;
  }
   
 /*
 *@desc retourne la liste des formulaires existant dans le CRF d'un patient
 *@param boolean bNotEmpty : retourne uniquement les FormData contenant un ou des ItemData*
 *@return array
 *@author tpi
 */
  public function getFormDatas($SubjectKey,$StudyEventOID="",$StudyEventRepeatKey="",$bNotEmpty=false)
  {
    $this->addLog("bocdiscoo->getFormDatas($SubjectKey,$StudyEventOID,$StudyEventRepeatKey)",INFO);
    
    $whereStudy = "";
    if($StudyEventOID!=""){
      $whereStudy .= "@StudyEventOID='$StudyEventOID'";
    }
    if($StudyEventOID!=""){
      if($whereStudy!="") $whereStudy .= " and ";
      $whereStudy .= "@StudyEventRepeatKey='$StudyEventRepeatKey'";
    }
    if($whereStudy!="") $whereStudy = "[".$whereStudy."]";
    
    $whereNotEmpty = "";
    if($bNotEmpty){
      $whereNotEmpty = "[odm:ItemGroupData/odm:*[@ItemOID]]";
    }
    
    $query = "
              let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              for \$FormData in \$SubjectData/odm:StudyEventData$whereStudy/
                                                   odm:FormData$whereNotEmpty
              return
                <FormData
                  StudyEventOID='{\$FormData/../@StudyEventOID}'
                  StudyEventRepeatKey='{\$FormData/../@StudyEventRepeatKey}'
                  FormOID='{\$FormData/@FormOID}'
                  FormRepeatKey='{\$FormData/@FormRepeatKey}'/>
             ";

    try{
      $FormDatas = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : getItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey) => $str",FATAL);
      die($str);
    }
    return $FormDatas;
  }
    
  function getItemDataTypes($igoid, $subj)
  {
              
    $query = "  let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$subj']/odm:ClinicalData/odm:SubjectData
                let \$Meta := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
                return
                    <Types>
                    {
                        for \$ItemRef in \$Meta/odm:ItemGroupDef[@OID='$igoid']/odm:ItemRef
                            let \$ItemDef := \$Meta/odm:ItemDef[@OID=\$ItemRef/@ItemOID]
                            return
                                <Item OID=\"{substring-after(\$ItemDef/@OID, '.')}\" Type=\"{\$ItemDef/@DataType}\" />
                    }
                    </Types>
                    
                ";
    try
    {
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }
    catch(xmlexception $e)
    {
      $str = "bocdiscoo->getItemDataTypes($igoid, $subj) Erreur de la requete : " . $e->getMessage() . " " . $query . "(". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    $doc = $doc[0];
    $res = array();
    foreach ($doc->children() as $type)
    {
        $field_name = (string)$type['OID'];
        $res[strtoupper($field_name)] = (string)$type['Type'];
    }
    return $res;
  }

  public function getItemGroupData($SubjectKey,$StudyEventOID,$FormOID,$ItemGroupOID,$RepeatKey)
  {
    $query = "
              let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              let \$ItemGroupData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID']/
                                                   odm:FormData[@FormOID='$FormOID']/
                                                   odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and (@ItemGroupRepeatKey='$RepeatKey' or not(@ItemGroupRepeatKey))]
              for \$ItemData in \$ItemGroupData/odm:*
              let \$MaxAuditRecordID := max(\$ItemGroupData/odm:*[@ItemOID=\$ItemData/@ItemOID]/string(@AuditRecordID))
              where \$ItemData/@AuditRecordID = \$MaxAuditRecordID
              return
                <Item OID='{\$ItemData/@ItemOID}'
                      Value='{\$ItemData/string()}'/>
             ";

    try{
    $ItemGroupData = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : getItemGroupData($SubjectKey,$StudyEventOID,$FormOID,$ItemGroupOID,$RepeatKey) => $str",FATAL);
      die($str);
    }
    return $ItemGroupData;
  }

/*
@modification wlt le 02/03/2011 : passage de public a private
                                  paramètres StudyEventOID,StudyEventRepeatKey,FormOID et FormRepeatKey rendus optionnels
              wlt le 03/03/2011 : Ajout du Status dans les données retournées
@author tpi, wlt
*/
  private function getItemGroupDatas($SubjectKey,$StudyEventOID="",$StudyEventRepeatKey="",$FormOID="",$FormRepeatKey="")
  {
    $this->addLog("bocdiscoo->getItemGroupDatas($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);
    $query = "
              let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              let \$FormData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey' or 
                                                                 '$StudyEventOID'='' and '$StudyEventRepeatKey'='']/
                                              odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey' or 
                                                           '$FormOID'='' and '$FormRepeatKey'='']
              for \$ItemGroupData in \$FormData/odm:ItemGroupData[@TransactionType!='Remove']
              return
                <ItemGroupData ItemGroupOID='{\$ItemGroupData/@ItemGroupOID}'
                               ItemGroupRepeatKey='{\$ItemGroupData/@ItemGroupRepeatKey}'
                               Status='{\$ItemGroupData/odm:Annotation/odm:Flag/odm:FlagValue/string()}'/>
             ";

    try{
    $ItemGroupData = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : getItemGroupData($SubjectKey,$StudyEventOID,$FormOID,$ItemGroupOID,$RepeatKey) => $str",FATAL);
      die($str);
    }
    return $ItemGroupData;
  }
  
  /* return the number of itemgroupdata for specified parameters - included Removed ItemGroupData
  */
  public function getItemGroupDataCount($SubjectKey,$StudyEventOID="",$StudyEventRepeatKey="",$FormOID="",$FormRepeatKey="")
  {
    $this->addLog("bocdiscoo->getItemGroupDataCount($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",TRACE);
    $query = "
              let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              let \$FormData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey' or 
                                                                 '$StudyEventOID'='' and '$StudyEventRepeatKey'='']/
                                              odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey' or 
                                                           '$FormOID'='' and '$FormRepeatKey'='']
               let \$countIG := count(\$FormData/odm:ItemGroupData)
              return
                <ItemGroupDatas CountIG='{\$countIG}'/>
             ";

    try{
    $ItemGroupDataCount = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "<html>xQuery error : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : getItemGroupDataCount($SubjectKey,$StudyEventOID,$FormOID,$ItemGroupOID,$RepeatKey) => $str",FATAL);
      die($str);
    }

    return (int)$ItemGroupDataCount[0]['CountIG'];
  }
   
  private function getMacros($SubjectKey){
    //On a plusieurs macros pour faciliter l'écriture des FormalExpression
    // 1°) getValue() => Retourne la valeur de l'ItemData courant
    // 2°) getValue('ItemOID') => Retourne la valeur d'un ItemData adjacent (dans le même ItemGroupData)
    // 3°) getValue('ItemGroupOID','ItemOID') => Retourne la valeur d'un ItemData du même formulaire, mais dans un ItemGroupData différent
    // 4°) getValue('FormOID','ItemGroupOID','ItemOID') => Même chose que le 3°) mais on peut jumper sur un autre Form
    // 5°) getValue('StudyEventOID','FormOID','ItemGroupOID','ItemOID') => Même chose que le 3°) mais on peut jumper sur une autre visite

    //!!!!!Attention!!!!! Note : pour le 3°,4° et 5°, on ne précise pas le RepeatKey, donc Attention à son utilisation
    $macros = "
        (:Pour comparer les dates partielles, on complète par convention avec des 01:)
        declare function local:fillPartialDate(\$partialDateValue as xs:string?) as xs:string
        {
          let \$length := string-length(\$partialDateValue)
          return
            if(\$length=7)
            then concat(\$partialDateValue,'-01')
            else
              if(\$length=4)
              then concat(\$partialDateValue,'-01-01')
              else
                if(\$length=0)
                then ''
                else \$partialDateValue
        };

        declare function local:fillAny(\$anyValue as xs:string?) as xs:string
        {
          let \$length := string-length(\$anyValue)
          return
            if(\$length=0)
            then ''
            else \$anyValue
        };

        declare function local:getValue(\$ItemData as node()*) as xs:string?
        {
          let \$v := '' (:car il nous faut un let :)
          (:Gestion particulière pour les PartialDate:)
          return
            if(\$ItemData/name()='ItemDataPartialDate')
            then local:fillPartialDate(\$ItemData[last()]/string())
            else
              if(\$ItemData/name()='ItemDataAny')
              then local:fillAny(\$ItemData[last()]/string())
              else \$ItemData[last()]/string()
        };

        declare function local:getValue(\$ItemData as node(),\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../odm:*[@ItemOID=\$ItemOID]
          return local:getValue(\$destItemData)
        };

        declare function local:getValue(\$ItemData as node(),\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getValue(\$destItemData)
        };

        declare function local:getValue(\$ItemData as node(),\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getValue(\$destItemData)
        };

        declare function local:getValue(\$ItemData as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getValue(\$destItemData)
        };

        declare function local:getValue(\$ItemData as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string, \$FormRepeatKey as xs:string, \$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID and @FormRepeatKey=\$FormRepeatKey]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getValue(\$destItemData)
        };

        declare function local:getRawValue(\$ItemData as node()*) as xs:string?
        {
          let \$v := '' (:car il nous faut un let :)
          return \$ItemData[last()]/string()
        };

        declare function local:getRawValue(\$ItemData as node(),\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../odm:*[@ItemOID=\$ItemOID]
          return local:getRawValue(\$destItemData)
        };

        declare function local:getRawValue(\$ItemData as node(),\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getRawValue(\$destItemData)
        };

        declare function local:getRawValue(\$ItemData as node(),\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getRawValue(\$destItemData)
        };

        declare function local:getRawValue(\$ItemData as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getRawValue(\$destItemData)
        };

        declare function local:getRawValue(\$ItemData as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string, \$FormRepeatKey as xs:string, \$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID and @FormRepeatKey=\$FormRepeatKey]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getRawValue(\$destItemData)
        };

        declare function local:getDecode(\$ItemData as node(),\$SubjectData as node(),\$MetaDataVersion as node(),\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getDecode(\$destItemData,\$SubjectData,\$MetaDataVersion)
        };

        declare function local:getDecode(\$ItemData as node(),\$SubjectData as node(),\$MetaDataVersion as node(),\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getDecode(\$destItemData,\$SubjectData,\$MetaDataVersion)
        };

        declare function local:getDecode(\$ItemData as node(),\$SubjectData as node(),\$MetaDataVersion as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getDecode(\$destItemData,\$SubjectData,\$MetaDataVersion)
        };

        declare function local:getDecode(\$ItemData as node(),\$SubjectData as node(),\$MetaDataVersion as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string, \$FormRepeatKey as xs:string, \$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID and @FormRepeatKey=\$FormRepeatKey]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getDecode(\$destItemData,\$SubjectData,\$MetaDataVersion)
        };

        declare function local:getDecode(\$ItemData as node(),\$SubjectData as node(),\$MetaDataVersion as node(),\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../odm:*[@ItemOID=\$ItemOID]
          return local:getDecode(\$destItemData,\$SubjectData,\$MetaDataVersion)
        };

        declare function local:getDecode(\$ItemData as node()*,\$SubjectData as node(),\$MetaDataVersion as node()) as xs:string?
        {
          let \$value := local:getValue(\$ItemData)
          let \$CodeListOID := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemData/@ItemOID]/odm:CodeListRef/@CodeListOID
          return
            if(\$CodeListOID)
            then \$MetaDataVersion/odm:CodeList[@OID=\$CodeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()
            else \$value
        };

        declare function local:compareDate(\$dt1 as xs:string,\$dt2 as xs:string) as xs:integer
        {
          let \$lenDt1 := string-length(\$dt1)
          let \$lenDt2 := string-length(\$dt2)
          return
            if(\$lenDt1=4 or \$lenDt2=4) then compare(substring(\$dt1,1,4),substring(\$dt2,1,4)) else
              if(\$lenDt1=7 or \$lenDt2=7) then compare(substring(\$dt1,1,7),substring(\$dt2,1,7)) else compare(\$dt1,\$dt2)              
        };

        declare function local:getMonth(\$month as xs:string) as xs:string
        {
          let \$months := ('January','February','March','April','May','June','July','August','September','October','November','December')
          return
            if(\$month!='') 
            then \$months[xs:integer(\$month)]
            else ''              
        };

        declare function local:DateISOtoFR(\$dt as xs:string*) as xs:string
        {
          let \$month := local:getMonth(substring(\$dt,6,2))
          return
            if (not(\$dt)) then
                ''
            else        
                if(string-length(\$dt)=10) then concat(substring(\$dt,9,2),'-',\$month,'-', substring(\$dt,1,4)) else
                    if(string-length(\$dt)=7) then concat(\$month,'-', substring(\$dt,1,4)) else
                      if(string-length(\$dt)=4) then substring(\$dt,1,4) else ''                
        };

        declare function local:getAnnotation(\$ItemData as node(),\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getAnnotation(\$destItemData)
        };

        declare function local:getAnnotation(\$ItemData as node(),\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getAnnotation(\$destItemData)
        };

        declare function local:getAnnotation(\$ItemData as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string,\$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getAnnotation(\$destItemData)
        };

        declare function local:getAnnotation(\$ItemData as node(),\$StudyEventOID as xs:string,\$FormOID as xs:string, \$FormRepeatKey as xs:string, \$ItemGroupOID as xs:string,\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../../../../odm:StudyEventData[@StudyEventOID=\$StudyEventOID]/odm:FormData[@FormOID=\$FormOID and @FormRepeatKey=\$FormRepeatKey]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]/odm:*[@ItemOID=\$ItemOID]
          return local:getAnnotation(\$destItemData)
        };

        declare function local:getAnnotation(\$ItemData as node(),\$ItemOID as xs:string) as xs:string?
        {
          let \$destItemData := \$ItemData/../odm:*[@ItemOID=\$ItemOID]
          return local:getAnnotation(\$destItemData)
        };
        
        declare function local:getAnnotation(\$ItemData as node()*) as xs:string?
        {
          let \$ClinicalData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData
          let \$AnnotationId := \$ItemData[last()]/@AnnotationID
          return
            if(\$AnnotationId)
              then \$ClinicalData/odm:Annotations/odm:Annotation[@ID=\$AnnotationId]/odm:Flag/odm:FlagValue/string()
              else ''
        };
        ";
    return $macros;
  }

  /*
  @desc retourne le prochain numero patient a utilisé - fonction utilisée dans saveItemGroupData
  @return string nouveau numero patient
  */
  protected function getNewPatientID()
  {   
    $query = "let \$SubjectsCol := collection('ClinicalData')
              let \$maxSubjId := max(\$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='". $this->config('BLANK_OID') ."']/@SubjectKey)
              return <MaxSubjId>{\$maxSubjId}</MaxSubjId>";   
    
    try
    {
      $Result = $this->m_ctrl->socdiscoo()->query($query, true);
    }
    catch(xmlexception $e)
    {
      $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query . " (". __METHOD__ .")";
      $this->addLog("bocdiscoo->getNewPatientID() Erreur : $str",FATAL);
      die($str);
    }
    
    if($ligneResult = $Result[0])
    {
      $subjKey = (string)$ligneResult + 1;
    }
    else
    {
        $subjKey = 1;
    }
    
    //HOOK => bocdiscoo_getNewPatientID_customSubjId
    $newSubjKey = $this->callHook(__FUNCTION__,"customSubjId",array($this));
    
    if($newSubjKey!=false){
      $subjKey = $newSubjKey;
    }
    
    return $subjKey;
  }

  //Retourne les formulaires composant une visite
  //On boucle ici sur les metadatas pour construire le squelette,
  //pour ensuite allez le remplir avec les données si elles sont présentes
  //On récupère également les codelits, les unités, l'audit Trail (option $includeAuditTrail), les élements traduits, ...
  function getStudyEventForms($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$includeAuditTrail=false,$paginateStart=0,$paginateEnd=0)
  {
    $this->addLog("bocdiscoo->getStudyEventForms($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);

    //Variables supplémentaires à récuperer, défini dans le fichier config
    $customQueryLet = "";
    $customQueryReturn = "";
    
    //if pagination is activated
    if($paginateEnd>0){
      $paginateWhere = "where \$pos gt $paginateStart and \$pos le $paginateEnd";
    }else{
      $paginateWhere = "";
    }
    
    if(isset($this->m_tblConfig['FORM_VAR'][$FormOID])){
      foreach($this->m_tblConfig['FORM_VAR'][$FormOID] as $key=>$col){
        $customQueryLet .= "let \$$key := \$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['SEOID']}' and @StudyEventRepeatKey='{$col['SERK']}']/
                                                          odm:FormData[@FormOID='{$col['FRMOID']}' and @FormRepeatKey='{$col['FRMRK']}']/
                                                          odm:ItemGroupData[@ItemGroupOID='{$col['IGOID']}' and @ItemGroupRepeatKey='{$col['IGRK']}']/
                                                          odm:*[@ItemOID='{$col['ITEMOID']}'][last()]
                    ";
      $customQueryReturn .= " $key ='{\$$key}' ";
      }
    }
    
    //xquery de récupération de l'audit trail
    $queryAT = "<ItemGroupDataAT />";
    if($includeAuditTrail){
      $queryAT = "          <ItemGroupDataAT>
                      			{
                              for \$ItemData in \$ItemGroupDatas/odm:*[@ItemOID=\$ItemOID]
                              let \$AuditRecord := \$SubjectData/../odm:AuditRecords/odm:AuditRecord[@ID=\$ItemData/@AuditRecordID]
                              let \$Annotation := \$SubjectData/../odm:Annotations/odm:Annotation[@ID=\$ItemData/@AnnotationID]
                      				order by \$ItemData/@ItemOID,\$ItemData/@AuditRecordID descending
                              return <ItemDataAT ItemOID='{\$ItemData/@ItemOID}'
                                               Value='{\$ItemData/string()}'
                                               AuditRecordID='{\$ItemData/@AuditRecordID}'
                                               TransactionType='{\$ItemData/@TransactionType}'>
                                        <AuditRecord User='{\$AuditRecord/odm:UserRef/@UserOID}'
                                                     Location='{\$AuditRecord/odm:LocationRef/@LocationOID}'
                                                     Date='{\$AuditRecord/odm:DateTimeStamp/string()}'
                                                     Reason='{\$AuditRecord/odm:ReasonForChange/string()}'/>
                                        <Annotation FlagValue='{\$Annotation/odm:Flag/odm:FlagValue/string()}'
                                                    Comment='{\$Annotation/odm:Comment/string()}'/>
                                     </ItemDataAT>
                      			}</ItemGroupDataAT>";
    }
    
    //$SubjectKey correspond au nom du document dans le container ClinicalData
    $query = "
              declare function local:getDecode(\$ItemData as node()*,\$SubjectData as node(),\$MetaDataVersion as node()) as xs:string?
              {
                let \$value := \$ItemData[last()]
                let \$CodeListOID := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemData/@ItemOID]/odm:CodeListRef/@CodeListOID
                return
                  if(\$CodeListOID)
                  then \$MetaDataVersion/odm:CodeList[@OID=\$CodeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()
                  else \$value
              };
        
              let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              let \$StudyEventData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
              let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
              let \$BasicDefinitions := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:BasicDefinitions[../odm:MetaDataVersion/@OID=\$SubjectData/../@MetaDataVersionOID]

              let \$SiteId := \$SubjectData/odm:StudyEventData[@StudyEventOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['SEOID']}' and @StudyEventRepeatKey='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['SERK']}']/
                                                        odm:FormData[@FormOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['FRMOID']}' and @FormRepeatKey='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['FRMRK']}']/
                                                        odm:ItemGroupData[@ItemGroupOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['IGOID']}' and @ItemGroupRepeatKey='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['IGRK']}']/
                                                        odm:ItemDataString[@ItemOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['ITEMOID']}'][last()]
              $customQueryLet
              return
                <StudyEvent OID='$StudyEventOID'
                            StudyEventRepeatKey='$StudyEventRepeatKey'
                            MetaDataVersionOID='{\$SubjectData/../@MetaDataVersionOID}'
                            SubjectKey='$SubjectKey'
                            SiteId='{\$SiteId}'
                            $customQueryReturn
                            Title='{\$MetaDataVersion/odm:StudyEventDef[@OID='$StudyEventOID']/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                {
                (: ici on ne recupère qu'un seul Form :)
                for \$FormRef in \$MetaDataVersion/odm:StudyEventDef[@OID='$StudyEventOID']/odm:FormRef[@FormOID='$FormOID']
                let \$FormDef := \$MetaDataVersion/odm:FormDef[@OID='$FormOID']
                return
                  <Form OID='$FormOID'
                        FormRepeatKey='$FormRepeatKey'
                        Repeating='{\$FormDef/@Repeating}'
                        Title='{\$FormDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                  {
                    for \$ItemGroupRef in \$FormDef/odm:ItemGroupRef
                    let \$ItemGroupOID := \$ItemGroupRef/@ItemGroupOID
                    let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupOID]
                    let \$ItemGroupDatas := \$StudyEventData/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                                            /odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID and @TransactionType!='Remove']
                    return
                      <ItemGroup OID='{\$ItemGroupOID}'
                                 Title='{\$ItemGroupDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                 Repeating='{\$ItemGroupDef/@Repeating}'>
                      {
                        (:Insertion des MetaData de l'item en cours, ainsi que de la dernière valeur saisie:)
                        for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                        let \$ItemOID := \$ItemRef/@ItemOID
                        let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
                        return
                          <Item OID='{\$ItemOID}'
                                Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                DataType='{\$ItemDef/@DataType}'
                                Length='{\$ItemDef/@Length}'
                                SignificantDigits='{\$ItemDef/@SignificantDigits}'
                                Mandatory='{\$ItemRef/@Mandatory}'
                                Role='{\$ItemRef/@Role}'
                                CollectionExceptionConditionOID='{\$ItemRef/@CollectionExceptionConditionOID}'>
                            <CodeList OID='{\$ItemDef/odm:CodeListRef/@CodeListOID}'>
                            {
                              for \$CodeListItem in \$MetaDataVersion/odm:CodeList[@OID=\$ItemDef/odm:CodeListRef/@CodeListOID]/*
                              return
                                <CodeListItem CodedValue='{\$CodeListItem/@CodedValue}'
                                              Decode='{\$CodeListItem/odm:Decode/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                </CodeListItem>
                            }</CodeList>
                            <MeasurementUnit>
                            {
                              for \$MeasurementUnitItem in \$BasicDefinitions/odm:MeasurementUnit[@OID=\$ItemDef/odm:MeasurementUnitRef/@MeasurementUnitOID]
                              return
                                <MeasurementUnitItem OID='{\$MeasurementUnitItem/@MeasurementUnitOID}'
                                                     Symbol='{\$MeasurementUnitItem/odm:Symbol/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                </MeasurementUnitItem>
                            }
                            </MeasurementUnit>
                            (: audit trail de l'item \$ItemOID :)
                            $queryAT
                          </Item>
                      }
                      {
                        if(count(\$StudyEventData/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID])=0)
                        then
                            <ItemGroupData
                                ItemGroupOID='{\$ItemGroupOID}'
                                ItemGroupRepeatKey='0'>
                            </ItemGroupData>    
                        else
                          for \$ItemGroupData at \$pos in \$StudyEventData/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]
                          $paginateWhere
                          return
                            <ItemGroupData
                                ItemGroupOID='{\$ItemGroupData/@ItemGroupOID}'
                                ItemGroupRepeatKey='{\$ItemGroupData/@ItemGroupRepeatKey}'
                                TransactionType='{\$ItemGroupData/@TransactionType}'>
                            {
                              for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
                              let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
                              let \$ItemData := \$ItemDatas[last()]
                              let \$ItemDataDecode := local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion)
                              let \$Annotation := \$SubjectData/../odm:Annotations/odm:Annotation[@ID=\$ItemData/@AnnotationID]
                              let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
                              return
                                <ItemData OID='{\$ItemOID}'
                                      Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                      DataType='{\$ItemDef/@DataType}'
                                      Length='{\$ItemDef/@Length}'
                                      TransactionType='{\$ItemData/@TransactionType}'
                                      Value='{\$ItemData/string()}'
                                      Decode='{\$ItemDataDecode}'>
                                      <Annotation FlagValue='{\$Annotation/odm:Flag/odm:FlagValue/string()}'
                                                  Comment='{\$Annotation/odm:Comment/string()}'/>
                                </ItemData>
                            }
                            </ItemGroupData>
                      }
                      </ItemGroup>
                  }
                  </Form>
                }</StudyEvent>
              ";

    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    }catch(xmlexception $e){
      $str = "bocdiscoo->getStudyEventForms() Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    $this->addLog("bocdiscoo->getStudyEventForms() return ".$doc->saveXML(),TRACE);
    //error_log(print_r($doc->saveXML(),true),3,"dumpDoc.xml");
    return $doc;
  }

  //Retourne la liste des patients
  //Un paramètre supplémentaire permet de filtrer la liste
  //SiteList : liste des centres
  function getSubjectList($siteList,$whereCondition = "",$orderBy="\$SubjectData/@SubjectKey ascending")
  {
    $this->addLog("bocdiscoo->getSubjectList($siteList,$whereCondition)",TRACE);
    if($siteList!=""){
     $where = "";
     foreach(explode(",", $siteList) as $siteId)
     {
      if($where!="") $where .= " or";
       $where .= " \$SiteId = ('$siteId')";
     }
     $where = "where (". $where .") and \$SiteId!=''";
    }else{
     $where = "";
    }

    if($where!="" && $whereCondition!="" ){
      $where .= " and $whereCondition";
    }else{
      if($whereCondition!=""){
        $where .= "where $whereCondition";
      }
    }

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
                let \$SubjectsCol := collection('ClinicalData')
                for \$SubjectData in \$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData
				        let \$FileOID := \$SubjectData/../../@FileOID
                ";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      $query .= "let \$col$key := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['Value']['SEOID']}' and @StudyEventRepeatKey='{$col['Value']['SERK']}']/
                                                        odm:FormData[@FormOID='{$col['Value']['FRMOID']}' and @FormRepeatKey='{$col['Value']['FRMRK']}']/
                                                        odm:ItemGroupData[@ItemGroupOID='{$col['Value']['IGOID']}' and @ItemGroupRepeatKey='{$col['Value']['IGRK']}']/
                                                        odm:*[@ItemOID='{$col['Value']['ITEMOID']}'])
                ";
    }            

    $query .= " $where
                order by $orderBy
                return 
                  <subj fileOID='{\$FileOID}'";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      $query .= " col$key ='{\$col$key}' ";
    } 
    
    $query .= "/>";
      
    $query .= "}
          </subjs>";
    
    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    return $doc;
  }
  
/*
@desc retourne le statut FILLED / INCONSISTENT / PARTIAL / EMPTY d'un SubjectData
@author tpi
*/
  public function getSubjectStatus($SubjectKey)
  {
    $this->addLog("bocdiscoo->getSubjectStatus($SubjectKey)",INFO);

    $query = "let \$value := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:Annotation[odm:Flag/odm:FlagValue[@CodeListOID='CL.SSTATUS']]/odm:Flag/odm:FlagValue/string()
              return
                <result value='{\$value}' />";

    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->getSubjectStatus() Erreur : $str",TRACE);
      die($str);
    }
    
    return (string)$doc[0]['value'];
  }

  /* return visits list (StudyEvent) from metadata
  *@return SimpleXMLElement list of visits
  *@author wlt
  */
  public function getStudyEventList()
  {
    $query = "
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID='".$this->m_tblConfig['METADATAVERSION']."']
        for \$StudyEventRef in \$MetaDataVersion/odm:Protocol/odm:StudyEventRef
        let \$StudyEventDef := \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventRef/@StudyEventOID] 
        return
          <StudyEvent StudyEventOID='{\$StudyEventRef/@StudyEventOID}'
                      Title='{\$StudyEventDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
          />";
    try{
      $doc = $this->m_ctrl->socdiscoo("MetaDataVersion")->query($query);
    }catch(xmlexception $e){
      $str = "Error in xQuery : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    return $doc;                        
  }

  /**
   *Get all subject forms and visits, with lock status for each form
   @return array 
   *@author wlt     
   **/  
  function getSubjectTblForm($SubjectKey)
  {
    $this->addLog("bocdiscoo->getSubjectTblForm($SubjectKey)",INFO);
    $query = "     
        (: let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData :)
        
        let \$SubjectData := index-scan('SubjectODM', '$SubjectKey', 'EQ')/odm:ClinicalData/odm:SubjectData

        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        return
          <SubjectData>
          {
            for \$StudyEventData in \$SubjectData/odm:StudyEventData
            let \$StudyEventDef := \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventData/@StudyEventOID]
            return
              <StudyEventData StudyEventOID='{\$StudyEventData/@StudyEventOID}'
                              StudyEventRepeatKey='{\$StudyEventData/@StudyEventRepeatKey}'
                              Title='{\$StudyEventDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                              >
              {
                for \$FormRef in \$StudyEventDef/odm:FormRef
                let \$FormDef := \$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]
                let \$AllFormData := \$StudyEventData/odm:FormData[@FormOID=\$FormRef/@FormOID]
                return
                    if (count(\$AllFormData) > 0)
                    then
                        for \$FormData in \$AllFormData
                            let \$ItemGroupDataCount := count(\$FormData/odm:ItemGroupData[@TransactionType!='Remove'])
                            let \$ItemGroupDataCountEmpty := count(\$FormData/odm:ItemGroupData[@TransactionType!='Remove']/odm:Annotation/odm:Flag[odm:FlagValue/string()='EMPTY'])
                            let \$ItemGroupDataCountFrozen := count(\$FormData/odm:ItemGroupData[@TransactionType!='Remove']/odm:Annotation/odm:Flag[odm:FlagValue/string()='FROZEN'])
                            
                            return
                                <FormData FormOID='{\$FormRef/@FormOID}'
                                    FormRepeatKey='{\$FormData/@FormRepeatKey}'
                                    MetaDataVersionOID='{\$MetaDataVersion/@OID}'
                                    ItemGroupDataCount='{\$ItemGroupDataCount}'
                                    ItemGroupDataCountEmpty='{\$ItemGroupDataCountEmpty}'
                                    ItemGroupDataCountFrozen='{\$ItemGroupDataCountFrozen}'
                                    TransactionType='{\$FormData/@TransactionType}'
                                    Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                              </FormData>
                    else
                        <FormData FormOID='{\$FormRef/@FormOID}'
                                  FormRepeatKey='0'
                                  MetaDataVersionOID='{\$MetaDataVersion/@OID}'
                                  Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                  Status='EMPTY'
                        </FormData>
              }
              </StudyEventData>
          }
          </SubjectData>";

    try{
      //$start = microtime();
      $doc = $this->m_ctrl->socdiscoo()->query($query,false);
      //$stop = microtime();
      //echo "-duration-".($stop-$start);
      //$this->dumpPre($doc->saveXML());
    }catch(xmlexception $e){
      $str = "Error in xQuery : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    
    //Set StudyEvent and Form status according to associated queries
    
    //Loop through visits
    $visits = $doc->getElementsByTagName('StudyEventData');
    foreach($visits as $visit){
      $nbForm = 0;
      $nbFormEmpty = 0;
      $nbFormFrozen = 0;
      $StudyEventOID = $visit->getAttribute('StudyEventOID');
      $StudyEventRepeatKey = $visit->getAttribute('StudyEventRepeatKey');
      
      //Loop through forms
      foreach($visit->childNodes as $form){
        if($form->nodeType!=1) continue; //tpi, why are there some DOMText ?
        if(!$form->hasAttribute('Status')){
          $nbForm++;
          $FormOID = $form->getAttribute('FormOID');
          $FormRepeatKey = $form->getAttribute('FormRepeatKey');                 
          
          $ItemGroupDataCount = $form->getAttribute('ItemGroupDataCount');
          $ItemGroupDataCountEmpty = $form->getAttribute('ItemGroupDataCountEmpty');
          $ItemGroupDataCountFrozen = $form->getAttribute('ItemGroupDataCountFrozen');
          
          if($ItemGroupDataCount==$ItemGroupDataCountEmpty){
            $frmStatus = "EMPTY";
            $nbFormEmpty++;  
          }else{
            if($ItemGroupDataCount==$ItemGroupDataCountFrozen){
              $frmStatus = "FROZEN";
              $nbFormFrozen++;  
            }else{
              $frmStatus = $this->m_ctrl->boqueries()->getFormStatus($SubjectKey, $StudyEventOID ,$StudyEventRepeatKey, $FormOID, $FormRepeatKey);
            }           
          }
          $form->setAttribute("Status",$frmStatus);
        }
      }
      if($nbForm==$nbFormEmpty){
        $visitStatus = "EMPTY";
      }else{
        if($nbForm==$nbFormFrozen){
          $visitStatus = "FROZEN"; 
        }else{
          $visitStatus = $this->m_ctrl->boqueries()->getStudyEventStatus($SubjectKey,$StudyEventOID ,$StudyEventRepeatKey);
        }           
      }
      $visit->setAttribute("Status",$visitStatus);
    }
    
   return $doc;
  }

  //@desc Retourne la valeur actuelle d'une variable donnée
  //@optional param ItemGroupOID => if empty, look into the whole form
  //@optional param exlude => exclude some values from the research of the value : can be a string or an array of strings
  //@author tpi
  public function getValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID,$exclude=false)
  {
    $andStudyEventRepeatKey = "";
    if($StudyEventRepeatKey!=""){
      $andStudyEventRepeatKey = "and @StudyEventRepeatKey='$StudyEventRepeatKey'";
    }
    $andFormRepeatKey = "";
    if($FormRepeatKey!=""){
      $andFormRepeatKey = "and @FormRepeatKey='$FormRepeatKey'";
    }
    $andItemGroupOID = "";
    if($ItemGroupOID!=""){
      $andItemGroupOID = "and @ItemGroupOID='$ItemGroupOID'";
    }
    $andItemGroupRepeatKey = "";
    if($ItemGroupRepeatKey!=""){
      $andItemGroupRepeatKey = "and @ItemGroupRepeatKey='$ItemGroupRepeatKey'";
    }
    $whereIGString = "";
    if($exclude!==false){
      if(!is_array($exclude)) $exclude = array($exclude);
      foreach($exclude as $val){
        $whereIGString .= " and local:getLastValue(./odm:*[@ItemOID='$ItemOID'])!=\"$val\"";
      }
    }
    
    //L'audit trail engendre plusieurs ItemData avec le même ItemOID, ce qui nous oblige
    //pour chaque item à rechercher le dernier en regardant l'attribut AuditRecordID qui est le plus grand, et ce pour chaque item
    $query = "
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$v := ''
        return \$ItemData[last()]/string()
      };
      
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$value := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[true() $andItemGroupOID $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType)) $whereIGString]/odm:*[@ItemOID='$ItemOID'])
      return
        <result value='{\$value}' />
    ";
    
    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    return (string)$doc[0]['value'];
  }
  
  //Fonction de création de la xQuery à éxécuter dans le checkFormConsistency
  //Retourne la xQuery à partir d'un ctrl[FormalExpression,FormalExpressionDecode]
  //@optional param Value for RunXQuery
  function getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl,$Value=false){
    $testExpr = $ctrl['FormalExpression'];
    
    /*******************************************************************************/
    //gestions des différentes valeurs utilisées dans l'expression : getValue, getRawValue, count, getAnnotation, etc
    //on va ajouter des let $a := getValue(...), $b:=getRawValue()... en dbut d'expression
    //et remplacer les fonctions par la variable correspondante dans l'expression
    $this->iVarForExpressionUpdate = 0; //identifiant de variable créée
    $this->expressionForExpressionUpdate = ""; //code xQuery a ajouter au début de l'expression
    $this->handeldExpressionsForExpressionUpdate = array(); //liste des sous-expressions gérées (getValue, etc) pour ne pas leur affecter 2 fois une variable (et ne pas les éxécuter 2 fois, et ne pas enregistréer 2 fois le résultat en base, etc)
    $xQueryFunctions = array("getValue","getRawValue","getAnnotation","count","max","count"); //,"compareDate","DateISOtoFR","days-from-duration","getMonth"
    $expressions = array();
    foreach($xQueryFunctions as $xQueryFunction){
      $expressions[] = "((". $xQueryFunction .")(\([^\(\)]*\)))";
    }
    $testExpr = preg_replace_callback($expressions, array($this, "updateFormalExpression"), $testExpr);
    $testExpr = $this->expressionForExpressionUpdate ."[!]". $testExpr;
    //finalement on peut créé une clé, signature du contexte (fonction des valeurs testées dans l'expression)
    $contextKey = "";
    for($i=0; $i<$this->iVarForExpressionUpdate; $i++){
      if($contextKey!=""){
        $contextKey .= ",'|',";
      }
      $contextKey .= "xs:string(". "$". chr(97 + $i) .")";
    }
    if($contextKey!=""){
      $contextKey = "concat('',". $contextKey .")";
    }else{
      $contextKey = "''";
    }
    /*******************************************************************************/
    
    $testExpr = str_replace("getValue()","local:getValue(\$ItemData)",$testExpr);
    $testExpr = str_replace("getValue('","local:getValue(\$ItemData,'",$testExpr);

    //getAnnotation - XQuery
    $testExpr = str_replace("getAnnotation()","local:getAnnotation(\$ItemData)",$testExpr);
    $testExpr = str_replace("getAnnotation('","local:getAnnotation(\$ItemData,'",$testExpr);

    //getRawValue
    $testExpr = str_replace("getRawValue()","local:getRawValue(\$ItemData)",$testExpr);
    $testExpr = str_replace("getRawValue('","local:getRawValue(\$ItemData,'",$testExpr);

    $testExpr = str_replace("compareDate(","local:compareDate(",$testExpr);
    
    list($lets, $testExpr) = explode("[!]", $testExpr); //on, découpe en deux, les 2 portions seront à 2 endroits différents de la requête finale
 
    $testExprDecode = "";
    if($ctrl['FormalExpressionDecode']!=""){
      $testExprDecode = $ctrl['FormalExpressionDecode']; 
      //$testExprDecode = str_replace("local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion","replace(local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion",$testExprDecode);//deleted TPI 20110830
      $testExprDecode = str_replace("getDecode($","replace(local:getDecode($",$testExprDecode); //added TPI 20110830
      $testExprDecode = str_replace("getDecode()","replace(local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion),' ','¤')",$testExprDecode);
      $testExprDecode = str_replace("getDecode('","replace(local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion,'",$testExprDecode);

      //getRawValue dans les decodes
      $testExprDecode = str_replace("getRawValue()","replace(local:getRawValue(\$ItemData),' ','¤')",$testExprDecode);
      $testExprDecode = str_replace("getRawValue('","replace(local:getRawValue(\$ItemData,'",$testExprDecode);

      $testExprDecode = str_replace("getValue()","local:getValue(\$ItemData)",$testExprDecode);
      $testExprDecode = str_replace("getValue('","local:getValue(\$ItemData,'",$testExprDecode);

      $testExprDecode = str_replace("')","'),' ','¤')",$testExprDecode,$nbDecodeCall2);

      $testExprDecode = str_replace("compareDate(","local:compareDate(",$testExprDecode);   
      
      $testExprDecode = "
            <Decode>
            {
              $testExprDecode
            }
            </Decode>";
    }
    
    $ItemData = "\$FormData/odm:ItemGroupData[@ItemGroupOID='{$ctrl['ItemGroupOID']}' and @ItemGroupRepeatKey='{$ctrl['ItemGroupRepeatKey']}']/
                                   odm:*[@ItemOID='{$ctrl['ItemOID']}' and @AuditRecordID='{$ctrl['AuditRecordID']}']";
    if($Value!==false){ //If value is specified somewhere (eCRFDesigner via RunXQuery())
      $ItemData = "<ItemData ItemOID='{$ctrl['ItemOID']}' AuditRecordID='{$ctrl['AuditRecordID']}'>$Value</ItemData>";
      //TODO: recreate the full context : recreate FormData and ItemGroupData with this Specified ItemData included
    }
    
    //Création de la requête de test pour l'ItemData en cours
    $testXQuery = "
      let \$StudyEventOID := '$StudyEventOID'
      let \$StudyEventRepeatKey := '$StudyEventRepeatKey'
      let \$FormOID := '$FormOID'
      let \$FormRepeatKey := '$FormRepeatKey'
      let \$ItemGroupOID := '{$ctrl['ItemGroupOID']}'
      let \$ItemGroupRepeatKey := '{$ctrl['ItemGroupRepeatKey']}'
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      let \$StudyEventData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
      let \$FormData := \$StudyEventData/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
      let \$ItemGroupData := \$FormData/odm:ItemGroupData[@ItemGroupOID='{$ctrl['ItemGroupOID']}' and @ItemGroupRepeatKey='{$ctrl['ItemGroupRepeatKey']}']
      let \$ItemData := $ItemData
      let \$value := local:getRawValue(\$ItemData)
      let \$decode := local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion)
      $lets
      return
        <Ctrl>
          <ContextKey>
          {
            $contextKey
          }
          </ContextKey>
          <Value>
            {\$value}
          </Value>
          <DecodedValue>
            {\$decode}
          </DecodedValue>
          <Result>
          {
            $testExpr
          }
          </Result>
          $testExprDecode
        </Ctrl>";
    
    return $testXQuery;
  }

  public function removeFormData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)
  {
    $this->addLog("bocdiscoo->removeFormData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);
    
    /*
    $query = "replace value of node collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/@TransactionType 
              with 'Remove'";
    */
    //SEDNA 3.5 syntax (still not conform with the XQuery Update Facility 1.0)
    $query = "UPDATE REPLACE \$x in collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";

    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->removeItemGroupData() Erreur : $str",FATAL);
      die($str);
    }
  }

  public function removeItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)
  {
    $this->addLog("bocdiscoo->removeItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)",INFO);
    /*
    $query = "replace value of node collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']/@TransactionType 
              with 'Remove'";
    */
    //SEDNA 3.5 syntax (still not conform with the XQuery Update Facility 1.0)
    $query = "UPDATE REPLACE \$x in collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";

    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->removeItemGroupData() Erreur : $str",FATAL);
      die($str);
    }
  }

  /**
   *save into xml database the ItemGroupData
   *@return boolean true if data have been updated
   *                false if database update was unnecessary        
   *@author wlt                 
  **/
  function saveItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$formVars,$who,$where,$why,$fillst="",$bFormVarsIsAlreadyDecoded=false)
  {
    try
    {
      $this->addLog("bocdiscoo->saveItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$formVars,$who,$where,$why,$fillst)",INFO);
      $this->addLog("$formVars = " . $this->dumpRet($formVars),TRACE);
      
      //DomDocument of Subject
      try{
        $subj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$SubjectKey,false);
      }catch(xmlexception $e){
        $str= "(". __METHOD__ .") Patient $SubjectKey not found : " . $e->getMessage();
        $this->addLog($str,FATAL);
        die($str);
      }

      $xPath = new DOMXPath($subj);
      $xPath->registerNamespace("odm", ODM_NAMESPACE);

      //Add StudyEventData if needed
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']");
      if($result->length==0){
        $this->addLog("bocdiscoo->saveItemGroupData() Ajout de StudyEventData[@StudyEventOID='$StudyEventOID']",INFO);
        $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData");
        if($result->length==1){
          $StudyEventData = $subj->createElementNS(ODM_NAMESPACE,"StudyEventData");
          $StudyEventData->setAttribute("StudyEventOID","$StudyEventOID");
          $StudyEventData->setAttribute("StudyEventRepeatKey",$StudyEventRepeatKey);
          $result->item(0)->appendChild($StudyEventData); //We add it
        }else{
          $str = "Erreur : Insertion StudyEventData[@StudyEventOID='$StudyEventOID'] (". __METHOD__ .")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }else{
        if($result->length==1){
          //Already here
        }else{
          $str = "Erreur : doublons de StudyEventData[@StudyEventOID='$StudyEventOID'] (". __METHOD__ .")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }
  
      //Add FormData if needed
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']");
      if($result->length==0){
        $this->addLog("bocdiscoo()->saveItemGroupData() Ajout de FormData=$FormOID FormRepeatKey=$FormRepeatKey",TRACE);
        $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']");
        if($result->length==1){
          $FormData = $subj->createElementNS(ODM_NAMESPACE,"FormData");
          $FormData->setAttribute("FormOID","$FormOID");
          $FormData->setAttribute("FormRepeatKey","$FormRepeatKey");
          $FormData->setAttribute("TransactionType","Insert");
  
          $result->item(0)->appendChild($FormData); //We add it
        }else{
          $str = "Erreur : Insertion FormData[@FormOID='$FormOID' @FormRepeatKey='$FormRepeatKey'] : result->length={$result->length} (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }else{
        if($result->length==1){
          //Already here
          $FormData = $result->item(0);
        }else{
          $str = "Erreur : doublons de FormData[@FormOID='$FormOID' @FormRepeatKey='$FormRepeatKey] (". __METHOD__ .")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }
  
      //Add AuditRecords if needed
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords");
      if($result->length==0){
        $this->addLog("Ajout de AuditRecords",INFO);
        $ClinicalData = $xPath->query("/odm:ODM/odm:ClinicalData");
        if($ClinicalData->length==1){
          $AuditRecords = $subj->createElementNS(ODM_NAMESPACE,"AuditRecords");
          $ClinicalData->item(0)->appendChild($AuditRecords); //We add it
        }else{
          $str = "Erreur : Bug à l'insertion AuditRecords ClinicalData->length = {$ClinicalData->length} (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }else{
        if($result->length==1){
          //Already here
          $AuditRecords = $result->item(0);
        }else{
          $str = "Erreur : doublons de AuditRecords pour le patient $SubjectKey result->length={$result->length} (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }
  
      //Add AuditRecord
      $AuditRecord = $subj->createElementNS(ODM_NAMESPACE,"AuditRecord");
      //Generation of new ID as Audit-XXXXXX
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords/odm:AuditRecord");
      $AuditRecordID = sprintf("Audit-%06s",$result->length+1);
      $AuditRecord->setAttribute("ID",$AuditRecordID);
      //Who
      $UserRef = $subj->createElementNS(ODM_NAMESPACE,"UserRef");
      $UserRef->setAttribute("UserOID",$who);
      $AuditRecord->appendChild($UserRef);
      //Where
      $LocationRef = $subj->createElementNS(ODM_NAMESPACE,"LocationRef");
      $LocationRef->setAttribute("LocationOID",$where);
      $AuditRecord->appendChild($LocationRef);
      //When
      $DateTimeStamp = $subj->createElementNS(ODM_NAMESPACE,"DateTimeStamp",date('c'));
      $AuditRecord->appendChild($DateTimeStamp);
      //Why
      $ReasonForChange = $subj->createElementNS(ODM_NAMESPACE,"ReasonForChange",$why);
      $AuditRecord->appendChild($ReasonForChange);
  
      $AuditRecords->appendChild($AuditRecord); 
  
      //Add Annotations if needed
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:Annotations");
      if($result->length==0){
        $this->addLog("Add Annotations",INFO);
        $ClinicalData = $xPath->query("/odm:ODM/odm:ClinicalData");
        if($ClinicalData->length==1){
          $Annotations = $subj->createElementNS(ODM_NAMESPACE,"Annotations");
          $ClinicalData->item(0)->appendChild($Annotations); //On l'ajoute
        }else{
          $str = "Error : While adding Annotations ClinicalData->length = {$ClinicalData->length} (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }else{
        if($result->length==1){
          //Il était déjà présent
          $Annotations = $result->item(0);
        }else{
          $str = "Error : multiple Annotations for patient $SubjectKey result->length={$result->length} (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }
  
      //New FormData to replace the old one
      $newFormData = $subj->createElementNS(ODM_NAMESPACE,"FormData");
      $newFormData->setAttribute("FormOID","$FormOID");
      $newFormData->setAttribute("FormRepeatKey","$FormRepeatKey");
  
      //Extraction from POSTed data
      if($bFormVarsIsAlreadyDecoded){
        $tblFilledVar = $formVars;  
      }else{
        $tblFilledVar = array();
        //loop through incoming POST variables
        foreach($formVars as $key=>$value)
        {
          //oid extraction
          $varParts = explode("_",$key);
          if($varParts[0]!="annotation" && end($varParts)==$ItemGroupRepeatKey)
          {          
            //WLT 01/02/2011 : modification du code d'extraction de l'itemOID, afin de gérer les itemoid contenant un "_"
            //Deux cas de figure : la val commence par text_dd_itemoid (exemple) ou par radio_itemoid (exemple)
            $rawItemOID = str_replace("_".end($varParts),"",$key);  //suppression de l'ItemGroupRepeatKey              
            if($varParts[0]=="radio" || $varParts[0]=="select"){
              $rawItemOID = str_replace($varParts[0]."_","",$rawItemOID);  //suppression de l'ItemGroupRepeatKey              
            }else{
              $rawItemOID = str_replace($varParts[0]."_".$varParts[1]."_","",$rawItemOID);  //suppression de l'ItemGroupRepeatKey
            }
            
            $ItemOID = str_replace("@",".",$rawItemOID);
            
            $this->addLog("rawItemOID=$rawItemOID ItemOID=$ItemOID",TRACE);
            
            $tblFilledVar["$ItemOID"] = $value;
          }
        }
        
      }
        
      //On va chercher toutes les variables à enregistrer dans les metadatas,
      //a partir du FormOID et de l'ItemGroupOID
      //On regarde également pour chaque item si nous avions une valeur précédente (utile pour le TransactionType)
      $query = "
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      let \$ItemGroupRef := \$MetaDataVersion/odm:FormDef[@OID='$FormOID']/odm:ItemGroupRef[@ItemGroupOID='$ItemGroupOID']
      let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupRef/@ItemGroupOID]
      return
      <ItemGroupRef ItemGroupOID='{\$ItemGroupRef/@ItemGroupOID}'
                    Repeating='{\$ItemGroupDef/@Repeating}'>
      {
        for \$ItemRef in \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupRef/@ItemGroupOID]/odm:ItemRef
        let \$ItemOID := \$ItemRef/@ItemOID
        let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
        let \$ItemData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/
                                        odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/
                                        odm:ItemGroupData[@ItemGroupOID=\$ItemGroupRef/@ItemGroupOID and @ItemGroupRepeatKey='$ItemGroupRepeatKey']/
                                        odm:*[@ItemOID=\$ItemOID]
        let \$LastItemData := \$ItemData[last()]
        return
          <Item ItemOID='{\$ItemOID}'
                DataType='{\$ItemDef/@DataType}'
                AnnotationID='{\$LastItemData/@AnnotationID}'>
                {
                    if(exists(\$ItemData))
                    then <PreviousItemValue>{\$LastItemData/string()}</PreviousItemValue>
                    else <NoPreviousItemValue/>
                }
          </Item>
      }
      </ItemGroupRef>
      ";
  
      try{
        $results = $this->m_ctrl->socdiscoo()->query($query);
        $this->addLog("bocdiscoo()->saveItemGroupData() : results = ".$this->dumpRet($results),TRACE);
      }catch(xmlexception $e){
        $str = "Erreur de la requete : " . $e->getMessage() . " " . $query ." (".__METHOD__.")";
        $this->addLog($str,FATAL);
        die($str);
      }
  
      //Add ItemGroupData to FormData if needed
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData
                                       /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                       /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                       /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']");
      if($result->length==0){
      
        $this->addLog("bocdiscoo->saveItemGroupData() Ajout de ItemGroupData=$ItemGroupOID RepeatKey=$ItemGroupRepeatKey",INFO);
        $IG = $subj->createElementNS(ODM_NAMESPACE,"ItemGroupData");
        $IG->setAttribute("ItemGroupOID",$ItemGroupOID);
        $IG->setAttribute("ItemGroupRepeatKey",$ItemGroupRepeatKey);
  
        $IG->setAttribute("TransactionType","Insert");
  
        //We are here because of incoming data - we set ItemGroupData Flag to FILLED
        $Annotation = $subj->createElementNS(ODM_NAMESPACE,"Annotation");
        $Flag = $subj->createElementNS(ODM_NAMESPACE,"Flag");
        $FlagValue = $subj->createElementNS(ODM_NAMESPACE,"FlagValue","FILLED");
        $FlagType = $subj->createElementNS(ODM_NAMESPACE,"FlagType","STATUS");
  
        $Annotation->setAttribute("SeqNum","1");
        $FlagValue->setAttribute("CodeListOID","CL.IGSTATUS");
        $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");
  
        $Flag->appendChild($FlagValue);
        $Flag->appendChild($FlagType);
        $Annotation->appendChild($Flag);
        $IG->appendChild($Annotation);
  
        $FormData->appendChild($IG);
      }else{
        if($result->length==1){
          $this->addLog("bocdiscoo->saveItemGroupData()Update of ItemGroupData=$ItemGroupOID",INFO);
          $IG = $result->item(0);
        }else{
          $str = "Erreur : multiple ItemGroupData=$ItemGroupOID RepeatKey=$ItemGroupRepeatKey (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
      }
  
      //ItemGroupData already here - status may need update, as it could came from the BLANK Subject
      $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData
                                       /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                       /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                       /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
                                       /odm:Annotation/odm:Flag[odm:FlagType/@CodeListOID='CL.FLAGTYPE']/odm:FlagValue");
      if($result->length!=1){
        $str = "bocdiscoo->saveItemGroupData() FlagValue not found (".__METHOD__.")";
        $this->addLog($str,INFO);
        $resultIGDT = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData
                                             /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                             /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                             /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']");
        if($resultIGDT->length!=1){
          $str = "Erreur pendant une tentative de création d'un FlagValue absent : ItemGroupData non trouvé  (".__METHOD__.")";
          $this->addLog($str,FATAL);
          die($str);
        }
        else
        {
          $IGDT = $resultIGDT->item(0);
          $Annotation = $subj->createElementNS(ODM_NAMESPACE,"Annotation");
          $Flag = $subj->createElementNS(ODM_NAMESPACE,"Flag");
          $FlagValue = $subj->createElementNS(ODM_NAMESPACE,"FlagValue","FILLED");
          $FlagType = $subj->createElementNS(ODM_NAMESPACE,"FlagType","STATUS");
    
          $Annotation->setAttribute("SeqNum","1");
          $FlagValue->setAttribute("CodeListOID","CL.IGSTATUS");
          $FlagType->setAttribute("CodeListOID","CL.FLAGTYPE");
    
          $Flag->appendChild($FlagValue);
          $Flag->appendChild($FlagType);
          $Annotation->appendChild($Flag);
          
          //Annotation must be the first child
          $firstChild = $IGDT->firstChild;
          if($firstChild)
          {
            $IGDT->insertBefore($Annotation, $firstChild);
          }
          else
          {
            $IGDT->appendChild($Annotation);
          }
        }
      }else{
        $FlagValue = $result->item(0);
      }
    
      //On a à notre disposition $FormData pour ajouter les ItemGroup
      $hasModif = false;
      foreach($results as $ItemGroupRef){
        //$bFormVarsIsAlreadyDecoded = true en cas d'import du coding, pour ne pas effacer les champs non présent dans l'import
        if($this->addItemData($IG,$ItemGroupRef,$formVars,$tblFilledVar,$subj,$AuditRecordID,!$bFormVarsIsAlreadyDecoded)){
          $hasModif = true;
        }
      }
            
      //Update XML DB only if needed
      if($hasModif)
      { 
        $this->m_ctrl->socdiscoo()->replaceDocument($subj,false,"ClinicalData");      
      }
    }
    catch(Exception $e)
    {
      $str = "Uncaught exception : " . $e->getMessage() . ", Line " . $e->getLine() . " (".__METHOD__.", ".__FILE__.", ".__LINE__.")";
      $this->addLog($str,FATAL);
    }
    return $hasModif;
  }

/*
met à jour le statut FROZEN / FILLED / INCONSISTENT / PARTIAL / EMPTY d'un ItemGroupData
@author wlt
*/
  protected function setItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status)
  {
    $this->addLog("bocdiscoo->setItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status)",INFO);
    
    /*
    $query = "replace value of node collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']
                                          /odm:ClinicalData/odm:SubjectData
                                          /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                          /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                          /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
                                          /odm:Annotation/odm:Flag[odm:FlagType/@CodeListOID='CL.FLAGTYPE']/odm:FlagValue
              with '$status'";
    */
    //SEDNA 3.5 syntax (still not conform with the XQuery Update Facility 1.0)
    $query = "UPDATE REPLACE \$x in collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']
                                          /odm:ClinicalData/odm:SubjectData
                                          /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                          /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                          /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
                                          /odm:Annotation/odm:Flag[odm:FlagType/@CodeListOID='CL.FLAGTYPE']/odm:FlagValue
             WITH <odm:FlagValue CodeListOID=\"CL.IGSTATUS\">$status</odm:FlagValue>";
    

    try{
      $res = $this->m_ctrl->socdiscoo()->query($query,true,false);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->setItemGroupStatus() Erreur : $str",TRACE);
      
      //Annotation absente ? On la créé avec le statut désiré
      $this->addItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status);
    }
  }

/*
@desc set le statut des itemgroupdata d'un formulaire donné
@creationdate 02/03/2011
@author wlt
*/
public function setLock($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$bLock)
{
  //pour tous les itemgroupdatas du formulaire
  $ItemGroupDatas = $this->getItemGroupDatas($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey);
  foreach($ItemGroupDatas as $ItemGroupData){
    $ItemGroupOID = (string)$ItemGroupData['ItemGroupOID'];
    $ItemGroupRepeatKey = (string)$ItemGroupData['ItemGroupRepeatKey'];
    if($bLock){
      $status = "FROZEN";
    }else{
      $status = "FILLED";
    }
    $this->setItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status);
  }
}

/*
@desc met à jour le statut FILLED / INCONSISTENT / PARTIAL / EMPTY d'un SubjectData
@author tpi
*/
  public function setSubjectStatus($SubjectKey,$status)
  {
    $this->addLog("bocdiscoo->setSubjectStatus($SubjectKey,$status)",INFO);
    
    /*
    $query = "replace value of node collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']
                                            /odm:ClinicalData/odm:SubjectData/odm:Annotation/odm:Flag[odm:FlagType/@CodeListOID='CL.FLAGTYPE']/odm:FlagValue 
              with '$status'";
    */
    //SEDNA 3.5 syntax (still not conform with the XQuery Update Facility 1.0)
    $query = "UPDATE REPLACE \$x in collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']
                                            /odm:ClinicalData/odm:SubjectData/odm:Annotation/odm:Flag[odm:FlagType/@CodeListOID='CL.FLAGTYPE']/odm:FlagValue
             WITH <odm:FlagValue CodeListOID=\"CL.SSTATUS\">$status</odm:FlagValue>";

    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->setSubjectStatus() Erreur : $str",TRACE);
      
      //Annotation absente ? On la créé avec le statut désiré
      $this->addSubjectStatus($SubjectKey,$status);
    }
  }
  
  //fonction de mise à jour des FormalExpression utilisées dans le checkFormConsistency
  function updateFormalExpression($matches){
    if(!isset($this->handeldExpressionsForExpressionUpdate[$matches[0]])){ //déjà matché ?
      $var = "$". chr(97 + $this->iVarForExpressionUpdate); //code ascii pour a,b,c etc => $a, $b, $c)
      $this->expressionForExpressionUpdate .= "let ". $var ." := ". $matches[0] ."\n";
      $this->handeldExpressionsForExpressionUpdate[$matches[0]] = $this->iVarForExpressionUpdate; //listes des expressions matchées
      $this->iVarForExpressionUpdate++;
    }else{
      $var = "$". chr(97 + $this->handeldExpressionsForExpressionUpdate[$matches[0]]); //code ascii pour a,b,c etc => $a, $b, $c)
    }
    return $var;
  }

  /*
  Run edit checks on asked form and update accordingly Form's ItemGroupData
  @see class.ajax.inc.php, runChecks.php
  @version 2 by WLT 01/07/2011 : Only update ItemGroupData Status if needed, add return value
  @version 3 by WLT 25/04/2012 : No more need to update ItemGroupData Status. From now only authorized status are EMPTY, FROZEN, and FILLED
  @return int number of queries
  @author tpi, wlt
  */
  public function updateFormStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey){
    //Look for queries on the form
    $errorsMandatory = $this->checkMandatoryData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey);
    $errorsConsistency = $this->checkFormConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey);
    return count($errorsMandatory)+count($errorsConsistency);
  }
  
}