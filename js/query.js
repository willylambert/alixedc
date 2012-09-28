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
// Queries manuelles
function initQueries(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,ItemOID,IGOID,IGRK){
  ItemOIDdashed = ItemOID.replace(".","-");
  var elementId = "query_div_"+ItemOIDdashed+"_"+IGOID+"_"+IGRK;

  //Formulaire d'ajout d'une query
  $("#"+elementId).dialog({
    	autoOpen: false,
    	height: 350,
    	width: 480,
    	modal: false,
    	open: function() {
        if(!$(this).attr("title")) $(this).attr("title", $(this).find("input").val()); //sauvegarde du contenu de l'input dans un atrribut title (nom de l'item)
        $(this).find("input").val($(this).attr("title")); //restauration du nom de l'item dans l'input (2ème ajout de query de suite sur le même élément)
    	  //on efface le '.' que je mets par défaut pour la génération du textarea
    	  if($(this).find("textarea").val()=="."){
          $(this).find("textarea").val("");
    	  }
    	},
    	buttons: {
    		'Cancel': function() {
          $(this).dialog('close');
    		},
    		'Save': function() {
    		  if($(this).find("input[type='text']").val()!=""){
      		  saveQueryDialog(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,$(this));
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


function toggleQuery(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,ItemOID,IGOID,IGRK){
  ItemOIDdashed = ItemOID.replace(".","-");
  var elementId = "query_div_"+ItemOIDdashed+"_"+IGOID+"_"+IGRK;
  
  //dialog initialized ?
  if($("#"+elementId).attr('initialized')=='false'){
    //Initialisation of Manual Query Dialog
    if(typeof(initQueries)=="function"){
      initQueries(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,ItemOID,IGOID,IGRK);
    }
  }
  
  $("#"+elementId).dialog('open');
}


//Enregistrer une nouvelle query
function saveQueryDialog(CurrentApp,SiteId,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId,dialog){
  QUERYTYPE = dialog.children("select").first().children("option:selected").val();
  LABEL = dialog.children("input").first().val();
  ANSWER = dialog.children("textarea").first().val();
  ITEMTITLE = dialog.attr("itemtitle");
  
  ItemGroupOID = dialog.attr("itemgroupoid");
  ItemGroupRepeatKey = dialog.attr("itemgrouprepeatkey");
  ItemOID = dialog.attr("itemoid");
  
  dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey+"&ItemGroupOID="+ItemGroupOID+"&ItemGroupRepeatKey="+ItemGroupRepeatKey+"&ItemOID="+ItemOID+"&LABEL="+escape(LABEL)+"&ANSWER="+escape(ANSWER)+"&ITEMTITLE="+escape(ITEMTITLE)+"&QUERYTYPE="+QUERYTYPE;
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+CurrentApp+".ajax.addQuery",
    async:true,
    data: dataString,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while saving", data);
    },
    success: function(data) {
        if(data.QUERYID!=false){
          //L'identifiant de querie a été changé
          queriesList[data.QUERYID] = data; //mise en cache (cf queries.js)
          
          //on ajoute un bloc html à la liste des queries
          addQueryToFormQueries(CurrentApp,ProfileId,data.QUERYID);
          
          //on vide le texte saisi dans les champs texte
          dialog.find("input[type='text']").val("");
          dialog.find("textarea").val("");
      }
    }
  });
}
