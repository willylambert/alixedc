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