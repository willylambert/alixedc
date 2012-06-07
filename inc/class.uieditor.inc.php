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
    
/**
* @desc UI class dedicated to the study design, edtion of XSL, XML, JS, etc
* @author TPI
**/ 
class uieditor extends CommonFunctions
{
  /**
  * @desc Constructor
  * @param array $configEtude array of configuration constants   
  * @param uietude $ctrlRef reference for instanciation instance , to which is delegated instanciation of objects (calls such as $this->m_ctrl->bcdiscoo() ) 
  * @author TPI
  * 
  **/ 
  function uieditor($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * @desc main function - return html to display, call by uietude
  * @return string HTML to display
  * @author TPI
  **/     
  public function getInterface()
  {      
    $this->addLog("uieditor->getInterface()",TRACE);
    $htmlRet = "";
    
    $topMenu = $this->m_ctrl->etudemenu()->getMenu();
    
    $htmlMenu = $this->getMenu();
    
    $this->m_ctrl->boeditor()->setXmlFiles();
    
    $htmlEditor = $this->getEditor();
   
    $htmlRet = "$topMenu
                
                
                <div id='formMenu' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Documents</span>
                  </div>
                  <div class='ui-dialog-content ui-widget-content'>
                    $htmlMenu
                  </div>             
                </div>
                <div id='mainForm' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Editor</span>
                  </div>
                  <div id='editorMainContainer' class='ui-dialog-content ui-widget-content'>
                    $htmlEditor
                  </div>
                </div>
                ";
    	         
    return $htmlRet;  
  }
  
  /**
   * @desc html code for the code editor : https://github.com/ajaxorg/ace
   * @return string html to display
   * @author TPI
   **/           
  private function getEditor(){
    $htmlRet = "";
    
    //Toolbar
    $commands = array(
      "new" => array("New", "new.png"),
      "save" => array("Save", "save.png"),
      "rename" => array("Rename", "rename.png"),
      "delete" => array("Delete", "delete.png"),
      "shortcuts" => array("Shortcuts", "shortcuts.png"),
    );
    $toolbar = "<ul id='editorToolbar'>";
    foreach($commands as $command => $params){
      $toolbar .= "<li name='". $command ."' class='command'><img src='". $this->getCurrentApp(false) ."/templates/default/images/editor/". $params[1] ."' /> ". $params[0] ."</li>";
    }
    $toolbar .= "<li>Theme : <select id='editor_Theme' onChange=\"editor_SetTheme(this.value,true)\"><option value='cobalt'>Cobalt</option><option value='textmate'>TextMate</option><option value='twilight'>Twilight</option><option value='vibrant_ink'>Vibrant Ink</option></select></li>";
    $toolbar .= "<li>Font size : <select id='editor_FontSize' onChange=\"editor_SetFontSize(this.value,true)\"><option value='10'>10px</option><option value='12'>12px</option><option value='14'>14px</option><option value='16'>16px</option><option value='18'>18px</option></select></li>";
     $toolbar .= "</ul>";
    
    //Submenus
    $shortcuts = $this->getShortcuts();
    $rename = $this->getRename();
    $new = $this->getNew();
    
    //variable added to Javascript URLs in order to force reloading on the user's browser
    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    
    $wwwroot = $GLOBALS['egw']->link('/'.$this->getCurrentApp(false));
    
    $htmlRet = "$toolbar
                <!--begin: submenus-->
                $shortcuts
                $rename
                $new
                <!--begin: submenus-->
                
                <div id='editorContainer'></div>
                
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/ace.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/theme-cobalt.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/theme-textmate.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/theme-twilight.js?'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/theme-vibrant_ink.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/mode-javascript.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/mode-php.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/mode-xml.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/mode-html.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/ace/mode-css.js'></SCRIPT>
                
                <script>
                  var _CurrentApp = '". $this->getCurrentApp(false) ."';
                </script>
                ";
    	         
    return $htmlRet;  
  }

/*
@desc return custom folder tree
@return string html to be displayed
*/  
  protected function getMenu(){
  
    $htmlRet = "";
    
    //variable added to Javascript URLs in order to force reloading on the user's browser
    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    
    $wwwroot = $GLOBALS['egw']->link('/'.$this->getCurrentApp(false));
    
    $htmlRet = "
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/jquery-1.6.2.min.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/jquery-ui-1.8.4.custom.min.js'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/helpers.js?". $jsVersion ."'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/lib/jqueryFileTree/jqueryFileTree.js?". $jsVersion ."'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $wwwroot ."/js/editor.js?". $jsVersion ."'></SCRIPT>
                
                <link rel='stylesheet' type='text/css' href='". $wwwroot ."/lib/jqueryFileTree/jqueryFileTree.css?". $jsVersion ."' />
                
                <div id='editorMenu'>
                  <div id='tree'></div>
                </div>
                
                <script>
                  $(document).ready(function() {
                    editor_initializeTree('". $this->getCurrentApp(false) ."');
                  });
                </script>
                ";
    	         
    return $htmlRet; 
  }

/*
@desc return list of shortcuts for the editor
@return string html to be displayed
*/  
  protected function getShortcuts(){
    return "
      <div id='editor_shortcuts' class='editor_submenu'><div style='text-align: right;'><a href='javascript:editor_shortcuts()'>X</a></div><table>
      <tr><th>PC (Windows/Linux)	</th><th>Mac	</th><th>action</th></tr>
      <tr><td></td><td>Ctrl-L	</td><td>center selection</td></tr>
      <tr><td>Ctrl-Alt-Down	</td><td>Command-Option-Down	</td><td>copy lines down</td></tr>
      <tr><td>Ctrl-Alt-Up	</td><td>Command-Option-Up	</td><td>copy lines up</td></tr>
      <tr><td>Ctrl-F	</td><td>Command-F	</td><td>find</td></tr>
      <tr><td>Ctrl-K	</td><td>Command-G	</td><td>find next</td></tr>
      <tr><td>Ctrl-Shift-K	</td><td>Command-Shift-G	</td><td>find previous</td></tr>
      <tr><td>Down	</td><td>Down,Ctrl-N	</td><td>go line down</td></tr>
      <tr><td>Up	</td><td>Up,Ctrl-P	</td><td>go line up</td></tr>
      <tr><td>Ctrl-End,Ctrl-Down	</td><td>Command-End,Command-Down	</td><td>go to end</td></tr>
      <tr><td>Left	</td><td>Left,Ctrl-B	</td><td>go to left</td></tr>
      <tr><td>Ctrl-L	</td><td>Command-L	</td><td>go to line</td></tr>
      <tr><td>Alt-Right,End	</td><td>Command-Right,End,Ctrl-E	</td><td>go to line end</td></tr>
      <tr><td>Alt-Left,Home	</td><td>Command-Left,Home,Ctrl-A	</td><td>go to line start</td></tr>
      <tr><td>PageDown	</td><td>Option-PageDown,Ctrl-V	</td><td>go to page down</td></tr>
      <tr><td>PageUp	</td><td>Option-PageUp	</td><td>go to page up</td></tr>
      <tr><td>Right	</td><td>Right,Ctrl-F	</td><td>go to right</td></tr>
      <tr><td>Ctrl-Home,Ctrl-Up	</td><td>Command-Home,Command-Up	</td><td>go to start</td></tr>
      <tr><td>Ctrl-Left	</td><td>Option-Left	</td><td>go to word left</td></tr>
      <tr><td>Ctrl-Right	</td><td>Option-Right	</td><td>go to word right</td></tr>
      <tr><td>Tab	</td><td>Tab	</td><td>indent</td></tr>
      <tr><td>Alt-Down	</td><td>Option-Down	</td><td>move lines down</td></tr>
      <tr><td>Alt-Up	</td><td>Option-Up	</td><td>move lines up</td></tr>
      <tr><td>Shift-Tab	</td><td>Shift-Tab	</td><td>outdent</td></tr>
      <tr><td>Insert	</td><td>Insert	</td><td>overwrite</td></tr>
      <tr><td></td><td>PageDown	</td><td>pagedown</td></tr>
      <tr><td></td><td>PageUp	</td><td>pageup</td></tr>
      <tr><td>Ctrl-Shift-Z,Ctrl-Y	</td><td>Command-Shift-Z,Command-Y	</td><td>redo</td></tr>
      <tr><td>Ctrl-D	</td><td>Command-D	</td><td>remove line</td></tr>
      <tr><td></td><td>Ctrl-K	</td><td>remove to line end</td></tr>
      <tr><td></td><td>Option-Backspace	</td><td>remove to linestart</td></tr>
      <tr><td></td><td>Alt-Backspace,Ctrl-Alt-Backspace	</td><td>remove word left</td></tr>
      <tr><td></td><td>Alt-Delete	</td><td>remove word right</td></tr>
      <tr><td>Ctrl-R	</td><td>Command-Option-F	</td><td>replace</td></tr>
      <tr><td>Ctrl-Shift-R	</td><td>Command-Shift-Option-F	</td><td>replace all</td></tr>
      <tr><td>Ctrl-A	</td><td>Command-A	</td><td>select all</td></tr>
      <tr><td>Shift-Down	</td><td>Shift-Down	</td><td>select down</td></tr>
      <tr><td>Shift-Left	</td><td>Shift-Left	</td><td>select left</td></tr>
      <tr><td>Shift-End	</td><td>Shift-End	</td><td>select line end</td></tr>
      <tr><td>Shift-Home	</td><td>Shift-Home	</td><td>select line start</td></tr>
      <tr><td>Shift-PageDown	</td><td>Shift-PageDown	</td><td>select page down</td></tr>
      <tr><td>Shift-PageUp	</td><td>Shift-PageUp	</td><td>select page up</td></tr>
      <tr><td>Shift-Right	</td><td>Shift-Right	</td><td>select right</td></tr>
      <tr><td>Ctrl-Shift-End,Alt-Shift-Down	</td><td>Command-Shift-Down	</td><td>select to end</td></tr>
      <tr><td>Alt-Shift-Right	</td><td>Command-Shift-Right	</td><td>select to line end</td></tr>
      <tr><td>Alt-Shift-Left	</td><td>Command-Shift-Left	</td><td>select to line start</td></tr>
      <tr><td>Ctrl-Shift-Home,Alt-Shift-Up	</td><td>Command-Shift-Up	</td><td>select to start</td></tr>
      <tr><td>Shift-Up	</td><td>Shift-Up	</td><td>select up</td></tr>
      <tr><td>Ctrl-Shift-Left	</td><td>Option-Shift-Left	</td><td>select word left</td></tr>
      <tr><td><tr><td>Ctrl-Shift-Right	</td><td>Option-Shift-Right	</td><td>select word right</td></tr>
      <tr><td></td><td>Ctrl-O	</td><td>split line</td></tr>
      <tr><td>Ctrl-7	</td><td>Command-7	</td><td>toggle comment</td></tr>
      <tr><td>Ctrl-T	</td><td>Ctrl-T	</td><td>transpose letters</td></tr>
      <tr><td>Ctrl-Z	</td><td>Command-Z	</td><td>undo</td></tr>
      </table>
      </div>";
  }

/*
@desc return submenu to rename the selected file
@return string html to be displayed
*/  
  protected function getRename(){
    return "
      <div id='editor_rename' class='editor_submenu'>
        Enter new name : <input name='filename' type='text' value='' /> <input name='ok' type='button' value='Ok' /> <input name='cancel' type='button' value='Cancel' />
      </div>
    ";
  }

/*
@desc return submenu to create a new file
@return string html to be displayed
*/  
  protected function getNew(){
    return "
      <div id='editor_new' class='editor_submenu'>
        Select a folder : <div id='editor_new_selectFolder'></div>
        Enter new filename : <input name='filename' type='text' value='' /> <input name='ok' type='button' value='Ok' /> <input name='cancel' type='button' value='Cancel' />
      </div>
    ";
  }
  
}
