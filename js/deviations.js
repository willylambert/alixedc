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
    
//cache de la liste de deviations
var deviationsList = [];

function getDeviation(CurrentApp,DeviationId){
  //on n'a pas forcément besoin d'une requête Ajax pour récupérer tous les éléments
  if(typeof(deviationsList[DeviationId]) == "undefined"){
    $.ajax({
      type: "POST",
      url: "index.php?menuaction="+CurrentApp+".ajax.getDeviation",
      data: "DeviationId="+ DeviationId,
      async: false,
      dataType: "json",
      error: function(data) {
          helper.displayError("An error occured while retrieving deviation "+ DeviationId, data);
        },
      success: function(data){
          if(data.DEVIATIONID){ //no error ?
            deviationsList[DeviationId] = data; //mise en cache
          };
        }
    });
  }
  return deviationsList[DeviationId];
}

function showDeviations(CurrentApp,SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey,ProfileId){ 
	deviationStatus = "O,U";
	
  dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey+"&status="+deviationStatus+"&isLast=Y";

  $("#formDeviations").empty();
  
  loadDeviations(CurrentApp,dataString,"loadFormDeviations('"+ CurrentApp +"','"+ ProfileId +"')",false);

  //on affiche la présence de deviations pour chaque formulaire dans le menu des visites
  $.ajax({
    type: "POST",
    async: true, 
    url: "index.php?menuaction="+CurrentApp+".ajax.getDeviationsFormList",
    data: dataString,
    dataType: "json",
    error: function(data) {
      //
    },
    success: function(data) {
      jSubjectMenu = $("#subjectMenu div.FormTitle");
      for(i=0;i<data.length;i++){
        jSubjectMenu.filter("[studyeventoid='"+ data[i].SEOID +"'][studyeventrepeatkey='"+ data[i].SERK +"'][formoid='"+ data[i].FRMOID +"'][formrepeatkey='"+ data[i].FRMRK +"']").find("span.FormDeviation").addClass("FormDeviationPresent");
      }
    }
  });
}

//Fonction de chargement des données
function loadDeviations(CurrentApp,DataStringKeys,onSuccess,async){
  $.ajax({
    type: "POST",
    url: "index.php?menuaction="+CurrentApp+".ajax.getDeviationsList",
    data: DataStringKeys,
    async: true,
    dataType: "json",
    error: function(data) {
        helper.displayError("An error occured while retrieving deviations", data);
      },
    success: function(data){
        for(i=0;i<data.length;i++){
          DeviationId = data[i].DEVIATIONID;
          deviationsList[DeviationId] = data[i]; //mise en cache
        };
        eval(onSuccess);
      }
    });
}

function loadFormDeviations(CurrentApp,ProfileId){
  for(var DeviationId in deviationsList){
    if(deviationsList[DeviationId].DEVIATIONID){ //no error ?
      addDeviationToFormDeviations(CurrentApp,ProfileId,DeviationId)
    }
  }
  $("#dialog-modal-save").dialog("close");
}

//Création du formulaire d'édition d'une déviation
function addDeviationToFormDeviations(CurrentApp,ProfileId,DeviationId){
  html = getDeviationHTML(CurrentApp,ProfileId,DeviationId);
  $("#formDeviations").append(html);
          
  //On supprime la possibilité d'ajouter une deviation sur l'élément correspondant (le bouton d'ajout placé par deviation.xsl)
  //$("#deviation_div_"+ deviationsList[DeviationId].ITEMOID.replace('.','-') +"_"+ deviationsList[DeviationId].IGRK +"_picture").remove();
}

//Obtention du code html d'une deviation
function getDeviationHTML(CurrentApp,ProfileId,DeviationId){
  //on n'a pas besoin d'une requête Ajax pour récupérer tous les éléments
  Description = "<b>"+ deviationsList[DeviationId].ITEMTITLE +"</b> : "+ deviationsList[DeviationId].DESCRIPTION;
  
  html = "";
  html += "<div id='deviation_"+DeviationId+"' class='Deviation'>";
    html += getDeviationButtons(CurrentApp,ProfileId,DeviationId);
    html += Description;
  html +="</div>";
  
  return html;
}

//Affiche les bouton Edit et Goto Item sur la barre de deviations
function getDeviationButtons(CurrentApp,ProfileId,DeviationId){
  return "<div class='DeviationEditButtons'><button class='ui-state-default ui-corner-all' onClick=\"toggleDeviationForm('"+CurrentApp+"','"+ProfileId+"',"+DeviationId+")\" altbox='Edit'><div class='imageEdit imageOnly image16 pointer'></div></button><button class='ui-state-default ui-corner-all' onClick=\"gotoDeviationItem("+ DeviationId +")\" altbox='See item'><div class='imageFindIn imageOnly image16 pointer'></div></button></div>";
}

//Scroll à l'item concerné par la deviation, item mis en évidence
function gotoDeviationItem(DeviationId){
  ItemOID = deviationsList[DeviationId].ITEMOID;
  ItemGroupRepeatKey = deviationsList[DeviationId].IGRK;
  //on change le style pour rendre l'élément visible
  $(".ItemIdentifier").removeClass("ItemIdentifier");
  $('tr[id$="_'+ItemGroupRepeatKey+'"]:has(*[itemoid="'+ ItemOID +'"])').addClass("ItemIdentifier");
  //on scroll
  pos = $(".ItemIdentifier").offset();
  $(document).scrollTop(pos.top);
}

//Identifiant du bloc d'édition d'un deviation
function getDeviationFormId(DeviationId){
  return "deviationForm_"+DeviationId;
}

//Affiche le formulaire d'édition d'une deviation
function toggleDeviationForm(CurrentApp,ProfileId,DeviationId,removeFromDOM){
  id = getDeviationFormId(DeviationId);
  //si l'élément n'existe pas déjà, on le créé et l'insère
  if(!document.getElementById(id)){
    html = getDeviationFormHTML(CurrentApp,ProfileId,DeviationId);
    $("#deviation_"+DeviationId).after(html);
  }
  //le toggle
  $(jq(id)).slideToggle('500',function() {
              if (typeof(setAllPostItsPotision) == 'function'){
                setAllPostItsPotision(); //update post-it positions
              }
              if(removeFromDOM){removeDeviationForm(DeviationId);} //suppression du bloc d'édition du DOM, si demandé
            });
  
}

//Obtention du code html du bloc d'édition d'une deviation
function getDeviationFormHTML(CurrentApp,ProfileId,DeviationId){
  id = getDeviationFormId(DeviationId);
  
  deviation = getDeviation(CurrentApp,DeviationId);
  ItemTitle = deviation.ITEMTITLE;
  Description = deviation.DESCRIPTION;
  Status = deviation.STATUS;
  
  html = "";
  html += "<div id='"+ id +"' class='DeviationForm'>";
    html += "<div class='DeviationFormHeader'>"+ ItemTitle +"</div>";
    html += "<div class='DeviationFormContent'>";
    html += "<div><b>Description :</b> <textarea style='vertical-align:text-top;' id='deviationDescription_"+ DeviationId +"' rows='10' cols='70'>"+ Description +"</textarea></div>";
    if(ProfileId=="INV" && Status!="C"){
      html += "<div class='DeviationFormButtons'><button class='ui-state-default ui-corner-all' onClick=\"hideDeviationForm("+ DeviationId +")\">Cancel</button><button id='deleteDeviationButton_"+ DeviationId +"' class='ui-state-default ui-corner-all' onClick=\"saveDeviationForm('"+CurrentApp+"','"+ProfileId+"',"+ DeviationId +",'C')\">Delete</button><button id='saveDeviationButton_"+ DeviationId +"' class='ui-state-default ui-corner-all' onClick=\"saveDeviationForm('"+CurrentApp+"','"+ProfileId+"',"+ DeviationId +",'U')\">Save</button></div>";
    }
    html += "</div>";
  html += "</div>";
  
  if($("table#listDeviations").length>0){ //alors on est dans le module de gestion globale des deviations (un tableau avec des tr et des td)
    //on va compter le nombre de td
    nbTd = $("table#listDeviations tr:first-child td").length;
    html = "<tr><td colspan='"+ nbTd +"'>"+ html +"</td></tr>";
  }
  
  return html;
}

//Masquer l'édition d'une deviation
function hideDeviationForm(DeviationId,removeFromDOM){
  toggleDeviationForm('','',DeviationId,removeFromDOM);
}

//Supprimer le bloc d'édition d'une querie du DOM
function removeDeviationForm(DeviationId){
  id = getDeviationFormId(DeviationId);
  $(jq(id)).remove();
}

//Enregistrer le formulaire d'édition d'une deviation : nouveau commentaire
function saveDeviationForm(CurrentApp,ProfileId,DeviationId,deviationStatus){
  bSave = true;
  if(deviationStatus=='U'){
    buttonId = "#saveDeviationButton_"+ DeviationId;
  }else{ // deviationStatus=='C'
    buttonId = "#deleteDeviationButton_"+ DeviationId;
    if(!confirm("Delete this deviation ?")){
      bSave = false;
    }
  }
  if(bSave){
    //Code couleur sur le clique => confirmation rapide que le bouton a été cliqué (couleur doit ensuite passer au vert à la fin de l'enregistrement)
    $(buttonId).css({color: '#ff0000'});
    $(buttonId).animate({opacity: 0.3});
    
    newDescription = $("#deviationDescription_"+ DeviationId).val();
    
    dataString = "DEVIATIONID="+DeviationId+"&DESCRIPTION="+escape(newDescription)+"&STATUS="+deviationStatus;
    $.ajax({
      type: "POST",
      url: "index.php?menuaction="+CurrentApp+".ajax.updateDeviation",
      async:true,
      data: dataString,
      dataType: "json",
      error: function(data) {
          helper.displayError("An error occured while saving", data);
        },
      success: function(data) {
          //Code couleur sur le clique => confirmation rapide que les données ont été enregistrées/checkées
          $(buttonId).css({color: '#393939'});
          $(buttonId).animate({opacity: 1});
          
          if(data.DEVIATIONID!=false){
            //L'identifiant de querie a été changé
            deviationsList[data.DEVIATIONID] = data; //mise en cache
            hideDeviationForm(DeviationId,true); //on cache puis supprime le bloc d'édition : il sera recréé automatiquement quand demandé
            
            if(data.STATUS=="C"//on cache la querie CLOSED (C)
              ){
              $("#deviation_"+DeviationId).remove();
            }else{
              //on remplace l'ancien bloc html => mise à jour de la deviation, notament de son identifiant
              html = getDeviationHTML(CurrentApp,ProfileId,data.DEVIATIONID);
              $("#deviation_"+DeviationId).replaceWith(html);
            }
            
          }else{
            hideQueryForm(DeviationId,false);
          }
        }
    });
  }
}

//retourne le libellé du status d'une deviation
function getDeviationStatusLabel(Status){
  switch(Status){
    case 'O':
      return "ADDED";
      break;
    case 'U':
      return "UPDATED";
      break;
    case 'C':
      return "DELETED";
      break;
  }
}
