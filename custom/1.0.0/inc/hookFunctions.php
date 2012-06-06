<?
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
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
  * @desc Hook called before the HTML restitution of a form
  * @param $FormOID OID of the form
  * @param $uisubject instance of uisubject
  * @return nothing
  * @author WLT
  **/  
function uisubject_getInterface_start($FormOID,$uisubject){  
}
  /**
  * @desc Define parameters passed to the XSL create the form final display
  * @param string $FormOID
  * @param XSLTProcessor $xslProc XSL processor  
  * @return XSLTProcessor HTML to display
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

      //Including UT lists into XSL
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
function bosubjects_updateSubjectInList_customVisitStatus($SubjectKey,$tblForm,$bosubjects){
  uisubject_getMenu_beforeRendering($SubjectKey,$tblForm,$bosubjects);
}

/**
 *@desc Should return the status of the subject in the CRF, determined from its data
 *@param SumpleXMLElement $subj : one subject as return by bosubjects->getSubjectsParams
 *@param string $SubjectStatus has to be updated 
 *@param bosubjects $bosubjects
 *@author tpi 
 */
function bosubjects_getSubjectStatus_customSubjectStatus($subj,&$SubjectStatus,$bosubjects){
    if($subj['colINCLUSIONDATE']!=""){
      $SubjectStatus = "Screened";
    }
}

function uisubject_getMenu_beforeRendering($SubjectKey,&$tblForm,$uisubject){
}

function bocdiscoo_getNewPatientID_customSubjId($bocdiscoo){
  //Return the new SubjectKey, incremented by site in this example
  $siteId = $_POST['text_string_ENROL@SITEID_0'];
  
  $query = "let \$SubjectsCol := collection('ClinicalData')
            let \$maxSubjId := max(\$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData[@SubjectKey!='BLANK' and odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']/odm:ItemDataString[@ItemOID='ENROL.SITEID']='$siteId']/@SubjectKey)   
            return <MaxSubjId>{\$maxSubjId}</MaxSubjId>";  

  $Result = $bocdiscoo->m_ctrl->socdiscoo()->query($query);
  
  if((string)$Result[0]!="")
  {
    $subjKey = (int)$Result[0] + 1;
  }
  else
  {
      $subjKey = $siteId."01";
  }
  
  $bocdiscoo->addLog("bocdiscoo_getNewPatientID_customSubjId() : New SubjId = $subjKey",INFO);
  
  return $subjKey;  
}

  /**
  * @desc Specific treatment of queries for each form when the queries are updated
  * @param string $FormOID
  * @param string $FormRepeatKey
  * @param string $queryType
  * @return array $queries
  * @author TPI
  **/  
function boqueries_updateQueries_form($FormOID, $FormRepeatKey, $queryType, $queries){
  //No query at enrolment
  if($FormOID=="FORM.ENROL"){
    $queries = array();
  }
}

  /**
  * @desc List of custom dashboards, will be used to create the menu in the left on the dashboard
  * @param uidashboard $uidashboard
  * @return array $boardItems
  * @author TPI
  **/  
function uidashboard_getMenu_boardMenu($uidashboard){
  $boardItems = array();
  $boardItems[] = array("id" => "saeList", "title" => "List of SAE"); //an id and a label
  $boardItems[] = array("id" => "keyFigures", "title" => "Key figures");
  //$boardItems[] = array("id" => "", "title" => "");
  //$boardItems[] = array("id" => "", "title" => "");
  return $boardItems;
}

  /**
  * @desc The content of the custom dashboards
  * @param string $id : the id of the specified custom dashboard (defined in uidashboard_getMenu_boardMenu)
  * @param string &$TITLE : the top title of the dashboard
  * @param string &$CONTENT : the html content of the dashboard
  * @param uidashboard $uidashboard
  * @author TPI
  **/  
function uidashboard_getInterface_boardContent($id,$TITLE,$CONTENT,$uidashboard){
  switch($id){
  
    case "saeList":
        $TITLE = "List of Serious Adverse Event";
        $CONTENT = "<div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
                      <table class='ui-grid-content ui-widget-content'>
                  			<thead>
                  				<tr>
                  					<th class='ui-state-default'> Site Id</th>
                  					<th class='ui-state-default'> Site Name</th>
                            <th class='ui-state-default'> Subject identifiant</th>
                  					<th class='ui-state-default'> Diagnosis</th>
                  					<th class='ui-state-default'> Action taken</th>
                  					<th class='ui-state-default'> Outcome</th>
                  					<th class='ui-state-default'> Causal relationship</th>
                  				</tr>
                  			</thead>
                        <tbody>";
        
        $query = "
              <aes>
              { 
                let \$SubjCol := collection('ClinicalData')
                for \$ItemGroupDataAE in \$SubjCol/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='AE']/odm:FormData[@FormOID='FORM.AE']/odm:ItemGroupData[@ItemGroupOID='AE' and @TransactionType!='Remove']
                let \$SubjectData := \$ItemGroupDataAE/../../../../odm:SubjectData
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
                let \$ItemGroupDataENROL := \$SubjectData/odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']
                
                let \$SiteId := \$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITEID'][last()]
                let \$SiteName := \$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITENAME'][last()]
                let \$SubjId := \$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SUBJID'][last()]
  							
                let \$Serious := \$ItemGroupDataAE/odm:*[@ItemOID='AE.AESER'][last()]
                
                let \$Diag := \$ItemGroupDataAE/odm:*[@ItemOID='AE.AETERM'][last()]
                let \$Action := \$ItemGroupDataAE/odm:*[@ItemOID='AE.AEACN'][last()]
                let \$Outcome :=  \$ItemGroupDataAE/odm:*[@ItemOID='AE.AEOUT'][last()]
                let \$Relation :=  \$ItemGroupDataAE/odm:*[@ItemOID='AE.AEREL'][last()]
  							
                let \$ActionDecode := \$MetaDataVersion/odm:CodeList[@OID='CL.\$AEACN']/odm:CodeListItem[@CodedValue=\$Action]/odm:Decode/odm:TranslatedText[@xml:lang='en']/string()                
  							let \$OutcomeDecode := \$MetaDataVersion/odm:CodeList[@OID='CL.\$OUT']/odm:CodeListItem[@CodedValue=\$Outcome]/odm:Decode/odm:TranslatedText[@xml:lang='en']/string()
                let \$RelationDecode := \$MetaDataVersion/odm:CodeList[@OID='CL.\$REL']/odm:CodeListItem[@CodedValue=\$Relation]/odm:Decode/odm:TranslatedText[@xml:lang='en']/string()
 								
 							  where \$Serious='1'
                return
                  <ae   siteId='{\$SiteId}'
                        siteName='{\$SiteName}'
                        subjId='{\$SubjId}'
                        subjectKey='{\$SubjectData/@SubjectKey}'
                        diag='{\$Diag}'
                        action='{\$Action}'
                        outcome='{\$Outcome}'
                        relation='{\$Relation}'
                        actionDecode='{\$ActionDecode}'
                        outcomeDecode='{\$OutcomeDecode}'
                        relationDecode='{\$RelationDecode}'
                        />
              }
              </aes>
              "; 
    
        try{
          $saes = $uidashboard->m_ctrl->socdiscoo()->query($query);
        }catch(xmlexception $e){
          $str = "xQuery error : " . $e->getMessage() ." (".__METHOD__.")";
          $this->addLog($str,FATAL);
        }
        
        $nbSAE = 0;
        $class = "";
        foreach($saes[0] as $sae)
        { 
            $nbSAE++;
            $class = ($class=="row_off"?"row_on":"row_off");
            $CONTENT .= "
                            <tr class='". $class ."'>
                              <td class='ui-widget-content'>". $sae['siteId'] ."</td>
                              <td class='ui-widget-content'>". $sae['siteName'] ."</td>
                              <td class='ui-widget-content'>". $sae['subjId'] ."</td>
                              <td class='ui-widget-content'>". $sae['diag'] ."</td>
                              <td class='ui-widget-content'>". $sae['actionDecode'] ."</td>
                              <td class='ui-widget-content'>". $sae['outcomeDecode'] ."</td>
                              <td class='ui-widget-content'>". $sae['relationDecode'] ."</td>
                            </tr>";
        }
        
        if($nbSAE==0){
          $CONTENT .= "<tr><td class='ui-widget-content'>No SAE.</td></tr>";
        }
        
        $CONTENT .= "
                          </tbody>
                        </table>
                      </div>";
      break;
      
    case "keyFigures":
        $TITLE = "Key figures";
        $stats = array(
                        array(
                              "label" => "Total number of screened subjects",
                              "query" =>" let \$value := count(\$SubjCol/odm:StudyEventData[@StudyEventOID='2' and @StudyEventRepeatKey='0']/
                                                                        odm:FormData[@FormOID='FORM.SV' and @FormRepeatKey='0']/
                                                                        odm:ItemGroupData[@ItemGroupOID='SV' and @ItemGroupRepeatKey='0']/
                                                                        odm:*[@ItemOID='SV.SVSTDTC'][last()][string() != ''])
                                          return <result value='{\$value}' />"
                              ),
                        array(                                          
                              "label" => "Subjects out of study",
                              "query" =>" let \$value := count(\$SubjCol/odm:StudyEventData[@StudyEventOID='5' and @StudyEventRepeatKey='0']/
                                                                        odm:FormData[@FormOID='FORM.SS' and @FormRepeatKey='0']/
                                                                        odm:ItemGroupData[@ItemGroupOID='DSSS' and @ItemGroupRepeatKey='0']/
                                                                        odm:*[@ItemOID='DS.DSCONT'][last()][string() = '0'])
                                          return <result value='{\$value}' />"
                              ),
                        array(
                              "label" => "Subjects who completed the study",
                              "query" =>" let \$value := count(\$SubjCol/odm:StudyEventData[@StudyEventOID='5' and @StudyEventRepeatKey='0']/
                                                                        odm:FormData[@FormOID='FORM.SS' and @FormRepeatKey='0']/
                                                                        odm:ItemGroupData[@ItemGroupOID='DSSS' and @ItemGroupRepeatKey='0']/
                                                                        odm:*[@ItemOID='DS.DSCONT'][last()][string() = '1'])
                                          return <result value='{\$value}' />"
                              ),
                        array(
                              "label" => "SAE",
                              "query" =>" let \$value := count(\$SubjCol/odm:StudyEventData[@StudyEventOID='AE' and @StudyEventRepeatKey='0']/
                                                                        odm:FormData[@FormOID='FORM.AE']/
                                                                        odm:ItemGroupData[@ItemGroupOID='AE' and @TransactionType!='Remove']/
                                                                        odm:*[@ItemOID='AE.AESER'][last()][string() = '1'])
                                          return <result value='{\$value}' />"
                              ),
                        );
        
        $CONTENT .= " <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
                        <table class='ui-grid-content ui-widget-content'>
                    			<thead>
                    				<tr>
                    					<th></th>
                    					<th class='ui-state-default'> Number</th>
                    				</tr>
                    			</thead>
                          <tbody>";
        
        foreach($stats as $stat){
          $query = "let \$SubjCol := collection('ClinicalData')/odm:ODM/odm:ClinicalData/odm:SubjectData";
          $query .= $stat['query'];

          $res = $uidashboard->m_ctrl->socdiscoo()->query($query);
    
          $res = (string)$res[0]['value'];
          
          $class = ($class=="row_off"?"row_on":"row_off");
          $CONTENT .= "
                          <tr class='". $class ."'>
                            <td class='ui-widget-content'>". $stat['label'] ."</td>
                            <td class='ui-widget-content'>". $res ."</td>
                          </tr>";
        }
        
        $CONTENT .= "
                          </tbody>
                        </table>
                      </div>";
      break;
  
  /*
    case "":
        $TITLE = "";
        $CONTENT = "";
      break;
  
    case "":
        $TITLE = "";
        $CONTENT = "";
      break;
  */
  
    default:
      $TITLE = "[$id]";
      $CONTENT = "Unknow dashboard Id '$id'.<br />Maybe you just forgot to create its content in hookFunctions.php";
  }
}



/**
* Hook called just before the creation of the profile page (html)
* @param $SubjectKey
* @param $html HTML generated 
* @param $uisubject
* @return nothing
* @author TPI
**/  
function uisubject_getProfile_profileContent($SubjectKey,$htmlContent,$uisubject){
  $htmlContent = "";
  
  //Template
  $template = dirname(__FILE__)."/templates/profile.htm";
  if(!file_exists($template)){
    $str = "Template not found '$template'.";
    $uisubject->addLog($str,ERROR);
  }
  $handle = fopen($template, "r");
  $htmlContent = fread($handle, filesize($template));
  fclose($handle);
  
  //Values
  $values = $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.ENROL","0","ENROL","0");
  $values += $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.IC","0","DM","0");
  $values += $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.IC","0","DS","0");
  $values += $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.MH2","0","DC","0");
  $values += $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.PTT","0","PTT","0");
  $values += $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"APPENDICES","0","FORM.TRT","0","TTI","0");
  
  //History (relevant)
  $HISTORY_values = array();
  $HISTORY = "";
  //Medical history
  $HValues = $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.DC","0","DC2","0");
  if($HValues['DC2.GROWTHDE']=="Y"){
    $HISTORY_values[] = "Growth delay";
  }
  if($HValues['CMD.RENAIMP']=="Y"){
    $HISTORY_values[] = "Renal impairment";
  }
  if($HValues['CMD.HEPAIMP']=="Y"){
    $HISTORY_values[] = "Hepatic impairment";
  }
  if($HValues['CMD.HEARTINS']=="Y"){
    $HISTORY_values[] = "Cardiac impairment";
  }
  if($HValues['MH2.MALARIA']=="Y"){
    $HISTORY_values[] = "Malaria";
  }
  if($HValues['MH2.TUBERCUL']=="Y"){
    $HISTORY_values[] = "Tuberculosis";
  }
  if($HValues['MH2.G6PD']=="Y"){
    $HISTORY_values[] = "G6PD deficiency ";
  }
  if($HValues['MH2.GENDIS']=="Y"){
    $HISTORY_values[] = utf8_decode($HValues['MH2.GENDIOTH']);
  }
  //Malignancies
  $DCMIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,"1","0","FORM.DC","0","DCM");
  foreach($DCMIGs as $DCMIG){
    if($DCMIG['ItemGroupRepeatKey']=="0") continue;
    $HValues = $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.DC","0","DCM",$DCMIG['ItemGroupRepeatKey']);
    $HISTORY_values[] = utf8_decode($HValues['DCM.DESC']);
  }
  //Other relevant diseases
  $CMOIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,"1","0","FORM.DC","0","CMO");
  foreach($CMOIGs as $CMOIG){
    if($CMOIG['ItemGroupRepeatKey']=="0") continue;
    $HValues = $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.DC","0","CMO",$CMOIG['ItemGroupRepeatKey']);
    $HISTORY_values[] = utf8_decode($HValues['CMO.DISDESC']);
  }
  //Final string
  $HISTORY .= ($HISTORY_values?"- ":"") . implode("<br />- ", $HISTORY_values);
  
  //Reasons for initiation
  $PREREAS = $values['TRT.PREREAS'];
  if($PREREAS=="Other") $PREREAS = utf8_decode($values['TRT.PRESOTH']);
  
  //Siklos® treatment
  $TRT = "";
  $TRTIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,"APPENDICES","0","FORM.TRT","0");
  $previousTRT_ACTION = "";
  foreach($TRTIGs as $TRTIG){
    if($TRTIG['ItemGroupRepeatKey']=="0") continue;
    $TRTvalues = $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"APPENDICES","0","FORM.TRT","0","TRT",$TRTIG['ItemGroupRepeatKey']);
    if($TRTvalues['TRT.ACTION']=="Modification"){
      if($TRTvalues['TRT.ACTION']!=$previousTRT_ACTION){
        $TRT .= "
            <tr>
              <td><b>Action</b>
              </td>
              <td><b>Date of modification</b>
              </td>
              <td><b>New dosage of treatment</b>
              </td>
              <td><b>Reason for modification</b>
              </td>
            </tr>
        ";
      }
      $TRT_DATE = $uisubject->formatDate($TRTvalues['TRT.NEWSTDT'], true);
      $TRT_DOSE = $TRTvalues['TRT.NEWDOSE'] ." ". ($TRTvalues['TRT.NEWDOSE']!=""?"mg/kg/day":"");
      $TRT_REAS = $TRTvalues['TRT.NEWREAS'];
      if($TRT_REAS=="Other") $TRT_REAS = utf8_decode($TRTvalues['TRT.OTHREAS']);
      $previousTRT_ACTION = $TRTvalues['TRT.ACTION'];
    }else{
      if($TRTvalues['TRT.ACTION']!=$previousTRT_ACTION){
        $TRT .= "
            <tr>
              <td><b>Action</b>
              </td>
              <td><b>Date treatment stopped</b>
              </td>
              <td><b>Dosage at stopping treatment</b>
              </td>
              <td><b>Reason for stopping treatment </b>
              </td>
            </tr>
        ";
      }
      $TRT_DATE = $uisubject->formatDate($TRTvalues['TRT.STOPDT'], true);
      $TRT_DOSE = $TRTvalues['TRT.STDOSE'] ." ". ($TRTvalues['TRT.STDOSE']!=""?"mg/kg/day":"");
      $TRT_REAS = $TRTvalues['TRT.STOPREAS'];
      if($TRT_REAS=="Other") $TRT_REAS = utf8_decode($TRTvalues['TRT.STOTHREA']);
      $previousTRT_ACTION = $TRTvalues['TRT.ACTION'];
    }
    $TRT .= "
        <tr>
          <td>". $TRTvalues['TRT.ACTION'] ."&nbsp;
          </td>
          <td>". $TRT_DATE ."&nbsp;
          </td>
          <td>". $TRT_DOSE ."&nbsp;
          </td>
          <td>". $TRT_REAS ."&nbsp;
          </td>
        </tr>
    ";
  }
  
  //Concomitants treatments
  $CTT = "";
  $CTTIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,"APPENDICES","0","FORM.CTT","0");
  foreach($CTTIGs as $CTTIG){
    $CTTvalues = $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"APPENDICES","0","FORM.CTT","0","CTT",$CTTIG['ItemGroupRepeatKey']);
    $CTT .= "
        <tr>
          <td>". $CTTvalues['CTT.TRTTYPE'] ."&nbsp;
          </td>
          <td>". $uisubject->formatDate($CTTvalues['CTT.STDT'], true) ."&nbsp;
          </td>
          <td>". $CTTvalues['CTT.ONGO'] ."&nbsp;
          </td>
          <td>". $uisubject->formatDate($CTTvalues['CTT.ENDT'], true) ."&nbsp;
          </td>
          <td>". utf8_decode($CTTvalues['CTT.INDIC']) ."&nbsp;
          </td>
        </tr>
    ";
  }
  
  //Sickle cell event
  $AESCD = "";
  //Events since last visit
  $SEs = $uisubject->m_ctrl->bocdiscoo()->getStudyEventDatas($SubjectKey);
  foreach($SEs as $SE){
    if($SE['StudyEventOID']!="FOLLOWUP") continue;
    $VISITDT = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.VDT","0","VDT","0","VDT.VISITDT");
    $Fs = $uisubject->m_ctrl->bocdiscoo()->getFormDatas($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey']);
    foreach($Fs as $F){
      if($F['FormOID']!="FORM.EVT") continue;
      $AESCDIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.EVT",$F['FormRepeatKey']);
      foreach($AESCDIGs as $AESCDIG){
        $AESCDIGvalues = $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.EVT",$F['FormRepeatKey'],$AESCDIG['ItemGroupOID'],$AESCDIG['ItemGroupRepeatKey']);
        if($AESCDIG['ItemGroupOID']=="MH2"){ //Events since last visit
          //Painful crisis > 48h
          if($AESCDIGvalues['MH2.VOCRISE']=="Yes"){
            $AESCD .= "
                <tr>
                  <td>". "Painful crisis > 48h" ."&nbsp;
                  </td>
                  <td>Number of crisis: ". $AESCDIGvalues['MH2.VOCRINUM'] ."&nbsp;
                  </td>
                  <td>". $uisubject->formatDate($VISITDT, true) ."&nbsp;
                  </td>
                  <td>". "" ."&nbsp;
                  </td>
                  <td>". $AESCDIGvalues['MH2.VOTRTCON'] ."&nbsp;
                  </td>
                </tr>
            ";
          }
          //Acute chest syndrome
          if($AESCDIGvalues['MH2.ACHEST']=="Yes"){
            $AESCD .= "
                <tr>
                  <td>". "Acute chest syndrome" ."&nbsp;
                  </td>
                  <td>Number of crisis: ". $AESCDIGvalues['MH2.ACHESNUM'] ."&nbsp;
                  </td>
                  <td>". $uisubject->formatDate($VISITDT, true) ."&nbsp;
                  </td>
                  <td>". "" ."&nbsp;
                  </td>
                  <td>". $AESCDIGvalues['MH2.ACTRTCON'] ."&nbsp;
                  </td>
                </tr>
            ";
          }
          //Hospitalisations related to SCD
          if($AESCDIGvalues['MH2.HOSP']=="Yes"){
            $AESCD .= "
                <tr>
                  <td>". "Hospitalisations related to SCD" ."&nbsp;
                  </td>
                  <td>Number of hospitalisations: ". $AESCDIGvalues['MH2.HOSPNB'] ."<br>Total number of days of hospitalisation: ". $AESCDIGvalues['MH2.HOSPNBDY'] ."
                  </td>
                  <td>". $uisubject->formatDate($VISITDT, true) ."&nbsp;
                  </td>
                  <td>". "" ."&nbsp;
                  </td>
                  <td>". $AESCDIGvalues['MH2.HOSTRTCO'] ."&nbsp;
                  </td>
                </tr>
            ";
          }
        }elseif($AESCDIG['ItemGroupOID']=="AESCD"){ //Other sickle cell event
          $TYPE = $AESCDIGvalues['AESCD.TYPE'];
          if($TYPE=="Other") $TYPE = utf8_decode($AESCDIGvalues['AE.OTHER']);
          $AESCD .= "
              <tr>
                <td>". $TYPE ."&nbsp;
                </td>
                <td>Outcome: ". $AESCDIGvalues['AE.EVOL'] ."&nbsp;
                </td>
                <td>". $uisubject->formatDate($VISITDT, true) ."&nbsp;
                </td>
                <td>". $AESCDIGvalues['AE.SEVERE'] ."&nbsp;
                </td>
                <td>". "" ."&nbsp;
                </td>
              </tr>
          ";
        }
      }
    }
  }
  
  //Concomitants treatments
  $AENOSCD = "";
  $AENOSCDIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,"APPENDICES","0","FORM.AENOSCD","0");
  foreach($AENOSCDIGs as $AENOSCDIG){
    $AENOSCDvalues = $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"APPENDICES","0","FORM.AENOSCD","0","AENOSCD",$AENOSCDIG['ItemGroupRepeatKey']);
    $TYPE = $AENOSCDvalues['AENOSCD.TYPE'];
    if($TYPE=="Other") $TYPE = utf8_decode($AENOSCDvalues['AE.OTHER']);
    $SERIOUS = $AENOSCDvalues['AE.SERIOUS'];
    if($SERIOUS=="Yes") $SERIOUS = $AENOSCDvalues['AE.SECR'];
    $AENOSCD .= "
        <tr>
          <td>". $TYPE ."&nbsp;
          </td>
          <td>". utf8_decode($AENOSCDvalues['AE.DESC']) ."&nbsp;
          </td>
          <td>". $uisubject->formatDate($AENOSCDvalues['AE.STDT'], true) ."&nbsp;
          </td>
          <td>". $AENOSCDvalues['AE.ONGO'] ."&nbsp;
          </td>
          <td>". $uisubject->formatDate($AENOSCDvalues['AE.ENDT'], true) ."&nbsp;
          </td>
          <td>". $AENOSCDvalues['AE.SEVERE'] ."&nbsp;
          </td>
          <td>". $AENOSCDvalues['AE.SIKLREL'] ."&nbsp;
          </td>
          <td>". $SERIOUS ."&nbsp;
          </td>
        </tr>
    ";
  }
  
  //Weight and Height the more recent (at the last visit where they have been entered)
  $WEIGHT = "";
  $HEIGHT = "";
  $DATEWEIGHT = "";
  $DATEHEIGHT = "";
  $LASTVISITDATE = "";
  $LASTVISITNAME = "";
  $nbSE = "".count($SEs);
  for($i=($nbSE-1); $i>=0; $i--){
    $SE = $SEs[$i];
    $WEIGHT = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],($SE['StudyEventOID']=="FOLLOWUP"?"FORM.CCC":"FORM.BOB"),"0","CCC","0","DM.WEIGHT");
    if($WEIGHT!=""){
      $DATEWEIGHT = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.VDT","0","VDT","0","VDT.VISITDT");
      break;
    }
  }
  for($i=($nbSE-1); $i>=0; $i--){
    $SE = $SEs[$i];
    $HEIGHT = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],($SE['StudyEventOID']=="FOLLOWUP"?"FORM.CCC":"FORM.BOB"),"0","CCC","0","DM.HEIGHT");
    if($HEIGHT!=""){
      $DATEHEIGHT = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.VDT","0","VDT","0","VDT.VISITDT");
      break;
    }
  }
  $MetaDescriptions = $uisubject->m_ctrl->bocdiscoo()->getDescriptions("1.0.0");
  for($i=($nbSE-1); $i>=0; $i--){
    $SE = $SEs[$i];
    $LASTVISITDATE = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.SV","0","SV","0","SV.SVSTDTC");
    if($LASTVISITDATE!=""){
      $LASTVISITNAME = $MetaDescriptions["1.0.0"]["StudyEventDef"]["{$SE['StudyEventOID']}"];
    }
  }
  
  //Remplacement dans le fichier html
  $code = array("SUBJID", "INITIALS", "SITEID", "SITENAME", "LASTVISITDATE", "LASTVISITNAME", "SEX", "BRTHDT", "INCDT", "HISTORY", "SCDDIADT", "SCDSTDT", "HUTRT", "WEIGHT", "HEIGHT", "DATEWEIGHT", "DATEHEIGHT", "PRESTDT", "PREDOSE", "PREREAS", "TRT", "CTT", "AESCD", "AENOSCD");
  $value = array($SubjectKey
                ,$values['ENROL.SUBJINIT']
                ,$values['ENROL.SITEID']
                ,utf8_decode($values['ENROL.SITENAME'])
                ,$uisubject->formatDate($LASTVISITDATE, true)
                ,$LASTVISITNAME
                ,$values['DM.SEX']
                ,$uisubject->formatDate($values['DM.BRTHDTC'], true)
                ,$uisubject->formatDate($values['DS.DSSTDTC'], true)
                ,$HISTORY
                ,$uisubject->formatDate($values['DC.SCDDIADT'], true)
                ,$uisubject->formatDate($values['DC.SCDSTDT'], true)
                ,$values['PTT.HUTRT']
                ,$WEIGHT
                ,$HEIGHT
                ,$uisubject->formatDate($DATEWEIGHT, true)
                ,$uisubject->formatDate($DATEHEIGHT, true)
                ,$uisubject->formatDate($values['TRT.PRESTDT'], true)
                ,$values['TRT.PREDOSE']
                ,$PREREAS
                ,$TRT
                ,$CTT
                ,$AESCD
                ,$AENOSCD
                );
  
  $htmlContent = str_replace(preg_replace("(.*)","{\${0}}",$code, 1), $value, $htmlContent);
}