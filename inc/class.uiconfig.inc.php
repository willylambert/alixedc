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
    
class uiconfig extends CommonFunctions
{
  
  function uiconfig($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }
  
  /**
   *@desc configuration page for Alix
   *@return string html to display
   *@author tpi        
   */  
  public function getInterface()
  {
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    
    $message = ""; //a message to display
    
    //save parameters
    if(isset($_POST['action']) && $_POST['action']=='save'){
      //retrieving the list of list of parameters (to save their values)
      $params = $this->m_ctrl->boconfig()->getParameters();
      foreach($params as $id => $param){
        if(isset($_POST[$id]) && $_POST[$id]!=$param['value']){
          $this->m_ctrl->boconfig()->setParameter($id, $_POST[$id]);
        }
      }
      $message = "Your settings have been saved";
    }
    
    //retrieving the list of list of parameters with their (new ?) values
    $params = $this->m_ctrl->boconfig()->getParameters();
    
    $htmlParams = "";
    $category = "";
    foreach($params as $id => $param){
      if(is_array($param['values'])){
        $input = "<select id='$id' name='$id'>";
        if($param['value']=="") $input .= "<option value=''>--</option>";
        foreach($param['values'] as $val => $lbl){
          $input .= "<option value='$val' ". ($val==$param['value']?"selected='selected'":"") .">$lbl</option>";
        }
        $input .= "</select>";
      }else{
        $input = "<input id='$id' name='$id' type='text' value=\"". $param['value'] ."\" />";
      }
      
      if($param['category']!=$category){ //a new title for new category
        $htmlParams .= "<div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'><span class='ui-dialog-title'>". $param['category'] ."</span></div>";
        $category = $param['category'];
      }
      $htmlParams .= "<div class='configParam'><label for='$label'>". $param['label'] ."</label>$input</div>";
    }
    
    $htmlRet = "$menu
                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>ALIX Configuration</span>
                  </div>
                  <div class='action_message'>$message</div>
                  <div id='alixConfiguration'>
                    <form action='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $GLOBALS['egw_info']['flags']['currentapp'].'.uietude.configInterface', 'action' => 'save'))."' method='post'>
                      <div class='parameters'>
                        $htmlParams
                      </div>
                      <input type='hidden' name='action' value='save' />
                      <input type='submit' class='ui-widget ui-button ui-state-default ui-corner-all' value='Save' role='button' aria-disabled='false' />
                    </form>
                  </div>
                </div>";
                
    return $htmlRet;
  }       
  
    
}