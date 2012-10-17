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
    
require_once("class.socdiscoo.inc.php");

require_once(EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/config.inc.php");

class uidbadmin extends CommonFunctions{
 	var $public_functions = array(
			'getExportFile'	=> True,
			'getImportFile' => True,
		);
 
  function __construct()
  {	
    global $configEtude;
    CommonFunctions::__construct($configEtude,null);
    
    $this->m_ctrl = new instanciation();
  }
  
  public function setCtrl($ctrl){
    $this->m_ctrl = $ctrl;
  }
  
 public function viewDoc()
  {
    //Check right
    if(!$GLOBALS['egw_info']['user']['apps']['admin']){
      $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
    }
    
    $content = ob_get_contents();
    ob_end_clean();
    
    header("content-type: text/xml");
    if(isset($_GET['asxml'])) header('Content-Disposition: attachment; filename="'.$_GET['doc'].'.xml"');
    //with SimpleXmlElement
    echo $this->m_ctrl->socdiscoo()->getDocument($_GET['container'],$_GET['doc'])->asXML();
    exit(0);
  }

  private function deleteDoc()
  {
    $FileOID = $_GET['doc'];
    $containerName = $_GET['container']; 
    $tblSubjs = $this->m_ctrl->bosubjects()->getSubjectsList();
    if(false && $containerName=="ClinicalData" && !in_array($FileOID,$tblSubjs)){
      $this->addLog("Unauthorized Access to deleteDoc $FileOID  - Administrator has been notified",FATAL);
    }else{
      $this->m_ctrl->socdiscoo()->deleteDocument($containerName,$FileOID);
      if($containerName=="ClinicalData"){
        $this->m_ctrl->bopostit()->deleteSubjectPostIt($FileOID);
        $this->m_ctrl->boqueries()->deleteQueries($FileOID);
        $this->m_ctrl->bodeviations()->deleteDeviations($FileOID);
      }
    }
  }
  
  public function getInterface()
  {
    $this->addLog("uidbadmin->getInterface()",INFO);
    $htmlRet = "";

    if(!isset($_GET['action'])){
      $htmlRet = $this->getDefaultInterface();
    }else{
      switch($_GET['action'])
      {
        case 'importDocInterface' :
              if($this->m_ctrl->boacl()->checkModuleAccess("importDoc")){
                $htmlRet = $this->getImportInterface();
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;

        case 'runXQuery' :
              
              if($this->m_ctrl->boacl()->checkModuleAccess("importDoc")){
                $htmlRet = $this->getSandBoxInterface($_POST['xQueryCode']);
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;

        case 'sandboxInterface' :
              if($this->m_ctrl->boacl()->checkModuleAccess("importDoc")){
                $htmlRet = $this->getSandBoxInterface();
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;
              
        case 'importDoc' :
              if($this->m_ctrl->boacl()->checkModuleAccess("importDoc")){
                $htmlRet = $this->importDoc();
                $htmlRet .= $this->getImportInterface();
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;
        
        case 'ImportBulkServerDir' :      
              if($this->m_ctrl->boacl()->checkModuleAccess("importDoc")){
                $htmlRet = $this->importDocBulkServerDir($_POST['importServerDir']);
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;
                                            
        case 'deleteDoc' :
              if($this->m_ctrl->boacl()->checkModuleAccess("importDoc")){
                $this->deleteDoc();
                $htmlRet = $this->getMainInterface($_GET['container']);
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }

              break;  

        case 'viewDoc' : 
              if($this->m_ctrl->boacl()->checkModuleAccess("viewDocs")){
                $this->viewDoc();
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }

              break;  

        case 'viewDocs' : 
              if($this->m_ctrl->boacl()->checkModuleAccess("viewDocs")){
                $htmlRet = $this->getMainInterface($_GET['container']);
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;  

        case 'viewDoc' : 
              if($this->m_ctrl->boacl()->checkModuleAccess("viewDocs")){
                $htmlRet = $this->viewDoc();
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;  
        
        case 'initDB' :
              if($this->m_ctrl->boacl()->checkModuleAccess("viewDocs")){
                $htmlRet = $this->initDB();
              }else{
                $this->addLog("Unauthorized Access {$_GET['action']} - Administrator has been notified",FATAL);
              }
              break;  
                              
        case 'updateSubjectStatus' : 
              $this->m_ctrl->bosubjects()->updateSubjectInList($_GET['doc']);
              $htmlRet = $this->getMainInterface("ClinicalData");
              break;            
        
      }        
    }

    $menu = $this->m_ctrl->etudemenu()->getMenu();

    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    
    $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "'></SCRIPT>
                
                $menu

                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  $htmlRet
                </div>";
     return $htmlRet;      
      
  }
  
  private function createMenuLink($params, $label){
    return "<h3 class='ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-state-highlight'><a href='".$GLOBALS['egw']->link('/index.php', $params) ."'>" . $label . "</a></h3>";
  }

  private function getDefaultInterface(){
    $menu = "";
    
    if($this->m_ctrl->boacl()->checkModuleAccess("viewDocs||importDoc")){
      if($this->m_ctrl->boacl()->checkModuleAccess("viewDocs"))
      {
        $collections = $this->m_ctrl->socdiscoo()->getCollections();
        foreach($collections as $collection){
          $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
    				                                                                        'container' => $collection,
                                                                                    'action' => 'viewDocs'),
                                              "Collection: ".$collection);
        }
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',  				                                                                      
                                                                                  'action' => 'initDB'),      
                                            "Init XML Database");
      }
      
      if($this->m_ctrl->boacl()->checkModuleAccess("importDoc"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'importDocInterface'),
                                            "Import Metadata / ClinicalData");
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'sandboxInterface'),
                                            "xQuery Sandbox");
      }
      
      $menu .= $this->getSubMenu("Database", $submenu);
    }
    
    if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers||ManageSites")){
      $submenu = "";

      if($this->m_ctrl->boacl()->checkModuleAccess("ManageUsers"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.usersInterface',
                                                                         'title' => urlencode(lang('Users'))),
                                          "Users");
      }
  
      if($this->m_ctrl->boacl()->checkModuleAccess("ManageSites"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.sitesInterface',
                                                                         'title' => urlencode(lang('Sites'))),
                                          "Sites");
      }
                                            
      $menu .= $this->getSubMenu("Accounts", $submenu);
    }

    if($this->m_ctrl->boacl()->checkModuleAccess("Configuration||EditDocs")){
      $submenu = "";
      
      if($this->m_ctrl->boacl()->checkModuleAccess("Configuration"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.configInterface',
  			                                                                        'action' => ''),
                                          "Configuration");
      }
      
      if($this->m_ctrl->boacl()->checkModuleAccess("EditDocs"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.editorInterface',
  			                                                                        'action' => ''),
                                          "Editor");
        
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.annotatedCRF',
  			                                                                        'action' => ''),
                                          "Annotated CRF");
      }
                                      
      $menu .= $this->getSubMenu("Design", $submenu);
    }

    if($this->m_ctrl->boacl()->checkModuleAccess("ExportData||importDoc||LockData||ImportDICOM"))
    {
      $submenu = "";
      
      if($this->m_ctrl->boacl()->checkModuleAccess("importDoc"))
      {
        $submenu .= "<h3 class='ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-state-highlight'><a href=\"javascript:helper.showPrompt('Please configure class.boimport.inc.php according to your purposes.', 'noon()', 1);\">Import Clinical Data</a></h3>";
      }

      if($this->m_ctrl->boacl()->checkModuleAccess("ExportData"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.exportInterface'),
                                        "Data export");  
      }

      if($this->m_ctrl->boacl()->checkModuleAccess("LockDB"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.lockdbInterface'),
                                        "Data locks");  
      }

      if($this->m_ctrl->boacl()->checkModuleAccess("ImportDICOM"))
      {
        $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.importDicomInterface'),
                                        "Import DICOM files");  
      }
                                                
      $menu .= $this->getSubMenu("Clinical data", $submenu);                                                                                                                     
    }

    if(true) //everyone
    {
      $submenu = "";
      
      if(true) //everyone
      {
      
      $submenu .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.preferencesInterface',
			                                                                        'action' => ''),
                                        "Profile");
      }
                                          
      $menu .= $this->getSubMenu("Preferences", $submenu);                                                                                                                     
    }
    
    if($menu==""){
      $menu .= $this->getSubMenu("Administration tools", "Sorry, you have no privilege in this module.");   
    }

    return $menu;  
  }
  
  private function getSubMenu($title,$html){
    return "
          <div style='width: 300px; float: left; min-height: 200px; padding: 0px 1px 0px 1px;'>
            <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
              <span class='ui-dialog-title'>$title</span>
            </div>
            <div class='ui-dialog-content ui-widget-content'>
              <div class='ui-accordion ui-widget ui-helper-reset ui-accordion-icons'>
              $html
              </div>
            </div>
          </div>";
  }

  private function getMainInterface($containerName)
  {
      $containerName = $_GET['container'];
      
      $docs = $this->m_ctrl->socdiscoo()->getDocumentsList($containerName);
            
      $htmlRet = '
            	<div class="divSideboxHeader" align="center"><span>'.$containerName.'</span></div>';
      
			$htmlRet .= "<table align='center' width='80%' cellspacing='0' cellpadding='2' class='tabloGris'>
			<tbody><tr>
			<th class='tabloGrisTitle'>Name</th>
			<th class='tabloGrisTitle'>ODM 1.3 compliance</th>
			<th class='tabloGrisTitle' colspan='4'>Actions</th>
			</tr>" ;  
      
      $class = "";
      
      // Enable user error handling
      libxml_use_internal_errors(true);
      
      foreach($docs as $doc)
      {
        $status = "ok";
        $xml = $this->m_ctrl->socdiscoo()->getDocument($containerName, $doc, false);
        if($xml->schemaValidate($this->m_tblConfig["ODM_1_3_SCHEMA"])==false){
          $errors = libxml_get_errors();
          $error = $errors[0];
          $lines = explode("\r", $xml->saveXML());
          $line = $lines[($error->line)-1];
          $status = "Not ODM 1.3 compliant : ". $error->message.' at line '.$error->line;
          libxml_clear_errors();
        }
        
        $class = ($class=="row_off" ? "row_on" : "row_off");
        $htmlRet .= "
           <tr class='$class' style='".($status != "ok"?'background-color: red; color: white;':'').";' onMouseOver=\"this.oldBGC = this.style.backgroundColor; this.style.backgroundColor='yellow';\" onMouseOut=\"this.style.backgroundColor=this.oldBGC;\"><td>" . $doc ."</td>
             <td style='text-align: center;'><small>$status</small></td> 
             <td style='text-align: center; font-weight: bold;'><small><a target='_new' href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'viewDoc',
                                                                                              'container' => $containerName,
                                                                                              'doc'=>$doc)) . "'>View</a></small></td>  
             <td style='text-align: center; font-weight: bold;'><small><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'viewDoc',
                                                                                              'container' => $containerName,
                                                                                              'doc'=>$doc,
                                                                                              'asxml'=>'true')) . "'>Download</a></small></td>                       
             <td style='text-align: center; font-weight: bold;'><small><a href=\"javascript:deleteDocument('".$doc."')\">Delete</a></small></td>                                                
           </tr>";      
      }
      
      $htmlRet .= "</tbody></table>";
      $nbDocs = count($docs);
      $htmlRet .= $nbDocs ." document". ($nbDocs>1?"s":"");
      
      $htmlRet .= "<form action='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'execXQuery',
                                                                                              'container' => $containerName))."' method='post'>
                    <textarea name='p_xquery' rows='15' cols='140'>".(isset($_POST['p_xquery'])?stripslashes($_POST['p_xquery']):'')."</textarea><br/>
                    <input type='submit'/></form>";
      
      $deleteUrl = $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'deleteDoc',
                                                                                              'container' => $containerName));
      $htmlRet .= "
                   <script>
                    //<![CDATA[
                      function deleteDocument(doc)
                      {
                        if(confirm('Delete the document '+doc+' ?'))
                        {
                          window.location = \"".$deleteUrl."&doc=\"+doc;
                        }
                      }
                    //]]>
                   </script>
                   ";  
      
      return $htmlRet; 
  }

  private function getImportInterface()
  {           
      $htmlRet = "<div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Import Metadata or Clinical Data Document</span>
                    </div>
                    <br/>
                    <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'importDoc')) . "' method='post' enctype='multipart/form-data'>
                      XML File to import : <input type='file' size='80' name='uploadedDoc'>
                      <input type='submit' value='Import'/>
                    </form>
                    <br/>
                    <hr size='1'/>
                    <br/>
                    <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'ImportBulkServerDir')) . "' method='post'>
                      Import all xml located in server folder : <input type='text' name='importServerDir' size='80'/>
                      <input type='submit' value='Import'/>
                      <div><i>Full server path without trailing slash</i></div>
                    </form>
                    <br/>";
      
      return $htmlRet;  
  }

  private function getSandBoxInterface($query=""){
    if($query!=""){
      try{
        $result = $this->m_ctrl->socdiscoo()->query($query,true,false,true);
        $resultMsg = $result[0]->asXML();
      }catch(Exception $e){
        $resultMsg = $e->getMessage();
      }
    }
    
    $htmlRet = "<div><h4>Execute xQuery</h4></div>
                <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'runXQuery')) . "' method='post'>
                  <div>XQuery to run : </div><textarea name='xQueryCode' cols='100' rows='10'>$query</textarea>
                  <div><input type='submit' value='Run'/></div>
                </form>
                <div><h4>Result : </h4></div><textarea name='xQueryCode' cols='100' rows='50'>$resultMsg</textarea>";
    
    return $htmlRet;      
  }

  function getExportFile(){
    //Only accessible to admin
    if(!$GLOBALS['egw_info']['user']['apps']['admin']){
      $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
    }

    //Only accessible to admin
    if(!$GLOBALS['egw_info']['user']['apps']['admin']){
      $this->addLog("Unauthorized Access to Admin Module - Administrator has been notified",FATAL);
    }

    $exportId = $_GET['exportid'];
    
    $this->m_ctrl->boexport()->getExportFile($exportId);
  }

    function getImportFile(){
    $exportId = $_GET['importid'];
    $importFileType = $_GET['importFileType'];
    
    $this->m_ctrl->boimport()->getImportFile($exportId,$importFileType);
  }

/**
 * Initialize XML Database environment :
 *  Create collections
 *  Create indexes
 *  Load XQuery functions library
 *@author wlt   
 **/   
  private function initDB(){
    $htmlRet = "<div style='text-align: left'><ul>";
    
    $results = $this->m_ctrl->socdiscoo()->initDB(false, true,true);
    foreach($results as $result){
      $htmlRet .= "<li>$result</li>";
    }
    
    $htmlRet .= "</ul></div>";
    return $htmlRet;
  }

/**
 * Import all xml located into the folder specified in parameter
 * @param string $serverDir server folder containing xml to import
 * @return string result of import as an html string
 * @author wlt    
 **/  
  private function importDocBulkServerDir($serverDir){   
   $html = "<div style='text-align: left'><ul>";
   $tblFiles = scandir($serverDir);
   $nbImportedFile = 0;
   if(!$tblFiles){
      $this->addLog("uidbadmin->importDocBulkServerDir() : Unable to open the directory $serverDir",FATAL);
   }else{
    foreach($tblFiles as $file){
      if($file!="." && $file!=".."){
        //Detection of the target container
        $fullFileName = $serverDir . '/' . $file; 
        $containerName = $this->m_ctrl->boimport()->getContainer($fullFileName);
        try{
          $html .= "<li>adding {$_FILES['uploadedDoc']['name']}...</li>";
          $fileOID = $this->m_ctrl->socdiscoo()->addDocument($fullFileName,false,$containerName);
          $nbImportedFile++;
        }catch(Exception $e){
            //maybe the document already existing, we will try to replace it
            $html .= "<li>document already imported, replacing...</li>";
            $fileOID = $this->m_ctrl->socdiscoo()->replaceDocument($fullFileName,false,$containerName);
            $nbImportedFile++;
        }
      }
    }   
   }
   $html .= "<li>import successfull !</li>";
   $html .= "</ul>";
   $html .= "<a href='".$GLOBALS['egw']->link('/index.php', array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
			                                                                        'container' => $containerName,
                                                                              'action' => 'viewDocs')) ."'>See $containerName</a><br/>";
   $html .= "</div>";

   return $html;
  }
  
  private function importDoc(){
    $html = "";
    
    $html .= "<div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                <span class='ui-dialog-title'>Importing document</span>
              </div>
              <br/>";
    
	  $uploaddir = $this->m_tblConfig['CDISCOO_PATH'] . "/import/";
    $uploadfile = $uploaddir . basename($_FILES['uploadedDoc']['name']);
        
    if (move_uploaded_file($_FILES['uploadedDoc']['tmp_name'], $uploadfile)){
      //Detection of the target container
      $containerName = $this->m_ctrl->boimport()->getContainer($uploadfile);

      $html .= "<div style='text-align: left'><ul>";
      try{
        $html .= "<li>adding {$_FILES['uploadedDoc']['name']}...</li>";
        $fileOID = $this->m_ctrl->socdiscoo()->addDocument($uploadfile,false,$containerName);
      }catch(Exception $e){
        if(sedna_ercls()=="SE2004"){ //SE2004 = Document with the same name already exists in the collection.
          try{
            $html .= "<li>document already imported, replacing...</li>";
            $fileOID = $this->m_ctrl->socdiscoo()->replaceDocument($uploadfile,false,$containerName);
          }catch(Exception $e){
            $this->addLog(__METHOD__." ".$e->getMessage(), FATAL);
          }
        }else{
          if(sedna_ercls()=="SE2003"){ //SE2003 = No collection with this name.
            try{
              //Database initialization
              $html .= "<li>oups, it appears that the database wasn't initialized. Initializing database...</li>";
              $this->initDB();
              //Try again to add the document
              $html .= "<li>adding {$_FILES['uploadedDoc']['name']}...</li>";
              $fileOID = $this->m_ctrl->socdiscoo()->addDocument($uploadfile,false,$containerName);
            }catch(Exception $e){
              $this->addLog(__METHOD__." ".$e->getMessage(), FATAL);
            }
          }else{
            $this->addLog(__METHOD__." ".$e->getMessage(), FATAL);
          }
        }
      }
      $html .= "<li>import successfull !</li>";
      $html .= "</ul>";
      $html .= "<a href='".$GLOBALS['egw']->link('/index.php', array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'container' => $containerName,
                                                                                  'action' => 'viewDocs')) ."'>See $containerName</a><br/>";
      $html .= "</div><br />";
      
      //Update the subject in the SubjectsList
      if(is_numeric($fileOID)){
        $this->m_ctrl->bosubjects()->updateSubjectInList($fileOID);
      }
      
      return $html;
    }
  }
}
