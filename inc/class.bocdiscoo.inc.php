<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *
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
  function bocdiscoo(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }

/*
Convert input POSTed data to XML string ODM Compliant, regarding metadata
@param boolean $bEraseNotFoundItem erase in XML Db Item not present in incoming data. usefull for clean disabled inputs 
@return array array of ItemDatas to be inserted into ItemGroupData, empty string if no modification 
@author wlt
*/
  private function addItemData($SubjectKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemGroupRef,$formVars,&$tblFilledVar,$subj,$AuditRecordID,$bEraseNotFoundItem=true,$nbAnnotations)
  {
    $this->addLog(__METHOD__ ."() : tblFilledVar = " . $this->dumpRet($tblFilledVar),TRACE);
    $tblRet = array();

    //Loop through all ItemDef for current ItemGroup
    foreach($ItemGroupRef as $Item)
    {
      //Annotations management : RAS/ND/NSP + comment
      $AnnotationID = "";
      $bAnnotationModif = false;
      $flag = $formVars["annotation_flag_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupOID" . "_$ItemGroupRepeatKey"];
      $previousflag = $formVars["annotation_previousflag_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupOID" . "_$ItemGroupRepeatKey"];
      $comment = $formVars["annotation_comment_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupOID" . "_$ItemGroupRepeatKey"];
      $previouscomment = $formVars["annotation_previouscomment_" . str_replace(".","-",$Item['ItemOID']) . "_$ItemGroupOID" . "_$ItemGroupRepeatKey"];
      
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
                  into index-scan('SubjectData','$SubjectKey','EQ')/../odm:Annotations";
        $this->m_ctrl->socdiscoo()->query($query);
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

  private function checkFormConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)
  {
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID)", INFO);

    //Loop through ItemDatas having Edit Checks (RangeCheck in ODM)
    $query = "
        import module namespace alix = 'http://www.alix-edc.com/alix';
         
        let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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
              let \$ItemData := \$ItemDatas[last()]
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
    $ctrls = $this->m_ctrl->socdiscoo()->query($query);
    $errors = array();

    if($ctrls[0]->getName()!="NoItemGroupData")
    {
      foreach($ctrls as $ctrl)
      {
        if($ctrl->getName()!="NoControl"){        
          $testXQuery = $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl);
          try{
            $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery,true,false,true);
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
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemOID,$Value,\$ErrorMessage,\$FormalExpression,\$FormalExpressionDecode,$SoftHard)", TRACE);
    
    $whereItemData = "";
    if($Value==false){
      $whereItemData = "where \$ItemData/string()!=''";
    }
    
    //Loop through ItemDatas with ItemDef having a FormalExpression (EditChecks)
    $query = "
        let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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
    $ctrls = $this->m_ctrl->socdiscoo()->query($query);
    $errors = array();

    foreach($ctrls as $ctrl)
    {
      $testXQuery = $macros . $this->getXQueryConsistency($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ctrl,$Value);
      try{
        $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery,true,false,true);
      }catch(xmlexception $e){
        //Error is probably due to the edit check code. Error is not display to the user, and administrator notified by email             
        $str = "xQuery error : " . $e->getMessage() . " " . $testXQuery;
        $this->addLog($str,ERROR);
        //Have to return the message !
        return $str;
      }

      $this->addLog(__METHOD__ ."() Control[{$StudyEventOID}][{$FormOID}][{$ctrl['ItemGroupOID']}][{$ctrl['ItemGroupRepeatKey']}]['{$ctrl['ItemOID'] }'] => Result=" . $ctrlResult[0]->Result, INFO);
      if($ctrlResult[0]->Result=='false'){
        if($ctrl['SoftHard']=="Hard"){
          $type = 'HC';
        }else{
          $type = 'SC';
        }
        //Handle of multiple decodes into error message
        $nbS = substr_count($ctrl['ErrorMessage'],"%s");
        if($nbS>0){
          $tblParams = explode(' ',$ctrlResult[0]->Decode . str_pad("",$nbS));
          $tblParams = str_replace("¤", " ", $tblParams); //restauration des espaces ' ' substitués (par des '¤', cf getXQueryConsistency())
          $ctrl['ErrorMessage'] = str_replace("\"","'",$ctrl['ErrorMessage']);
          $cmdEval = "\$desc = sprintf(\"".$ctrl['ErrorMessage']."\",\"".implode('","',$tblParams)."\");";
          eval($cmdEval);
        }else{
          //No decode, simply display the error messages as-is
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
      //test if value to test exists
      $value = $this->getValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,"","",$ItemOID,"");
      if($value=="") return "No error. Value not found or empty in the CRF.";
    }
    
    return $errors;
  }
  
  /**
  * Verify conformity of submitted values against associated metadata type
  * @param string $MetaDataVersion
  * @param string $ItemGroupOID
  * @param string $ItemGroupRepeatKey
  * @param array $formVars received POSTed vars
  * @return array array of found errors
  * @author wlt 
  **/
  function checkItemGroupDataSanity($SubjectKey,$MetaDataVersion,$ItemGroupOID,$ItemGroupRepeatKey,$formVars)
  {
    $this->addLog(__METHOD__ ."($SubjectKey,$MetaDataVersion,$ItemGroupOID,$ItemGroupRepeatKey,formVars)",INFO);

    $Form = array();
    $errors = array();
	
    //loop throught submited vars
    foreach($formVars as $key=>$value)
    {
		  //get Item OID
      $varParts = explode("_",$key);
      $ItemOID = str_replace("@",".",$varParts[count($varParts)-2]);
      //handle only vars having values. Date are processed below
      if($value!="" && !in_array($varParts[1],array('dd','mm','yy')) ){
        $Form["$ItemOID"] = $value;
      }
    }

    //Request metadata
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

    $results = $this->m_ctrl->socdiscoo()->query($query);

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
          //Handle of Date and PartialDate type
          case 'partialDate' :
            $isBadFormated = false;
            $dd = $formVars["text_dd_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $mm = $formVars["text_mm_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            $yy = $formVars["text_yy_" . str_replace(".","@",$Item['ItemOID']) . "_$ItemGroupRepeatKey"];
            
            if(!$isBadFormated){
              //First, we need numeric values !
              if(($dd!="" && !is_numeric($dd)) || ($mm!="" && !is_numeric($mm)) || ($yy!="" && !is_numeric($yy))){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //If month is filled, year must be filled
              //If day is filled, month and year must be filled
              if(($mm!="" && $yy=="") || ($dd!="" && ($mm=="" || $yy==""))){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //In any case, check year. Note that the mktime fonction does not support year lower than 1901
              if($yy!=""){
                if($yy<=1901 || $yy>date('Y')+5){
                  $isBadFormated = true;
                }else{
                  $Form["{$Item['ItemOID']}"] = "$yy";
                }
              }
            }
            
            if(!$isBadFormated){
              //If we have all date parts, normal check 
              if($dd!="" && $mm!="" && $yy!=""){
                if(!@checkdate($mm,$dd,$yy)){
                  $isBadFormated = true;
                }else{
                  $Form["{$Item['ItemOID']}"] = "$yy-$mm-$dd";
                }
              }else{
                //Only year and month
                //For date, sanity boundary are 1901 <=> current year + 10 year
                if($mm!="" && $yy!=""){
                  if($mm>12 || $yy<=1901 || $yy>date('Y')+10){
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
              //First, we need numeric values !
              if(($dd!="" && !is_numeric($dd)) || ($mm!="" && !is_numeric($mm)) || ($yy!="" && !is_numeric($yy))){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //If we have one parameters, we need all parameters
              if(($dd!="" || $mm!="" || $yy!="") && ($dd=="" || $mm=="" || $yy=="")){
                $isBadFormated = true;
              }
            }
            
            if(!$isBadFormated){
              //In any case, check year. Note that the mktime fonction does not support year lower than 1901
              if($yy!=""){
                if($yy<=1901 || $yy>date('Y')+5){
                  $isBadFormated = true;
                }
              }
            }
            
            if(!$isBadFormated){
              if($dd!="" && $mm!="" && $yy!=""){
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
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)", INFO);

    $query = "
        let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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
      $str = "XQuery error " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    
    //Loop through errors to run CollectionConditionException
    $tblRet = array();
    foreach($errors[0] as $error){
      $this->addLog(__METHOD__ ."() : error=".$this->dumpRet($error),TRACE);
      
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
          $ctrlResult = $this->m_ctrl->socdiscoo()->query($testXQuery,true,false,true);
        }catch(xmlexception $e){
          //Error is probably due to the ConditionDef code. Error is not display to the user, and administrator notified by email             
          $str = "Mandatory : Erreur in editcheck code : " . $e->getMessage() . " " . $testXQuery;
          $this->addLog($str,ERROR);
          $desc = "Misformated control on the {$error['ItemOID']} value. Notification was sent to administrator.";
          $errors["$desc"] = array( 'desc' => $desc,
                                    'type' => 'badformat',
  								                  'itemOID' => $error['ItemOID']);
        }
  
        $this->addLog(__METHOD__ ."() Control[{$StudyEventOID}][{$FormOID}][{$error['ItemGroupOID']}][{$error['ItemGroupRepeatKey']}]['{$error['ItemOID'] }'] => Result=" . $ctrlResult[0]->Result, INFO);
        
        if($ctrlResult[0]->Result=='true'){
          // CollectionConditionException return true - the Item is no more longer mandatory
          $this->addLog(__METHOD__ ."() : " . $error['ItemOID'] . " is no longer mandatory",INFO);
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
            $cmdEval = "\$desc = sprintf(\"".addcslashes($error["Description"],"\"")."\",\"".implode('","',$tblParams)."\");";
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
  
  /**
  * Create a new subject. The BLANK patient is duplicated, and saved into a new subject document 
  * @return string the new SubjectKey  
  **/    
  function enrolNewSubject()
  {
    $newSubj = $this->m_ctrl->socdiscoo()->getDocument("ClinicalData",$this->m_tblConfig['BLANK_OID']);

    //Get the new Patient id
    $subjKey = $this->getNewPatientID($site);
    //zero padding
    $subjKey = sprintf($this->m_tblConfig["SUBJID_FORMAT"],$subjKey);

    //Update the BLANK copy
    $newSubj['FileOID'] = $subjKey;
    $newSubj['Description'] = "";
    $newSubj->ClinicalData->SubjectData['SubjectKey'] = $subjKey;

    //Save the new patient
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
    
    $query = "let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
              let \$SubjectBLANK := index-scan('SubjectData','".$this->m_tblConfig["BLANK_OID"]."','EQ')
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
                                                    let \$ItemData := \$ItemDatas[last()]
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

    $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    return $doc;
  }
  
  /**
   * Get the Audit Trail for requested sites between $startDate and $enDate
   * @param array $sitesList
   * @param string $startDate (ISO format) YYYY-MM-DD
   * @param string $endDate (ISO format) YYYY-MM-DD
   * @return array of SimpleXML Objects <audit subjectKey='{\$SubjectKey'
                                               studyEvent='{\$StudyEventTitle}'
                                               form='{\$FormTitle}'
                                               item='{\$ItemTitle}'
                                               value='{\$Value}'
                                               user='{\$UserOID}'
                                               auditDate='{\$AuditDate}'                           
                    />            
   **/     
  public function getAuditTrailByDate($sitesList,$startDate,$endDate){

    $queryCol = array();
    foreach($sitesList as $siteId){
      $queryCol[] = "index-scan('SiteRef','$siteId','EQ')";  
    }
   
    $SubjectDatasSelect = implode(" union ",$queryCol);

    $startDate .= "T00:00:00";
    $endDate .= "T00:00:00";
    
    $query = "import module namespace alix = 'http://www.alix-edc.com/alix';
                  
              let \$SubjectDatas := $SubjectDatasSelect
              for \$SubjectData in \$SubjectDatas 
                let \$SubjectKey := \$SubjectData/@SubjectKey 
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
                for \$AuditRecord in \$SubjectData/../
                      odm:AuditRecords/odm:AuditRecord[xs:dateTime(odm:DateTimeStamp) > xs:dateTime('$startDate') and  
                                                       xs:dateTime(odm:DateTimeStamp) < xs:dateTime('$endDate')]
                 let \$AuditDate := \$AuditRecord/odm:DateTimeStamp 
                 let \$UserOID := \$AuditRecord/odm:UserRef/@UserOID 
                 let \$ID := \$AuditRecord/@ID
                 for \$ItemData in \$SubjectData/odm:StudyEventData/odm:FormData/odm:ItemGroupData/odm:*[@AuditRecordID=\$ID]
                   let \$Value := alix:getDecode(\$ItemData,\$MetaDataVersion)
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
                    />";    
    $result = $this->m_ctrl->socdiscoo()->query($query);

    return $result;
  }
  
  /**
   * Return the audit trail of the requested item
   **/     
  public function getAuditTrail($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)
  {
    $this->addLog(__METHOD__."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemOID)",INFO);

    $query = "let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
              let \$StudyEventData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
              let \$ItemGroupData := \$StudyEventData/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
              let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID='$ItemOID']
              
              return
                    (: audit trail of item \$ItemOID :)
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
    $doc = $this->m_ctrl->socdiscoo()->query($query,false);

    $this->addLog(__METHOD__." return ".$doc->saveXML(),TRACE);
    
    return $doc;
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
      let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
      let \$value := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType))]/odm:*[@ItemOID='$ItemOID'][last()]
      let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      let \$codeListOID := \$MetaDataVersion/odm:ItemDef[@OID='$ItemOID']/odm:CodeListRef/@CodeListOID
      let \$decodedValue := \$MetaDataVersion/odm:CodeList[@OID=\$codeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()
      return
        <result value='{\$decodedValue}' />
    ";
    
    $doc = $this->m_ctrl->socdiscoo()->query($query);

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
    $sxe = $this->m_ctrl->socdiscoo("BLANK")->query($query,true);
    
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
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey)",INFO);
    
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
              let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
              for \$FormData in \$SubjectData/odm:StudyEventData$whereStudy/
                                                   odm:FormData$whereNotEmpty
              return
                <FormData
                  StudyEventOID='{\$FormData/../@StudyEventOID}'
                  StudyEventRepeatKey='{\$FormData/../@StudyEventRepeatKey}'
                  FormOID='{\$FormData/@FormOID}'
                  FormRepeatKey='{\$FormData/@FormRepeatKey}'/>
             ";
    $FormDatas = $this->m_ctrl->socdiscoo()->query($query);
    return $FormDatas;
  }
    
  function getItemDataTypes($igoid, $subj)
  {
              
    $query = "  let \$SubjectData := index-scan('SubjectData','$subj','EQ')
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
    $doc = $this->m_ctrl->socdiscoo()->query($query);

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
              let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
              let \$ItemGroupData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID']/
                                                   odm:FormData[@FormOID='$FormOID']/
                                                   odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$RepeatKey']
              for \$ItemData in \$ItemGroupData/odm:*
              let \$MaxAuditRecordID := max(\$ItemGroupData/odm:*[@ItemOID=\$ItemData/@ItemOID]/string(@AuditRecordID))
              where \$ItemData/@AuditRecordID = \$MaxAuditRecordID
              return
                <Item OID='{\$ItemData/@ItemOID}'
                      Value='{\$ItemData/string()}'/>
             ";

    $ItemGroupData = $this->m_ctrl->socdiscoo()->query($query);
    return $ItemGroupData;
  }

/*
@modification wlt le 02/03/2011 : passage de public a private
                                  paramètres StudyEventOID,StudyEventRepeatKey,FormOID et FormRepeatKey rendus optionnels
              wlt le 03/03/2011 : Ajout du Status dans les données retournées
              tpi le 06/06/2012 : passage private à public pour utilisation dans hookFunctions.php
@author tpi, wlt
*/
  public function getItemGroupDatas($SubjectKey,$StudyEventOID="",$StudyEventRepeatKey="",$FormOID="",$FormRepeatKey="")
  {
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);
    $query = "
              let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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
    $ItemGroupData = $this->m_ctrl->socdiscoo()->query($query);
    return $ItemGroupData;
  }
  
  /* return the number of itemgroupdata for specified parameters - included Removed ItemGroupData
  */
  public function getItemGroupDataCount($SubjectKey,$StudyEventOID="",$StudyEventRepeatKey="",$FormOID="",$FormRepeatKey="")
  {
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",TRACE);
    $query = "
              let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
              let \$FormData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey' or 
                                                                 '$StudyEventOID'='' and '$StudyEventRepeatKey'='']/
                                              odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey' or 
                                                           '$FormOID'='' and '$FormRepeatKey'='']
               let \$countIG := count(\$FormData/odm:ItemGroupData)
              return
                <ItemGroupDatas CountIG='{\$countIG}'/>
             ";

    $ItemGroupDataCount = $this->m_ctrl->socdiscoo()->query($query);

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
    
    $Result = $this->m_ctrl->socdiscoo()->query($query, true);
    
    if($ligneResult = $Result[0]){
      $subjKey = (string)$ligneResult + 1;
    }else{
      $subjKey = 1;
    }
    
    //HOOK => bocdiscoo_getNewPatientID_customSubjId
    $newSubjKey = $this->callHook(__FUNCTION__,"customSubjId",array($this));
    
    if($newSubjKey!=false){
      $subjKey = $newSubjKey;
    }
    
    return $subjKey;
  }

  /**
   * Return the list of StudyEvents for a subject.
   * @author wlt         
   **/  
  public function getStudyEventDatas($SubjectKey)
  {
    $this->addLog(__METHOD__."($SubjectKey)",INFO);
    $query = "
              let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
              for \$StudyEventData in \$SubjectData/odm:StudyEventData
              return
                <StudyEventData StudyEventOID='{\$StudyEventData/@StudyEventOID}'
                               StudyEventRepeatKey='{\$StudyEventData/@StudyEventRepeatKey}'/>
             ";

    try{
    $StudyEventData = $this->m_ctrl->socdiscoo($SubjectKey)->query($query);
    }catch(xmlexception $e){
      $str = "<html>Erreur de la requete : " . htmlentities($e->getMessage()) . "<br/><br/>" . htmlentities($query) . "</html> (". __METHOD__ .")";
      $this->addLog("Erreur : getStudyEventDatas($SubjectKey) => $str",FATAL);
      die($str);
    }
    return $StudyEventData;
  }

  /**
   * Return the requested form for a subject. 
   * First we build the metadata skeleton of form and itemgroup / item children,
   * then this skeleton is filled with values, codelists, units, translated element, ...
   * @author wlt         
   **/     
  function getStudyEventForms($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$includeAuditTrail=false,$paginateStart=0,$paginateEnd=0)
  {
    $this->addLog(__METHOD__."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);

    //Depending of the form, additionnal item could be retrieved - see config.inc.php
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
    
    //audit trail xquery
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
    
    $query = "import module namespace alix = 'http://www.alix-edc.com/alix';
              let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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
                              let \$ItemData := \$ItemDatas[last()]
                              let \$ItemDataDecode := alix:getDecode(\$ItemData,\$MetaDataVersion)
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

    $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    return $doc;
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
    $doc = $this->m_ctrl->socdiscoo()->query($query);

    return $doc;                        
  }

  /**
   * Get all subject(s) forms and visits, with status for each form (EMPTY, PARTIAL, INCONSISTENT, FROZEN)
   * @return DOMDocument
   * @author wlt, tpi
   **/  
  public function getSubjectsTblForm($SubjectKey)
  {
    $this->addLog(__METHOD__ ."($SubjectKey)",INFO);
    
    $query = "     
        let \$SubjectData := index-scan('SubjectData', '$SubjectKey', 'EQ')
        let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
        return
          <SubjectData SubjectKey='{\$SubjectData/../../@FileOID}' MetaDataVersionOID='{\$MetaDataVersion/@OID}'>
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
                                          Mandatory='{\$FormRef/@Mandatory}'
                                          TransactionType='{\$FormData/@TransactionType}'
                                          ItemGroupDataCount='{\$ItemGroupDataCount}'
                                          ItemGroupDataCountEmpty='{\$ItemGroupDataCountEmpty}'
                                          ItemGroupDataCountFrozen='{\$ItemGroupDataCountFrozen}'
                                          Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'>
                                </FormData>
                    else
                        <FormData FormOID='{\$FormRef/@FormOID}'
                                  FormRepeatKey='0'
                                  Mandatory='{\$FormRef/@Mandatory}'
                                  MetaDataVersionOID='{\$MetaDataVersion/@OID}'
                                  Title='{\$MetaDataVersion/odm:FormDef[@OID=\$FormRef/@FormOID]/odm:Description/odm:TranslatedText[@xml:lang='{$this->m_lang}']/string()}'
                                  Status='EMPTY'>
                        </FormData>
              }
              </StudyEventData>
          }
          </SubjectData>";

    $doc = $this->m_ctrl->socdiscoo()->query($query,false);
    
    //Custom XSL Form for conditional forms - Applied only if exists
    $MetaDataVersionOID = $doc->documentElement->getAttribute("MetaDataVersionOID");
    $xslTblFormFile = EGW_INCLUDE_ROOT ."/".$this->getCurrentApp(false)."/custom/$MetaDataVersionOID/xsl/SubjectsTblForm.xsl";
    if(file_exists($xslTblFormFile))
    { 
      $xslTblForm = new DOMDocument;
      $xslTblForm->load($xslTblFormFile);
      
      $proc = new XSLTProcessor;
      $proc->importStyleSheet($xslTblForm);
      
      //HOOK => bocdiscoo_getSubjectsTblForm_xslParameters
      $this->callHook(__FUNCTION__,"xslParameters",array($SubjectKey,$proc,$this));
      
      $doc = $proc->transformToDoc($doc);
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
        $nbFormPartial = 0;
        $nbFormInconsistent = 0;
        $nbFormFilled = 0;
        $StudyEventOID = $visit->getAttribute('StudyEventOID');
        $StudyEventRepeatKey = $visit->getAttribute('StudyEventRepeatKey');
        
        //Loop through forms
        foreach($visit->childNodes as $form){
          if($form->nodeType!=1) continue; //tpi, why are there some DOMText ?
          $frmStatus = $form->getAttribute('Status');
          if(!$frmStatus){
            $FormOID = $form->getAttribute('FormOID');
            $FormRepeatKey = $form->getAttribute('FormRepeatKey');                 
            
            $ItemGroupDataCount = $form->getAttribute('ItemGroupDataCount');
            $ItemGroupDataCountEmpty = $form->getAttribute('ItemGroupDataCountEmpty');
            $ItemGroupDataCountFrozen = $form->getAttribute('ItemGroupDataCountFrozen');
            
            if($ItemGroupDataCount==$ItemGroupDataCountEmpty){
              $frmStatus = "EMPTY"; 
            }else{
              if($ItemGroupDataCount==$ItemGroupDataCountFrozen){
                $frmStatus = "FROZEN";
              }else{
                $frmStatus = $this->m_ctrl->boqueries()->getFormStatus($SubjectKey, $StudyEventOID ,$StudyEventRepeatKey, $FormOID, $FormRepeatKey);
              }           
            }
            $form->setAttribute("Status",$frmStatus);
          }
          //counting forms and statuses => used to determine visit status
          $nbForm++;
          if($frmStatus=="EMPTY"){
            //if the form is not Mandatory, it should not be considered as empty to calculate the visit status => considered FILLED
            if($form->getAttribute('Mandatory')=="No"){
              $nbFormFilled++;
            }else{
              $nbFormEmpty++;
            }
          }elseif($frmStatus=="FROZEN"){
            $nbFormFrozen++;
          }elseif($frmStatus=="MISSING"){
            $nbFormPartial++;
          }elseif($frmStatus=="INCONSISTENT"){
            $nbFormInconsistent++;
          }elseif($frmStatus=="FILLED"){
            $nbFormFilled++;
          }
        }
        if($nbForm==$nbFormEmpty){
          $visitStatus = "EMPTY";
        }else{
          if($nbForm==$nbFormFilled){
            $visitStatus = "FILLED"; 
          }else{
            if($nbForm==$nbFormFrozen){
              $visitStatus = "FROZEN"; 
            }else{
              if($nbFormInconsistent>0){
                $visitStatus = "INCONSISTENT";
              }else{
                  $visitStatus = "MISSING";
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
    $whereIGString = "";
    if($exclude!==false){
      if(!is_array($exclude)) $exclude = array($exclude);
      foreach($exclude as $val){
        $whereIGString .= " and ./odm:*[@ItemOID='$ItemOID'][last()]/string()!=\"$val\"";
      }
    }
    
    $query = "
      let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
      let \$value := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                 /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                 /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey' and @TransactionType!='Remove' $whereIGString]
                                 /odm:*[@ItemOID='$ItemOID'][last()]/string()
      return
        <result value='{\$value}' />
    ";
    
    $doc = $this->m_ctrl->socdiscoo()->query($query);

    return (string)$doc[0]['value'];
  }

  /*
  * @desc return decoded data for an ItemGroupData
  * @author TPI
  */  
  public function getDecodedValues($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)
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
      declare function local:getDecode(\$ItemData as node()*,\$SubjectData as node(),\$MetaDataVersion as node()) as xs:string?
      {
        let \$value := \$ItemData[last()]
        let \$CodeListOID := \$MetaDataVersion/odm:ItemDef[@OID=\$ItemData/@ItemOID]/odm:CodeListRef/@CodeListOID
        return
          if(\$CodeListOID)
          then \$MetaDataVersion/odm:CodeList[@OID=\$CodeListOID]/odm:CodeListItem[@CodedValue=\$value]/odm:Decode/odm:TranslatedText[@xml:lang='".$this->m_lang."']/string()
          else \$value
      };
      
      let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
      let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
      let \$ItemGroupData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType))]
      return
        <results>
        {
          for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
          let \$ItemDatas := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID]
          let \$ItemData := \$ItemDatas[last()]
          let \$ItemDataDecode := local:getDecode(\$ItemData,\$SubjectData,\$MetaDataVersion)
          return
            <result ItemOID='{\$ItemOID}' value='{\$ItemDataDecode}' />
        }
        </results>
    ";
    $doc = $this->m_ctrl->socdiscoo($SubjectKey)->query($query);
    $results = array();
    foreach($doc[0] as $result){
      $results[(string)$result[0]['ItemOID']] = (string)$result[0]['value'];
    }    
    return $results;
  }

  /*
  * @desc return raw data for an ItemGroupData
  * @author TPI
  */    
  public function getValues($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)
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
      let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
      let \$ItemGroupData := \$SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' $andStudyEventRepeatKey]/odm:FormData[@FormOID='$FormOID' $andFormRepeatKey]/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' $andItemGroupRepeatKey and (@TransactionType!='Remove' or not(@TransactionType))]
      return
        <results>
        {
          for \$ItemOID in distinct-values(\$ItemGroupData/odm:*/@ItemOID)
          let \$value := \$ItemGroupData/odm:*[@ItemOID=\$ItemOID][last()]/string()
          return
            <result ItemOID='{\$ItemOID}' value='{\$value}' />
        }
        </results>
    ";
    
    try{
      $doc = $this->m_ctrl->socdiscoo($SubjectKey)->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html> (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }
    $results = array();
    foreach($doc[0] as $result){
      $results[(string)$result[0]['ItemOID']] = (string)$result[0]['value'];
    }
    
    return $results;
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
      let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)",INFO);
    
    //FormData
    $query = "UPDATE REPLACE \$x in index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";
    $res = $this->m_ctrl->socdiscoo()->query($query);
    
    //ItemGroupDatas
    $query = "UPDATE REPLACE \$x in index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";
    $res = $this->m_ctrl->socdiscoo()->query($query);
  }

  public function removeItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)
  {
    $this->addLog("bocdiscoo->removeItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey)",INFO);

    $query = "UPDATE REPLACE \$x in index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']/@TransactionType
              WITH attribute {'TransactionType'} {'Remove'}";
    $res = $this->m_ctrl->socdiscoo()->query($query);
  }

  /**
  * save into xml database the ItemGroupData
  * @return boolean true if data have been updated
  *                false if database update was unnecessary        
  * @author wlt                 
  **/
  function saveItemGroupData($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$formVars,$who,$where,$why,$fillst="",$bFormVarsIsAlreadyDecoded=false)
  {
    $hasModif = false;
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$formVars,$who,$where,$why,$fillst,$bFormVarsIsAlreadyDecoded)",INFO);
        
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

    //Add StudyEventData if needed
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']");
    if($result->length==0){
      //Get where to insert StudyEventData
      $query = "let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
                let \$StudyEventRef := \$MetaDataVersion/odm:Protocol/odm:StudyEventRef[@StudyEventOID='$StudyEventOID']
                return \$StudyEventRef/following-sibling::*[1]";
      $studyEventRef = $this->m_ctrl->socdiscoo()->query($query);
      
      $nextStudyEventOID = (string)$studyEventRef[0]['StudyEventOID'];
      
      $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                UPDATE
                insert <StudyEventData StudyEventOID='$StudyEventOID' StudyEventRepeatKey='$StudyEventRepeatKey' TransactionType='Insert'/>
                preceding index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$nextStudyEventOID']";
      $this->m_ctrl->socdiscoo()->query($query);
      $this->addLog("bocdiscoo()->saveItemGroupData() Adding StudyEventOID=$StudyEventOID StudyEventRepeatKey=$StudyEventRepeatKey preceding$nextStudyEventOID",INFO);
      
    }else{
      if($result->length!=1){
        $str = "Error duplicate entry StudyEventData[@StudyEventOID='$StudyEventOID' @StudyEventRepeatKey='$StudyEventRepeatKey] (". __METHOD__ .")";
        $this->addLog($str,FATAL);
      }
    }
    
    //Add FormData if needed
    $result = $xPath->query("/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']");
    if($result->length==0){
      $this->addLog("bocdiscoo()->saveItemGroupData() Adding FormData=$FormOID FormRepeatKey=$FormRepeatKey",TRACE);
      $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                UPDATE
                insert <FormData FormOID='$FormOID' FormRepeatKey='$FormRepeatKey' TransactionType='Insert'/>
                into index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']";
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
              following index-scan('SubjectData','$SubjectKey','EQ')/../odm:AuditRecords/odm:AuditRecord[last()]";
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
                into index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                                                 /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']";
      $this->m_ctrl->socdiscoo()->query($query);
    }else{
      if($igdata->length!=1){
        $str = "Error : duplicate entries for ItemGroupData=$ItemGroupOID RepeatKey=$ItemGroupRepeatKey (".__METHOD__.")";
        $this->addLog($str,FATAL);
      }else{
        //Update the ItemGroupData Status if needed
        $FlagValue = $igdata->item(0)->getElementsByTagName("FlagValue");
        if($FlagValue->item(0)->nodeValue == "EMPTY"){
          $this->setItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,"FILLED");    
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
    let \$SubjectData := index-scan('SubjectData','$SubjectKey','EQ')
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

    $ItemGroupRef = $this->m_ctrl->socdiscoo()->query($query);

    $tblItemDatas = $this->addItemData($SubjectKey,$ItemGroupOID,$ItemGroupRepeatKey,$ItemGroupRef[0],$formVars,$tblFilledVar,$subj,$AuditRecordID,!$bFormVarsIsAlreadyDecoded,$nbAnnotations);
    $strItemDatas = implode(',',$tblItemDatas);      
    //Update XML DB only if needed
    if($strItemDatas!="")
    { 
      $hasModif = true;
      $query = "declare default element namespace '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';
                UPDATE
                insert ($strItemDatas)
                following index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                                                      /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                                                      /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
                                                                      /odm:*[last()]";
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
                                                                    odm:*[@ItemOID='{$SITEIDdef['ITEMOID']}'][last()]}";  

        $this->addLog("bocdiscoo->saveItemGroupData() : updating SiteRef",INFO); 
        $this->m_ctrl->socdiscoo()->query($query);
      }
    }

    return $hasModif;
  }

  /**
  * Update ItemGroupData Status to FROZEN / FILLED / INCONSISTENT / PARTIAL / EMPTY
  **/
  protected function setItemGroupStatus($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status)
  {
    $this->addLog(__METHOD__ ."($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$status)",INFO);
    
    $query = "UPDATE REPLACE \$x in index-scan('SubjectData','$SubjectKey','EQ')/odm:StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']
                                                                                /odm:FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']
                                                                                /odm:ItemGroupData[@ItemGroupOID='$ItemGroupOID' and @ItemGroupRepeatKey='$ItemGroupRepeatKey']
                                                                                /odm:Annotation/odm:Flag[odm:FlagType/@CodeListOID='CL.FLAGTYPE']/odm:FlagValue
             WITH <odm:FlagValue CodeListOID=\"CL.IGSTATUS\">$status</odm:FlagValue>";
    $res = $this->m_ctrl->socdiscoo()->query($query);
  }

  /**
  * Set all itemgroupdata status of given form to FROZEN or FILLED according to boolean $bLock parameter
  * @author wlt
  **/
  public function setLock($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$bLock)
  {
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