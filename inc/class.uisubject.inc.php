<?php
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
    
class uisubject extends CommonFunctions
{
  /**
  * class constructor
  * @param array $configStudy array of configuration values    
  * @param uietude $ctrlRef reference to instance of the instanciation class, used to delegate instanciation of all objects ( call syntax : $this->m_ctrl->bocdiscoo()->my_method_name ) 
  * @author WLT
  **/ 
  function uisubject($configStudy,$ctrlRef)
  {	
    CommonFunctions::__construct($configStudy,$ctrlRef);
  }

  /**
  * Main function called from uietude
  * @return string HTML to display
  * @author WLT
  **/     
  public function getInterface()
  {      
      $this->addLog("uisubject->getInterface()",TRACE);
      $htmlRet = "";
      
      //Incoming parameters tell which form to display
      $SubjectKey = $_GET['SubjectKey'];
      $StudyEventOID = $_GET['StudyEventOID'];
      $StudyEventRepeatKey = $_GET['StudyEventRepeatKey'];
      $FormOID = $_GET['FormOID'];
      $FormRepeatKey = $_GET['FormRepeatKey'];
      
      //Pagination handling
      if(isset($this->m_tblConfig['FORM_PAGINATE']["$FormOID"])){      
        $page = (isset($_GET['page']) ? $_GET['page'] : 0);
        $paginateStart = $page * $this->m_tblConfig['FORM_PAGINATE']["$FormOID"]["IG_PER_PAGE"]; 
        $paginateEnd = $paginateStart + $this->m_tblConfig['FORM_PAGINATE']["$FormOID"]["IG_PER_PAGE"];
      }
                              
      $profile = $this->m_ctrl->boacl()->getUserProfile("",substr($SubjectKey,0,2));
         
      //HOOK => uisubject_getInterface_start
      $this->callHook(__FUNCTION__,"start",array($FormOID,$this));
           
      //Common XSL transformation - For main design
      $xsl = new DOMDocument;
      $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/StudyEventForm.xsl"); 
     
      //Get Form Data / without design      
      $xml = $this->m_ctrl->bocdiscoo()->getStudyEventForms($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,false,$paginateStart,$paginateEnd);    

      $FormTag = $xml->getElementsByTagName("Form");  
      $FormTitle = $FormTag->item(0)->getAttribute("Title");

      $StudyEventTag = $xml->getElementsByTagName("StudyEvent");  
      $StudyEventTitle = $StudyEventTag->item(0)->getAttribute("Title");
      $SiteId = $StudyEventTag->item(0)->getAttribute("SiteId");
      $MetaDataVersionOID = $StudyEventTag->item(0)->getAttribute("MetaDataVersionOID");
             
      //Inclusion case
      if($SubjectKey=="BLANK"){
        $profiles = $this->m_ctrl->boacl()->getUserProfiles();
        $i = 0;
        
        while($profiles[$i]['profileId']!='INV' && $i<count($profiles)){
          $i++;
        }
        if($i<count($profiles)){
          //Investigator profile found - used as default site
          $SiteId = $profiles[$i]['siteId'];
          $profile = $profiles[$i];
        }else{
          $this->addLog("Error : user '". $this->m_ctrl->boacl()->getUserId() ."' not found as Investigator.",FATAL);
        }      
      }else{
        //Access right check      
        $profile = $this->m_ctrl->boacl()->getUserProfile("",$SiteId);
  
        if(!isset($profile['profileId']) || $profile['profileId']==""){
          //Could be Sponsor, check the default profile
          $defaultProfile = $this->m_ctrl->boacl()->getUserProfile();
          if($defaultProfile['profileId']!="SPO"){
            $this->addLog("Access violation : You don't have suffisient privilege to access the subject $SubjectKey, Site $SiteId",FATAL);
          }
        }
      }
      $profileId = $profile['profileId'];

      //Study Flow Chart : Contains Visits and Forms
      $formStatus = ""; //By Ref parameters
      $htmlMenu = $this->getMenu($SubjectKey,$MetaDataVersionOID,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$formStatus,$profileId);
               
      $proc = new XSLTProcessor;     
      $proc->importStyleSheet($xsl);
      $proc->setParameter('','lang',$GLOBALS['egw_info']['user']['preferences']['common']['lang']);
      
      $proc->setParameter('','CurrentApp',$this->getCurrentApp(false));
      $proc->setParameter('','SubjectKey',$SubjectKey);
      $proc->setParameter('','StudyEventOID',$StudyEventOID);
      $proc->setParameter('','StudyEventRepeatKey',$StudyEventRepeatKey);
      $proc->setParameter('','FormOID',$FormOID);
      $proc->setParameter('','FormRepeatKey',$FormRepeatKey);
      
      if($profileId!="INV" || $formStatus=="FROZEN"){
        $ReadOnly = "true";
      }else{        
        $ReadOnly = "false";
      }
    
      $proc->setParameter('','ReadOnly',$ReadOnly);
      
      $proc->setParameter('','ProfileId',$profileId);
      
      if(isset($this->m_tblConfig['FORM_PAGINATE']["$FormOID"])){
        $proc->setParameter('','Paginate',"true");
        $proc->setParameter('','CurrentPage',$page);
        $numberOfIG = $this->m_ctrl->bocdiscoo()->getItemGroupDataCount($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey);        
        $proc->setParameter('','NumberOfRecords',$numberOfIG);
        $proc->setParameter('','IGperPage',$this->m_tblConfig['FORM_PAGINATE']["$FormOID"]['IG_PER_PAGE']);
      }else{
        $proc->setParameter('','Paginate',"false");
      }
      
      //Current form can show deviations ?
      $ShowDeviation = 'false';
      if($this->m_ctrl->bodeviations()->formCanHaveDeviation($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)){
        $ShowDeviation = 'true';
      }
      $proc->setParameter('','ShowDeviations',$ShowDeviation);
      
      //HOOK => uisubject_getInterface_xslParameters
      $this->callHook(__FUNCTION__,"xslParameters",array("",$proc,$this));
/*
      echo "-------------<br>";
      echo "<pre>";
      echo $xml->saveXML();
      echo "</pre>";
      echo "-------------<br>";   
*/
      $doc = $proc->transformToDoc($xml);
/*
      echo "##############<br>";
      echo "<pre>";
      echo $doc->saveXML();
      echo "</pre>";
      echo "##############<br>";   
*/
      //Custom XSL Form - Applied only if exists
      $xslFormFile = EGW_INCLUDE_ROOT ."/".$this->getCurrentApp(false)."/custom/$MetaDataVersionOID/xsl/$FormOID.xsl";

      if(file_exists($xslFormFile))
      {        
        $xslForm = new DOMDocument;
        $xslForm->load($xslFormFile); 
        
        $proc->importStyleSheet($xslForm);
        $proc->setParameter('','ReadOnly',$ReadOnly);
        
       //Extra vars could be passed to the XSL - see config.inc.php
        if(isset($this->m_tblConfig['FORM_VAR'][$FormOID])){
          foreach($this->m_tblConfig['FORM_VAR'][$FormOID] as $key=>$col){
            $customVar = $StudyEventTag->item(0)->getAttribute($key);
            $proc->setParameter('',$key,$customVar);
          }
        }
        
        //HOOK => uisubject_getInterface_xslParameters
        $this->callHook(__FUNCTION__,"xslParameters",array($FormOID,$proc,$this));
        
        $doc = $proc->transformToDoc($doc);
       }
  
      //HOOK => uisubject_getInterface_afterXSLT
      $doc = $this->callHook(__FUNCTION__,"afterXSLT",array($MetaDataVersionOID,$FormOID,$this,$doc));
/*
      echo "**************<br>";
      echo "<pre>";
      echo $doc->saveXML();
      echo "</pre>";
      echo "**************<br>";
*/
      //$htmlForm = $doc->saveXML($doc->childNodes->item(0)); //tpi: why item(0) ?
      $htmlForm = $doc->saveXML();
      
      //Hack to manage a saveXML feature, which transform <textarea></textarea> into <textarea/>
      $htmlForm = str_replace(">EMPTY</textarea>","></textarea>",$htmlForm);
         
      $topMenu = $this->m_ctrl->etudemenu()->getMenu($SiteId);
      
      //We disable the legend - we have to find a better place
      /*
      $legend = "<div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Legend</span>
                 </div> 
                 <div class='ui-dialog-content ui-widget-content'>" .
                      $this->getLegend();
                 ."</div>";             
      */
      $legend = "";
      
      //Toolbox : buttons on top of the form
      $toolbox = $this->getToolbox($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$profileId);
      
      //Context menu : actions when user click right
      $inputContextMenu = $this->getInputContextMenu();
      
      //Do we have to check the data consistency before saving => set for each site in their configuration
      if($profile['checkOnSave']==2){
        $bCheckFormData = "false";
      }else{
        $bCheckFormData = "true";
      }
      
      //May be overrided by the config.inc.php file
      if(isset($this->m_tblConfig['FORM_DO_NOT_CHECK'][$FormOID]) && $this->m_tblConfig['FORM_DO_NOT_CHECK'][$FormOID]==true){
        $bCheckFormData = "false";
      }else{
        $bCheckFormData = "true";
      }      
      
      //Version of Javascript scripts
      $jsVersion = $this->m_tblConfig['JS_VERSION'];
      
      $htmlRet = "                  
                  $topMenu
                  
                  $inputContextMenu
                  
                  <div id='formMenu' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                    <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Flow Chart</span>
                    </div>
                    <div class='ui-dialog-content ui-widget-content'>
                      $htmlMenu
                    </div>
                    $legend
                  </div>
                  <div id='mainForm' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                    <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Subject $SubjectKey / $StudyEventTitle / $FormTitle</span>
                      <span class='ToolBox ToolBoxOneButton'>$toolbox</span>
                    </div>
                    <div class='ui-dialog-content ui-widget-content'>
                      <div id='formDeviations'></div>
                      <div id='formQueries'></div>
                      $htmlForm
                    </div>
                  </div>
                                    
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/queries.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/query.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/deviations.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/deviation.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/annotations.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/audittrail.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/postit.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/custom/'.$MetaDataVersionOID.'/js/alixlib.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.jqAltBox.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.contextMenu.js') . "?$jsVersion'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/contextMenu.js') . "?$jsVersion'></SCRIPT>
                  <link href='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/jquery.contextMenu.css') . "' type='text/css' rel='StyleSheet' />
                  
                  <script>
                  //<![CDATA[
                    $(document).ready(function() {
                                    loadAlixCRFjs('".$this->getCurrentApp(false)."','$SiteId','$SubjectKey','$StudyEventOID','$StudyEventRepeatKey','$FormOID','$FormRepeatKey','".$profileId."','".$formStatus."',$bCheckFormData);
                                     }); 
                  //]]>
                  </script>
                  ";
      
      return $htmlRet;  
  }

  /**
  *get the flow chart of the current subject
  *@return string html of study visits and forms
  *@return byref FormStatus Status of the current Form
  **/  
  protected function getMenu($SubjectKey,$MetaDataVersionOID,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,&$formStatus,$profileId){
  
    $tblForm = $this->m_ctrl->bocdiscoo()->getSubjectsTblForm($SubjectKey);
  
    //HOOK => uisubject_getMenu_beforeRendering
    $this->callHook(__FUNCTION__,"beforeRendering",array($SubjectKey,&$tblForm,$this));
        
    if($profileId=="CRA" || $profileId=="DM"){
      $AllowLock = "true"; 
    }else{
      $AllowLock = "false";
    }
    
    //Default XSL
    $xsl = new DOMDocument;
    $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/SubjectMenu.xsl");       

    $proc = new XSLTProcessor;     
    $proc->importStyleSheet($xsl);
    
    $proc->setParameter('','SubjectKey',"$SubjectKey");
    $proc->setParameter('','AllowLock',"$AllowLock");
    $proc->setParameter('','CurrentApp',$this->getCurrentApp(false));
    
    $doc = $proc->transformToDoc($tblForm);

    //Specific Optional XSL
    $xslMenuFile = EGW_INCLUDE_ROOT ."/".$this->getCurrentApp(false)."/custom/$MetaDataVersionOID/xsl/SubjectMenu.xsl";

    if(file_exists($xslMenuFile))
    {        
     $xslMenu = new DOMDocument;
     $xslMenu->load($xslMenuFile); 
    
     $proc->importStyleSheet($xslMenu);

     //HOOK => uisubject_getMenu_xslParameters
     $this->callHook(__FUNCTION__,"xslParameters",array($proc,$this));
      
     $doc = $proc->transformToDoc($doc);
    }
  
    //HOOK => uisubject_getMenu_saveSubjectStatus
    $this->callHook(__FUNCTION__,"saveSubjectStatus",array($SubjectKey,$tblForm,$this));

    $htmlRet .= $doc->saveXML($doc->childNodes->item(0));

    return $htmlRet;
  }
  
/**
*Return icon legend as html
*@return string html
**/  
  protected function getLegend(){
    $htmlRet = "";
    $legend = array(
      "Buttons" => array(
                    /*
                    array("Save", ""),
                    array("Cancel", ""),
                    array("Add", ""),
                    */
                    array("Annotation", "templates/default/images/post_note_empty.gif"),
                    //array("Calculator", ""),
                    array("Audit Trail", "templates/default/images/clock-history.png"),
                   ),
      "Visit status" => array(
                    array("Empty", "templates/default/images/circle_lightgray_16.png"),
                    array("Partial", "templates/default/images/circle_orange_16.png"),
                    array("Inconsistent", "templates/default/images/circle_red_16.png"),
                    array("Complete", "templates/default/images/circle_green_16.png"),
                    //array("Mandatory", "templates/default/images/circle_orange_16.png"),
                   ),
      "Form status" => array(
                    array("Empty", "templates/default/images/legend_form_empty_16.png"),
                    array("Partial", "templates/default/images/legend_form_partial_16.png"),
                    array("Inconsistent", "templates/default/images/legend_form_inconsistent_16.png"),
                    array("Complete", "templates/default/images/legend_form_complete_16.png"),
                    //array("Mandatory", "templates/default/images/circle_orange_16.png"),
                   ),
      "Queries" => array(
                    //array("Add query", "phpgwapi/templates/idots/images/16x16/errorAdd.gif"),
                    array("Open", "templates/default/images/circle_red_16.png"),
                    array("Closed", "templates/default/images/circle_lightgray_16.png"),
                    array("Information", "templates/default/images/type_info_lightblue_16.png"),
                    array("Inconsistency", "templates/default/images/type_warning_light_16.png"),
                    array("Missing or badformat", "templates/default/images/type_error_lightred_16.png"),
                    array("Resolution proposed", "templates/default/images/bubble_blue_16.png"),
                    array("Resolved (value changed after proposed resolution)", "templates/default/images/bubble_orange_16.png"),
                    array("Value confirmed", "templates/default/images/bubble_green_16.png"),
                    array("Manual query", "templates/default/images/bubble_red_16.png"),
                    //array("Inconsistency response", ""),
                    //array("Information, missing or badformat response", ""),
                   ),
      "Information" => array(
                    array("Mandatory", "templates/default/images/star_mandatory_16.png"),
                    //array("Mandatory with condition", ""),
                    array("Partial date accepted", "templates/default/images/legend_partial_date_21x16.png"),
                   ),
    );
    
    foreach($legend as $title => $sublegend){
      $htmlRet .= "<div class='ui-widget-header legend-subtitle'>$title</div>";
      foreach($sublegend as $description){
        $htmlRet .= "<div class='ui-widget-content legend-description'><img src='".$GLOBALS['egw']->link('/'. $this->getCurrentApp(false) ."/". $description[1]) ."' />". $description[0] ."</div>";
      }
    }
    
    return $htmlRet;
  }
  
/**
Return the toolbox buttons (post-it for CRA)
@return string html
**/  
  protected function getToolbox($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$profileId=""){
    $htmlRet = "";
    
    //everybody can run form checking
    $htmlRet .= '<button id="btnRunChecks" class="ui-state-default ui-corner-all" onclick="checkFormData(\''.$this->getCurrentApp(false).'\',\''.$this->getCurrentApp(false).'\',\''.$SubjectKey.'\',\''.$StudyEventOID.'\',\''.$StudyEventRepeatKey.'\',\''.$FormOID.'\',\''.$FormRepeatKey.'\');location.reload();"><img src="'.$this->getCurrentApp(false).'/templates/default/images/ok.png" style="float:left; margin-right: 5px;" />Run checks</button>';
                   
    //only CRA could put Post-It
    if($profileId=="CRA"){
      $htmlRet .= '<button id="btnAddPostIt" class="ui-state-default ui-corner-all" onclick="displayNewPostIt(\''.$SubjectKey.'\',\''.$StudyEventOID.'\',\''.$StudyEventRepeatKey.'\',\''.$FormOID.'\',\''.$FormRepeatKey.'\');"><img src="'.$this->getCurrentApp(false).'/templates/default/images/postit_14.png" style="float:left; margin-right: 3px;" />Add a post-it</button>';
    }
    //only investigator could put deviation
    if($profileId=="INV"){
      //and only on specified forms
      if($this->m_ctrl->bodeviations()->formCanHaveDeviation($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey)){
      /*
        //Si la dernière déviation enregistrée en base n'est pas une chaine de caractère vide (équivalent à déviation supprimée)
        if($this->m_ctrl->bodeviations()->getFormDeviation($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey) == ""){
          $htmlRet .= '<button id="btnAddDeviation" class="ui-state-default ui-corner-all" onclick="$(\'#formDeviation\').slideDown();$(\'#btnAddDeviation\').fadeOut();"><img src="'.$GLOBALS['egw_info']['flags']['currentapp'].'/templates/default/images/delta_add.png" style="float:left; margin-right: 3px;" />Deviation</button>';
        }
      */
      }
    }
    
    return $htmlRet;
  }
  
/**
return a context menu, can be used with a right click on inputs
@return string html
**/  
  protected function getInputContextMenu(){
    $htmlRet = "";
    
    $htmlRet .= "<ul id='inputContextMenu' class='contextMenu'>";
    
    $htmlRet .= "
                  <li class='contextMenuItem contextMenuAuditTrail'>
                      <a href='#contextMenuAuditTrail'>Audit Trail</a>
                  </li>";
    
    $htmlRet .= "</ul>";
    
    return $htmlRet;
  }
  
  /**
  *return all subject data into PDF format (binary data)
  *@param $SubjectKey patient id
  *@return PDF data - binary content
  **/
  public function getPDF($SubjectKey){
    $doc = $this->m_ctrl->bocdiscoo()->getAllSubjectFormsAndIGsForPDF($SubjectKey);
 
    //clean html to convert to a more simple HTML 4 output without css
    //Default XSL
    $xsl = new DOMDocument;
    $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/SubjectPDF.xsl"); 

    $proc = new XSLTProcessor;     
    $proc->importStyleSheet($xsl);
    
    //XSL Parameters
    $siteId = $this->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");
    $subjId = sprintf($this->m_tblConfig["SUBJID_FORMAT"],$SubjectKey);
    $siteName = $this->m_ctrl->bosites()->getSiteName($siteId);
    $proc->setParameter('','studyName',$this->m_tblConfig["APP_NAME"]);
    $proc->setParameter('','siteId',$siteId);
    $proc->setParameter('','subjId',$subjId);
    $proc->setParameter('','siteName',$siteName);
    
    $doc = $proc->transformToDoc($doc);
    
    $html = $doc->saveHTML();

    //convert to PDF using htmldoc command line
    $htmlTemp = tempnam("/tmp","htmlDocGfpc");
    $tmpHandle = fopen($htmlTemp,"w");
    fwrite($tmpHandle,$html);
    fclose($tmpHandle);
    
    # Tell HTMLDOC not to run in CGI mode...
    putenv("HTMLDOC_NOCGI=1");
    //Generation and output on standard output
    $cmd = "htmldoc -t pdf --quiet --color --webpage --jpeg  --left 30 --top 20 --bottom 20 --right 20 --footer c.: --fontsize 10 --textfont {helvetica}";
    ob_start();
    $err = passthru("$cmd '$htmlTemp'");
    $pdf = ob_get_contents();
    ob_end_clean();
    unlink($htmlTemp);
    
    return $pdf;
  }
  
  /**
  *return patient profile into PDF format (binary data)
  *@param $SubjectKey patient id
  *@return PDF data - binary content
  **/
  public function getProfile($SubjectKey){
    $htmlContent = "";
    
    //HOOK => uisubject_getProfile_profileContent
    $this->callHook(__FUNCTION__,"profileContent",array($SubjectKey,&$htmlContent,$this));
    
    //Default template
    if($htmlContent == ""){
      //values
      $siteId = $this->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"SITEID");
      $siteName = $this->m_ctrl->bosites()->getSiteName($siteId);
      $inclusionDate = $this->formatDate($this->m_ctrl->bosubjects()->getSubjectColValue($SubjectKey,"INCLUSIONDATE"));
      
      $htmlContent = '<html><head><meta http-equiv=Content-Type content="text/html; charset=iso8859-2"></head><body> <font face="Arial"><center><table border="1" width="500" cellpadding="5" rules="rows"><tr> <td width="500" bgcolor="#000000"><font color="#ffffff"><b>'.$this->m_tblConfig['APP_NAME'].'</b></font></td></tr><tr> <td colspan="1">Patient '.$SubjectKey.'</td></tr><tr> <td colspan="1"><b>Patient profile</b></td></tr></table> <br><table border="1" width="500" cellpadding="5" rules="rows"><tr> <td colspan="2" bgcolor="#000000"><font color="#ffffff"><b>Patient profile</b></font></td></tr><tr> <td width="350"><b>Subject identifier </b></td> <td width="150">'.$SubjectKey.'</td></tr><tr> <td width="350"><b>Site Id </b></td> <td width="150">'.$siteId.'</td></tr><tr> <td width="350"><b>Site Name </b></td> <td width="150">'.$siteName.'</td></tr><tr> <td width="350"><b>Inclusion date </b></td> <td width="150">'.$inclusionDate.'</td></tr></table></center></font></body></html>';
      
      /*
      //template file
      $template = dirname(__FILE__)."/templates/profile.htm";
      if(!file_exists($template)){
        $str = "Template not found '$template'.";
        $this->m_ctrl->addLog($str,ERROR);
      }
      $handle = fopen($template, "r");
      $htmlContent = fread($handle, filesize($template));
      fclose($handle);
      
      $code = array("STUDYNAME", "SUBJECTKEY", "SITEID", "SITENAME", "INCLUSIONDATE");
      $value = array($this->m_tblConfig['APP_NAME'], $SubjectKey, $siteId, $siteName, $inclusionDate);
      $htmlContent = str_replace(preg_replace("(.*)","{\${0}}",$code, 1), $value, $htmlContent);
      */
    }
    
    $htmlTemp = tempnam("/tmp","htmlDocGfpc");
    $tmpHandle = fopen($htmlTemp,"w");
    fwrite($tmpHandle,$htmlContent);
    fclose($tmpHandle);
    
    # Tell HTMLDOC not to run in CGI mode...
    putenv("HTMLDOC_NOCGI=1");
    //Generation and output
    $cmd = "htmldoc -t pdf --quiet --color --webpage --jpeg  --left 10 --top 20 --bottom 20 --right 10 --footer c.: --fontsize 10 --textfont {helvetica}";
    ob_start();
    $err = passthru("$cmd '$htmlTemp'");
    $pdf = ob_get_contents();
    ob_end_clean();
    unlink($htmlTemp);
    
    return $pdf;
  }
  
}
