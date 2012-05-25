    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2011 Business & Decision Life Sciences                         *
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
    

//Profil de l'utilisateur
profileId = "";

//Libellé de l'application egroupware
currentApp = "";

/*
@desc point d'entrée - appelée pour initialiser le comportement AJAX
@author wlt
*/
function loadAlixCRFjs(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,FormStatus,bCheckFormData)
{ 
  //Utile (ex: postit.js)
  profileId = ProfileId;
  currentApp = CurrentApp;
  
  //Bind des buttons
  $("#btnSave").click(function(){
    
    //Dialog d'attente durant l'enregistrement
  	$("#dialog-modal-save").dialog("open");
    
    //Code couleur sur le clique => confirmation rapide que le bouton a été cliqué (couleur doit ensuite passer au ver tà la fin de l'enregistrement)
    $("#btnSave").animate({opacity: 0.25}, 200, function(){
      saveAllItemGroup(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,bCheckFormData);
    });
  });
  
  $("#btnCancel").click(function(){
    location.reload(); 
  });

  //Bouton de suppression d'un ItemGroup
  $("button[name='btnRemoveItemGroup']").each(
    function(intIndex){
      $(this).click(function(){

        if(confirm("Do you confirm deletion ?")){
          dataString = $(this).closest('form').serialize();
          removeItemGroup(CurrentApp,dataString);
          return false;
        }
      });    
    }
  );

  //Bouton de suppression d'un FormData
  $("button[name='btnRemoveFormData']").each(
    function(intIndex){
      $(this).click(function(){

        if(confirm("Do you confirm deletion ?")){
          dataString = $(this).closest('form').serialize();
          removeFormData(CurrentApp,dataString);
          return false;
        }
      });    
    }
  );

  //Effet hover sur le bouton save
  $("button").hover(
  	function(){ 
  		$(this).addClass("ui-state-hover"); 
  	},
  	function(){ 
  		$(this).removeClass("ui-state-hover"); 
  	}
  );

  //Bouton d'ajout d'un ItemGroup
  $("#btnAddItemGroup").click(function(){
    ItemGroupOID = $(this).attr("itemgroupoid");
    newIGRK = 0;
    
    //Recherche du nouveau ItemGroupRepeatKey
    newIGRK = $("form[name='"+ItemGroupOID+"']").first().find("input[name='NewItemGroupRepeatKey']").val();   
    newForm = $("form[name='"+ItemGroupOID+"']").first().clone();
    sourceIGRK = newForm.find("input[name='ItemGroupRepeatKey']").val(); 
    newForm.find("input[name='ItemGroupRepeatKey']").val(newIGRK);
    newForm.attr('style','');
    newForm.find("table.ItemGroup").show();
    
    //On masque le bouton d'ajout
    $(this).hide();
    //on sort le formulaire du tableau
    newForm.find("div.itemGroupHeadLine").detach();
    newForm.find("div.itemGroupLine").detach();

    //Pour les input
    newForm.find(":input").each(function(index){
                                                  inputName = new String($(this).attr("name"));
                                                  $(this).attr("name",inputName.replace("_"+sourceIGRK,"_"+newIGRK));
                                                }); 
    
    newForm.find("td.ItemDataAnnot img").each(function(index){
                                                  inputId = new String($(this).attr("id"));
                                                  $(this).attr("id",inputId.replace("_"+sourceIGRK,"_"+newIGRK));
                                                  
                                                  onClickStr = new String($(this).attr("onClick"));
                                                  $(this).attr("onClick",onClickStr.replace(new RegExp("_"+sourceIGRK, "g" ),"_"+newIGRK));                                                  
                                                }); 
    
    newForm.find("td.ItemDataAnnot").each(function(index){
                                                  //On clone les boites annotations
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
                                                  
                                                  //alert("#annotation_div_"+tdName.replace(".","-")+"_"+sourceIGRK+"_flagvalue");
                                                  $(this).find("#annotation_div_"+tdName.replace(".","-")+"_"+sourceIGRK+"_flagvalue").text(" ");
                                                  $(this).find("#annotation_div_"+tdName.replace(".","-")+"_"+sourceIGRK+"_flagvalue").attr("id","annotation_div_"+tdName.replace(".","-")+"_"+newIGRK+"_flagvalue");
                                                  
                                                  $("body").append(newAnnot);                                                  
                                                }); 
          
    //Pour les TR
    newForm.find("tr").each(function(index){
                              trId = new String($(this).attr("id"));
                              $(this).attr("id",trId.replace("_"+sourceIGRK,"_"+newIGRK));
                            });
                              
    //Reset des styles
    newForm.find("td").removeClass("ui-state-error ui-priority-secondary");
    newForm.find("td").removeClass("ui-state-highlight ui-priority-secondary");
    newForm.find("table").removeClass("TransactionTypeRemove");
    
    //On ne garde qu'un seul bouton d'ajout
    newForm.find("button").detach();
        
    newInsertedForm = $("div#Form").append("<form id='newForm' name="+ItemGroupOID+">"+newForm.html()+"</form>");

    //Reset of inputs
    clearForm($("#newForm"));
    
    //Dynamism handle
    initDyn(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,newInsertedForm);
                   
    return false; //Le bouton est dans un Form - false pour ne pas le soumettre   
  });
  
  //Initialisation des boites de dialogue
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
  //Animation du menu
  $("#subjectMenu h3").click(function() {
		$(this).next().toggle('slow');
		return false;
	}).next().hide();
	
	//Highligth de la visite et du formulaire en cours
  $("#visit_"+StudyEventOID+"_"+StudyEventRepeatKey).addClass("ui-state-highlight").next().show();
	//note FormOID : Car nous avons un id avec "." - il faut le gérer avec un \\ pour jquery
  $("#visit_"+StudyEventOID+"_"+StudyEventRepeatKey+"_form_"+FormOID.replace(".","\\.")+"_"+FormRepeatKey).addClass("ui-state-highlight");

  //Affichage des queries
  showQueries(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId);
  
  //Affichage des deviations
  showDeviations(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId);
  
  //Initialisation des Post-it
  initPostIt(SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);
 
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
          checkFormData(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey); 
          location.replace(location.href + "&donotcheck");
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
  
  //Seuls les investigateurs peuvent modifier les valeurs du CRF
  if(ProfileId!='INV' || FormStatus=='FROZEN'){
    $("td.ItemDataInput input[type=text]").attr("readonly","readonly");
    $("td.ItemDataInput input[type=radio]").attr("disabled","disabled");
    $("td.ItemDataInput select").attr("disabled","disabled");
    $("td.ItemDataInput textarea").attr("readonly","readonly");
    
    $("div[id^=annotation] input[type=radio]").attr("disabled","disabled");
    $("div[id^=annotation] textarea").attr("readonly","readonly");
  }
  
  //les ARC peuvent modifier les valeurs des post-it
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
  
  //Gestion du dynamisme des écrans
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
*@desc Appel ajax pour faire tourner les controles de cohérences
*@author wlt
*/
function checkFormData(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey){
  //Dialog d'attente durant le check
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
@desc supprime l'ItemGroupData
@author wlt
*/
function removeItemGroup(CurrentApp,dataString){
  //Dialog d'attente durant la suppression
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
      location.reload();  
    }
   });
   
  //le bouton est dans un form - il ne faut pas le soumettre
  return false; 		
}

/*
@desc supprime un FormData
@author wlt
*/
function removeFormData(CurrentApp,dataString){
  //Dialog d'attente durant la suppression
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
      location.reload();  
    }
   });
   
  //le bouton est dans un form - il ne faut pas le soumettre
  return false; 		
}


/*
*@desc Loop through ItemGroupData, and submit them individually
*@author wlt, tpi
*/
function saveAllItemGroup(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,bCheckFormData){

  bSanityErrors = false;
  newSubjectKey = "";

  // 1 Form = 1 ItemGroupData
  $("form").each( 
    function(index){
      dataString = $(this).serialize();
      if(dataString!=$(this).data('initials_values'))
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
                  //alert("Data NOT SAVED - Please input sane data");
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
  
  //Si la sauvegarde a conduit à l'enregistrement d'un nouveau patient, on recharge la page
  if(typeof(newSubjectKey)!='undefined' && newSubjectKey!=""){
    SubjectKey = newSubjectKey;
    newUrl = "index.php?menuaction="+CurrentApp+".uietude.subjectInterface&action=view&SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey;
    $(location).attr('href',newUrl);
  }else{
    //Mise à jour des queries
    if(bCheckFormData!==false && $("div[class='pagination']").length==0){ //uniquement si le check à l'enregistrement n'est pas désactivé dans la configuration du centre
      checkFormData(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);                        
      location.replace(location.href + "&donotcheck");
    }else{
      location.reload();
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
