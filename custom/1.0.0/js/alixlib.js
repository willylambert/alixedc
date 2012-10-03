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

function removeAccent(s){
  var r=s.toLowerCase();
  r = r.replace(new RegExp("\\s", 'g'),"");
  r = r.replace(new RegExp("[àáâãäå]", 'g'),"a");
  r = r.replace(new RegExp("æ", 'g'),"ae");
  r = r.replace(new RegExp("ç", 'g'),"c");
  r = r.replace(new RegExp("[èéêë]", 'g'),"e");
  r = r.replace(new RegExp("[ìíîï]", 'g'),"i");
  r = r.replace(new RegExp("ñ", 'g'),"n");                            
  r = r.replace(new RegExp("[òóôõö]", 'g'),"o");
  r = r.replace(new RegExp("œ", 'g'),"oe");
  r = r.replace(new RegExp("[ùúûü]", 'g'),"u");
  r = r.replace(new RegExp("[ýÿ]", 'g'),"y");
  r = r.replace(new RegExp("\\W", 'g'),"");
  return r;
}


function compactItemGroup(FormOID,tblCol){

  $(document).ready(function(){
   
    //Head line
    $("form[name='"+FormOID+"'] table.ItemGroup").first().before(function(){
      //Creation headline
      //Loop through ItemDataLabel
      //width = $(this).find("tr:visible td.ItemDataLabel").size() * 105;
      width = 0;
      linesRet = "";
      $(this).find("td.ItemDataLabel").each(function(){
        if(typeof(tblCol)=="undefined" || typeof(tblCol)!="undefined" && tblCol[$(this).parent().attr('name')]==true){        
          if($(this).parent().is(':visible')){
            linesRet += "<div>"+$(this).text()+"</div>";
            width += 105;
          }      
        }
      });
      htmlRet = "<div style='min-width:"+width+"px;'>";
      htmlRet += linesRet;
      htmlRet += "</div>";
      return htmlRet;
    }).prev().addClass("itemGroupHeadLine").addClass("ui-state-default");
  
    //Itemgroupdata line
    $("form[name='"+FormOID+"'] table.ItemGroup").before(function(){

      //width = $(this).find("tr:visible td[class='ItemDataInput']").size() * 105;
      htmlRet = "<div style='min-width:"+width+"px;'>";
      //Loop through itemDataInput
      $(this).find("td.ItemDataInput").each(function(){
        if(typeof(tblCol)=="undefined" || typeof(tblCol)!="undefined" && tblCol[$(this).parent().attr('name')]==true){
          if($(this).parent().is(':visible')){
            value = $(this).attr("lastvalue");
            if(value==""){
              value = " ";
            }
            if(helper.isOldIE()){ //old version of IE
              htmlRet += "<span>&#160;"+value+"</span>";
            }else{
              htmlRet += "<div>&#160;"+value+"</div>";
            }
          }          
        }
      });
      htmlRet += "</div>";
      return htmlRet;
      
    }).hide()
      .prev()
      .addClass("itemGroupLine")
      .addClass("ui-widget-content ui-row-ltr")
      .click(function(){
        $(this).next()
               .slideToggle(200,function(){
                 postItToggle(this);
                 setAllPostItsPotision();
               });
      });
    
    //Handle TransactionType 
    $(".itemGroupLine").each(function(){
      $(this).addClass($(this).next().attr('class'));
      postItToggle($(this).next());
    });
    
    //If TransactionType = remove, no slide
    $(".itemGroupLine.TransactionTypeRemove").unbind('click');
    
    //remove unneeded H3
    $("form[name='"+FormOID+"'] h3").detach();
  });
  
}

//Toggle visibility of the post-its when an ItemGroup is clicked
function postItToggle(ig){
  //if(helper.isOldIE(8)){ //the management of visibility is needed only with IE <= IE8
  //management of visibility is needed for IE<8 AND for new post-its (they are at the end of the DOM, not inside the IG)
    igid = $(ig).find("tr[id]").attr("id");
    igparams = igid.split(new RegExp("_", "g"));
    igoidref = igparams[1];
    igrkref = igparams[2];
    $('.PostIt').each( function(){
      params = $(this).attr("id").split(new RegExp("_", "g"));
      igoid = params[6];
      igrk = params[7];
      if(igoid==igoidref && igrk==igrkref){ //post-it in the concerned ItemGroup
        if($(ig).is(':visible')){
          $(this).show();
        }else{
          $(this).hide();
        }
      }
    });
  //}
}