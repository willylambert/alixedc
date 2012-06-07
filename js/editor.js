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
    

var _editor;
var _file = "";
var _editorTheme = '';
var _editorFontSize = '';
var _root = '../../../custom/';


$(document).ready( function(){
  editor_preferences(false);
  editor_initNew();
  editor_initRename();
  updateToolbar();
  
  //handle click on the toolbar commands
  $('#editorToolbar li.command').click( function(){
    eval('editor_'+ $(this).attr('name') +'()');
  });
});

function editor_initializeTree(CurrentApp){
  $('#tree').fileTree({
    root: _root,
    script: CurrentApp +'/lib/jqueryFileTree/connectors/jqueryFileTree.php',
    expandSpeed: 500,
    collapseSpeed: 500,
    multiFolder: false
    },
    function(file) {
      _file = file;
      editor_OpenFile(CurrentApp,file);
    });
}

function editor_OpenFile(CurrentApp,file){
  dataString = 'file='+ file;
  helper.setLoader('editorContainer', 'big', 'Loading...');
  $.ajax({
    type: 'POST',
    url: 'index.php?menuaction='+CurrentApp+'.ajax.getFileContent',
    async:true,
    data: dataString,
    dataType: 'json',
    error: function(data) {
      helper.displayError('An error occured while loading file', data);
    },
    success: function(data) {
      editor_createEditor(data.file.substring(16), data.content);
      var mode = '';
      extension = file.substring(file.lastIndexOf('.') + 1);
      switch(extension){
        case 'css':
          mode = 'css';
          break;
        case 'htm':
          mode = 'html';
          break;
        case 'html':
          mode = 'html';
          break;
        case 'js':
          mode = 'javascript';
          break;
        case 'php':
          mode = 'php';
          break;
        case 'xml':
          mode = 'xml';
          break;
        case 'xsl':
          mode = 'xml';
          break;
        default:
          break;
      }
      initEditor(mode);
      helper.unsetLoader('editorContainer', 'File loaded');
      updateToolbar();
    }
  });
}

function editor_reloadFolder(dirToReload){
  if(dirToReload.substr(dirToReload.lastIndexOf('/')-2,2)=='..'){ //base path ?
    editor_initializeTree(_CurrentApp);
  }else{
    //collapse
    $("#jqueryFileTree a[rel='"+ dirToReload+"/".replace(/(\.)/g,"\.") +"']").triggerHandler('click');
    //expand
    $("#jqueryFileTree a[rel='"+ dirToReload+"/".replace(/(\.)/g,"\.") +"']").triggerHandler('click');
  }
}

function editor_createEditor(title,content){
  if($('#editor').size()==0){
    $("#editorContainer").append("<div id='editor_title'></div><pre id='editor'></pre>");
  }
  $('#editor_title').text(title);
  if(typeof(content)!="undefined") $('#editor').text(content);
}
function editor_destroyEditor(){
  $("#editorContainer").html("");
}

function initEditor(mode){
  var editMode = 'javascript';
  if(typeof(mode)!='undefined' && mode!=''){
    editMode = mode;
  }
  
  _editor = ace.edit('editor');
  
  var Mode = require('ace/mode/'+ editMode).Mode;
  _editor.getSession().setMode(new Mode());
  _editor.getSession().setTabSize(2);
  _editor.setShowPrintMargin(false);
  
  editor_SetTheme();
  editor_SetFontSize();
}
function editor_SetTheme(theme,bSave){
  if(typeof(theme)!='undefined' && theme!=''){
    _editorTheme = theme;
  }
  
  if(typeof(_editor)!='undefined'){
    _editor.setTheme('ace/theme/'+ _editorTheme);
  }
  
  if(bSave) editor_preferences();
}
function editor_SetFontSize(fontSize,bSave){
  if(typeof(fontSize)!='undefined' && fontSize!=''){
    _editorFontSize = fontSize;
  }
  
  if(typeof(_editor)!='undefined'){
    document.getElementById('editor').style.fontSize = _editorFontSize +'px';
  }
  
  if(bSave) editor_preferences();
}

function editor_preferences(async){
  if(typeof(async)=='undefined'){
    async = true;
  }
  
  var dataString = 'theme='+ _editorTheme +'&fontsize='+ _editorFontSize;
  //helper.setLoader('editorContainer', 'big', 'Managing preferences...');
  $.ajax({
    type: 'POST',
    url: 'index.php?menuaction='+_CurrentApp+'.ajax.storeEditorPreferences',
    async: async,
    data: dataString,
    dataType: 'json',
    error: function(data) {
      helper.displayError('An error occured while saving preferences', data);
    },
    success: function(data) {
      if(data.theme!=''){
        _editorTheme = data.theme;
      }else{
        _editorTheme = 'textmate';
      }
      if(data.fontsize!=''){
        _editorFontSize = data.fontsize;
      }else{
        _editorFontSize = '12';
      }
      $('#editor_Theme').val(_editorTheme);
      $('#editor_FontSize').val(_editorFontSize);
      //helper.unsetLoader('editorContainer', 'Preferences managed');
    }
  });
}

//toolbar commands
function editor_shortcuts(){
  if($('#editor_shortcuts').css('display')=='none'){
    $('.editor_submenu').slideUp();
    $('#editor_shortcuts').slideDown();
  }else{
    $('#editor_shortcuts').slideUp();
  }
}

function editor_initNew(){
  $('#editor_new input[name=ok]').click(function(){
    if($("#editor_new_selectFolder input[name=editor_new_selectedFolder]:checked").size()==0){
      alert("Please select a folder.");
      return false;
    }else{
      folder = $("#editor_new_selectFolder input[name=editor_new_selectedFolder]:checked").val();
    }
    filename = $('#editor_new input[type=text]').val();
    if(filename==''){
      alert("Please enter a filename.");
      return false;
    }
    dataString = {root: _root, folder: folder, filename: filename};
    helper.setLoader('editorContainer', 'big', 'Creating file...');
    $.ajax({
      type: 'POST',
      url: 'index.php?menuaction='+_CurrentApp+'.ajax.createFile',
      async: true,
      data: dataString,
      dataType: 'json',
      error: function(data) {
        helper.displayError('An error occured while creating file', data);
      },
      success: function(data) {
        _file = data;
        editor_new();
        dirToReload = _file.substring(0,_file.lastIndexOf('/'));
        editor_reloadFolder(dirToReload);
        helper.unsetLoader('editorContainer', 'File created');
        editor_OpenFile(_CurrentApp,_file);
      }
    });
  });
  $('#editor_new input[name=cancel]').click( function(){
    editor_new();
  });
}
function editor_new(){
  if($('#editor_new').css('display')=='none'){
    $('.editor_submenu').slideUp();
    $('#editor_new input[type=text]').val("");
    
    dataString = {root: _root};
    helper.setLoader('editor_new', 'none', 'Retrieving list of folders...');
    $.ajax({
      type: 'POST',
      url: 'index.php?menuaction='+_CurrentApp+'.ajax.getSelectableFolderTree',
      async: true,
      data: dataString,
      dataType: 'json',
      error: function(data) {
        helper.displayError('An error occured while retrieving the list of folders', data);
      },
      success: function(data) {
        $("#editor_new_selectFolder").html(data);
        $("#editor_new_selectFolder input[name=editor_new_selectedFolder]").change( function(){
          $("#editor_new_selectFolder input[name=editor_new_selectedFolder][value!='"+ $(this).val().replace(/(\.)/g,'\\$1') +"']").attr('checked', false);
        });
        helper.unsetLoader('editor_new', 'Folders list retrieved');
      }
    });
    
    $('#editor_new').slideDown();
  }else{
    $('#editor_new').slideUp();
  }
}
function editor_save(){
  var content = _editor.getSession().getValue();
  //content = helper.addslashes(content);
  //content = '<?xml version="1.0" standalone="yes"?><ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ODMVersion="1.3" FileOID="0010001" FileType="Transactional" Description="" CreationDateTime="2008-02-01T18:31:00" Originator="Peter Holmes"><ClinicalData StudyOID="ESOGIA" MetaDataVersionOID="1.0.2">TEST</ClinicalData></ODM>';
  content = encodeURIComponent(content);
  //content = escape(content);
  //alert(content);
  //return false;
  //dataString =  'file='+ _file +'&content='+ content; //ok php => faire un urldecode + stripslashes //ko xml
  dataString = {file: _file, content: content}; //ok xml => faire un urldecode + str_replacede \\'  //ok php => faire un urldecode + str_replace de \\'
  //dataString =  'file='+ _file +'&content='+ content;
  //alert(decodeURIComponent(dataString.content));
  helper.setLoader('editorContainer', 'big', 'Saving...');
  $.ajax({
    type: 'POST',
    url: 'index.php?menuaction='+_CurrentApp+'.ajax.setFileContent',
    async: true,
    data: dataString,
    dataType: 'json',
    error: function(data) {
      helper.displayError('An error occured while saving file', data, true);
      helper.unsetLoader('editorContainer', 'File not saved');
    },
    success: function(data) {
      //
      if(data!=_file){
        _file = data;
        editor_reloadContextUI();
      }else{
        updateToolbar();
      }
      helper.unsetLoader('editorContainer', 'File saved');
    }
  });
}
function editor_initRename(){
  $('#editor_rename input[name=ok]').click(function(){
    dataString = {file: _file, newName: $('#editor_rename input[type=text]').val()};
    helper.setLoader('editorContainer', 'big', 'Renaming file...');
    $.ajax({
      type: 'POST',
      url: 'index.php?menuaction='+_CurrentApp+'.ajax.renameFile',
      async: true,
      data: dataString,
      dataType: 'json',
      error: function(data) {
        helper.displayError('An error occured while renaming file', data);
      },
      success: function(data) {
        editor_rename();
        _file = _file.substring(0,_file.lastIndexOf('/') + 1) + data;
        editor_reloadContextUI();
        helper.unsetLoader('editorContainer', 'File renamed');
      }
    });
  });
  $('#editor_rename input[name=cancel]').click( function(){
    editor_rename();
  });
}
function editor_rename(){
  if($('#editor_rename').css('display')=='none'){
    $('.editor_submenu').slideUp();
    $('#editor_rename input[type=text]').val(_file.substring(_file.lastIndexOf('/')+1));
    $('#editor_rename').slideDown();
  }else{
    $('#editor_rename').slideUp();
  }
}
function editor_delete(){
  helper.showPrompt("This action will definitely delete '"+ _file.substring(_file.lastIndexOf('/') + 1) +"'. Continue ?", "editor_deleteConfirm()");
}
function editor_deleteConfirm(){
  if($("#helper_prompt_result").val() == "ok"){
    dataString = {file: _file};
    helper.setLoader('editorContainer', 'big', 'Deleting file...');
    $.ajax({
      type: 'POST',
      url: 'index.php?menuaction='+_CurrentApp+'.ajax.deleteFile',
      async: true,
      data: dataString,
      dataType: 'json',
      error: function(data) {
        helper.displayError('An error occured while deleting file', data);
      },
      success: function(data) {
        if(_file==data){
          editor_destroyEditor();
          dirToReload = _file.substring(0,_file.lastIndexOf('/'));
          editor_reloadFolder(dirToReload);
          _file = "";
          helper.unsetLoader('editorContainer', 'File deleted');
          updateToolbar();
        }else{
          helper.displayError("The file couldn't be deleted", data);
        }
      }
    });
  }else{
    //
  }
}

function updateToolbar(){
  if(_file==''){
    $("#editorToolbar li[name=save]").fadeOut();
    $("#editorToolbar li[name=rename]").fadeOut();
    $("#editorToolbar li[name=delete]").fadeOut();
  }else{
    $("#editorToolbar li[name=save]").fadeIn();
    $("#editorToolbar li[name=rename]").fadeIn();
    $("#editorToolbar li[name=delete]").fadeIn();
  }
}

function editor_reloadContextUI(){
  dirToReload = _file.substring(0,_file.lastIndexOf('/'));
  $('#editor_title').text(_file.substring(16));
  editor_reloadFolder(dirToReload);
  editor_OpenFile(_CurrentApp,_file);
  updateToolbar();
}