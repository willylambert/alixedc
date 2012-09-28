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
    
//////////////////////////////////////////////////////////////
// Deviations manuelles
function initDeviations(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,ItemOID,IGRK){
  ItemOIDdashed = ItemOID.replace(".","-");
  var elementId = "deviation_div_"+ItemOIDdashed+"_"+IGRK;

  //Formulaire d'ajout d'une deviation
  $("#"+elementId).dialog({
    	autoOpen: false,
    	height: 350,
    	width: 480,
    	modal: false,
    	open: function() {
    	  if($(this).find("textarea").val()=="."){
          $(this).find("textarea").val("");
    	  }
    	},
    	buttons: {
    		'Cancel': function() {
          $(this).dialog('close');
    		},
    		'Save': function() {
    		  if($(this).find("textarea").val()!=""){
      		  saveDeviationDialog(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,$(this));
            $(this).dialog('close');
          }else{
            alert("Description is mandatory.");
          }
    		}
    	},
    	close: function() {
    	}
  });
  
  $("#"+elementId).attr('initialized','true');
}


function toggleDeviation(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,ItemOID,IGRK){
  ItemOIDdashed = ItemOID.replace(".","-");
  var elementId = "deviation_div_"+ItemOIDdashed+"_"+IGRK;
  
  //dialog initialized ?
  if($("#"+elementId).attr('initialized')=='false'){
    //Initialisation of Manual Deviation Dialog
    if(typeof(initDeviations)=="function"){
      initDeviations(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,ItemOID,IGRK);
    }
  }
  
  $("#"+elementId).dialog('open');
}


//Enregistrer une nouvelle deviation
function saveDeviationDialog(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,dialog){
  DESCRIPTION = dialog.children("textarea").first().val();
  ITEMTITLE = dialog.attr("itemtitle");
  
  ItemGroupOID = dialog.attr("itemgroupoid");
  ItemGroupRepeatKey = dialog.attr("itemgrouprepeatkey");
  ItemOID = dialog.attr("itemoid");
  
  dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey+"&ItemGroupOID="+ItemGroupOID+"&ItemGroupRepeatKey="+ItemGroupRepeatKey+"&ItemOID="+ItemOID+"&DESCRIPTION="+escape(DESCRIPTION)+"&ITEMTITLE="+escape(ITEMTITLE);
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+CurrentApp+".ajax.addDeviation",
    async:true,
    data: dataString,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while saving", data);
    },
    success: function(data) {
        if(data.DEVIATIONID!=false){
          //L'identifiant de deviation a été changé
          deviationsList[data.DEVIATIONID] = data; //mise en cache (cf deviations.js)
          
          //on ajoute un bloc html à la liste des deviations
          addDeviationToFormDeviations(CurrentApp,ProfileId,data.DEVIATIONID);
          
          //on vide le texte saisi dans les champs texte
          dialog.find("textarea").val("");
      }
    }
  });
}
