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
Convert input POSTed data to XML string ODM Compliant, regarding metadata
@param boolean $bEraseNotFoundItem erase in XML Db Item not present in incoming data. usefull for clean disabled inputs 
@return array array of ItemDatas to be inserted into ItemGroupData, empty string if no modification 
@author wlt
*/
  private function addItemData($SubjectKey,$ItemGroupRepeatKey,$ItemGroupRef,$formVars,&$tblFilledVar,$subj,$AuditRecordID,$bEraseNotFoundItem=true,$nbAnnotations)
  {
    $this->addLog("bocdiscoo->addItemData() : tblFilledVar = " . $this->dumpRet($tblFilledVar),TRACE);
    $tblRet = array();

    //Loop through all ItemDef for current ItemGroup
    foreach($ItemGroupRef as $Item)
    {
      //Annotations management : RAS/ND/NSP + comment
      $AnnotationID = "";
      $bAnnotationModif = false;
      $flag = $formVars["annotation_flag_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      $previousflag = $formVars["annotation_previousflag_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      $comment = $formVars["annotation_comment_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      $previouscomment = $formVars["annotation_previouscomment_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
      
      if($flag != $previousflag || $comment != $previouscomment)
      {
        $bAnnotationModif = true;
        $hasModif = true;
        
        $AnnotationSeqNum = $nbAnnotations+1;
        $AnnotationID = sprintf("Annot-%06s",$nbAnnotations+1);
        $comment = htmlspecialchars(stripslashes($comment));     
        $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                  UPDATE
                  insert <Annotation ID='$AnnotationID' SeqNum='$AnnotationSeqNum'>
                          <Comment>$comment</Comment>
                          <Flag>
                            <FlagValue CodeListOID='ANNOTFLA'>$flag</FlagValue> 
                          </Flag>
                         </Annotation> 
                  into collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:Annotations";
        try{
          $this->m_ctrl->socdiscoo()->query($query);
        }catch(xmlexception $e){
          $str = "Error in query " . $e->getMessage() . " " . $query ." (".__METHOD__.")";
          $this->addLog($str,FATAL);
        }
        $this->addLog("bocdiscoo->addItemData() Adding annotation $AnnotationID : ". $flag ." / ". $comment,INFO);        
      }
      else
      {
        $AnnotationID = $Item['AnnotationID'];
      }

      //Specific handle of types Date and PartialDate
      switch($Item['DataType'])
      {
        case 'datetime' :
        case 'date' :
          $dd = $formVars["text_dd_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $mm = $formVars["text_mm_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
          $yy = $formVars["text_yy_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];

          if($dd=="" && $mm=="" && $yy==""){
            //If null value, it can't be save as "ItemDataDate", must use ItemDataAny
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
          //If null value, it can't be save as "ItemDataDate", must use ItemDataAny
          if((string)$tblFilledVar["{$Item['ItemOID']}"]==""){ //do not confuse 0 and "" (see http://www.php.net/manual/en/types.comparisons.php)
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
        if((string)$tblFilledVar["{$Item['ItemOID']}"]==NULL){  //do not confuse value 0 (zéro) and value NULL (see http://www.php.net/manual/en/types.comparisons.php)
          $tblFilledVar["{$Item['ItemOID']}"] = "";
        }
      }
      
      //New ItemData only if new Item or updated value and value present in POSTed vars, unless $bEraseNotFoundItem = true (default)
      if(isset($tblFilledVar["{$Item['ItemOID']}"]) && 
          (
           !isset($Item->PreviousItemValue) ||
           isset($Item->PreviousItemValue) && 
           (string)($Item->PreviousItemValue) != (string)($tblFilledVar["{$Item['ItemOID']}"]) || 
           $bAnnotationModif
          ) || 
          !isset($tblFilledVar["{$Item['ItemOID']}"]) && $bEraseNotFoundItem 
        )                                     
      {
        $this->addLog("bocdiscoo->addItemData() Adding ItemData={$Item['ItemOID']} PreviousItemValue=".$Item->PreviousItemValue." Value=".$tblFilledVar["{$Item['ItemOID']}"],INFO);

        //Value may contains & caracters
        $encodedValue = htmlspecialchars($tblFilledVar["{$Item['ItemOID']}"],ENT_NOQUOTES); 
        
        if(isset($Item->PreviousItemValue)){
          $transacType='Update';
        }else{
          $transacType='Insert';
        }
        if($AnnotationID!=""){
          $annotationAttr = "AnnotationID='$AnnotationID'";
        }else{
          $annotationAttr = "";
        }
        $tblRet[] = "
                    <ItemData".ucfirst($Item['DataType'])."
                        ItemOID='".$Item['ItemOID']."' 
                        AuditRecordID='$AuditRecordID'
                        $annotationAttr
                        TransactionType='$transacType'>$encodedValue</ItemData".ucfirst($Item['DataType']).">";
                        
      }
    }
    return $tblRet;
  }


/*
@desc Ajout d'une Annotation à un SubjectData
@author tpi
*/
  private function addSubjectStatus($SubjectKey,$status="EMPTY",$SeqNum="1")
  {
    $this->addLog("bocdiscoo->addSubjectStatus($SubjectKey,$status,$SeqNum)",INFO);
    
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
    $this->addLog("bocdiscoo->checkFormConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID)", INFO);

    //Loop through ItemData having Edit Checks (RangeCheck in ODM)
    $query = "
        import module namespace alix = 'http://www.alix-edc.com/alix';
         
        let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        let \$ItemGroupDatas := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                            /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey' and @TransactionType!='Remove']
                                            /odm:ItemGroupData[@TransactionType!='Remove'] 
        return
          if (count(\$ItemGroupDatas)=0)
          then <NoItemGroupData />      
          else
            for \$ItemGroupData in \$ItemGroupDatas
            let \$ItemGroupOID := \$ItemGroupData/@ItemGroupOID
            let \$ItemGroupRepeatKey := \$ItemGroupData/@ItemGroupRepeatKey
            for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
              let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
              let \$ItemData := \$ItemDatas[1]
              let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
              return
                if (count(\$ItemDef/odm:RangeCheck) = 0 and \$ItemData/string()!='') 
                then <NoControl />
                else
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
                             Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/text()}' />";
    try{
      $ctrls = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "XQuery error" . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    $errors = array();

    if($ctrls[0]->getName()!="NoItemGroupData")
    {
      foreach($ctrls as $ctrl)
      {
        if($ctrl->getName()!="NoControl"){        
          $testXQuery = $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl);
          try{
            $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery);
          }catch(xmlexception $e){
            //Error is probably due to the edit check code. Error is not display to the user, and administrator notified by email 
            $str = "Consistency : Xquery error : " . $e->getMessage() . " " . $testXQuery;
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
              //Here no decode available, simply display the error message
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
          let \$ItemData := \$ItemDatas[1]
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

/**
 * Check for missing data into the asked form
 * a missing mandatory data could not generate an error if the CollectionConditionException return true
 * This function also fill the queries db, and close opened queries if needed 
 * @return array of missing errors   
 **/
  function checkMandatoryData($SubjectKey, $StudyEventOID, $StudyEventRepeatKey, $FormOID, $FormRepeatKey)
  {
    $this->addLog("bocdiscoo->checkMandatoryData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)", INFO);

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
                  let \$ItemData := \$ItemDatas[1]
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
      $str = "XQuery error " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    
    //Loop through errors to run CollectionConditionException
    $tblRet = array();
    foreach($errors[0] as $error){
      $this->addLog("bocdiscoo->checkMandatoryData() : error=".$this->dumpRet($error),TRACE);
      
      //Position = 1 Because only one mandatory query can exist per Item
      //Type = M stand for Mandatory
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
        $testXQuery = $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$error);//-
        try{
          $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery);
          $this->addLog("query=".$testXQuery,INFO);
        }catch(xmlexception $e){
          //Error is probably due to the ConditionDef code. Error is not display to the user, and administrator notified by email             
          $str = "Mandatory : Erreur du controle : " . $e->getMessage() . " " . $testXQuery;
          $this->addLog($str,ERROR);
          $desc = "Misformated control on the {$error['ItemOID']} value. Notification was sent to administrator.";
          $errors["$desc"] = array( 'desc' => $desc,
                                    'type' => 'badformat',
  								                  'itemOID' => $error['ItemOID']);
        }
  
        $this->addLog("bocdiscoo->checkFormMandatory() Control[{$StudyEventOID}][{$FormOID}][{$error['ItemGroupOID']}][{$error['ItemGroupRepeatKey']}]['{$error['ItemOID'] }'] => Result=" . $ctrlResult[0]->Result, INFO);
        
        if($ctrlResult[0]->Result=='true'){
          // CollectionConditionException return true - the Item is no more longer mandatory
          $this->addLog("bocdiscoo->checkFormMandatory() : " . $error['ItemOID'] . " n'est plus obligatoire",INFO);
        }else{
          //Decode(s) extraction
          //We use an eval here to handle multiple decode, passed as parameters to function sprintf()
          if(substr_count($error["Description"],"%s")>0){
            //$tblParams = explode(' ',$ctrlResult[0]->Decode);
            
            $tblParams=array();
            foreach($ctrlResult[0]->Decode as $decode){
              $tblParams[] = $decode;
              $this->addLog("Decode=".$decode,INFO);
            }
            //$tblParams = str_replace("¤", " ", $tblParams); //restauration des espaces ' ' substitués (par des '¤', cf getXQueryConsistency())
            //$error["Description"] = str_replace("\"","'",$error["Description"]);
            $cmdEval = "\$desc = sprintf(\"".$error["Description"]."\",\"".implode('","',$tblParams)."\");";
            $this->addLog("Eval=".$cmdEval,INFO);
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
    $newSubj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$this->m_tblConfig['BLANK_OID']);

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
  

/*  
@desc returns the list of the visits, forms and itemgroups to display a full CRF (usefull for PDF generation)
*/
  function getAllSubjectFormsAndIGsForPDF($SubjectKey, $insertAuditTrail=false)
  {
    $this->addLog(__METHOD__."($SubjectKey,$insertAuditTrail)",INFO);
    
    $ItemGroupDataAT = "";
    if($insertAuditTrail){
      $ItemGroupDataAT = "
      {
      (:Insertion des données d'Audit Trail:)
      let \$ItemGroupData := \$StudyEventData/odm:FormData[@FormOID=\$FormOID and (@FormRepeatKey=\$FormData/@FormRepeatKey or not(\$FormData/@FormRepeatKey))]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]
      return
          <ItemGroupDataAT OID='{\$ItemGroupData/@ItemGroupOID}'>
          {
              for \$ItemData in \$ItemGroupData/odm:*
                  let \$AuditRecord := \$SubjectData/../odm:AuditRecords/odm:AuditRecord[@ID=\$ItemData/@AuditRecordID]
                  order by \$ItemData/@ItemOID,\$ItemData/@AuditRecordID descending
                  return
                      <ItemDataAT ItemOID='{\$ItemData/@ItemOID}'
                                  Value='{\$ItemData/string()}'
                                  AuditRecordID='{\$ItemData/@AuditRecordID}'
                                  TransactionType='{\$ItemData/@TransactionType}'>
                          <AuditRecord    User='{\$AuditRecord/odm:UserRef/@UserOID}'
                                          Location='{\$AuditRecord/odm:LocationRef/@LocationOID}'
                                          Date='{\$AuditRecord/odm:DateTimeStamp/string()}'
                                          Reason='{\$AuditRecord/odm:ReasonForChange/string()}'/>
                      </ItemDataAT>
          }
          </ItemGroupDataAT>
      }";
    }
    
    $query = "let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
              let \$SubjectBLANK := collection('ClinicalData')/odm:ODM[@FileOID='". $this->m_tblConfig["BLANK_OID"] ."']/odm:ClinicalData/odm:SubjectData
              let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
              let \$BasicDefinitions := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:BasicDefinitions[../odm:MetaDataVersion/@OID=\$SubjectData/../@MetaDataVersionOID]
              return
                <SubjectData>
                {
                    for \$StudyEventData in \$SubjectData/odm:StudyEventData
                    return
                        <StudyEvent OID='{\$StudyEventData/@StudyEventOID}'
                                    StudyEventRepeatKey='{\$StudyEventData/@StudyEventRepeatKey}'
                                    Title='{\$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventData/@StudyEventOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                        {
                            for \$FormData in \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventData/@StudyEventOID]/odm:FormRef
                                let \$FormRef := \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventData/@StudyEventOID]/odm:FormRef[@FormOID=\$FormData/@FormOID]
                                let \$FormOID := \$FormData/@FormOID
                                let \$FormDef := \$MetaDataVersion/odm:FormDef[@OID=\$FormData/@FormOID]
                                return
                                <Form   OID='{\$FormOID}'
                                        FormReapeatKey='{\$FormData/@FormRepeatKey}'
										                    Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormData/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                {
                                    for \$ItemGroupRef in \$FormDef/odm:ItemGroupRef
                                    let \$ItemGroupOID := \$ItemGroupRef/@ItemGroupOID
                                    let \$ItemGroupDef := \$MetaDataVersion/odm:ItemGroupDef[@OID=\$ItemGroupOID]
                                    return
                                        <ItemGroup  OID='{\$ItemGroupOID}'
                                                    Title='{\$ItemGroupDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                                    Repeating='{\$ItemGroupDef/@Repeating}'>
                                        {
                                            (:Insertion des MetaData de l'item en cours, ainsi que de la dernière valeur saisie:)
                                            for \$ItemRef in \$ItemGroupDef/odm:ItemRef
                                            let \$ItemOID := \$ItemRef/@ItemOID
                                            let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
                                            return
                                                <Item   OID='{\$ItemOID}'
                                                        Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                                        DataType='{\$ItemDef/@DataType}'
                                                        Length='{\$ItemDef/@Length}'
                                                        SignificantDigits='{\$ItemDef/@SignificantDigits}'
                                                        Mandatory='{\$ItemRef/@Mandatory}'
                                                        CollectionExceptionConditionOID='{\$ItemRef/@CollectionExceptionConditionOID}'>
                                                    <CodeList>
                                                    {
                                                        for \$CodeListItem in \$MetaDataVersion/odm:CodeList[@OID=\$ItemDef/odm:CodeListRef/@CodeListOID]/*
                                                        return
                                                            <CodeListItem   CodedValue='{\$CodeListItem/@CodedValue}'
                                                                            Decode='{\$CodeListItem/odm:Decode/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                                            </CodeListItem>
                                                    }
                                                    </CodeList>
                                                    <MeasurementUnit>
                                                    {
                                                        for \$MeasurementUnitItem in \$BasicDefinitions/odm:MeasurementUnit[@OID=\$ItemDef/odm:MeasurementUnitRef/@MeasurementUnitOID]
                                                        return
                                                            <MeasurementUnitItem    OID='{\$MeasurementUnitItem/@MeasurementUnitOID}'
                                                                                    Symbol='{\$MeasurementUnitItem/odm:Symbol/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                                            </MeasurementUnitItem>
                                                    }
                                                    </MeasurementUnit>
                                                    $ItemGroupDataAT
                                                </Item>
                                        }
                                        {
                                            for \$ItemGroupData in \$StudyEventData/odm:FormData[@FormOID=\$FormOID and (@FormRepeatKey=\$FormData/@FormRepeatKey or not(\$FormData/@FormRepeatKey))]/odm:ItemGroupData[@ItemGroupOID=\$ItemGroupOID]
                                            return
                                                <ItemGroupData  ItemGroupOID='{\$ItemGroupData/@ItemGroupOID}'
                                                                ItemGroupRepeatKey='{\$ItemGroupData/@ItemGroupRepeatKey}'>
                                                {
                                                    for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
                                                    let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
                                                    let \$ItemData := \$ItemDatas[1]
                                                    let \$ItemDef := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]
                                                    return
                                                        <ItemData   OID='{\$ItemData/@ItemOID}'
                                                                    Title='{\$ItemDef/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                                                    DataType='{\$ItemDef/@DataType}'
                                                                    Length='{\$ItemDef/@Length}'
                                                                    Value='{\$ItemData/string()}'>
                                                        </ItemData>
                                                }
                                                </ItemGroupData>
                                        }
                                        </ItemGroup>
                                }
                                </Form>
                        }
                        </StudyEvent>
                }
                </SubjectData>";

    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    }catch(xmlexception $e){
      $str = "xQuery error : " . $e->getMessage();
      $this->addLog($str,FATAL);
    }
    return $doc;
  }

  //Retourne pour un formulaire donnée un tableau des Items le composant, avec toutes les infos (cohérences, ...)
  function getAnnotedCRF($FormOID)
  {
    $this->addLog("bocdiscoo->getAnnotedCRF($FormOID)",INFO);
    $query = "let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='". $this->m_tblConfig["BLANK_OID"] ."']/odm:ClinicalData/odm:SubjectData
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
  
  public function getAuditTrailByDate($sitesList,$startDate,$endDate){
    //From sitesList, we extract a subject list
    $query = "
              for \$Subject in doc('SubjectsList')/subjects/subject
              return
                <subject siteId='{\$Subject/colSITEID}' subjectKey='{\$Subject/SubjectKey}'/>
             ";
    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    
    $subjList = "";
    foreach($result as $subj){
      $siteId = (string)$subj['siteId'];
      if(in_array($siteId,$sitesList)){
         if($subjList!="") $subjList .= ",";
         $subjList .= "'". (string)$subj['subjectKey'] ."'";
      }
    }

    $startDate .= "T00:00:00";
    $endDate .= "T00:00:00";
    
    $query = "
              declare function local:getDecode(\$ItemData as node(),\$MetaDataVersion as node()) as xs:string?
              {
                let \$value := \$ItemData/string()
                let \$CodeListOID := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemData/@ItemOID]/odm:CodeListRef/@CodeListOID
                return
                  if(\$CodeListOID)
                  then \$MetaDataVersion/odm:CodeList[@OID=\$CodeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()
                  else \$value
              };
                  
              for \$ClinicalData in collection('ClinicalData')/odm:ODM[exists(index-of(($subjList),@FileOID))]/odm:ClinicalData
               let \$SubjectKey := \$ClinicalData/odm:SubjectData/@SubjectKey 
               let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$ClinicalData/odm:SubjectData/../@MetaDataVersionOID]
                 for \$AuditRecord in \$ClinicalData/
                      odm:AuditRecords/odm:AuditRecord[xs:dateTime(odm:DateTimeStamp) > xs:dateTime('$startDate') and  
                                                       xs:dateTime(odm:DateTimeStamp) < xs:dateTime('$endDate')]
                 let \$AuditDate := \$AuditRecord/odm:DateTimeStamp 
                 let \$UserOID := \$AuditRecord/odm:UserRef/@UserOID 
                 let \$ID := \$AuditRecord/@ID
                 for \$ItemData in \$ClinicalData/odm:SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:*[@AuditRecordID=\$ID]
                   let \$Value := local:getDecode(\$ItemData,\$MetaDataVersion)
                   let \$StudyEventOID := \$ItemData/../../../@StudyEventOID 
                   let \$FormOID := \$ItemData/../../@FormOID
                   let \$ItemOID := \$ItemData/@ItemOID
                   let \$StudyEventTitle := \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()
                   let \$FormTitle := \$MetaDataVersion/odm:FormDef[@OID=\$FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()
                   let \$ItemTitle := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemOID]/odm:Question/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()

                   return 
                    <audit subjectKey='{\$SubjectKey}'
                           studyEvent='{\$StudyEventTitle}'
                           form='{\$FormTitle}'
                           item='{\$ItemTitle}'
                           value='{\$Value}'
                           user='{\$UserOID}'
                           auditDate='{\$AuditDate}'
                           
                    />
                 
    ";    
    try{
      $result = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "xQuery error : " . $e->getMessage() . "<br/><br/>" . $query . " (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }

    return $result;
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
                return \$ItemData[1]/string()
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
        return \$ItemData[1]/string()
      };
      
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$value := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType))]/odm:*[@ItemOID='$ItemOID'])
      let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
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
   
  /*
  @desc retourne le prochain numero patient a utilisé - fonction utilisée dans saveItemGroupData
  @return string nouveau numero patient
  */
  protected function getNewPatientID()
  {   
    $query = "let \$SubjectsCol := collection('ClinicalData')
              let \$maxSubjId := max(\$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='". $this->m_tblConfig['BLANK_OID'] ."']/@SubjectKey)
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
                                                          odm:*[@ItemOID='{$col['ITEMOID']}'][1]
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
    $query = "declare function local:getDecode(\$ItemData as node()*,\$SubjectData as node(),\$MetaDataVersion as node()) as xs:string?
              {
                let \$value := \$ItemData[1]
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
                                                        odm:ItemDataString[@ItemOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value']['ITEMOID']}'][1]
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
                            (: audit trail - if asked :)
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
                              let \$ItemData := \$ItemDatas[1]
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
    $this->addLog("bocdiscoo->getStudyEventForms() ended",INFO);
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
        return \$ItemData[1]/string()
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
   * @desc Get all subject(s) forms and visits, with status for each form (EMPTY, PARTIAL, INCONSISTENT, FROZEN)
   * @return DOMDocument
   * @author wlt, tpi
   **/  
  public function getSubjectsTblForm($SubjectKey)
  {
    $this->addLog(__METHOD__ ."()",INFO);
    
    //for performance issues, the same MetaDataVersionOID is used for every subject. (I assume that every subject in the study in created or updated with the same version of Metadatas)
    $query = "     
        let \$SubjectData := index-scan('SubjectData', '$SubjectKey', 'EQ')
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        return
          <SubjectData SubjectKey='{\$SubjectData/../../@FileOID}'>
          {
            for \$StudyEventData in \$SubjectData/odm:StudyEventData
            let \$StudyEventDef := \$MetaDataVersion/odm:StudyEventDef[@OID=\$StudyEventData/@StudyEventOID]
            return
              <StudyEventData StudyEventOID='{\$StudyEventData/@StudyEventOID}'
                              StudyEventRepeatKey='{\$StudyEventData/@StudyEventRepeatKey}'
                              Title='{\$StudyEventDef/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
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
                                          Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                </FormData>
                    else
                        <FormData FormOID='{\$FormRef/@FormOID}'
                                  FormRepeatKey='0'
                                  MetaDataVersionOID='{\$MetaDataVersion/@OID}'
                                  Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                  Status='EMPTY'>
                        </FormData>
              }
              </StudyEventData>
          }
          </SubjectData>";

    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    }catch(xmlexception $e){
      $str = "Error in xQuery : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    
    //Set StudyEvent and Form status according to associated queries   
    //Loop through SubjectDatas
    $SubjectDatas = $doc->getElementsByTagName("SubjectData");
    foreach($SubjectDatas as $SubjectData){
      //Loop through visits
      $SubjectKey = $SubjectData->getAttribute("SubjectKey");
      $visits = $SubjectData->getElementsByTagName("StudyEventData");
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
            if($visitStatus=="FILLED"){
              //FILLED only if there is no empty forms
              if($nbFormEmpty>0){
                $visitStatus = "PARTIAL";
              }
            }
          }           
        }
        $visit->setAttribute("Status",$visitStatus);
      }
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
        return \$ItemData[1]/string()
      };
      
      let \$SubjectData := collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
      let \$value := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[true() $andItemGroupOID $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType)) $whereIGString]/odm:*[@ItemOID='$ItemOID'])
      return
        <result value='{\$value}' />
    ";
    
    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "xQuery error : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    return (string)$doc[0]['value'];
  }
  
  /**
   * Optimize query written into the metadata
   * @return string optimized query code
   * @author tpi       
   **/  
  private function getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl,$Value=false){
    $testExpr = $ctrl['FormalExpression'];   
    
    //To optimize query, each call to functions getValue,getRawValue,count and max with same parameters is made unique.
    //We put a section containing let $a := getValue(...), $b:=getRawValue()... code before edit check 
    //Then $a, $b, $c, ... vars are used multiple times in the edit check
    //used of members variable due to the preg_replace callback function updateFormalExpression
    $this->iVarForExpressionUpdate = 0;
    $this->expressionForExpressionUpdate = "";
    $this->handeldExpressionsForExpressionUpdate = array(); 
    //functions list to be factorized
    $xQueryFunctions = array("alix:getValue","alix:getRawValue","alix:getAnnotation","count","max","count"); //,"compareDate","DateISOtoFR","days-from-duration","getMonth"
    $expressions = array();
    foreach($xQueryFunctions as $xQueryFunction){
      $expressions[] = "((". $xQueryFunction .")(\([^\(\)]*\)))"; //Function signature
    }
    $testExpr = preg_replace_callback($expressions, array($this, "updateFormalExpression"), $testExpr);
    $testExpr = $this->expressionForExpressionUpdate ."[!]". $testExpr;
    //Context is stored into queries bd - used to 'sign' the query
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
    //Finnaly, split let section and optimized query
    list($lets, $testExprOptimized) = explode("[!]", $testExpr);
 
    $testExprDecode = "";
    if($ctrl['FormalExpressionDecode']!=""){
      $testExprDecode = $ctrl['FormalExpressionDecode'];
      $tblTestExprDecode = explode("|",$testExprDecode);
      $queryDecode = "";
      foreach($tblTestExprDecode as $expr){
        $queryDecode .= "<Decode>
                         {
                          $expr
                         }
                         </Decode>";  
      } 
      /*
      $testExprDecode = str_replace("alix:getDecode($","replace(alix:getDecode($",$testExprDecode); //added TPI 20110830
      $testExprDecode = str_replace("getDecode()","replace(alix:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion),' ','¤')",$testExprDecode);
      $testExprDecode = str_replace("getDecode('","replace(alix:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion,'",$testExprDecode);
      
      $testExprDecode = str_replace("')","'),' ','¤')",$testExprDecode);
      */
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
      import module namespace alix = 'http://www.alix-edc.com/alix';
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
      let \$value := alix:getRawValue(\$ItemData)
      let \$decode := alix:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion)
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
            $testExprOptimized
          }
          </Result>
          $queryDecode
        </Ctrl>";
    return $testXQuery;
  }

  public function removeFormData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)
  {
    $this->addLog("bocdiscoo->removeFormData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);

    $query = "UPDATE REPLACE \$x in collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";
    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "XQuery error " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->removeItemGroupData() Error : $str",FATAL);
    }
  }

  public function removeItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)
  {
    $this->addLog("bocdiscoo->removeItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)",INFO);

    $query = "UPDATE REPLACE \$x in collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";
    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "xQuery error : " . $e->getMessage() . " : " . $query;
      $this->addLog("bocdiscoo->removeItemGroupData() Erreur : $str",FATAL);
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
    $hasModif = false;
    $this->addLog("bocdiscoo->saveItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$formVars,$who,$where,$why,$fillst,$bFormVarsIsAlreadyDecoded)",INFO);
        
    //DomDocument of Subject
    try{
      $subj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$SubjectKey,false);
    }catch(xmlexception $e){
      $str= "(". __METHOD__ .") Patient $SubjectKey not found : " . $e->getMessage();
      $this->addLog($str,FATAL);
    }

    $xPath = new DOMXPath($subj);
    $xPath->registerNamespace("odm", ODM_NAMESPACE);

    //Note : StudyEventData, AuditRecords and Annotations elements must be declared into the blank subject 
    
    //Add FormData if needed
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']");
    if($result->length==0){
      $this->addLog("bocdiscoo()->saveItemGroupData() Adding FormData=$FormOID FormRepeatKey=$FormRepeatKey",TRACE);
      $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                UPDATE
                insert <FormData FormOID='$FormOID' FormRepeatKey='$FormRepeatKey' TransactionType='Insert'/>
                into collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']";
      $this->m_ctrl->socdiscoo()->query($query);
    }else{
      if($result->length!=1){
        $str = "Error duplicate entry FormData[@FormOID='$FormOID' @FormRepeatKey='$FormRepeatKey] (". __METHOD__ .")";
        $this->addLog($str,FATAL);
      }
    }

    //Generation of new ID as Audit-XXXXXX
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:AuditRecords/odm:AuditRecord");
    $AuditRecordID = sprintf("AT-%06s",$result->length+1);

    $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
              UPDATE
              insert <AuditRecord ID='$AuditRecordID'>
                      <UserRef UserOID='$who'/>
                      <LocationRef LocationOID='$where'/>
                      <DateTimeStamp>".date('c')."</DateTimeStamp>
                      <ReasonForChange>$why</ReasonForChange>
                     </AuditRecord>
              following collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:AuditRecords/odm:AuditRecord[1]";
    $this->m_ctrl->socdiscoo()->query($query);

    $annotationsChildren = $xPath->query("/odm:ODM/odm:ClinicalData/odm:Annotations/odm:Annotation");
    $nbAnnotations = $annotationsChildren->length;

    //Add ItemGroupData to FormData if needed
    $igdata = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData
                                     /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                     /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                     /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']");
    if($igdata->length==0){      
      $this->addLog("bocdiscoo->saveItemGroupData() Adding ItemGroupData=$ItemGroupOID RepeatKey=$ItemGroupRepeatKey",INFO);
      $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                UPDATE
                insert <ItemGroupData ItemGroupOID='$ItemGroupOID' ItemGroupRepeatKey='$ItemGroupRepeatKey' TransactionType='Insert'>
                        <Annotation SeqNum='1'>
                          <Flag>
                            <FlagValue CodeListOID='CL.SSTATUS'>FILLED</FlagValue>
                            <FlagType CodeListOID='CL.FLAGTYPE'>STATUS</FlagType>
                          </Flag>
                        </Annotation>
                       </ItemGroupData>
                into collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
                                               /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                               /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']";
      $this->m_ctrl->socdiscoo()->query($query);
    }else{
      if($igdata->length!=1){
        $str = "Error : duplicate entries for ItemGroupData=$ItemGroupOID RepeatKey=$ItemGroupRepeatKey (".__METHOD__.")";
        $this->addLog($str,FATAL);
      }else{
        //We have one itemgroupdata - we need to know if the annotation element is here
        //$this->addLog("NodeName = ".$igdata->item(0)->getElementsByTagName("Annotation"),INFO);
        $igAnnot = $igdata->item(0)->getElementsByTagName("Annotation");
        if($igAnnot->length==0){
          $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                    UPDATE
                    insert <Annotation SeqNum='1'>
                              <Flag>
                                <FlagValue CodeListOID='CL.SSTATUS'>FILLED</FlagValue>
                                <FlagType CodeListOID='CL.FLAGTYPE'>STATUS</FlagType>
                              </Flag>
                            </Annotation>
                    preceding collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
                                                        /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                                        /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                                        /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
                                                        /odm:*[1]";
          $this->m_ctrl->socdiscoo()->query($query); 
        }
      }
    }
    
    
    
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
          // Here we handle itemoid containing '_' character in itemoid string
          $rawItemOID = str_replace("_".end($varParts),"",$key);  //remove ItemGroupRepeatKey              
          if($varParts[0]=="radio" || $varParts[0]=="select"){
            $rawItemOID = str_replace($varParts[0]."_","",$rawItemOID);  //remove ItemGroupRepeatKey              
          }else{
            $rawItemOID = str_replace($varParts[0]."_".$varParts[1]."_","",$rawItemOID);  //remove ItemGroupRepeatKey
          }
          
          $ItemOID = str_replace("@",".",$rawItemOID);
          
          $this->addLog("rawItemOID=$rawItemOID ItemOID=$ItemOID",TRACE);
          
          $tblFilledVar["$ItemOID"] = $value;
        }
      }
      
    }
      
    //Get all Items to save from metadata (Format,...)
    //and look for existing value (usefull for TransactionType)
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
      let \$LastItemData := \$ItemData[1]
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
      $ItemGroupRef = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Error in query " . $e->getMessage() . " " . $query ." (".__METHOD__.")";
      $this->addLog($str,FATAL);
    }

    $tblItemDatas = $this->addItemData($SubjectKey,$ItemGroupRepeatKey,$ItemGroupRef[0],$formVars,$tblFilledVar,$subj,$AuditRecordID,!$bFormVarsIsAlreadyDecoded,$nbAnnotations);
    $strItemDatas = implode(',',$tblItemDatas);      
    //Update XML DB only if needed
    if($strItemDatas!="")
    { 
      $hasModif = true;
      $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                UPDATE
                insert ($strItemDatas)
                following collection('ClinicalData')/odm:ODM[@FileOID='$SubjectKey']/odm:ClinicalData/odm:SubjectData
                                                    /odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                                    /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']/odm:Annotation";
      $this->m_ctrl->socdiscoo()->query($query);
    }

    if($hasModif){
      //We may need to update the SITEID - SiteRef Element
      $SITEIDdef = $this->m_tblConfig['SUBJECT_LIST']['COLS']['SITEID']['Value'];     
      if($StudyEventOID==$SITEIDdef['SEOID'] && $StudyEventRepeatKey==$SITEIDdef['SERK'] && 
         $FormOID==$SITEIDdef['FRMOID'] && $FormRepeatKey==$SITEIDdef['FRMRK'] && 
         $ItemGroupOID==$SITEIDdef['IGOID'] && $ItemGroupRepeatKey==$SITEIDdef['IGRK']){
 
        $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                  UPDATE
                  replace \$r in index-scan('SubjectData','$SubjectKey','EQ')/odm:SiteRef/@LocationOID
                  with attribute LocationOID {index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='{$SITEIDdef['SEOID']}' and @StudyEventRepeatKey='{$SITEIDdef['SERK']}']/
                                                                    odm:FormData[@FormOID='{$SITEIDdef['FRMOID']}' and @FormRepeatKey='{$SITEIDdef['FRMRK']}']/
                                                                    odm:ItemGroupData[@ItemGroupOID='{$SITEIDdef['IGOID']}' and @ItemGroupRepeatKey='{$SITEIDdef['IGRK']}']/
                                                                    odm:*[@ItemOID='{$SITEIDdef['ITEMOID']}'][1]}";  

        $this->addLog("bocdiscoo->saveItemGroupData() : updating SiteRef",INFO); 
        $this->m_ctrl->socdiscoo()->query($query);
      }
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
  
/**
 *Callback function used int getXQueryConsistency, to build let section
 *@author tpi 
 **/
  private function updateFormalExpression($matches){
    if(!isset($this->handeldExpressionsForExpressionUpdate[$matches[0]])){ //already matched
      $var = "$". chr(97 + $this->iVarForExpressionUpdate); //ascii code for a,b,c etc => $a, $b, $c)
      $this->expressionForExpressionUpdate .= "let ". $var ." := ". $matches[0] ."\n";
      $this->handeldExpressionsForExpressionUpdate[$matches[0]] = $this->iVarForExpressionUpdate; //liste of matched expressions
      $this->iVarForExpressionUpdate++;
    }else{
      $var = "$". chr(97 + $this->handeldExpressionsForExpressionUpdate[$matches[0]]); //ascii code for a,b,c etc => $a, $b, $c)
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