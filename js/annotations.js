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
// Annotations
function initAnnotation(CurrentApp, ItemOID, IGOID, IGRK, annotation_comment_name, annotation_picure_id){

  var elementId = "#annotation_div_"+ItemOID+"_"+IGOID+"_"+IGRK;
  var elementIdEsc = elementId.replace(".","-");

  $(elementIdEsc).dialog({
    	autoOpen: false,
    	height: 300,
    	width: 380,
    	modal: false,
    	buttons: {
    		'Close': function() {
          $(this).dialog('close');
          //update icon (with or without a pin)
          updateAnnotPict(CurrentApp, annotation_comment_name, annotation_picure_id);
    		}
    	},
    	close: function() {
    	}
    });
  
  $(elementIdEsc).attr('initialized','true');
}


function toggleAnnotation(CurrentApp, ItemOID, IGOID, IGRK, annotation_comment_name, annotation_picure_id){
  var elementId = "#annotation_div_"+ItemOID+"_"+IGOID+"_"+IGRK;
  var elementIdEsc = elementId.replace(".","-");
  
  //update icon
  updateAnnotPict(CurrentApp, annotation_comment_name, annotation_picure_id);
  
  //dialog initialized ?
  if($(elementIdEsc).attr('initialized')=='false'){
    //Initialisation of AuditTrail
    if(typeof(initAnnotation)=="function"){
      initAnnotation(CurrentApp, ItemOID, IGOID, IGRK, annotation_comment_name, annotation_picure_id);
    }
  }
  
  $(elementIdEsc).dialog('open');
}

function setState(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, FlagValue)
{
  //on coche le bouton radio correspondant au flag dans l'annotation à l'élément.
  var DivId = 'annotation_div_'+ItemOID+'_'+ItemGroupOID+'_'+CurrentItemGroupRepeatKey;
  $("input[name='annotation_flag_"+ItemOID+'_'+ItemGroupOID+'_'+CurrentItemGroupRepeatKey+"'][value='"+FlagValue+"']").attr('checked',true);
  
  //on affiche la valeur du flag à côté de l'image d'annotation, puis on disable les champs correspondants
  updateFlag(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, FlagValue);
}
function setStateEx(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, FlagValue, Loading)
{
  if(Loading)
  {
    //on active ou désactive les éléments selon la demande
    freezeFields(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, (FlagValue!='Ø'));
  }
  else
  {
    //on adapte l'état activé/désactivé des éléments et le Flag de leur annotation
    setState(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, FlagValue);
  }
}

function updateFlag(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, FlagValue, keepDisabled, freezeOnlyEmpty)
//keepDisabled est optionnel, il sert à ne pas altérer l'état d'un élément déjà disabled lors du chargement de la page
//freezeOnlyEmpty est optionnel, il sert à altérer l'état d'un élément uniquement quand sa valeur est vide => utile pour les partialDate
{
  //alert(updateFlag);
  var elementId = 'annotation_div_'+ItemOID+'_'+ItemGroupOID+'_'+CurrentItemGroupRepeatKey+'_flagvalue';
  
  //Recopie des valeurs dans les champs du formulaire
  $("*[name='"+ItemGroupOID+"'] :input[name='annotation_flag_"+ItemOID+"_"+ItemGroupOID+"_"+CurrentItemGroupRepeatKey+"']").val(FlagValue);
  
  updateElementContent(elementId,FlagValue+'&#160;'); //Affichage du libellé du flag saisi à gauche de l'icône d'annotation
  if(FlagValue=='' || FlagValue=='Ø')
  {
    bFreeze = false;
  }
  else                                              
  {
    bFreeze = true;
  }
  if(FlagValue=='ND' && freezeOnlyEmpty)
  {
    // on défreeze tous les champs avant d'altérer leur état. (partial date)
    freezeFields(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, false, false, false);
    //alert(freezeOnlyEmpty);
  }
  freezeFields(ItemOID,ItemGroupOID, CurrentItemGroupRepeatKey, bFreeze, keepDisabled, freezeOnlyEmpty); 
}

function updateElementContent(elementId,content){
  if(document.getElementById(elementId))
  {
    document.getElementById(elementId).innerHTML = content;
  }
}

function freezeFields(ItemOID, ItemGroupOID, CurrentItemGroupRepeatKey, bFreeze, keepDisabled, freezeOnlyEmpty)
{  
  filterEmpty="";
  if(freezeOnlyEmpty){ 
    filterEmpty = "[value='']";
  }

  flagValue = $("*[name='"+ItemGroupOID+"'] :input[name='annotation_flag_"+ItemOID.replace(".","-")+"_"+ItemGroupOID+"_"+CurrentItemGroupRepeatKey+"']").val();

  if(flagValue!="Ø" && flagValue!="" && typeof(flagValue)!="undefined"){
    keepDisabled = true;
  }
  
  ItemOID = ItemOID.replace(".","\\.");  
  ItemOID = ItemOID.replace("-","\\.");  
  $("#"+ItemOID+"_"+ItemGroupOID+"_"+CurrentItemGroupRepeatKey+" :input.inputItem"+ filterEmpty).attr('disabled',function () {
                                                                      if(!(keepDisabled * this.disabled)) return bFreeze;
                                                                    });
}

//Mise à jour de l'image de l'annotation
function updateAnnotPict(CurrentApp, annotation_comment_name, annotation_picure_id)
{
  var emptyPic = CurrentApp+'/templates/default/images/post_note_empty.gif';
  var annotPic = CurrentApp+'/templates/default/images/post_note.gif';
  
  element = document.getElementsByName(annotation_comment_name);
  if(element[0].value.length>1)
  {
    document.getElementById(annotation_picure_id).style.backgroundImage = "url('"+ annotPic +"')";
  }
  else
  {
    document.getElementById(annotation_picure_id).style.backgroundImage = "url('"+ emptyPic +"')";
  }
}
