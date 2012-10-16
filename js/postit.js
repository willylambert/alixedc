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
    
var defaultPostItText = "Drag and drop me...";

function getDropZoneSelector(ItemOID,ItemGroupRepeatKey,ItemGroupOID){
  id = ItemOID +"_"+ ItemGroupOID +"_"+ ItemGroupRepeatKey;
  if($("table[name='"+ItemGroupOID+"'][inline='yes']").length==1){//inline itemgroups (alixlib.xsl)
    return "td"+ jq(id);
  }else{ //normal itemgroups
    return jq(id) +" td.ItemDataInput";
  }
}

//Initialisation des post-it, affichage des post-it existant en base
function initPostIt(SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey){
  
  //on affiche la présence de post-its pour chaque formulaire dans le menu des visites
  dataString = "SubjectKey="+SubjectKey;
  $.ajax({
    type: "POST",
    async: false,
    url: "index.php?menuaction="+currentApp+".ajax.getPostItFormList",
    data: dataString,
    dataType: "json",
    error: function(data) {
      //
    },
    success: function(data) {
      jSubjectMenu = $("#subjectMenu div.FormTitle");
      for(i=0;i<data.length;i++){
        jSubjectMenu.filter("[studyeventoid='"+ data[i].SEOID +"'][studyeventrepeatkey='"+ data[i].SERK +"'][formoid='"+ data[i].FRMOID +"'][formrepeatkey='"+ data[i].FRMRK +"']").find("span.FormPostIt").addClass("FormPostItPresent");
      }
    }
  });
  
  
  //on affiche les post-its de ce formulaire
  dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey;
  $.ajax({
    type: "POST",
    async: false,
    url: "index.php?menuaction="+currentApp+".ajax.getPostItList",
    data: dataString,
    dataType: "json",
    error: function(data) {
      //
    },
    success: function(data) {
      for(i=0;i<data.length;i++){
        txt = data[i].TXT;
        ItemGroupOID = data[i].IGOID;
        ItemGroupRepeatKey = data[i].IGRK;
        ItemOID = data[i].ITEMOID;
        postItId = "PostIt_"+ SubjectKey +"_"+ StudyEventOID +"_"+ StudyEventRepeatKey +"_"+ FormOID +"_"+ FormRepeatKey +"_"+ ItemGroupOID +"_"+ ItemGroupRepeatKey +"_"+ ItemOID;
        html = getPostItHTML(postItId, txt);
        //on positionne sur le bon td
        dropzone = getDropZoneSelector(ItemOID,ItemGroupRepeatKey,ItemGroupOID);
        if(!(jQuery.browser.msie && jQuery.browser.version<9)){ //test à mettre à jour si IE9 se révèle être toujours aussi pourri qu'IE8
          $(dropzone).append(html);
        }else{
          $("body").append(html);
        }
        setPostItPosition(postItId);
        $(jq(postItId)).draggable({ revert: "invalid" });
        $(dropzone).addClass("DroppedZone");
      }
      //Les ARC peuvent déplacer ces post-it
      if(profileId=='CRA'){
        initDroppables();
      }
      //On s'assure que le post-it clické est visible par dessus les autres
      ensurePostItVisibility();
      //On s'assure que le post-it puisse être redimensionné
      ensurePostIsResizable();
    }
  });
}

//set the position for a post-it
function setPostItPosition(postItId){
  var keys = postItId.split(new RegExp("_"));
  if(keys.length<9) return false; //may be a new and still unidentified post-it (not dropped)
  ItemGroupOID = keys[6];
  ItemGroupRepeatKey = keys[7];
  ItemOID = keys[8];
  dropzone = getDropZoneSelector(ItemOID,ItemGroupRepeatKey,ItemGroupOID);
  var jDropZone = $(dropzone);
  pos = jDropZone.offset();
  pos.left = pos.left + jDropZone.width();
  pos.top = pos.top;
  var jPostIt = $(jq(postItId));
  if(jDropZone.width() > jPostIt.width()){
    pos.left = pos.left - jPostIt.innerWidth();
  }else{
    pos.left = jDropZone.offset().left;
  }
  pos.top = pos.top + (jDropZone.height() - jPostIt.height())/2;
  jPostIt.offset(pos);
}

//set the position of every post-it in the form
function setAllPostItsPotision(){
  $("div[id^='PostIt_']").each(function(index) {
      setPostItPosition($(this).attr("id"));
  });
}

//s'assurer que le post-it clické est visible par dessus les autres
function ensurePostItVisibility(){
  $(".PostIt").click( function(){
      $(".PostIt").removeClass("PostItClicked");
      $(this).addClass("PostItClicked");
    }
  );
}

//s'assurer que le post-it puisse être redimensionné
function ensurePostIsResizable(){
  $(".PostIt textarea").resizable();
}

//Obtention de l'id pour un nouveau post-it
function getNewPostItId(SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey){
  return "PostIt_"+ SubjectKey +"_"+ StudyEventOID +"_"+ StudyEventRepeatKey +"_"+ FormOID +"_"+ FormRepeatKey;
}

//Création d'un nouveau post-it (si inexistant)
function displayNewPostIt(SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey){
  //On crÚe un nouveau post-it
  var newid = getNewPostItId(SubjectKey,StudyEventOID,StudyEventRepeatKey,FormOID,FormRepeatKey);
  if($(jq(newid)).length==0){
    initDroppables();
    var html = getPostItHTML(newid);
    $("body").append(html);
    $(jq(newid)).offset($("#btnAddPostIt").offset());
    $(jq(newid)).draggable({ revert: "invalid" });
    //On s'assure que le post-it clické est visible par dessus les autres
    ensurePostItVisibility();
    //On s'assure que le post-it puisse être redimensionné
    ensurePostIsResizable();
  }
}

//Retourne le code HTML d'un post-it
//txt est optionnel (pour un nouveau post-it)
function getPostItHTML(id,txt){
  var options = "";
  var readonly = "";
  var onActions = "";
  //Seuls les ARC peuvent supprimer et modifier un post-it
  if(profileId=='CRA'){
    options = "<img src='"+currentApp+"/templates/default/images/delete_12.png' onClick='deletePostIt(this.parentNode.parentNode)' altbox='Delete'/>";
    readonly = "";
    onActions = "onFocus='editPostIt(this)' onKeyUp='postItId=helper.getFirstParentId(this); savePostIt(postItId)'";
  }else{
    options = "<img src='"+currentApp+"/templates/default/images/delete_12.png' onClick='hidePostIt(this.parentNode.parentNode)' altbox='Hide'/>";
    readonly = "readonly='readonly'";
    onActions = "";
  }
  if(!txt){
    txt = defaultPostItText;
  }
  html = "<div id='"+ id +"' class='PostIt'><div class='PostItHeader' altbox='Move'>Post-it"+ options +"</div><textarea cols='20' rows='3' "+ onActions +" "+ readonly +">"+ txt +"</textarea></div>";
  return html;
}

//Activation des zones où l'on peut poser un post-it
function initDroppables(){
  $("td.ItemDataInput").each(function(index){
    setDroppable($(this));
  });
  
  //Toutes les lignes qui contiennent déjà un post-it ne sont pas droppables
  $("div.PostIt").each(function(index){
    setUndroppable($(this));
  });
}

//Rend un élément droppable
//el doit être un objet jQuery
function setDroppable(el){
  el.droppable({
    accept: '.PostIt',
    hoverClass: "DroppableZone",
    drop: function(event, ui){
      $(this).addClass("DroppedZone");
      //Identification à donner au post-it
      keys = ui.draggable.attr("id").split(new RegExp("_"));
      SubjectKey = keys[1];
      StudyEventOID = keys[2];
      StudyEventRepeatKey = keys[3];
      FormOID = keys[4];
      FormRepeatKey = keys[5];
      oldItemGroupOID = keys[6]
      oldItemGroupRepeatKey = keys[7]
      oldItemOID = keys[8]
      var isCompactedIG = ($(this).closest("tr").attr("id").substr(0,1) == "_");
      if(isCompactedIG){
        ItemGroupOID = $(this).closest("table").attr("name"); //compacted ItemGroup
      }else{
        ItemGroupOID = $(this).closest("form").attr("name"); //normal ItemGroup
      }
      
      if(isCompactedIG){
        ids = $(this).attr("id").split(new RegExp("_")); //compacted ItemGroup
      }else{
        ids = $(this).closest("tr").attr("id").split(new RegExp("_")); //normal ItemGroup
      }
      ItemGroupRepeatKey = ids[2];
      ItemOID = ids[0];
      newId = "PostIt_"+ SubjectKey +"_"+ StudyEventOID +"_"+ StudyEventRepeatKey +"_"+ FormOID +"_"+ FormRepeatKey +"_"+ ItemGroupOID +"_"+ ItemGroupRepeatKey +"_"+ ItemOID;
      
      //On regarde si c'est pas un post-it existant qui a été déplacé
      if(ui.draggable.attr("id") != "PostIt_"+ SubjectKey +"_"+ StudyEventOID +"_"+ StudyEventRepeatKey +"_"+ FormOID +"_"+ FormRepeatKey){
        //on supprime l'ancienne référence en base, sans supprimer le post-it du DOM
        deletePostIt(ui.draggable,true);
        //on permet de nouveau à la ligne d'être droppable
        keys = ui.draggable.attr("id").split(new RegExp("_"));
        ItemGroupRepeatKey = keys[7];
        ItemOID = keys[8];
        dropzone = getDropZoneSelector(oldItemOID,oldItemGroupRepeatKey,oldItemGroupOID);
        //la ligne n'est plus droppée
        $(dropzone).removeClass("DroppedZone");
      }
      
      ui.draggable.attr("id", newId); //identification du post-it au format PostIt_SubjectKey_StudyEventOID_StudyEventRepeatKey_FormOID_FormRepeatKey_ItemGroupOID_ItemGroupRepeatKey_ItemOID
      
      savePostIt(newId);
      
      //on actualise la liste des éléments droppables
      initDroppables();
    }/*,
    out: function(event, ui){
      $(this).removeClass("DroppedZone");
    }*/
  });
}

//Rend un élément non droppable
//el doit être un objet jQuery
function setUndroppable(el){
  keys = el.attr("id").split(new RegExp("_"));
  ItemGroupOID = keys[6];
  ItemGroupRepeatKey = keys[7];
  ItemOID = keys[8];
  
  dropzone = getDropZoneSelector(ItemOID,ItemGroupRepeatKey,ItemGroupOID);
  $(dropzone).droppable({
    accept: '#nothing'
  });
}

function editPostIt(el){
  if(el.value==defaultPostItText){
    el.value = "";
  }
}

//Enregistrement d'un post-it
var postItTimeout = {};
function savePostIt(id){
  try{
    clearTimeout(postItTimeout);
  }catch(err){
    //
  }
  postItTimeout = setTimeout("savePostItEx('"+id+"')",1500);
}

function savePostItEx(id){
  postit = $(jq(id));
  
  keys = postit.attr("id").split("_");
  SubjectKey = keys[1];
  StudyEventOID = keys[2];
  StudyEventRepeatKey = keys[3];
  FormOID = keys[4];
  FormRepeatKey = keys[5];
  ItemGroupOID = keys[6];
  ItemGroupRepeatKey = keys[7];
  ItemOID = keys[8];
  
  //si le post-it n'est plus nouveau
  if(postit.attr("id")!="PostIt_"+ SubjectKey +"_"+ StudyEventOID +"_"+ StudyEventRepeatKey +"_"+ FormOID +"_"+ FormRepeatKey){
    
    //Texte à enregistrer
    var txt = postit.find("textarea").val();
    
    //S'il ne s'agit pas du texte par défaut (pas un nouveau post-it) on enregistre
    if(txt!=defaultPostItText){
      //Statut : post-it en cours d'enregistrement
      postit.addClass("PostItBeingSaved");
      
      //Enregistrement
      dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey+"&ItemGroupOID="+ItemGroupOID+"&ItemGroupRepeatKey="+ItemGroupRepeatKey+"&ItemOID="+ItemOID+"&txt="+escape(txt);
      $.ajax({
        type: "POST",
        async: false,
        url: "index.php?menuaction="+currentApp+".ajax.savePostIt",
        data: dataString,
        dataType: "json",
        error: function(data) {
          postit.removeClass("PostItBeingSaved");
          postit.addClass("PostItNotSaved");
        },
        success: function(data) {
          postit.removeClass("PostItBeingSaved");
          postit.removeClass("PostItNotSaved");
        }
      });
    }
  }
}

function deletePostIt(postit,keepVisible){
  if(keepVisible || confirm("Delete this post-it ?")){
    if(!postit.context){ //on test si c'est un élément jQuery passé en paramÞtre
      //Récupération jQuery de l'élément (post-it)
      postit = $(jq(postit.id));
    }
      
    keys = postit.attr("id").split(new RegExp("_"));
    SubjectKey = keys[1];
    StudyEventOID = keys[2];
    StudyEventRepeatKey = keys[3];
    FormOID = keys[4];
    FormRepeatKey = keys[5];
    ItemGroupOID = keys[6];
    ItemGroupRepeatKey = keys[7];
    ItemOID = keys[8];
    
    //si le post-it n'est plus nouveau
    if(postit.attr("id")!="PostIt_"+ SubjectKey +"_"+ StudyEventOID +"_"+ StudyEventRepeatKey +"_"+ FormOID +"_"+ FormRepeatKey){
      //Statut : post-it en cours d'enregistrement
      postit.addClass("PostItBeingSaved");
      
      //Enregistrement
      dataString = "SubjectKey="+SubjectKey+"&StudyEventOID="+StudyEventOID+"&StudyEventRepeatKey="+StudyEventRepeatKey+"&FormOID="+FormOID+"&FormRepeatKey="+FormRepeatKey+"&ItemGroupOID="+ItemGroupOID+"&ItemGroupRepeatKey="+ItemGroupRepeatKey+"&ItemOID="+ItemOID;
      $.ajax({
        type: "POST",
        async: false,
        url: "index.php?menuaction="+currentApp+".ajax.deletePostIt",
        data: dataString,
        dataType: "json",
        error: function(data) {
          postit.removeClass("PostItBeingSaved");
          postit.addClass("PostItNotSaved");
        },
        success: function(data) {
          postit.removeClass("PostItBeingSaved");
          postit.removeClass("PostItNotSaved");
          dropzone = getDropZoneSelector(ItemOID,ItemGroupRepeatKey,ItemGroupOID);
          $(dropzone).removeClass("DroppedZone"); //on enlève la teinte de zone droppée
          if(!keepVisible){
            postit.remove();
          }
          
          //on actualise la liste des éléments droppables
          initDroppables(); //on s'assure que la ligne puisse être de nouveau droppée
        }
      });
    }else{
      postit.remove();
    }
  }
}

//Permet de cacher un post-it
function hidePostIt(postit){
  postit.style.display = "none";
}

function getPos(el) {
    // yay readability
    for (var lx=0, ly=0;
         el != null;
         lx += el.offsetLeft, ly += el.offsetTop, el = el.offsetParent);
    return {x: lx,y: ly};
}
