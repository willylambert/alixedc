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
    
require_once("class.CommonFunctions.php");
require_once("class.instanciation.inc.php");

require_once(EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/config.inc.php");

class uiexport extends CommonFunctions{
 	var $public_functions = array(
			'getExportFile'	=> True,
		);
 
  function __construct()
  {	
    global $configEtude;
    CommonFunctions::__construct($configEtude,null);
    
    $this->m_ctrl = new instanciation();
  }
  
  function getInterface(){
    $this->addLog("uisubject->getInterface()",TRACE);
    
    if(!$this->m_ctrl->boacl()->checkModuleAccess("ExportData")){
      $this->addLog("Unauthorized Access to Export Module - Administrator has been notified",FATAL);
    }

    if(!isset($_GET['action'])){
      $htmlRet = $this->getExportList();
    }else{
      if($_GET['action']=='defineExport'){
        $htmlRet = $this->getExportList($errors);  
      }else{
        if($_GET['action']=="edit"){
          $htmlRet = $this->editInterface($_GET['id']);
        }else{
          if($_GET['action']=="save"){
            $id = $_POST['id'];            
            $errors = $this->defineExport($id,$_POST['name'],$_POST['description'],$_POST['share'],$_POST['raw']);
            if($errors==""){
              $this->m_ctrl->boexport()->saveExport($id,$_POST['ItemGroups'],$_POST['Items']);
            }
            $htmlRet = $this->getExportList($errors);
          }else{
            if($_GET['action']=="run"){            
              $this->m_ctrl->boexport()->runExport($_GET['id'],$_GET['type'],$_GET['raw']);
              $htmlRet = "<br/></br><a href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.exportInterface'))."'>Return to export interface</a>";
            }
          }  
        }
      }
    }
      
    return $htmlRet;        
  }
  
  /**
  * Interface to edit an export type
  * @param integer $id id of the export to edit - an empty string for a new export type   
  * @return string html to display
  * @author wlt - 07/12/2011        
  **/  
  function editInterface($id){

    if($id!=""){
      //We retrieve values from database
      $export = $this->m_ctrl->boexport()->getExportList($id);
      $name = $export[0]['name'];
      $description = $export[0]['description'];
      $share = $export[0]['share'];
      $raw = $export[0]['raw'];
    }else{
      $name = "";
      $description = "";
      $share = "Y";
      $raw = "N";
    }
    
    //Here we build the ItemGroup / Item selector interface
    $tblItems = $this->m_ctrl->boexport()->getMetaDataStructure();
    
    $tblCheckedItems = $this->m_ctrl->boexport()->getExportDef($id); 
        
    $treeItems = "<ul id='treeItems'>";
    foreach($tblItems as $StudyEvent){
      $treeItems .= "<li><input type='checkbox' />" . $StudyEvent['StudyEventTitle'];
      $treeItems .= "<ul>";
      foreach($StudyEvent as $Form){
        $treeItems .= "<li><input type='checkbox' />" . $Form['FormTitle'];
        $treeItems .= "<ul>";
        foreach($Form as $ItemGroup){
          $treeItems .= "<li><input type='checkbox' name='ItemGroups[]' value='". $StudyEvent['StudyEventOID'] . "_" . $Form['FormOID'] . "_" . $ItemGroup['ItemGroupOID']."' />" . $ItemGroup['ItemGroupTitle'];
          $treeItems .= "<ul>";
          foreach($ItemGroup as $Item){
            if(isset($tblCheckedItems["{$StudyEvent['StudyEventOID']}"]["{$Form['FormOID']}"]["{$ItemGroup['ItemGroupOID']}"]) && 
              in_array($Item['ItemOID'],$tblCheckedItems["{$StudyEvent['StudyEventOID']}"]["{$Form['FormOID']}"]["{$ItemGroup['ItemGroupOID']}"])){
              $checked = "checked";
            }else{
              $checked = "";
            }
            $treeItems .= "<li><input type='checkbox' name='Items[]' $checked value='". $StudyEvent['StudyEventOID'] . "_" . $Form['FormOID'] . "_" . $ItemGroup['ItemGroupOID'] . "_" . $Item['ItemOID']."' />" . $Item['Question'] . "</li>";
          }
          $treeItems .= "</ul></li>";        
        }
        $treeItems .= "</ul></li>";
      }
      $treeItems .= "</ul></li>";  
    } 
    $treeItems .= "</ul>";
    
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    $htmlRet = "
      <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.export.js') . "'></SCRIPT>
      <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.collapsibleCheckboxTree.js') . "'></SCRIPT>
      <link rel='stylesheet' type='text/css' href='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/jquery.collapsibleCheckboxTree.css') ."'/>
      $menu
      <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
        <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
          <span class='ui-dialog-title'>Define Export $name</span>
        </div>
      </div>
      
      <div class='ui-grid-users ui-dialog-content ui-widget-content'>             
        <form id='defineExport' action='index.php?menuaction=".$this->getCurrentApp(false).".uietude.exportInterface&action=save' method='post'>
        	<input type='hidden' name='id' value='$id'/> 
          <fieldset>
        		<label for='name'>Name</label>  
        		<input type='text' name='name' value='$name'/>
        		<label for='description'>Description</label>  
        		<input type='text' name='description' value='$description'/>
        		<label for='share'>Share ?</label> 
        		<select name='share'>
        		  <option value='Y' ".($share=='Y'?'selected':'').">Y</option>
        		  <option value='N' ".($share=='N'?'selected':'').">N</option>
            </select> 
        		<label for='raw'>Raw values ?</label>
            <select name='raw'>
        		  <option value='Y' ".($raw=='Y'?'selected':'').">Y</option>
        		  <option value='N' ".($raw=='N'?'selected':'').">N</option>
            </select> 
          </fieldset>
      	  Select items : <br/>
          $treeItems
      	</form>
      </div>
      <div id='ActionsButtons'>
          <button id='btnCancel'>Cancel</button>
          <button id='btnSave'>Save</button>
      </div> 
      <script>loadAlixCRFexporteditJS('".$this->getCurrentApp(false)."');</script>";           
    
    return $htmlRet;
  }

  /**
  Produce a new data export. Rules are as follows :
      1 file is produced per ItemGroup
      Variables to be exported could be defined in the UI or/and the config file config.export.inc.php
      CSV files are zipped and password protected in 1 file which is avaiable for download from UI
  @author WLT - 09/12/2011
  **/  
  private function runExport($id,$type)
  {
    $this->m_ctrl->boexport()->export($id,$type);   
  }
  
  function defineExport(&$id,$name,$description,$share,$raw){
    $errors = "";
    
    //If id is not null, we are in edit mode. otherwise we are adding a new export
    //If adding, $id will receive the new id by reference 
    try{
      $this->m_ctrl->boexport()->defineExport($id,$name,$description,$share,$raw);    
    }catch(Exception $e){
      $errors = $e->getMessage();
      die($errors);
    }
    return $errors;
  }
  
  /**
   * Building of the Export List
   * @author wlt
  **/
  function getExportList($errors=""){
  
    $htmlExports = "
      <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
		    <div class='ui-grid-header ui-widget-header ui-corner-top'>Exports Definition list</div>
        <table id='tblExport' class='ui-grid-content ui-widget-content'>
  			<thead>
  				<tr>
  					<th class='ui-state-default'>Name</th>
  					<th class='ui-state-default'>Description</th>
  					<th class='ui-state-default'>User</th>
  					<th class='ui-state-default'>Date of creation</th>
  					<th class='ui-state-default'>Share</th>
  					<th class='ui-state-default'>Raw values</th>
  					<th class='ui-state-default'>Last run</th>
  					<th class='ui-state-default' colspan='2'>Actions</th>
  				</tr>
  			</thead>
        <tbody>";                  
    
    $exportList = $this->m_ctrl->boexport()->getExportList();
      
    foreach($exportList as $export)
		{
		   $editLink = "&nbsp;";
		   //Only owner can edit 
       if($export['user']==$GLOBALS['egw_info']['user']['userid']){
        $editLink =  "<a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.exportInterface',
                                                                                              'action' => 'edit',
                                                                                              'id' => $export['id'])) . "'>Edit</a>";
       }
       
       $htmlExports .= "<tr id='".$export['id']."'>
              					<td class='ui-widget-content'>".$export['name']."</td>
              					<td class='ui-widget-content'>".$export['description']."</td>
              					<td class='ui-widget-content'>".$export['user']."</td>
              					<td class='ui-widget-content'>".$export['creationDate']."</td>
              					<td class='ui-widget-content'>".$export['share']."</td>
              					<td class='ui-widget-content'>".$export['raw']."</td>
              					<td class='ui-widget-content'>".$export['lastrun']."</td>
              					<td class='ui-widget-content'><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.exportInterface',
                                                                                              'action' => 'run',
                                                                                              'id' => $export['id'],
                                                                                              'raw' => $export['raw'],
                                                                                              'type' => 'db')) . "'>Run</a></td>
              					<td class='ui-widget-content'>$editLink</td>
              				  </tr>";
		}
		
    //Add export from config file
    foreach($this->m_tblConfig['EXPORT']['TYPE'] as $exportId=>$exportInfos){
      if(isset($exportInfos['share']) && $exportInfos['share']=="Y" || 
         $GLOBALS['egw_info']['user']['apps']['admin']){
         if(!isset($exportInfos['raw'])){
          $rawExport = "Y";
         }else{
          $rawExport = $exportInfos['raw'];
         } 
        $htmlExports .= "<tr><td class='ui-widget-content'>".$exportInfos['name']."</td>
                             <td class='ui-widget-content'>".$exportInfos['description']."</td>
                    				 <td class='ui-widget-content'>Admin</td>
                    				 <td class='ui-widget-content'>-</td>
                    				 <td class='ui-widget-content'>".$exportInfos['share']."</td>
                    				 <td class='ui-widget-content'>$rawExport</td>
                    				 <td class='ui-widget-content'>-</td>
                   					 <td class='ui-widget-content'><a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.exportInterface',
                                                                              'action' => 'run',
                                                                              'id'=>$exportId,
                                                                              'raw'=>$rawExport,
                                                                              'type'=>'config_file' )) . "'>Run</a></td>
                         </tr>"; 
      }
    }
    	
		$htmlExports .= "</tbody></table></div>";
    
    //Export log : list of all exports done
    $tblLogExport = $this->m_ctrl->boexport()->getLogExport();
    
    $htmlExportsLog = "<table class='ui-grid-content ui-widget-content'>
                  <tr><th class='ui-state-default'>Date</th>
                      <th class='ui-state-default'>Name</th>
                      <th class='ui-state-default'>User</th>
                      <th class='ui-state-default'>Password</th>
                      <th class='ui-state-default'>File</th>
                  </tr>";
    
    foreach($tblLogExport as $exportLog){
      if($exportLog['exporttype']=="db"){
        $exportName = $exportLog['exportname'];
      }else{
        $exportName = $this->m_tblConfig['EXPORT']['TYPE'][$exportLog['exportid']]["name"];
      }
      $htmlExportsLog .= "<tr>
                    <td class='ui-widget-content'>{$exportLog['exportdate']}</td>
                    <td class='ui-widget-content'>$exportName</td>
                    <td class='ui-widget-content'>{$exportLog['exportuser']}</td>
                    <td class='ui-widget-content'>{$exportLog['exportpassword']}</td>
                    <td class='ui-widget-content'><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uiexport.getExportFile',
                                                                             'id' => $exportLog['logid'],
                                                                             )) ."'>{$exportLog['exportfilename']}</a></td>                   
                   </tr>";
    }
    $htmlExportsLog .= "</table>";
    
    $menu = $this->m_ctrl->etudemenu()->getMenu();

    if($errors!=''){
      $errors = "<div class='ui-state-error'>$errors</div>";
    }

    $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.6.2.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.16.custom.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.export.js') . "'></SCRIPT>

                $menu

                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Admin</span>
                  </div>
                  $errors
                  <div class='ui-grid-export ui-dialog-content ui-widget-content'>
                    $htmlExports                    
                  </div>
                  <div>
                    <form method='post' action='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.exportInterface',
                                                                             'action' => 'edit',
                                                                             'id'=>'0'))."' >
                      <input type='submit' id='btnAddExport' class='ui-widget ui-button' value='Add new export' />
                    </form>
                  </div>
                  <div class='ui-grid-export ui-dialog-content ui-widget-content'>
                    $htmlExportsLog
                  </div>
                </div>                               
                
                <script>loadAlixCRFexportJS('".$this->getCurrentApp(false)."');</script>";                  
    return $htmlRet;    
  }

  function getExportFile(){
    $id = $_GET['id'];
    
    $this->m_ctrl->boexport()->getExportFile($id);
  }
  
}