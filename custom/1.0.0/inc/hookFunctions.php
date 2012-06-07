<?
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
  
  /**
  * @desc Hook called befor the HTML restitution of a form
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

function ajax_saveItemGroupData_afterSave($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,$hasModif,$ajax){
  //Send AE notification
  $ajax->addLog(__FUNCTION__ . "($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,\$ajax)",INFO);
  if($StudyEventOID=="AE" && $FormOID=="FORM.AE" && $hasModif ){
    //Template
    $template = dirname(__FILE__)."/templates/AE.htm";
    if(!file_exists($template)){
      $str = "Template not found '$template'.";
      $uisubject->addLog($str,ERROR);
    }
    $handle = fopen($template, "r");
    $htmlContent = fread($handle, filesize($template));
    fclose($handle);
    //Values
    $values = $ajax->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.ENROL","0","ENROL","0");
    $values += $ajax->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.IC","0","DS","0");    
    $values += $ajax->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.IC","0","DM","0");    
    $values += $ajax->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey);

    $code = array("{SITENAME}", "{SITEID}", "{INVID}", "{SUBJID}", "{BRTHDT}", "{SEX}", "{AERK}", "{DIAG}", "{STDT}", "{AESEV}", "{AECONTR}", "{AEACN}", "{AEOUT}", "{AEENDTC}", "{AESER}", "{AECOM}");
    $value = array($values['ENROL.SITENAME']
                  ,$values['ENROL.SITEID']
                  ,$values['DS.INVNAM']
                  ,$SubjectKey
                  ,$values['DM.BRTHDTC']
                  ,$values['DM.SEX']
                  ,$FormRepeatKey
                  ,utf8_decode($values['AE.AETERM'])
                  ,$values['AE.AESTDTC']
                  ,$values['AE.AESEV']
                  ,$values['AE.AECONTR']
                  ,$values['AE.AEACN']
                  ,$values['AE.AEOUT']
                  ,$values['AE.AEENDTC']
                  ,$values['AE.AESER']
                  ,$values['AE.AECOM']);
        
    $htmlContent = str_replace($code, $value, $htmlContent);
    
    $filename = $ajax->m_tblConfig["APP_NAME"].'_Adverse_Event_-_Patient_'. $SubjectKey .'_Site_'.$values['ENROL.SITEID'].'.pdf';
    
    $mailSubject = $ajax->m_tblConfig["APP_NAME"].' - Adverse Event - Patient '. $SubjectKey .', Site '.$values['ENROL.SITEID'];
    
    $bodyMessage = "
    Please find enclosed a notice of adverse event.
    
    The patient profile can be downloaded here: http://". $GLOBALS['egw']->accounts->config['hostname'] . $GLOBALS['egw']->accounts->config['webserver_url'] ."/index.php?menuaction=alixedc.uietude.subjectPDF&mode=profile&SubjectKey=". $SubjectKey ."";
            
    //Description qui figurera en en-tête de la page de notification
    $description = "Adverse Event";
    
    $recipients = $ajax->m_ctrl->bocdiscoo()->getValue($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemGroupOID,$ItemGroupRepeatKey,"AE.PVEMAIL");

    sendNotification($htmlContent, $mailSubject, $recipients, $filename, $bodyMessage,$ajax);

  }
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

  $fwDrug1 = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"FW","1","FORM.SVFW","0","SVFW","0","SVFW.DRUGD");
  $fwDrug2 = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"FW","2","FORM.SVFW","0","SVFW","0","SVFW.DRUGD");
  $fwDrug3 = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"FW","3","FORM.SVFW","0","SVFW","0","SVFW.DRUGD");
  $fwDrug4 = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"FW","4","FORM.SVFW","0","SVFW","0","SVFW.DRUGD");
  $fwDrug5 = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"FW","5","FORM.SVFW","0","SVFW","0","SVFW.DRUGD");
  $fwDrug6 = $uisubject->m_ctrl->bocdiscoo()->getValue($_GET['SubjectKey'],"FW","6","FORM.SVFW","0","SVFW","0","SVFW.DRUGD");
  
  $xslProc->setParameter('','SelDT',$selDT);
  $xslProc->setParameter('','IncDT',$incDT);
  $xslProc->setParameter('','DMAGE',$dmAge);
  $xslProc->setParameter('','FwDrug1',$fwDrug1);
  $xslProc->setParameter('','FwDrug2',$fwDrug2);
  $xslProc->setParameter('','FwDrug3',$fwDrug3);
  $xslProc->setParameter('','FwDrug4',$fwDrug4);
  $xslProc->setParameter('','FwDrug5',$fwDrug5);
  $xslProc->setParameter('','FwDrug6',$fwDrug6);
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
  try
  {
    $Result = $bocdiscoo->m_ctrl->socdiscoo()->query($query);
  }
  catch(xmlexception $e)
  {
    $str = "Query error : " . $e->getMessage() . " : " . $query . " (". __METHOD__ .")";
    $bocdiscoo->addLog("bocdiscoo->getNewPatientID() Error : $str",FATAL);
    die($str);
  }
  
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
    
    
          try{
            $res = $uidashboard->m_ctrl->socdiscoo()->query($query);
          }catch(xmlexception $e){
            $str = "xQuery error : " . $e->getMessage() ." (".__METHOD__.")";
            $uidashboard->addLog($str,FATAL);
          }
    
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
  
  //Medical and Surgical History
  $MH = "";
  $MH .= "
      <tr>
        <td><b>#</b>
        </td>
        <td><b>Medical/Surgical condition</b>
        </td>
        <td><b>Current medication</b>
        </td>
        <td><b>Onset date</b>
        </td>
        <td><b>On going</b>
        </td>
        <td><b>Date of end</b>
        </td>
      </tr>
  ";
  $MHIGs = $uisubject->m_ctrl->bocdiscoo()->getItemGroupDatas($SubjectKey,"1","0","FORM.MH","0");
  foreach($MHIGs as $MHIG){
    if($MHIG['ItemGroupOID']!="MH") continue;
    $MHvalues = $uisubject->m_ctrl->bocdiscoo()->getDecodedValues($SubjectKey,"1","0","FORM.MH","0","MH",$MHIG['ItemGroupRepeatKey']);
    
    $MH .= "
        <tr>
          <td>". $MHvalues['MH.MHSEQ'] ."&nbsp;
          </td>
          <td>". utf8_decode($MHvalues['MH.MHTERM']) ."&nbsp;
          </td>
          <td>". $MHvalues['MH.MHCONTRT'] ."&nbsp;
          </td>
          <td>". $uisubject->formatDate($MHvalues['MH.MHSTDTC'], true) ."&nbsp;
          </td>
          <td>". $MHvalues['MH.MHONGO'] ."&nbsp;
          </td>
          <td>". $uisubject->formatDate($MHvalues['MH.MHENDTC'], true) ."&nbsp;
          </td>
        </tr>
    ";
  }
  
  //Weight
  $weightValues = $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.VS","0","VS","1");
  $WEIGHT = $weightValues['VS.VSORRES'] ." kg";
  //Height
  $heightValues = $uisubject->m_ctrl->bocdiscoo()->getValues($SubjectKey,"1","0","FORM.VS","0","VS","2");
  $HEIGHT = $heightValues['VS.VSORRES'] ." cm";
  
  //Events since last visit
  $SEs = $uisubject->m_ctrl->bocdiscoo()->getStudyEventDatas($SubjectKey);
  $MetaDescriptions = $uisubject->m_ctrl->bocdiscoo()->getDescriptions("1.0.0");
  $nbSE = count($SEs);
  for($i=($nbSE-1); $i>=0; $i--){
    $SE = $SEs[$i];
    $LASTVISITDATE = $uisubject->m_ctrl->bocdiscoo()->getValue($SubjectKey,$SE['StudyEventOID'],$SE['StudyEventRepeatKey'],"FORM.SV","0","SV","0","SV.SVSTDTC");
    if($LASTVISITDATE!=""){
      $LASTVISITNAME = $MetaDescriptions["1.0.0"]["StudyEventDef"]["{$SE['StudyEventOID']}"];
    }
  }
  
  //Remplacement dans le fichier html
  $code = array("SUBJID", "INITIALS", "SITEID", "SITENAME", "LASTVISITDATE", "LASTVISITNAME", "SEX", "BRTHDT", "INCDT", "WEIGHT", "HEIGHT", "MH");
  $value = array($SubjectKey
                ,$values['ENROL.SUBJINIT']
                ,$values['ENROL.SITEID']
                ,utf8_decode($values['ENROL.SITENAME'])
                ,$uisubject->formatDate($LASTVISITDATE, true)
                ,$LASTVISITNAME
                ,$values['DM.SEX']
                ,$uisubject->formatDate($values['DM.BRTHDTC'], true)
                ,$uisubject->formatDate($values['DS.DSSTDTC'], true)
                ,$WEIGHT
                ,$HEIGHT
                ,$MH
                );
  
  $htmlContent = str_replace(preg_replace("(.*)","{\${0}}",$code, 1), $value, $htmlContent);
}

  /**
   *@desc Envoi de la notification
   *@param 
   *       $mailSubject : Objet de l'email
   *       $toNumber : destinatires (email et numéro de fax séparés par des virgules)
   *       $filename : nom du fichier à envoyer
   *       $bodyMessage : corps du message email
   *@author tpi
   *@return      
   *        boolean
   *@comment           
   */  
  function sendNotification($htmlContent, $mailSubject, $recipients, $filename, $bodyMessage,$ajax)
  {
    $ajax->addLog("sendNotification(\$htmlContent, $mailSubject, $recipients, $filename, \$bodyMessage)",INFO);
    require_once(dirname(__FILE__) ."/CMailFile.php3");
    try
    {
      $htmlTemp = tempnam("/tmp","htmlDocGfpc");
      $tmpHandle = fopen($htmlTemp,"w");
      fwrite($tmpHandle,$htmlContent);
      fclose($tmpHandle);
      
      # Tell HTMLDOC not to run in CGI mode...
      putenv("HTMLDOC_NOCGI=1");
      //Generation et affichage sur la sortie standard
      //$cmd = "htmldoc -f $filename -t pdf --quiet --color --webpage --jpeg  --left 30 --top 20 --bottom 20 --right 20 --footer c.: --fontsize 10 --textfont {helvetica}";
      $cmd = "htmldoc -t pdf --quiet --color --webpage --jpeg  --left 30 --top 20 --bottom 20 --right 20 --footer c.: --fontsize 10 --textfont {helvetica}";
      ob_start();
      $err = passthru("$cmd '$htmlTemp'");
      $content = ob_get_contents();
      ob_end_clean();
      $tmpHandle = fopen(dirname(__FILE__)."/tmp/".$filename,"w");
      fwrite($tmpHandle,$content);
      fclose($tmpHandle);
      unlink($htmlTemp);
      
      if($err!=0){
        throw new Exception("HTMLDOC error with code '$err'.");
      }
      
      $newmail = new CMailFile($mailSubject,$recipients,"svp.clinical@businessdecision.com",utf8_decode($bodyMessage),dirname(__FILE__)."/tmp/$filename","application/octet-stream",$filename);
      $newmail->sendfile();
      
      $ajax->addLog("Notification sent : $mailSubject",INFO);
      return true;
    }
    catch(Exception $e)
    {
      $str= "Error while sending a notification (".$filename.") : Line ". $e->getLine() ." - ". $e->getMessage() ." (". __METHOD__ .")";
      $ajax->addLog($str,ERROR);
      return false;
    }
  }
