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
    
/**
* Entry point - called to initialize AJAX behavior for displaying subjects list
* @author tpi
**/
function loadAlixCRFSubjectsJS(CurrentApp, jsonConfig, bShowPDF)
{
  var config = eval('(' + jsonConfig + ')');
  
  loadSubjectsGrid(CurrentApp, config, bShowPDF);
}

function loadSubjectsGrid(CurrentApp, config, bShowPDF)
{
  $(document).ready(function(){
    var colNames = new Array();
    var colModel = new Array();
      
    //Direct access (<a> link)
    colNames.push(" ");
    colModel.push({
      name: "SUBJECTCRF",
      index: "SUBJECTCRF",
      width: 20,
      align:'center',
      search: false
    });
       
    //Columns defined in config.inc.php
    for(i in config)
    {
      if(config[i].Visible == true){
        if(config[i].Width != 0){
          width = config[i].Width;
        }else{
          width = 150;
        }
        
        if(config[i].Orientation== 'V'){
          formatter = "rotateText";
          if(!$.browser.msie){
            title = config[i].Title;
          }else{
            //No rotation under IE : we use instead a shortTitle
            formatter = "";
            title = config[i].ShortTitle;
          }
        }else{
          formatter = "";
          title = config[i].Title;
        }
        
        colNames.push(title);
        
        //Handle of magic column SITEID
        
        if(config[i].Key=="SITEID"){
          siteId = getSiteIdFromCookie();
          colModel.push({
            name: "col"+ config[i].Key,
            index: "col"+ config[i].Key,
            width: width,
            align:'center',
            formatter: formatter,
            search: true,
            searchoptions: { defaultValue:siteId }
          });
        }else{
          colModel.push({
            name: "col"+ config[i].Key,
            index: "col"+ config[i].Key,
            width: width,
            align:'center',
            formatter: formatter,
            search: false,
          });
        }
      }
    }
      
    colNames.push("Patient<br/>status");
    colModel.push({
      name: "SUBJECTSTATUS",
      index: "SUBJECTSTATUS",
      width: 120,
      align:'center',
      search: false
    });
    /*
    colNames.push("Post-it<br/>number");
    colModel.push({
      name: "POSTITNUM",
      index: "POSTITNUM",
      width: 65,
      align:'center',
      search: false
    });
    */
    colNames.push("Queries<br/>number");
    colModel.push({
      name: "QUERIESNUM",
      index: "QUERIESNUM",
      width: 65,
      align:'center',
      search: false
    });
/*
    colNames.push("CRF<br/>status");
    colModel.push({
      name: "CRFSTATUS",
      index: "CRFSTATUS",
      width: 50,
      align:'center',
      search: false
    });
*/    
    //PDF
    if(bShowPDF){
      colNames.push(" ");
      colModel.push({
        name: "PDF",
        index: "PDF",
        width: 35,
        align:'center',
        search: false
      });
    }
    /*
    //runConsistencyChecks
    if(bShowChecks){
      colNames.push("");
      colModel.push({
        name: "",
        index: "",
        width: 150,
        align:'center',
        search: false
      });
    }
    */
    //Chargement du tableau
    var mygrid = jQuery("#listSubjects").jqGrid({
      url: "index.php?menuaction="+CurrentApp+".ajax.getSubjectsDataList",
      mtype: "POST",
    	datatype: "local",
    	height: "auto",
     	colNames: colNames,
     	colModel: colModel,
     	rowNum: 20,
     	rowList: [10,20,50,100,200],
     	pager: '#pagerSubjects',
     	sortname: 'SubjectKey',
      viewrecords: true,
      gridview : true,
      sortorder: "asc",
      caption: "",
      loadComplete : function(){
        saveSiteIdToCookie();
      },
      onSelectRow: function(id){
        ids = id.split('_');
        subjkey = ids[1];
        goSubject(subjkey);
      },
      ajaxGridOptions: {
        dataFilter: function(data,dataType){    // preprocess the data
          if (data == "NOBLANK") {   // check for a warning of missing BLANK file
              alert("The subjects list could not be found. Have you imported the BLANK file and Metadata ?");
              window.location="index.php?menuaction="+CurrentApp+".uietude.dbadminInterface&action=importDocInterface";
              return false;
          }else{
            //is data evaluable as JSON ?
            try{
              var myTestObject = eval('(' + data + ')');
            }catch(e){
              alert(e+"\n"+data);
            }finally{
              return data;
            }
          }
        }
      }
    });
        
    //Rotate headline - from http://www.trirand.com/blog/?page_id=393/feature-request/headers-with-vertical-orientation/
    var trHead = $("thead:first tr");
    var cm = mygrid.getGridParam("colModel");
    $("thead:first tr th").height("120px");
    var headerHeight = $("thead:first tr th").height();

    for (var iCol = 0; iCol < cm.length; iCol++) {
      var cmi = cm[iCol];
      if (cmi.formatter === 'rotateText') {
          // we must set width of column header div BEFOR adding class "rotate" to
          // prevent text cutting based on the current column width
          var headDiv = $("th:eq(" + iCol + ") div", trHead);
          headDiv.width(headerHeight).addClass("rotate");
          if (!$.browser.msie) {
              if ($.browser.mozilla)
                  headDiv.css("left", (cmi.width - headerHeight) / 2 + 3).css("bottom", 7);
              else
                  headDiv.css("left", (cmi.width - headerHeight) / 2);
          }
          else {
              var ieVer = jQuery.browser.version.substr(0, 3);
              // Internet Explorer
              if (ieVer != "6.0" && ieVer != "7.0" && ieVer != "8.0") {
                  headDiv.css("left", cmi.width / 2 - 4).css("bottom", headerHeight / 2 - 3);
                  $("span", headDiv).css("left", 0);
              }
              else
                  headDiv.css("left", 3);
          }
      }
    }
    
    jQuery("#listSubjects").jqGrid('navGrid','#pagerSubjects',{edit:false,add:false,del:false,search:false,refresh:false});
    jQuery("#listSubjects").jqGrid('navButtonAdd',"#pagerSubjects",{caption:"Toggle",title:"Toggle Search Toolbar", buttonicon :'ui-icon-pin-s',
    	onClickButton:function(){
    		mygrid[0].toggleToolbar()
    	} 
    });
    jQuery("#listSubjects").jqGrid('navButtonAdd',"#pagerSubjects",{caption:"Clear",title:"Clear Search",buttonicon :'ui-icon-refresh',
    	onClickButton:function(){
    		mygrid[0].clearToolbar()
    	} 
    });
    
    //Here we add the Excel export button
    jQuery('#listSubjects').jqGrid('navButtonAdd','#pagerSubjects',{caption:'Export to Excel',title:'Export to Excel',
      onClickButton : function(e){
        window.location.target="_new";
        window.location.href=("index.php?menuaction="+CurrentApp+".ajax.getSubjectsDataList&excelExport=true");
      }
    });
    
    jQuery("#listSubjects").jqGrid('filterToolbar');
    
    $('#listSubjects').setGridParam({datatype: 'json'});
    $('#listSubjects')[0].triggerToolbar();
        
  });
}

/**
 * Save jqgrid parameters into a cookie - 
 * @see http://www.intothecloud.nl/index.php/2010/04/saving-jqgrid-parameters-in-cookie/ 
**/
function saveSiteIdToCookie() {
  if(typeof JSON!="undefined"){
    var gridInfo = new Object();
    name = 'listSubjects' + window.location.pathname;
    gridInfo.siteId = $("#gs_colSITEID").val(); 
    $.cookie(name, JSON.stringify(gridInfo));
  }
}
 
function getSiteIdFromCookie() {
  if(typeof JSON!="undefined"){
    var c = $.cookie('listSubjects' + window.location.pathname);
    if (c == null)
    return;
    var gridInfo = JSON.parse(c);
    return gridInfo.siteId; 
  }
}

/*
@desc liste des patients
@author tpi
*/
function initSubjectsList(){
  
  //Initialisation des boites de dialogue
  $("#dialog-modal-check").dialog({ height: 150, autoOpen: false, modal: false });
}

/*
@desc point d'entrée - accès CRF patient : comportement non Ajax
@author tpi
*/
function loadAlixCRFSubject(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey)
{
  newUrl = "index.php?menuaction="+CurrentApp+".uietude.subjectInterface&action=view&SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey;
  $(location).attr('href',newUrl);
}

/*
@desc mise en queue de fonctions, avec un temps d'attente entre l'éxécution de chaque fonction
@author tpi from http://debuggable.com/posts/run-intense-js-without-freezing-the-browser:480f4dd6-f864-4f72-ae16-41cccbdd56cb
*/
$.queue = {
    _timer: null,
    _queue: [],
    add: function(fn, context, time) {
        var setTimer = function(time) {
            $.queue._timer = setTimeout(function() {
                time = $.queue.add();
                if ($.queue._queue.length) {
                    setTimer(time);
                }
            }, time || 2);
        }

        if (fn) {
            $.queue._queue.push([fn, context, time]);
            if ($.queue._queue.length == 1) {
                setTimer(time);
            }
            return;
        }

        var next = $.queue._queue.shift();
        if (!next) {
            return 0;
        }
        next[0].call(next[1] || window);
        return next[2];
    },
    clear: function() {
        clearTimeout($.queue._timer);
        $.queue._queue = [];
    }
};

/*
@desc lance l'éxécution des contrôles de cohérence sur l'ensemble du CRF du patient. Allez-retours Ajax pour afficher la progression du contrôle
@author tpi
*/
var bCancelConsistencyChecks = false;
function runConsistencyChecks(CurrentApp,SiteId,SubjectKey,callback){
  bCancelConsistencyChecks = false;
  
  //Dialogue d'attente durant le check
	$("#dialog-modal-check").dialog("open");
	$("#dialog-modal-check-subject").html("Subject "+ SubjectKey);
	//Bouton d'annulation
	$("#dialog-modal-check-cancel").click( function(){
    bCancelConsistencyChecks=true;
  });
	
	//récupérer la liste des formulaire à checker (clés StudyEventOID, StudyEventRepeatKey, FormOID, FormRepeatKey
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+CurrentApp+".ajax.getFormDataList",
    async: false,
    data: "SubjectKey="+SubjectKey+"&SiteId="+SiteId,
    dataType: "json",
    error: function(data){
      helper.displayError("An error occured while checking", data);
    },
    success: function(data){
	    //pour chaque formulaire : effectuer le contrôle
	    var i = 0;
	    var j = -1;
	    var nbSuccess = 0;
	    var nbError = 0;
      var len = data.length;
      for(i=0; i<len; i++)
      {
        StudyEventOID = data[i].StudyEventOID;
        StudyEventRepeatKey = data[i].StudyEventRepeatKey;
        FormOID = data[i].FormOID;
        FormRepeatKey = data[i].FormRepeatKey;
        
        data[i].dataString = "SubjectKey="+SubjectKey+"&SiteId="+SiteId+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey;
        
        doCheck = function() {
          if(bCancelConsistencyChecks){
            setTimeout("$('#dialog-modal-check').dialog('close')","3000");
            return false;
          };
          j++;
          $.ajax({
            type: "POST",
            url: "index.php?menuaction="+CurrentApp+".ajax.checkFormData",
            async: false,
            data: data[j].dataString,
            dataType: "json",
            error: function(data){
              nbError++;
              helper.displayError("An error occured while checking", data);
            },
            success: function(data){
              nbSuccess++;
              $("#dialog-modal-check-progress").html(Math.round((100*nbSuccess)/len));
    
              if(nbSuccess+nbError>=len){
                if(typeof(callback)!="undefined"){
                  setTimeout(callback,100);
                }else{
                  setTimeout("$('#dialog-modal-check').dialog('close')",3000);
                }
              }
            }
          });
        };
        $.queue.add(doCheck, this, 100);
      }
    }
  });
}
