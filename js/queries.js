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
    
//cache de la liste de queries
var queriesList = [];

function getQuery(CurrentApp,QueryId){
  //on n'a pas forcément besoin d'une requête Ajax pour récupérer tous les éléments
  if(typeof(queriesList[QueryId]) == "undefined"){
    $.ajax({
      type: "POST",
      url: "index.php?menuaction="+CurrentApp+".ajax.getQuery",
      data: "QueryId="+ QueryId,
      async: false,
      dataType: "json",
      error: function(data) {
          helper.displayError("An error occured while retrieving query "+ QueryId, data);
        },
      success: function(data){
          if(data.QUERYID){ //no error ?
            queriesList[QueryId] = data; //mise en cache
          };
        }
    });
  }
  return queriesList[QueryId];
}

function showQueries(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId){
	
	//Les queries à afficher dépendent du profile
	queryStatus = "O";
	if(ProfileId=="INV"){
	  queryStatus = "O,P";
	}else{
	  queryStatus = "O,A,R,P";
	}
  
  dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey+"&queryStatus="+queryStatus+"&isLast=Y";

  $("#formQueries").empty();
  
  loadQueries(CurrentApp,dataString,"loadFormQueries('"+ CurrentApp +"','"+ ProfileId +"')",false);
}

//Fonction de chargement des données
function loadQueries(CurrentApp,DataStringKeys,onSuccess,async){
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+ CurrentApp +".ajax.getQueriesList",
    async: async,
    data: DataStringKeys,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while retrieving queries", data);
      },
    success: function(data){
        if(data.length>1000){
          alert("An error occured : too much queries ("+ data.length +")."+ sMsgError);
          return false;
        }
        for(i=0;i<data.length;i++){
          QueryId = data[i].QUERYID;
          queriesList[QueryId] = data[i]; //mise en cache
        };
        eval(onSuccess);
      }
    });
}

function loadFormQueries(CurrentApp,ProfileId){
  for(var QueryId in queriesList){
    if(queriesList[QueryId].QUERYID){ //no error ?
      addQueryToFormQueries(CurrentApp,ProfileId,QueryId)
    }
  }
  $("#dialog-modal-save").dialog("close");
}

//Création du formulaire d'édition d'une query
function addQueryToFormQueries(CurrentApp,ProfileId,QueryId){
  html = getQueryHTML(CurrentApp,ProfileId,QueryId);
  $("#formQueries").append(html);
          
  //On supprime la possibilité d'ajouter une querie sur l'élément correspondant (le bouton d'ajout placé par query.xsl)
  //$("#query_div_"+ queriesList[QueryId].ITEMOID.replace('.','-') +"_"+ queriesList[QueryId].IGRK +"_picture").remove();
}

//Obtention du code html d'une query
function getQueryHTML(CurrentApp,ProfileId,QueryId){
  //on n'a pas besoin d'une requête Ajax pour récupérer tous les éléments
  query = getQuery(CurrentApp,QueryId);
  QueryOrigin = query.QUERYORIGIN;
  QueryType = query.QUERYTYPE;
  QueryStatus = query.QUERYSTATUS;
  Description = query.LABEL;
  
  html = "";
  html += "<div id='query_"+QueryId+"' class='QueryType QueryType"+ QueryType +" QueryStatus"+ QueryStatus +" QueryOrigin"+ QueryOrigin +"'>";
    html += getQueryButtons(CurrentApp,ProfileId,QueryId);
    html += Description;
  html +="</div>";
  
  return html;
}

//Affiche les bouton Edit et Goto Item sur la barre de queries
function getQueryButtons(CurrentApp,ProfileId,QueryId){
  //return "<div class='QueryEditButtons'><button class='ui-state-default ui-corner-all' onClick=\"toggleQueryForm('"+CurrentApp+"','"+ProfileId+"',"+QueryId+")\">Edit</button><button class='ui-state-default ui-corner-all' onClick=\"gotoQueryItem("+ QueryId +")\">See item</div>";
  return "<div class='QueryEditButtons'><button class='ui-state-default ui-corner-all' onClick=\"toggleQueryForm('"+CurrentApp+"','"+ProfileId+"',"+QueryId+")\" altbox='Edit'><div class='imageEdit imageOnly image16 pointer'></div></button><button class='ui-state-default ui-corner-all' onClick=\"gotoQueryItem('"+ CurrentApp +"', '"+ QueryId +"')\" altbox='See item'><div class='imageFindIn imageOnly image16 pointer'></div></button></div>";
}

//Scroll à l'item concerné par la querie, item mis en évidence
function gotoQueryItem(CurrentApp,QueryId){
  query = getQuery(CurrentApp,QueryId);
  ItemOID = query.ITEMOID;
  ItemGroupRepeatKey = query.IGRK;
  //on change le style pour rendre l'élément visible
  $(".ItemIdentifier").removeClass("ItemIdentifier");
  $('tr[id$="_'+ItemGroupRepeatKey+'"]:has(*[itemoid="'+ ItemOID +'"])').addClass("ItemIdentifier");
  //on scroll
  pos = $(".ItemIdentifier").offset();
  $(document).scrollTop(pos.top);
}

//Identifiant du bloc d'édition d'un query
function getQueryFormId(QueryId){
  return "queryForm_"+QueryId;
}

//Affiche le formulaire d'édition d'une query
function toggleQueryForm(CurrentApp,ProfileId,QueryId,removeFromDOM){
  id = getQueryFormId(QueryId);
  //si l'élément n'existe pas déjà, on le créé et l'insère
  if(!document.getElementById(id)){
    html = getQueryFormHTML(CurrentApp,ProfileId,QueryId);
    $("#query_"+QueryId).after(html);
  }
  
  //le toggle
  $(jq(id)).slideToggle('500',function() {
              if (typeof(movePostItsTopPositionRelativeTo) == 'function'){
                movePostItsTopPositionRelativeTo(jq(id)); //appel d'une fonction présente dans postit.js
              }
              if(removeFromDOM){removeQueryForm(QueryId);} //suppression du bloc d'édition du DOM, si demandé
            });
  
}

//Obtention du code html du bloc d'édition d'une query
function getQueryFormHTML(CurrentApp,ProfileId,QueryId){
  id = getQueryFormId(QueryId);
  
  query = getQuery(CurrentApp,QueryId);
  QueryOrigin = query.QUERYORIGIN;
  QueryStatus = query.QUERYSTATUS;
  QueryType = query.QUERYTYPE;
  ItemTitle = query.ITEMTITLE;
  Description = query.LABEL;
  Answer = query.ANSWER;
  Decode = query.DECODE;
  
  if(Answer!="") Answer = "<textarea style='vertical-align:text-top;' rows='5' cols='70' readonly='readonly'>"+ Answer +"</textarea>";
  
  html = "";
  html += "<div id='"+ id +"' class='QueryForm'>";
    html += "<div class='QueryFormHeader'>"+ ItemTitle +"</div>";
    html += "<div class='QueryFormContent'>";
      html += "<div><b>Current status :</b> "+ getQueryStatusLabel(QueryStatus) +"</div>";
      html += "<div><b>Comment :</b> "+ Answer +"</div>";
      html += "<div><b>Description :</b> "+ Description +"</div>";
      html += "<div><b>Value :</b> "+ Decode +"</div>";
      //l'utilisateur peut-il changer le statut de la querie ?
      newStatuses = getNextStatuses(QueryStatus,QueryOrigin,ProfileId);
      if(newStatuses.length>0){
        html += "<div><b>New status :</b> "+ getQueryStatusSelector(QueryId,QueryStatus,QueryOrigin,ProfileId) +"</div>";
        html += "<div><b>Comment :</b> <textarea style='vertical-align:text-top;' id='queryStatusComment_"+ QueryId +"' rows='5' cols='70'></textarea></div>";
        html += "<div class='QueryFormButtons'><button class='ui-state-default ui-corner-all' onClick=\"hideQueryForm("+ QueryId +")\">Cancel</button><button id='saveQueryButton_"+ QueryId +"' class='ui-state-default ui-corner-all' onClick=\"saveQueryForm('"+CurrentApp+"','"+ProfileId+"',"+ QueryId +")\">Save</button></div>";
      }
    html += "</div>";
  html += "</div>";
  
  if($("table#listQueries").length>0){ //alors on est dans le module de gestion globale des queries (un tableau avec des tr et des td)
    //on va compter le nombre de td
    nbTd = $("table#listQueries tr:first-child td").length;
    html = "<tr><td colspan='"+ nbTd +"'>"+ html +"</td></tr>";
  }
  
  return html;
}

//Masquer l'édition d'une querie
function hideQueryForm(QueryId,removeFromDOM){
  toggleQueryForm('','',QueryId,removeFromDOM);
}

//Supprimer le bloc d'édition d'une querie du DOM
function removeQueryForm(QueryId){
  id = getQueryFormId(QueryId);
  $(jq(id)).remove();
}

//Enregistrer le formulaire d'édition d'une querie : nouveau statut et commentaire
function saveQueryForm(CurrentApp,ProfileId,QueryId){
  //Code couleur sur le clique => confirmation rapide que le bouton a été cliqué (couleur doit ensuite passer au vert à la fin de l'enregistrement)
  $("#saveQueryButton_"+ QueryId).css({color: '#ff0000'});
  $("#saveQueryButton_"+ QueryId).animate({opacity: 0.3});
  
  //newStatus = $("select#queryStatusSelector_"+ QueryId).val(); //ne marche pas sous Google Chrome
  newStatus = $("select#queryStatusSelector_"+ QueryId +" option:selected").val();
  newComment = $("#queryStatusComment_"+ QueryId).val();
  
  dataString = "QUERYID="+QueryId+"&QUERYSTATUS="+newStatus+"&ANSWER="+escape(newComment);
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+CurrentApp+".ajax.updateQuery",
    async:true,
    data: dataString,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while saving", data);
      },
    success: function(data) {
        //Code couleur sur le clique => confirmation rapide que les données ont été enregistrées/checkées
        $("#saveQueryButton_"+ QueryId).css({color: '#393939'});
        $("#saveQueryButton_"+ QueryId).animate({opacity: 1});
        
        //Le serveu peut demander le rechargement complet de la page
        if(data.ForceReload){
          location.reload();
        }else{        
          if(data.QUERYID!=false){
            //L'identifiant de query a été changé
            queriesList[data.QUERYID] = data; //mise en cache
            hideQueryForm(QueryId,true); //on cache puis supprime le bloc d'édition : il sera recréé automatiquement quand demandé
            
            if(ProfileId=="INV" && data.QUERYSTATUS=="A"//pour les investigateurs, on cache la querie CONFIRMED (A)
              || data.QUERYSTATUS=="C"//on cache la querie CLOSED (C)
              ){
              $("#query_"+QueryId).remove();
            }else{
              //on remplace l'ancien bloc html => mise à jour de la querie, notament de son identifiant
              if($("table#listQueries").length>0){ //alors on est dans le module de gestion globale des queries (un tableau avec des tr et des td)
                //après rechargement de la grille, la position de la ligne de laquery va être modifiée (relative à la règle de tri, par date, patient, etc, donc on montre la dispoarition en douceur
                $("#query_"+QueryId).fadeOut(1000,function(){
                  $('#listQueries').trigger("reloadGrid");
                });
              }else{
                html = getQueryHTML(CurrentApp,ProfileId,data.QUERYID);
                $("#query_"+QueryId).replaceWith(html);
              }
            }
          }else{
            hideQueryForm(QueryId,false);
          }
        }
      }
  }); 
}

//Retourne le code HTML d'un select pour choisir l'état d'une querie
function getQueryStatusSelector(QueryId,QueryStatus,QueryOrigin,ProfileId){
  newStatuses = getNextStatuses(QueryStatus,QueryOrigin,ProfileId);
  
  html = "";
  html += "<select id='queryStatusSelector_"+ QueryId +"'>";
  for(var i=0; i<newStatuses.length; i++){
    html += "<option value='"+ newStatuses[i] +"'>"+ getQueryStatusLabel(newStatuses[i]) +"</option>";
  }
  html += "</select>";
  return html;
}

//retourne la liste des changements de statut autorisés
function getNextStatuses(QueryStatus,QueryOrigin,ProfileId){
  newStatuses = [];
  //les statuts accessibles dépendent des droits de l'utilisateur, du statut actuel de la query, et de comment la query a été ouverte (manuellement par un ARC ou automatiquement par le CRF)
  //O : OPEN, query ouverte (manuellement par un ARC, ou automatiquement par le CRF)
  //R : RESOLVED, valeur modifiée par l'investigateur (non source d'incohérence)
  //A : CONFIRMED, valeur confirmée par l'investigateur
  //P : RESOLUTION PROPOSED, proposition de correction par l'ARC (sur queries ouvertes automatiquement par le CRF)
  //C : CLOSED, query fermée
  switch(QueryStatus){
    case 'O':
        switch(ProfileId){
          case 'INV':
            newStatuses = ['A'];
            break;
          case 'CRA':
            if(QueryOrigin=="A"){
              newStatuses = ['P'];
            }else{
              newStatuses = ['C'];
            }
            break;
        }
      break;
    case 'A':
        switch(ProfileId){
          case 'INV':
            newStatuses = [];
            break;
          case 'CRA':
            newStatuses = ['O','C'];
            break;
        }
      break;
    case 'P':
        switch(ProfileId){
          case 'INV':
            newStatuses = ['A'];
            break;
          case 'CRA':
            newStatuses = ['P'];
            break;
        }
      break;
    case 'R':
        switch(ProfileId){
          case 'INV':
            newStatuses = [];
            break;
          case 'CRA':
            newStatuses = ['O','C'];
            break;
        }
      break;
    case 'C':
        switch(ProfileId){
          case 'INV':
            newStatuses = [];
            break;
          case 'CRA':
            newStatuses = [];
            break;
        }
      break;
  }
  return newStatuses;
}

//retourne le libellé du status d'une query
function getQueryStatusLabel(QueryStatus){
  switch(QueryStatus){
    case 'O':
      return "OPEN";
      break;
    case 'A':
      return "CONFIRMED";
      break;
    case 'P':
      return "RESOLUTION PROPOSED";
      break;
    case 'R':
      return "RESOLVED";
      break;
    case 'C':
      return "CLOSED";
      break;
  }
}

//retourne le libellé du type d'une query
function getQueryTypeLabel(QueryType){
  switch(QueryType){
    case 'CM':
      return "Missing or badformat";
      break;
    case 'HC':
      return "Inconsistency";
      break;
    case 'SC':
      return "Information";
      break;
  }
}
