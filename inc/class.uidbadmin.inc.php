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
    
require_once("class.socdiscoo.inc.php");

require_once(EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/config.inc.php");

class uidbadmin extends CommonFunctions{
 	var $public_functions = array(
			'viewDoc'	=> True,
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
  
  //Appel direct depuis la liste des patients (par ex)
  function viewDoc()
  {
    $content = ob_get_contents();
    ob_end_clean();
    
    header("content-type: text/xml");
    if(isset($_GET['asxml'])) header('Content-Disposition: attachment; filename="'.$_GET['doc'].'.xml"');
    //with SimpleXmlElement
    if($_GET['container']=="MetaDataVersion"){
      echo $this->m_ctrl->socdiscoo($_GET['doc'])->getDocument("MetaDataVersion.dbxml",$_GET['doc'])->asXML();
    }else{
      echo $this->m_ctrl->socdiscoo($_GET['doc'])->getDocument("{$_GET['doc']}.dbxml",$_GET['doc'])->asXML();
    }
    exit(0); 
  }

  function deleteDoc()
  {
    $this->m_ctrl->socdiscoo()->deleteDocument("{$_GET['doc']}.dbxml",$_GET['doc']);
  }
  
  function getInterface()
  {
    $this->addLog("uidbadmin->getInterface()",TRACE);
    $htmlRet = "";

      //En fonction du contexte, on retourne l'html à afficher
      if(!isset($_GET['action'])){
        $htmlRet = $this->getDefaultInterface();
      }else{
        switch($_GET['action'])
        {
          case 'importDocInterface' :
                $htmlRet = $this->getImportInterface();
                break;
                
          case 'importDoc' :
                $container=$_POST['p_container'];
                $htmlRet = $this->importDoc($container);
                break;
                
          case 'importECGDoc' :
                $htmlRet = $this->importECGDoc();
                $htmlRet .= $this->getImportECGInterface();
                break;               

          case 'importLABDoc' :
                $htmlRet = $this->importLABDoc();
                $htmlRet .= $this->getImportLABInterface();
                break;    

          case 'importCodingDoc' :
                $htmlRet = $this->importCodingDoc();
                $htmlRet .= $this->getImportCodingInterface();
                break;  
                                
          case 'deleteDoc' :
                $this->deleteDoc();
                $htmlRet = $this->getMainInterface($_GET['container']);
                break;

          case 'viewDoc' : 
                $this->viewDoc();
                break;  

          case 'viewDocs' : 
                $htmlRet = $this->getMainInterface($_GET['container']);
                break;  

          case 'exportInterface' : 
                $htmlRet = $this->getExportInterface();
                break;
          
          case 'importECG' :
                $htmlRet = $this->getImportECGInterface();
                break;

          case 'importLAB' :
                $htmlRet = $this->getImportLABInterface();
                break;
          
          case 'importCoding' :
                $htmlRet = $this->getImportCodingInterface();
                break;
                                                
          case 'export' :               
                $this->export($_GET['type']);
                $htmlRet = $this->getExportInterface();      
                break;
                
          case 'updateSubjectStatus' : 
                $this->m_ctrl->bosubjects()->updateSubjectInList($_GET['doc']);
                $htmlRet = $this->getMainInterface("ClinicalData");
                break;            

          case 'viewLocks' : 
                $htmlRet = $this->getLocksInterface();
                break; 

        }        
      }

    $menu = $this->m_ctrl->etudemenu()->getMenu();

    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    
    $htmlRet = "
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.6.2.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "'></SCRIPT>
                
                $menu

                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Admin</span>
                  </div>
                  <div class='ui-dialog-content ui-widget-content'>
                    <div class='ui-accordion ui-widget ui-helper-reset ui-accordion-icons'>
                    $htmlRet
                    </div>
                  </div>
                </div>";
     return $htmlRet;      
      
  }
  
  private function createMenuLink($params, $label){
    return "<h3 class='ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-state-highlight'><a href='".$GLOBALS['egw']->link('/index.php', $params) ."'>" . $label . "</a></h3>";
  }

  private function getDefaultInterface(){
        $menu_admin = "";
        
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.usersInterface',
                                                                           'title' => urlencode(lang('Users')),),
                                            "Users");
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.sitesInterface',
                                                                           'title' => urlencode(lang('Sites'))),
                                            "Sites");
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'container' => 'ClinicalData',
                                                                                  'action' => 'viewDocs'),
                                            "ClinicalData");
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'container' => 'MetaDataVersion',
                                                                                  'action' => 'viewDocs'),
                                            "MetaData");
        /*$menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'container' => 'SubjectsList',
                                                                                  'action' => 'viewDocs'),
                                            "SubjectsList");
        */
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'importDocInterface'),
                                            "Import Metadata / ClinicalData");
        /* 
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'importECG'),
                                            "Import ECG");     
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'importLAB'),
                                            "Import LAB");  
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'importCoding'),
                                            "Import Coding");
        */
        $menu_admin .= "<h3 class='ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-state-highlight'><a href=\"javascript:helper.showPrompt('Please configure class.boimport.inc.php according to your purposes.', 'noon()', 1);\">Import Clinical Data</a></h3>";
        
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
  				                                                                        'action' => 'exportInterface'),
                                            "Data export");                   
        $menu_admin .= $this->createMenuLink(array('menuaction' => $this->getCurrentApp(false).'.uietude.editorInterface',
  				                                                                        'action' => ''),
                                            "Editor");                                                                                                      

    return $menu_admin;  
  }

  private function getMainInterface($containerName)
  {             
 		  if($containerName=="SubjectsList"){
        $collection = "collection('SubjectsList.dbxml')";
      }elseif($containerName=="ClinicalData"){
        $collection = $this->m_ctrl->socdiscoo()->getClinicalDataCollection();
      }else{
        $collection = "collection('MetaDataVersion.dbxml')";
      }
      //Boucle sur les documents
      $query = "<docs>
                {
                let \$Col := $collection
                for \$doc in \$Col 
                return 
                <doc>{dbxml:metadata('dbxml:name', \$doc)}</doc>
                }
                </docs>
                "; 

      try{
        $docs = $this->m_ctrl->socdiscoo()->query($query);
      }catch(xmlexception $e){
        $str = "xQuery error : " . $e->getMessage() . "<br/><br/>" . $query . "</html>";
        die($str);
      }

      $htmlRet = '<table width="100%"><tr><td valign="top">';
      
      //Pour les containers SAS, on ajoute un bouton d'import des fichers depuis le container correspondant en ODM 1.3
      
      if($containerName=="ClinicalDataSAS" || $containerName=="MetaDataVersionSAS"){
        $htmlRet .= "<a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                           'action' => 'importToSAS',
                                                                           'container' => substr($containerName,0,strlen($containerName)-3))) . "'>Mettre à jour la base SAS</a>";
      }
      
      $htmlRet .= '<div class="divSidebox">
            	<div class="divSideboxHeader" align="center"><span>'.$containerName.'</span></div>';
      
			$htmlRet .= "<table align='center' width='80%' cellspacing='0' cellpadding='2' class='tabloGris'>
			<tbody><tr>
			<th class='tabloGrisTitle'>Name</th>
			<th class='tabloGrisTitle'>Status</th>
			<th class='tabloGrisTitle' colspan='3'>Actions</th>
			</tr>" ;  
      
      $class = "";

      foreach($docs[0] as $doc)
      {
        //checking if document can be accessed
        $status = "ok";
        try{
          if($containerName=="SubjectsList"){
            $xquery = "let \$FileOID := collection('SubjectsList.dbxml')/odm:ODM/@FileOID";
          }elseif($containerName=="ClinicalData"){
            $xquery = "let \$FileOID := collection('$doc.dbxml')/odm:ODM/@FileOID";
          }else{
            $xquery = "let \$FileOID := doc('MetaDataVersion.dbxml/$doc')/odm:ODM/@FileOID";
          }
          $xquery .= "
                     return
                           <result FileOID='{\$FileOID}' />";
          $results = $this->m_ctrl->socdiscoo()->query($xquery);
          if((string)$results[0]['FileOID'] == '') throw new xmlexception("Cannot find doc('$containerName.dbxml/$doc')/odm:ODM/@FileOID");
        }catch(xmlexception $e){
          $status = "<input type='button' value='ko' onClick=\"alert('". addslashes($e->getMessage()) ."')\" />";
        }
        
        $class = ($class=="row_off" ? "row_on" : "row_off");
        
        $htmlRet .= "
           <tr class='$class'><td>" . $doc ."</td>
             <td style='text-align: center;'>$status</td> 
             <td><small><a target='_new' href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                                              'action' => 'viewDoc',
                                                                                              'container' => $containerName,
                                                                                              'doc'=>$doc)) . "'>View</a></small></td>   
             <td><small><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'viewDoc',
                                                                                              'container' => $containerName,
                                                                                              'doc'=>$doc,
                                                                                              'asxml'=>'true')) . "'>Download</a></small></td>
             <td><small><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'updateSubjectStatus',
                                                                                              'container' => $containerName,
                                                                                              'doc'=>$doc)) . "'>Update Subject Status</a></small></td>
             <td><small><a href=\"javascript:deleteDocument('".$doc."')\">Delete</a></small></td>

           </tr>";        
      }

      //Ajout du BLANK
      if($containerName=="ClinicalData"){
        $htmlRet .= "
           <tr class='$class'><td>BLANK</td>
             <td style='text-align: center;'>--</td> 
             <td><small><a target='_new' href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                                              'action' => 'viewDoc',
                                                                                              'doc'=>"BLANK")) . "'>View</a></small></td>   
             <td><small><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'viewDoc',
                                                                                              'doc'=>"BLANK",
                                                                                              'asxml'=>'true')) . "'>Download</a></small></td>
             <td><small><a href=\"javascript:deleteDocument('".$doc."')\">Delete</a></small></td>
           </tr>";  
      }
      $htmlRet .= "</tbody></table>";
      
      $htmlRet .= "<form action='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                                              'action' => 'execXQuery',
                                                                                              'container' => $containerName))."' method='post'>
                    <textarea name='p_xquery' rows='15' cols='140'>".(isset($_POST['p_xquery'])?$_POST['p_xquery']:'')."</textarea><br/>
                    <input type='submit'/></form>";
      
      $deleteUrl = $GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.dbadminInterface',
                                                                                              'action' => 'deleteDoc',
                                                                                              'container' => $containerName));
      $htmlRet .= "
                   <script>
                    function deleteDocument(doc)
                    {
                      if(confirm('Delete document '+doc+' ?'))
                      {
                        window.location = \"".$deleteUrl."&doc=\"+doc;
                      }
                    }
                   </script>
                   ";  
      
      return $htmlRet; 
  }

  private function getImportECGInterface()
  {           
      $htmlRet = "<div class='subjectBold'>Import des données ECG</div>
                    <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'importECGDoc')) . "' method='post' enctype='multipart/form-data'>
                      Fichier ECG XML à importer : <input type='file' name='uploadedDoc'>
                      <input type='submit' value='Importer'/>
                    </form>";
      
      //On liste les exports déja réalisé
      $tblImport = $this->m_ctrl->boimport()->getImportList("ECG");
      
      $htmlRet .= "<br/></br><br/><table><tr><th>ECG File Creation Date</th><th>User</th><th>Status</th><th>Errors</th><th>Import File</th><th>Report File</th></tr>";
      
      foreach($tblImport as $import){
        $htmlRet .= "<tr>
                      <td>{$import['DATE_IMPORT_FILE']}</td>
                      <td>{$import['USER']}</td>
                      <td>{$import['STATUS']}</td>
                      <td>{$import['ERROR_COUNT']}</td>
                      <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                               'importid' => $import['IMPORTID'],
                                                                               'importFileType' => 'IMPORT_FILE',
                                                                               )) ."'>{$import['IMPORT_FILE']}</a></td>                   
                      <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                               'importid' => $import['IMPORTID'],
                                                                               'importFileType' => 'REPORT_FILE',
                                                                               )) ."'>{$import['REPORT_FILE']}</a></td>                   
                     </tr>";
      }
      $htmlRet .= "</table>"; 
        
      return $htmlRet;  
  }  

  private function getImportLABInterface()
  {           
      $htmlRet = "<div class='subjectBold'>Import des données LAB</div>
                    <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'importLABDoc')) . "' method='post' enctype='multipart/form-data'>
                      Fichier LAB XML à importer : <input type='file' name='uploadedDoc'>
                      <input type='submit' value='Importer'/>
                    </form>";
      
      //On liste les exports déja réalisé
      $tblImport = $this->m_ctrl->boimport()->getImportList("LAB");
      
      $htmlRet .= "<br/></br><br/><table><tr><th>ECG File Creation Date</th><th>User</th><th>Status</th><th>Errors</th><th>Import File</th><th>Report File</th></tr>";
      
      foreach($tblImport as $import){
        $htmlRet .= "<tr>
                      <td>{$import['DATE_IMPORT_FILE']}</td>
                      <td>{$import['USER']}</td>
                      <td>{$import['STATUS']}</td>
                      <td>{$import['ERROR_COUNT']}</td>
                      <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                               'importid' => $import['IMPORTID'],
                                                                               'importFileType' => 'IMPORT_FILE',
                                                                               )) ."'>{$import['IMPORT_FILE']}</a></td>                   
                      <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                               'importid' => $import['IMPORTID'],
                                                                               'importFileType' => 'REPORT_FILE',
                                                                               )) ."'>{$import['REPORT_FILE']}</a></td>                   
                     </tr>";
      }
      $htmlRet .= "</table>"; 
        
      return $htmlRet;  
  } 

  private function getImportCodingInterface()
  {           
      $htmlRet = "<div class='subjectBold'>Import des données Coding</div>
                    <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'importCodingDoc')) . "' method='post' enctype='multipart/form-data'>
                      Fichier Coding CSV (AE, CM ou MH) à importer : <input type='file' name='uploadedDoc'>
                      <input type='submit' value='Importer'/>
                    </form>";
      
      //On liste les exports déja réalisé
      $tblImport = $this->m_ctrl->boimport()->getImportList("Coding");
      
      $htmlRet .= "<br/></br><br/><table><tr><th>Import Date</th><th>User</th><th>Status</th><th>Errors</th><th>Import File</th><th>Report File</th></tr>";
      
      foreach($tblImport as $import){
        $htmlRet .= "<tr>
                      <td>{$import['DATE_IMPORT_FILE']}</td>
                      <td>{$import['USER']}</td>
                      <td>{$import['STATUS']}</td>
                      <td>{$import['ERROR_COUNT']}</td>
                      <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                               'importid' => $import['IMPORTID'],
                                                                               'importFileType' => 'IMPORT_FILE',
                                                                               )) ."'>{$import['IMPORT_FILE']}</a></td>                   
                      <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getImportFile',
                                                                               'importid' => $import['IMPORTID'],
                                                                               'importFileType' => 'REPORT_FILE',
                                                                               )) ."'>{$import['REPORT_FILE']}</a></td>                   
                     </tr>";
      }
      $htmlRet .= "</table>"; 
        
      return $htmlRet;  
  } 

  private function getImportInterface()
  {           
      $htmlRet = "<div class='subjectBold'>Import</div>
                       <form action='" . $GLOBALS['egw']->link('/index.php',array('menuaction'=>$this->getCurrentApp(false).'.uietude.dbadminInterface','action'=>'importDoc')) . "' method='post' enctype='multipart/form-data'>
                      XML File to import : <input type='file' name='uploadedDoc'>
                      <select name='p_container'>
                        <option value='ClinicalData'>ClinicalData</option>
                        <option value='MetaDataVersion'>MetaDataVersion</option>
                      </select>
                      <input type='submit' value='Importer'/>
                    </form>";
      
      return $htmlRet;  
  }  
  
  private function getExportInterface()
  {
    $htmlRet = "<h3>Data export</h3>";

    //Links to process export, read from config file
    foreach($this->m_tblConfig['EXPORT']['TYPE'] as $exportType=>$exportInfos){
      $htmlRet .= "<div><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                              'action' => 'export','type'=>$exportType )) . "'>".$exportInfos['name']."</a></div>";
      
    }
/*
    $htmlRet .= "<div><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                            'action' => 'exportDSMB')) . "'>Export DSMB</a></div>
                 <div><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                            'action' => 'exportCoding')) . "'>Export Coding</a></div>                        
                 <div><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dbadminInterface',
                                                                            'action' => 'exportFullDB')) . "'>Export Full</a></div>";
*/
    
    //On liste les exports déja réalisé
    $tblExport = $this->m_ctrl->boexport()->getExportList();
    
    $htmlRet .= "<table><tr><th>Date</th><th>Type</th><th>User</th><th>Password</th><th>File</th></tr>";
    
    foreach($tblExport as $export){
      $htmlRet .= "<tr>
                    <td>{$export['exportdate']}</td>
                    <td>{$export['exporttype']}</td>
                    <td>{$export['exportuser']}</td>
                    <td>{$export['exportpassword']}</td>
                    <td><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uidbadmin.getExportFile',
                                                                             'exportid' => $export['exportid'],
                                                                             )) ."'>{$export['exportname']}</a></td>                   
                   </tr>";
    }
    $htmlRet .= "</table>";
      
    return $htmlRet;  
  }

  function getExportFile(){
    $exportId = $_GET['exportid'];
    
    $this->m_ctrl->boexport()->getExportFile($exportId);
  }

    function getImportFile(){
    $exportId = $_GET['importid'];
    $importFileType = $_GET['importFileType'];
    
    $this->m_ctrl->boimport()->getImportFile($exportId,$importFileType);
  }

/*
@desc Produit un nouvel export des données. Les règles sont les suivantes :
      1 fichier csv est généré par ItemGroup
      Les variables à exporter sont indiquées dans le fichier de configuration
      Les fichiers csv sont zippés dans un fichier qui s'ajoute à la liste des exports précédent (pas d'annule et remplace)  
*/  
  private function export($type)
  {
    $this->m_ctrl->boexport()->export($type);   
  }
  
  function importDoc($container){
	  $uploaddir = $this->m_tblConfig['CDISCOO_PATH'] . "/xml/";
    $uploadfile = $uploaddir . basename($_FILES['uploadedDoc']['name']);
    
    if($container=="MetaDataVersion"){
      $containerName = "MetaDataVersion.dbxml";
    }else{
      $containerName="";
    }
    
    if (move_uploaded_file($_FILES['uploadedDoc']['tmp_name'], $uploadfile)){
      try{
        echo "adding {$_FILES['uploadedDoc']['name']}...<br/>";
        $this->m_ctrl->socdiscoo()->addDocument($uploadfile,false,$containerName);
      }catch(Exception $e){
          //déjà présent, on va essayer de le remplacer
          echo "document already imported, replacing...<br/>";
          $this->m_ctrl->socdiscoo()->replaceDocument($uploadfile,false,$containerName);
      }
      echo "import successfull !<br/>";         
    }
  }

  function importECGDoc()
  {
	  $uploaddir = $this->m_tblConfig['IMPORT_BASE_PATH'];
    
    $uploadfile = $uploaddir . basename($_FILES['uploadedDoc']['name']);    
    
    $tblBlockingErrors = array();
       
    if(move_uploaded_file($_FILES['uploadedDoc']['tmp_name'], $uploadfile)){      
      $this->m_ctrl->boimport()->importECG($uploadfile);   
    }
  }

  function importLABDoc()
  {
	  $uploaddir = $this->m_tblConfig['IMPORT_BASE_PATH'];
    
    $uploadfile = $uploaddir . basename($_FILES['uploadedDoc']['name']);    
    
    $tblBlockingErrors = array();
       
    if(move_uploaded_file($_FILES['uploadedDoc']['tmp_name'], $uploadfile)){      
      $this->m_ctrl->boimport()->importLAB($uploadfile);   
    }
  }
  
  function importCodingDoc()
  {
	  $uploaddir = $this->m_tblConfig['IMPORT_BASE_PATH'];
    
    $uploadfile = $uploaddir . basename($_FILES['uploadedDoc']['name']);    
    
    $tblBlockingErrors = array();
       
    if(move_uploaded_file($_FILES['uploadedDoc']['tmp_name'], $uploadfile)){      
      $this->m_ctrl->boimport()->importCoding($uploadfile);   
    }
  }
}
