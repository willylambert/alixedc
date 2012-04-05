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
    
/**
* @desc Class d'UI dédié à l'edition du CRF
* @author WLT
**/ 
class uisubject extends CommonFunctions
{
  /**
  * @desc Constructeur de class
  * @param array $configEtude tableau des constantes de configuration    
  * @param uietude $ctrlRef reference vers l'instance instanciation, où est délégué l'installation des objets (appel du type $this->m_ctrl->bcdiscoo() ) 
  * @author WLT
  * 
  **/ 
  function uisubject($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * @desc fonction principale - retoure l'html à afficher, appelé depuis uietude
  * @return string HTML à afficher
  * @author WLT
  **/     
  public function getInterface()
  {      
      $this->addLog("uisubject->getInterface()",TRACE);
      $htmlRet = "";
      
      //On recoit en paramètre le formulaire à afficher
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
           
      //Xsl de transformation commun
      $xsl = new DOMDocument;
      $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/StudyEventForm.xsl"); 
     
      //Recuperation du formulaire 
      $xml = $this->m_ctrl->bocdiscoo()->getStudyEventForms($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,false,$paginateStart,$paginateEnd);    
      
      $FormTag = $xml->getElementsByTagName("Form");  
      $FormTitle = $FormTag->item(0)->getAttribute("Title");

      $StudyEventTag = $xml->getElementsByTagName("StudyEvent");  
      $StudyEventTitle = $StudyEventTag->item(0)->getAttribute("Title");
      $SiteId = $StudyEventTag->item(0)->getAttribute("SiteId");
      $MetaDataVersionOID = $StudyEventTag->item(0)->getAttribute("MetaDataVersionOID");
             
      //Cas de l'inclusion
      if($SubjectKey=="BLANK"){
        //On va chercher dans les profiles le premier centre pour lequel l'utilisateur connecté est investigateur
        $profiles = $this->m_ctrl->boacl()->getUserProfiles();
        $i = 0;
        
        while($profiles[$i]['profileId']!='INV' && $i<count($profiles)){
          $i++;
        }
        if($i<count($profiles)){
          //Profil investigateur trouvé - on prend ce centre par défaut
          $SiteId = $profiles[$i]['siteId'];
          $profile = $profiles[$i];
        }else{
          $this->addLog("Error : user '". $this->m_ctrl->boacl()->getUserId() ."' not found as Investigator.",FATAL);
        }      
      }else{
        //Vérification des droits d'accès au patient demandé par l'utilisateur      
        //Est-ce que l'utilisateur connecté a le droit d'accéder à ce patient ?
        $profile = $this->m_ctrl->boacl()->getUserProfile("",$SiteId);
  
        if(!isset($profile['profileId']) || $profile['profileId']==""){
          //Peut-être le Sponsor, on regarde le profil par défaut
          $defaultProfile = $this->m_ctrl->boacl()->getUserProfile();
          if($defaultProfile['profileId']!="SPO"){
            $this->addLog("Access violation : You don't have suffisient privilege to access the subject $SubjectKey, Site $SiteId",FATAL);
          }
        }
      }
      $profileId = $profile['profileId'];

      //Menu gauche : liste des visites et des formulaires
      $formStatus = ""; //Variable passé par ref à la fonction getMenu
      $htmlMenu = $this->getMenu($SubjectKey,$MetaDataVersionOID,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$formStatus,$profileId);
               
      //Création du processeur XSLT et application sur le doc xml
      $proc = new XSLTProcessor;     
      $proc->importStyleSheet($xsl);
      $proc->setParameter('','lang',$GLOBALS['egw_info']['user']['preferences']['common']['lang']);
      
      //Les variables de base du ou des ItemGroupDatas à rendre
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
      
      //Possibilité de renseigner des déviations sur le formulaire
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
      //Xsl spécifique au form - si présent
      $xslFormFile = EGW_INCLUDE_ROOT ."/".$this->getCurrentApp(false)."/custom/$MetaDataVersionOID/xsl/$FormOID.xsl";

      if(file_exists($xslFormFile))
      {        
       $xslForm = new DOMDocument;
       $xslForm->load($xslFormFile); 
      
       $proc->importStyleSheet($xslForm);
       $proc->setParameter('','ReadOnly',$ReadOnly);
       
       //Passage des variables venant d'un autre endroit (cf config.inc)
       if(isset($this->m_tblConfig['FORM_VAR'][$FormOID])){
         foreach($this->m_tblConfig['FORM_VAR'][$FormOID] as $key=>$col){
           $customVar = $StudyEventTag->item(0)->getAttribute($key);
           $proc->setParameter('',$key,$customVar);
         }
       }
       
       //HOOK => uisubject_getInterface_xslParameters
       $this->callHook(__FUNCTION__,"xslParameters",array($FormOID,$proc,$this));
        
       $doc = $proc->transformToDoc($doc);
  
      //HOOK => uisubject_getInterface_afterXSLT
      $doc = $this->callHook(__FUNCTION__,"afterXSLT",array($MetaDataVersionOID,$FormOID,$this,$doc));
        
      $htmlForm = $doc->saveXML();
  
      //Hack pour gérer un pb xsl, qui transforme <textarea></textarea> en <textarea/>
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
      
      $toolbox = $this->getToolbox($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$profileId);
      
      //Do we have to check the data consistency before saving => set for each site in their configuration
      if($profile['checkOnSave']==2){
        $bCheckFormData = "false";
      }else{
        $bCheckFormData = "true";
      }
      
      //May be override by the config.inc.php file
      if(isset($this->m_tblConfig['FORM_DO_NOT_CHECK'][$FormOID]) && $this->m_tblConfig['FORM_DO_NOT_CHECK'][$FormOID]==true){
        $bCheckFormData = "false";
      }else{
        $bCheckFormData = "true";
      }      
      
      //variable ajoutée aux URLs Javascript afin de forcer le rechargement coté navigateur
      $jsVersion = $this->m_tblConfig['JS_VERSION'];
        
      $htmlRet = "                  
                  $topMenu
                  
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
                  
                  
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/queries.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/query.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/deviations.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/deviation.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/annotations.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/audittrail.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/postit.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/custom/'.$MetaDataVersionOID.'/js/alixlib.js') . "'></SCRIPT>
                  <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.jqAltBox.js') . "'></SCRIPT>
                  
                  <script>
                    $(document).ready(function() {
                                    loadAlixCRFjs('".$this->getCurrentApp(false)."','$SiteId','$SubjectKey','$StudyEventOID','$StudyEventRepeatKey','$FormOID','$FormRepeatKey','".$profileId."','".$formStatus."',$bCheckFormData);
                                     }); 
                  </script>
                  ";
      }	         
      return $htmlRet;  
  }

/*
@desc retourne un tableau de formulaires
@param string $SubjectKey identifiant du patient
@return string html du tableau
@return byref FormStatus = statut du formulaire demandé en paramètre
*/  
  protected function getMenu($SubjectKey,$MetaDataVersionOID,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,&$formStatus,$profileId){
  
    //Recuperation de tous les formulaires du patient
    $tblForm = $this->m_ctrl->bocdiscoo()->getSubjectTblForm($SubjectKey);
  
    //HOOK => uisubject_getMenu_beforeRendering
    $this->callHook(__FUNCTION__,"beforeRendering",array($SubjectKey,&$tblForm,$this));
        
    if($profileId=="CRA" || $profileId=="DM"){
      $AllowLock = "true"; 
    }else{
      $AllowLock = "false";
    }
    
    //Extraction du statut du formulaire demandé en param (generalement le formulaire en cours d'affichage)
    $xpath = new DOMXPath($tblForm);

    // Nous commençons à l'élément racine
    $query = "/SubjectData/StudyEventData[@StudyEventOID='$StudyEventOID' and @StudyEventRepeatKey='$StudyEventRepeatKey']/FormData[@FormOID='$FormOID' and @FormRepeatKey='$FormRepeatKey']/status";   
    $formdata = $xpath->query($query);
    $formStatus = $formdata->item(0)->nodeValue;
    
    //Application de l'XSL par défaut
    $xsl = new DOMDocument;
    $xsl->load(EGW_INCLUDE_ROOT . "/".$this->getCurrentApp(false)."/xsl/SubjectMenu.xsl");       

    //Création du processeur XSLT et application sur le doc xml
    $proc = new XSLTProcessor;     
    $proc->importStyleSheet($xsl);
    
    $proc->setParameter('','SubjectKey',"$SubjectKey");
    $proc->setParameter('','AllowLock',"$AllowLock");
    $proc->setParameter('','CurrentApp',$this->getCurrentApp(false));
    
    $doc = $proc->transformToDoc($tblForm);

    //Xsl spécifique au form - si présent
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

    $htmlRet = $doc->saveXML();
    
    return $htmlRet;
  }
  
/*
@desc retourne la légende des icônes
@return string html
*/  
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
  
/*
@desc retourne une boîte à Outil (post-it pour les ARC)
@return string html
*/  
  protected function getToolbox($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$profileId=""){
    $htmlRet = "";
    
    //everybody can run form checking
    $htmlRet .= '<button id="btnRunChecks" class="ui-state-default ui-corner-all" onclick="checkFormData(\''.$this->getCurrentApp(false).'\',\''.$this->getCurrentApp(false).'\',\''.$SubjectKey.'\',\''.$StudyEventOID.'\',\''.$StudyEventRepeatKey.'\',\''.$FormOID.'\',\''.$FormRepeatKey.'\');location.reload();"><img src="'.$this->getCurrentApp(false).'/templates/default/images/ok.png" style="float:left; margin-right: 5px;" />Run checks</button>';
                   
    //seuls les ARC peuvent placer des post-it
    if($profileId=="CRA"){
      $htmlRet .= '<button id="btnAddPostIt" class="ui-state-default ui-corner-all" onclick="displayNewPostIt(\''.$SubjectKey.'\',\''.$StudyEventOID.'\',\''.$StudyEventRepeatKey.'\',\''.$FormOID.'\',\''.$FormRepeatKey.'\');"><img src="'.$this->getCurrentApp(false).'/templates/default/images/postit_14.png" style="float:left; margin-right: 3px;" />Add a post-it</button>';
    }
    //seuls les investigateurs peuvent saisir une déviation
    if($profileId=="INV"){
      //sur les formulaire susceptible de comporter une déviation uniquement
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
  
}
