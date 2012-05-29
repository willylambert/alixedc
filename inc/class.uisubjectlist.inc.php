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
    
require_once("class.CommonFunctions.php");

class uisubjectlist extends CommonFunctions
{
 
  function uisubjectlist($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  } 
  
  function getInterface()
  {

    $menu = $this->m_ctrl->etudemenu()->getMenu();

    $lstSubjectsEx = $this->getSubjectsListEx();

    $htmlRet = "
                $menu

                <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Subjects List</span>
                  </div>                  
                  <div class='ui-dialog-content ui-widget-content'>
                    $lstSubjectsEx
                  </div>                  
                </div>";
      
      return $htmlRet;    
  }
  
  private function getSubjectsListEx()
  {
    $html = "";
    
    //Columns setup - we remove the Value field
    $cols = array();
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key => $col){
      $cols[] = array('Key' => $key,
                      'Visible' => $col['Visible'],
                      'Title'=> $col['Title'],
                      'ShortTitle'=> $col['ShortTitle'],
                      'Width'=> $col['Width'],
                      'Orientation' => $col['Orientation']);
    }
    
    //INV, ARC and DM can download PDF
    $bShowChecks = false;
    if($this->m_ctrl->boacl()->existUserProfileId(array("DM","CRA","INV"))){
      $bShowPDF = true;
    }
    
    //variable added to URLs, to force browser files reload 
    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    
    $html .= "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqGrid/grid.locale-en.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqGrid/jquery.jqGrid.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.cookie.js') . "'></SCRIPT>              
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "?$jsVersion'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.subjects.js') . "?$jsVersion'></SCRIPT>
              
              <table id='listSubjects'></table>
              <div id='pagerSubjects'></div>
              <div id='filter' style='margin-left:30%;display:none'></div>
              
              <script>
                //<![CDATA[
                  loadAlixCRFSubjectsJS('".$this->getCurrentApp(false)."','".json_encode($cols)."', ".($bShowPDF?'true':'false').");
                //]]>
              </script>";
    
    $html .= "<script>
                //<![CDATA[
                  function goSubject(SubjectKey){
                    CurrentApp = '".$this->getCurrentApp(false)."';
                    StudyEventOID = '".$this->m_tblConfig['ENROL_SEOID']."';
                    StudyEventRepeatKey = '".$this->m_tblConfig['ENROL_SERK']."';
                    FormOID = '".$this->m_tblConfig['ENROL_FORMOID']."';
                    FormRepeatKey = '".$this->m_tblConfig['ENROL_FORMRK']."';
                    loadAlixCRFSubject(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);
                  }
                  
                  $(document).ready(function() {
                    initSubjectsList();
                  }); 
                //]]>                
              </script>";
              
    return $html;
  }
}