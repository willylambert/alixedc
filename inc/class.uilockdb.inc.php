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
require_once("class.instanciation.inc.php");

require_once(EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/config.inc.php");

class uilockdb extends CommonFunctions{
 	var $public_functions = array(
			'getLockDBFile'	=> True,
		);
 
  function __construct()
  {	
    global $configEtude;
    CommonFunctions::__construct($configEtude,null);
    
    $this->m_ctrl = new instanciation();
  }
  
  function getInterface(){
    $this->addLog("uisubject->getInterface()",TRACE);
    
    if(!$this->m_ctrl->boacl()->checkModuleAccess("LockDB")){
      $this->addLog("Unauthorized Access to Lock Module - Administrator has been notified",FATAL);
    }

    if(!isset($_GET['action'])){
      $htmlRet = $this->getLockDBList();
    }else{
      if($_GET['action']=='defineLockDB'){
        $htmlRet = $this->getLockDBList($errors);  
      }else{
        if($_GET['action']=="edit"){
          $htmlRet = $this->editInterface($_GET['id']);
        }else{
          if($_GET['action']=="save"){
            $id = $_POST['id'];            
            $errors = $this->defineLockDB($id,$_POST['name'],$_POST['description'],$_POST['share']);
            if($errors==""){
              $this->m_ctrl->bolockdb()->saveLockDB($id,$_POST['ItemGroups'],$_POST['Items']);
            }
            $htmlRet = $this->getLockDBList($errors);
          }else{
            if($_GET['action']=="activate"){            
              $this->m_ctrl->bolockdb()->activateLockDB($_GET['id']);
              $htmlRet = $this->getLockDBList();
            }else{
              if($_GET['action']=="inactivate"){
                $this->m_ctrl->bolockdb()->inactivateLockDB($_GET['id']);
                $htmlRet = $this->getLockDBList();
              }
            }
          }  
        }
      }
    }
      
    return $htmlRet;        
  }
  
  /**
  * Interface to edit a lock definition still not run
  * @param integer $id id of the lock to edit - an empty string for a new lock definition
  * @return string html to display
  * @author tpi - 08/10/2012        
  **/  
  function editInterface($id){

    if($id!=""){
      //We retrieve values from database
      $lock = $this->m_ctrl->bolockdb()->getLockDBList($id);
      $name = $lock[0]['name'];
      $description = $lock[0]['description'];
      $share = $lock[0]['share'];
      $active = $lock[0]['active'];
    }else{
      $name = "";
      $description = "";
      $share = "Y";
      $active = "";
    }
    
    //Here we build the ItemGroup / Item selector interface
    $tblItems = $this->m_ctrl->bolockdb()->getMetaDataStructure();
    
    $tblCheckedItems = $this->m_ctrl->bolockdb()->getLockDBDef($id); 
        
    $treeItems = "<ul id='treeItems'>";
    foreach($tblItems as $StudyEvent){
      $treeItems .= "<li><input type='checkbox' />" . $StudyEvent['StudyEventTitle'];
      $treeItems .= "<input type='text' name='StudyEventRepeatKey_" . str_replace('.','@',$StudyEvent['StudyEventOID']) . "' value='" . $tblCheckedItems["studyeventrepeatkey_{$StudyEvent['StudyEventOID']}"] . "' />";
      $treeItems .= "<ul>";
      foreach($StudyEvent as $Form){
        $treeItems .= "<li><input type='checkbox' />" . $Form['FormTitle'];
        $treeItems .= "<input type='text' name='FormRepeatKey_" . str_replace('.','@',$StudyEvent['StudyEventOID']) . "_" . str_replace('.','@',$Form['FormOID']) . "' value='" . $tblCheckedItems["formrepeatkey_{$StudyEvent['StudyEventOID']}_{$Form['FormOID']}"] . "' />";
        $treeItems .= "<ul>";
        foreach($Form as $ItemGroup){
          $treeItems .= "<li><input type='checkbox' name='ItemGroups[]' value='". $StudyEvent['StudyEventOID'] . "_" . $Form['FormOID'] . "_" . $ItemGroup['ItemGroupOID']."' />" . $ItemGroup['ItemGroupTitle'];
          $treeItems .= "<input type='text' name='ItemGroupRepeatKey_" . str_replace('.','@',$StudyEvent['StudyEventOID']) . "_" . str_replace('.','@',$Form['FormOID']) . "_" . str_replace('.','@',$ItemGroup['ItemGroupOID']) . "' value='" . $tblCheckedItems["itemgrouprepeatkey_{$StudyEvent['StudyEventOID']}_{$Form['FormOID']}_{$ItemGroup['ItemGroupOID']}"] . "' />";
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
      <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.lockdb.js') . "'></SCRIPT>
      <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.collapsibleCheckboxTree.js') . "'></SCRIPT>
      <link rel='stylesheet' type='text/css' href='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/jquery.collapsibleCheckboxTree.css') ."'/>
      $menu
      <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
        <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
          <span class='ui-dialog-title'>Define Lock $name</span>
        </div>
      </div>
      
      <div class='ui-grid-users ui-dialog-content ui-widget-content'>             
        <form id='defineLockDB' action='index.php?menuaction=".$this->getCurrentApp(false).".uietude.lockdbInterface&action=save' method='post'>
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
          </fieldset>
      	  Select items : <br/>
      	  <i>Filters on RepeatKeys can be specified. Each value must be separated by a comma.</i>
          $treeItems
      	</form>
      </div>
      <div id='ActionsButtons'>
          <button id='btnCancel'>Cancel</button>
          <button id='btnSave' style='". ($active=="Y"?"width: 250px;":"") ."'>Save". ($active=="Y"?" and apply modifications":"") ."</button>
      </div> 
      <script>loadAlixCRFlockeditJS('".$this->getCurrentApp(false)."');</script>";           
    
    return $htmlRet;
  }
  
  function defineLockDB(&$id,$name,$description,$share){
    $errors = "";
    
    //If id is not null, we are in edit mode. otherwise we are adding a new lock
    //If adding, $id will receive the new id by reference 
    try{
      $this->m_ctrl->bolockdb()->defineLockDB($id,$name,$description,$share);    
    }catch(Exception $e){
      $errors = $e->getMessage();
      die($errors);
    }
    return $errors;
  }
  
  /**
   * Building of the LockDB List
   * @author wlt
  **/
  function getLockDBList($errors=""){
  
    $htmlLockDBs = "
      <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
		    <div class='ui-grid-header ui-widget-header ui-corner-top'>LockDBs Definition list</div>
        <table id='tblLockDB' class='ui-grid-content ui-widget-content'>
  			<thead>
  				<tr>
  					<th class='ui-state-default'>Name</th>
  					<th class='ui-state-default'>Description</th>
  					<th class='ui-state-default'>User</th>
  					<th class='ui-state-default'>Date of creation</th>
  					<th class='ui-state-default'>Share</th>
  					<th class='ui-state-default'>Last activation</th>
  					<th class='ui-state-default'>Status</th>
  					<th class='ui-state-default' colspan='2'>Actions</th>
  				</tr>
  			</thead>
        <tbody>";                  
    
    $lockList = $this->m_ctrl->bolockdb()->getLockDBList();
    
    //Manage an alert when one lock definition is already activated
    $existOneActivatedLock = false;
    foreach($lockList as $lock)
		{
		  if($lock['active']=="Y") $existOneActivatedLock = true;
		}
		$jsActivate = "";
		if($existOneActivatedLock){
		  $jsActivate = "if(confirm('Activating this lock definition will inactivate the currently activated lock definition. Continue ?')){return true;}else{return false;}";
		}
		$jsInactivate = "if(confirm('This will inactivate the lock so that the data can be modified. Continue ?')){return true;}else{return false;}";
		
    foreach($lockList as $lock)
		{
		   $editLink = "&nbsp;";
		   //Only owner can edit 
       if($GLOBALS['egw_info']['user']['apps']['admin'] ||
          $lock['user']==$GLOBALS['egw_info']['user']['userid']){
        $editLink =  "<a href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.lockdbInterface',
                                                                                              'action' => 'edit',
                                                                                              'id' => $lock['id'])) . "'>Edit</a>";
       }
       
       $action = "";
       $onaction = "";
       if($lock['active']=="Y"){
         $action = "inactivate";
         $onaction = "onclick=\"$jsInactivate\"";
       }else{
         $action = "activate";
         //$onaction = "onclick=\"$jsActivate\"";
       }
       
       $htmlLockDBs .= "<tr id='".$lock['id']."'>
              					<td class='ui-widget-content'>".$lock['name']."</td>
              					<td class='ui-widget-content'>".$lock['description']."</td>
              					<td class='ui-widget-content'>".$lock['user']."</td>
              					<td class='ui-widget-content'>".$lock['creationDate']."</td>
              					<td class='ui-widget-content'>".$lock['share']."</td>
              					<td class='ui-widget-content'>".$lock['lastactivation']."</td>
              					<td class='ui-widget-content'>".($lock['active']=='Y'?'<b>Active</b>':'')."</td>
              					<td class='ui-widget-content'><a $onaction href='" . $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.lockdbInterface',
                                                                                              'action' => $action,
                                                                                              'id' => $lock['id'])) . "'>".ucfirst($action)."</a></td>
              					<td class='ui-widget-content'>$editLink</td>
              				  </tr>";
		}
    	
		$htmlLockDBs .= "</tbody></table></div>";
    
    //LockDB log : list of all locks done
    $tblLogLockDB = $this->m_ctrl->bolockdb()->getLogLockDB();
    
    $htmlLockDBsLog = "<table class='ui-grid-content ui-widget-content'>
                  <tr><th class='ui-state-default'>Date</th>
                      <th class='ui-state-default'>Name</th>
                      <th class='ui-state-default'>User</th>
                      <th class='ui-state-default'>PDF</th>
                      <th class='ui-state-default'>CSV</th>
                  </tr>";
    
    foreach($tblLogLockDB as $lockLog){
      $lockName = $lockLog['lockname'];
      $htmlLockDBsLog .= "<tr style='text-align: left;'>
                    <td class='ui-widget-content'>{$lockLog['lockdate']}</td>
                    <td class='ui-widget-content'>$lockName</td>
                    <td class='ui-widget-content'>{$lockLog['lockuser']}</td>
                    <td class='ui-widget-content'><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uilockdb.getLockDBFile',
                                                                             'id' => $lockLog['logid'],
                                                                             'format' => 'pdf',
                                                                             )) ."'>{$lockLog['lockfilename']}.pdf</a></td>      
                    <td class='ui-widget-content'><a target='new' href='". $GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uilockdb.getLockDBFile',
                                                                             'id' => $lockLog['logid'],
                                                                             'format' => 'csv',
                                                                             )) ."'>{$lockLog['lockfilename']}.csv</a></td>                   
                   </tr>";
    }
    $htmlLockDBsLog .= "</table>";
    
    $menu = $this->m_ctrl->etudemenu()->getMenu();

    if($errors!=''){
      $errors = "<div class='ui-state-error'>$errors</div>";
    }

    $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.6.2.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.16.custom.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.lockdb.js') . "'></SCRIPT>

                $menu

                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Admin</span>
                  </div>
                  $errors
                  <div class='ui-grid-lock ui-dialog-content ui-widget-content'>
                    $htmlLockDBs                    
                  </div>
                  <div>
                    <form method='post' action='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.lockdbInterface',
                                                                             'action' => 'edit',
                                                                             'id'=>'0'))."' >
                      <input type='submit' id='btnAddLockDB' class='ui-widget ui-button' value='Add new Lock' />
                    </form>
                  </div>
                  <div class='ui-grid-lock ui-dialog-content ui-widget-content'>
                    $htmlLockDBsLog
                  </div>
                </div>                               
                
                <script>loadAlixCRFlockJS('".$this->getCurrentApp(false)."');</script>";                  
    return $htmlRet;    
  }

  function getLockDBFile(){
    $id = $_GET['id'];
    $format = $_GET['format'];
    
    $this->m_ctrl->bolockdb()->getLockDBFile($id,$format);
  }
  
}