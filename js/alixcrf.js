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
    
//User profile
var profileId = "";

//Current alix module - several alix modules can be used in the same egroupware instance
var currentApp = "";

//Set to true if a version of IE < 9 is detected
var isOldIE = false;

//By default save is only done on modified ItemGroupData. Sometime, we need to force this behavior.
var bForceSave = false;

/*
Entry point - used to initialize AJAX behavior
@author wlt
*/
function loadAlixCRFjs(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,FormStatus,bCheckFormData)
{ 
  //Utile (ex: postit.js)
  profileId = ProfileId;
  currentApp = CurrentApp;
  isOldIE = helper.isOldIE();
  
  //Bind des buttons
  $("#btnSave").click(function(){
    
    //Waiting dialog while saving
  	$("#dialog-modal-save").dialog("open");
    
    //When user click, change color to confirm the button click action
    $("#btnSave").animate({opacity: 0.25}, 200, function(){
      saveAllItemGroup(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,bCheckFormData);
    });
  });
  
  $("#btnCancel").click(function(){
    reloadLocation();
  });

  //Button to remove an ItemGroupData
  $("button[name='btnRemoveItemGroup']").each(
    function(intIndex){
      $(this).click(function(){

        if(confirm("Do you confirm deletion ?")){
          dataString = $(this).closest('form').serialize();
          removeItemGroup(CurrentApp,dataString);
        }
        return false; //do not submit form
      });    
    }
  );

  //Button to remove current FormData
  $("button[name='btnRemoveFormData']").each(
    function(intIndex){
      $(this).click(function(){

        if(confirm("Do you confirm deletion ?")){
          dataString = $(this).closest('form').serialize();
          removeFormData(CurrentApp,dataString);
        }
        return false; //do not submit form
      });    
    }
  );

  //Save button, hover effect
  $("button").hover(
  	function(){ 
  		$(this).addClass("ui-state-hover"); 
  	},
  	function(){ 
  		$(this).removeClass("ui-state-hover"); 
  	}
  );

  //Button to add ItemGroupData 
  $("#btnAddItemGroup").click(function(){
    ItemGroupOID = $(this).attr("itemgroupoid");
    newIGRK = 0;
    
    //Get the new ItemGroupRepeatKey
    newIGRK = $("form[name='"+ItemGroupOID+"']").first().find("input[name='NewItemGroupRepeatKey']").val();
    //fix a bug with clone with IE7
    if(isOldIE){
      newForm = helperIE.clone($("form[name='"+ItemGroupOID+"']").first());
    }else{
      newForm = $("form[name='"+ItemGroupOID+"']").first().clone();
    }
    sourceIGRK = newForm.find("input[name='ItemGroupRepeatKey']").val(); 
    newForm.find("input[name='ItemGroupRepeatKey']").val(newIGRK);
    newForm.attr('style','');
    newForm.find("table.ItemGroup").show();
    
    //Hide add button
    $(this).hide();

    //Get the new form out of the source table
    newForm.find("div.itemGroupHeadLine").detach();
    newForm.find("div.itemGroupLine").detach();

    //handle form inputs
    newForm.find(":input").each(function(index){
                                                  inputName = new String($(this).attr("name"));
                                                  $(this).attr("name",inputName.replace("_"+sourceIGRK,"_"+newIGRK));
                                                  
                                                  //IE7 and earlier do not allow users to dynamically set the name attribute of an input element at all.
                                                  if(isOldIE){
                                                    try{
                                                      var tagName = $(this).prop("tagName");
                                                      if(tagName=="INPUT" || tagName=="TEXTAREA" || tagName=="SELECT"){
                                                        if(tagName=="INPUT"){
                                                          //attribute "name" is also needed and is different than "Name" for IE.
                                                          var attrs = new Array("type", "value", "class", "id", "name", "maxLength", "size", "MaxAuditRecordID", "flagvalue", "oldvalue", "itemoid", "readonly");
                                                        }else if(tagName=="TEXTAREA"){
                                                          var attrs = new Array("class", "id", "name", "MaxAuditRecordID", "flagvalue", "oldvalue", "itemoid", "rows", "cols", "readonly");
                                                        }else if(tagName=="SELECT"){
                                                          var attrs = new Array("class", "id", "name", "MaxAuditRecordID", "flagvalue", "oldvalue", "itemoid", "readonly");
                                                        }
                                                        
                                                        newInput=document.createElement(tagName);
                                                        newInput.Name = $(this).attr("name"); //here is the trick ! (.Name)
                                                        newInput = $(newInput);
                                                        for(var i=0; i<attrs.length; i++){
                                                          if($(this).attr(attrs[i])!="undefined"){
                                                            newInput.attr(attrs[i], $(this).attr(attrs[i]));
                                                          }
                                                        }
                                                        
                                                        //for SELECT => copy OPTIONs
                                                        if(tagName=="SELECT"){
                                                          $(this).find("option").each( function(){
                                                            newInput.append($(this));
                                                          });
                                                        }
                                                        
                                                        $(this).replaceWith(newInput);
                                                      }
                                                    }catch(err){
                                                      helper.showPrompt(err.message);
                                                    }
                                                  }
                                                }); 
    
    newForm.find("td.ItemDataAnnot img").each(function(index){
                                                  inputId = new String($(this).attr("id"));
                                                  $(this).attr("id",inputId.replace("_"+sourceIGRK,"_"+newIGRK));
                                                  
                                                  onClickStr = new String($(this).attr("onClick"));
                                                  $(this).attr("onClick",onClickStr.replace(new RegExp("_"+sourceIGRK, "g" ),"_"+newIGRK));                                                  
                                                }); 
    
    newForm.find("td.ItemDataAnnot").each(function(index){
                                                  //Annotations dialogs are cloned too
                                                  tdName = new String($(this).attr("name"));
                                                  newAnnot = $("#annotation_div_"+tdName.replace(".","-")+"_"+sourceIGRK).clone();
                                                  
                                                  newAnnot.attr("id","annotation_div_"+tdName.replace(".","-")+"_"+newIGRK);
                                                  newAnnot.find(":input").each(function(index){
                                                    inputName = new String($(this).attr("name"));
                                                    $(this).attr("name",inputName.replace("_"+sourceIGRK,"_"+newIGRK));
                                                    
                                                    if(this.type=="hidden"){
                                                      this.value="";
                                                    }else{
                                                      if(this.type=="textarea"){
                                                        onChangeStr = new String($(this).attr("onChange"));
                                                        $(this).attr("onChange",onChangeStr.replace("_"+sourceIGRK,"_"+newIGRK));                 
                                                      }else{
                                                        onClickStr = new String($(this).attr("onClick"));
                                                        $(this).attr("onClick",onClickStr.replace(new RegExp("'"+sourceIGRK+"'", "g" ),"'"+newIGRK+"'"));  
                                                      }
                                                    }                                                    
                                                  });
                                                  
                                                  $(this).find("#annotation_div_"+tdName.replace(".","-")+"_"+sourceIGRK+"_flagvalue").text(" ");
                                                  $(this).find("#annotation_div_"+tdName.replace(".","-")+"_"+sourceIGRK+"_flagvalue").attr("id","annotation_div_"+tdName.replace(".","-")+"_"+newIGRK+"_flagvalue");
                                                  
                                                  $("body").append(newAnnot);                                                  
                                                }); 
      
    //Handle tr - one TR = one Item
    newForm.find("tr").each(function(index){
                              trId = new String($(this).attr("id"));
                              $(this).attr("id",trId.replace("_"+sourceIGRK,"_"+newIGRK));
                            });
                              
    //Reset styles
    newForm.find("td").removeClass("ui-state-error ui-priority-secondary");
    newForm.find("td").removeClass("ui-state-highlight ui-priority-secondary");
    newForm.find("table").removeClass("TransactionTypeRemove");
    
    //Keep only one add button
    newForm.find("button").detach();
      
    $("form[name='"+ItemGroupOID+"']").last().after("<form id='newForm' name="+ItemGroupOID+">"+newForm.html()+"</form>");

    newInsertedForm = $("#newForm");

    //Reset inputs
    clearForm($("#newForm"));
    
    //Handle form dynamism
    initDyn(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,newInsertedForm);
                   
    return false; //False prevent the form to be submitted, as we use ajax to submit form   
  });
  
  //Initialise Dialog boxes
  $("#dialog-modal-save").dialog({ height: 140, autoOpen: false, modal: false });
  
  $( "#dialog-modal-info" ).dialog({
      			modal: false,
      			autoOpen: false,
      			buttons: {
      				Ok: function() {
      					$( this ).dialog( "close" );
      				}
      			}
      		});
  
  //Animate menu - Study Workflow
  $("#subjectMenu h3").click(function() {
		$(this).next().toggle('slow');
		return false;
	}).next().hide();
	
	//Highligth current form
  $("#visit_"+StudyEventOID+"_"+StudyEventRepeatKey).addClass("ui-state-highlight").next().show();
  $("#visit_"+StudyEventOID+"_"+StudyEventRepeatKey+"_form_"+FormOID.replace(".","\\.")+"_"+FormRepeatKey).addClass("ui-state-highlight");

  //Display queries
  showQueries(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId);
  
  //Display deviations
  showDeviations(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId);
  
  //Post-it initialisation
  initPostIt(SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);
 
  //Handle form dynamism
  initDyn(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId);
  
  //Handle of "Quit without saving"
  if(ProfileId=='INV'){
    $("a").click(function(){
      if($(this).attr('href')!="javascript:void(0)" && $(this).attr('href')!="#")
      {
        //First, we look for modified data
        var dataModified = false;
        
        //Here we detect if data were modified
        $("form").each(
          function(index){
            dataString = $(this).serialize();
            if(dataString!=$(this).data('initials_values')){
              dataModified = true;
            }
          }
        );
        
        //if there is more than XX form, runs were not executed. We ask the user to run them now
        if($("div[class='pagination']").length>0 && dataModified==false && location.href.indexOf("donotcheck")==-1 && $(this).attr('href').indexOf("page")==-1 ){
          alert("Checks will be run now");
          checkFormData(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);
          reloadLocation("donotcheck");
          return false;
        }
             
        if(dataModified){
          if(confirm("Quit without saving ?")){
            return true;
          }else{
            return false;
          }
        }
      }  
    });
  }
  
  //Only investigators can update CRFs values
  if(ProfileId!='INV' || FormStatus=='FROZEN'){
    $("td.ItemDataInput input[type=text]").attr("readonly","readonly");
    $("td.ItemDataInput input[type=radio]").attr("disabled","disabled");
    $("td.ItemDataInput select").attr("disabled","disabled");
    $("td.ItemDataInput textarea").attr("readonly","readonly");
    
    $("div[id^=annotation] input[type=radio]").attr("disabled","disabled");
    $("div[id^=annotation] textarea").attr("readonly","readonly");
  }
  
  //CRA can modify post-it values
  if(ProfileId=='CRA'){
    $("div.PostIt textarea").removeAttr("readonly");
  } 

  //We store form data in a serialized manner to submit only modified forms by user
  $("form").each( 
    function(index){
      dataString = $(this).serialize();
      $(this).data('initials_values',dataString);
    }
   );
     
  if(typeof(afterInit)=="function"){
    afterInit();
  }
  
}

function initDyn(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,element){

  if(typeof(element)!="undefined"){
    tblElem = element.find(":input[type!='hidden']");
  }else{
    tblElem = $("div#Form").find(":input[type!='hidden']");
  }
  
  tblElem.each(function(index){
                  if($(this).attr('itemoid'))
                  {
                    $(this).change(function(){
                      IGRK = $(this).attr('name').split('_').pop();
                      IGOID = $(this).closest("form").attr('name'); 
                      if(typeof(IGOID)=="undefined"){
                        IGOID = $(this).closest("table").attr('name');  
                      }                                        
                      if(typeof(updateUI) == 'function') {
                        updateUI(this,false,IGOID,IGRK);
                      }
                    })
  
                    //updateUI is called for all input on initialisation
                    if($(this).attr('name')){
                      IGRK = $(this).attr('name').split('_').pop();
                      IGOID = $(this).closest("form").attr('name');
                      if(typeof(IGOID)=="undefined"){
                        IGOID = $(this).closest("table").attr('name');  
                      } 
                      ItemOID = $(this).closest("tr").attr('name');
                      if(typeof(ItemOID)=="undefined"){
                        ItemOID = $(this).closest("td").attr('name');
                      } 
                      if(typeof(updateUI) == 'function') {
                        updateUI(this,true,IGOID,IGRK);
                      }
                    }
                  }
                });  
}

/*
*@desc Ajax call to run consistency checks
*@author wlt
*/
function checkFormData(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey){
  //Waiting dialog
	$("#dialog-modal-save").dialog("open");
  
  dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey;
  
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+CurrentApp+".ajax.checkFormData",
    async:false,
    data: dataString,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while checking", data);
      },
    success: function(data) {
        $("#dialog-modal-save").dialog("close");
      }
    }); 	 
}

/*
@desc remove ItemGroupData
@author wlt
*/
function removeItemGroup(CurrentApp,dataString){
  //Waiting dialog while removing
	$("#dialog-modal-save").dialog("open");

  $.ajax({
    type: "POST",
    async: false,
    url: "index.php?menuaction="+CurrentApp+".ajax.removeItemGroupData",
    data: dataString,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while saving", data);
      },
    success: function(data){
      checkFormData(CurrentApp,data.SubjectKey,data.StudyEventOID,data.StudyEventRepeatKey,data.FormOID,data.FormRepeatKey);
      reloadLocation();
    }
   });
   
  //Return false to not submit form
  return false; 		
}

/*
@desc remove FormData
@author wlt
*/
function removeFormData(CurrentApp,dataString){
  //Popup waiting dialog
	$("#dialog-modal-save").dialog("open");

  $.ajax({
    type: "POST",
    async: false,
    url: "index.php?menuaction="+CurrentApp+".ajax.removeFormData",
    data: dataString,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while saving", data);
      },
    success: function(data){
      checkFormData(CurrentApp,data.SubjectKey,data.StudyEventOID,data.StudyEventRepeatKey,data.FormOID,data.FormRepeatKey);
      reloadLocation();
    }
   });
   
  //Return false to not submit form
  return false; 		
}


/*
*@desc Loop through ItemGroupData, and submit them one by one
*@author wlt, tpi
*/
function saveAllItemGroup(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,bCheckFormData){

  bSanityErrors = false;
  newSubjectKey = "";

  // 1 Form = 1 ItemGroupData
  $("form").each( 
    function(index){
      dataString = $(this).serialize();
      if(dataString!=$(this).data('initials_values') || bForceSave==true || index==$("form").length-1 )
      {
        //Form is modified - we submit it
        $.ajax({
          type: "POST",
          async: false,
          url: "index.php?menuaction="+CurrentApp+".ajax.saveItemGroupData",
          data: dataString,
          dataType: "json",
          error: function(data) {
              helper.displayError("An error occured while saving", data);
            },
          success: function(data){
              if(data.errors.length > 0){
                bSanityErrors = true;
                // If sanity errors occur, only these errors are displayed
                $("#formQueries").empty();
                for(i=0;i<data.errors.length;i++){
                  ItemOID = data.errors[i].ItemOID;
                  ItemGroupRepeatKey  = data.errors[i].ItemGroupRepeatKey;
                  Description = data.errors[i].desc;
                  $("#formQueries").append("<div id='query_"+ItemOID.replace(".","_")+"_"+ItemGroupRepeatKey+"' class='QueryType QueryTypeCM'>"+ Description +"</div>");
                }
              };   
              
              //Only run Edit Check if no sanity errors
              if(bSanityErrors==false){
                //Handle of new Subject - i.e. enrolment
                if(typeof(data.newSubjectId)!='undefined'){
                  newSubjectKey = data.newSubjectId[0];
                  SubjectKey = newSubjectKey;
                  //All forms must have the good SubjectKey value
                  $("form input[name='SubjectKey']").val(SubjectKey);
                }
              }
              
              if(index==$("form").length-1){
                if(bSanityErrors!=false){
                  regexp = new RegExp("(<b>)|(</b>)","gi");
                  Description = Description.replace(regexp,"");
                  alert(Description);        
                }
                $("#btnSave").animate({opacity: 1});
              }
            }
          });
        }
    }
  );
  
  //If while saving a new subject is created, page is reloaded
  if(typeof(newSubjectKey)!='undefined' && newSubjectKey!=""){
    SubjectKey = newSubjectKey;
    newUrl = "index.php?menuaction="+CurrentApp+".uietude.subjectInterface&action=view&SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey;
    $(location).attr('href',newUrl);
  }else{
    //Queries update
    if(bCheckFormData!==false && $("div[class='pagination']").length==0){
      //only if check on save is not disabled for this site
      checkFormData(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);
      reloadLocation("donotcheck");
    }else{
      reloadLocation();
    }
  }
 
  $("#dialog-modal-save").dialog("close");

}

/*
@author http://www.learningjquery.com/2007/08/clearing-form-data
*/
function clearForm(form) {
  // iterate over all of the inputs for the form
  // element that was passed in
  $(':input', form).each(function() {
 var type = this.type;
 var tag = this.tagName.toLowerCase(); // normalize case
 this.disabled=false;
 // it's ok to reset the value attr of text inputs,
 // password inputs, and textareas
 if (type == 'text' || type == 'password' || tag == 'textarea'){
   this.value = "";
  }
 // checkboxes and radios need to have their checked state cleared
 // but should *not* have their 'value' changed
 else if (type == 'checkbox' || type == 'radio')
   this.checked = false;
 // select elements need to have their 'selectedIndex' property set to -1
 // (this works for both single and multiple select elements)
 else if (tag == 'select')
   this.selectedIndex = -1;
  });
}

function showInfo(html,width,height){
  $("#dialog-modal-info").html(html);
  $("#dialog-modal-info").dialog( "option", "height", height );
  $("#dialog-modal-info").dialog( "option", "width", width );
  $("#dialog-modal-info").dialog("open");
  return false;
}

/**
 * @desc Reload the page with optional parameters, each parameter will be added as a get &parameter
 * @author tpi 
 */ 
function reloadLocation(){
  if(arguments.length>0){
    var newLocation = location.href;
    //adding optional parameters
    for (var i = 0; i < arguments.length; i++) {
      if(newLocation.indexOf(arguments[i])==-1){
        newLocation += "&" + arguments[i];
      }
    }
    location.replace(newLocation);
  }else{
    location.reload();
  }
}
