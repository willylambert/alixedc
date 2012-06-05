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


function compactItemGroup(FormOID){

  $(document).ready(function(){
   
    //Head line
    $("form[name='"+FormOID+"'] table.ItemGroup").first().before(function(){
      //Creation headline
      //Loop through ItemDataLabel
      width = $(this).find("tr:visible td.ItemDataLabel").size() * 105;
      htmlRet = "<div style='min-width:"+width+"px;'>";
      $(this).find("td.ItemDataLabel").each(function(){
        if($(this).parent().is(':visible')){
          htmlRet += "<div>"+$(this).text()+"</div>";
        }      
      });
      htmlRet += "</div>";
      return htmlRet;
    }).prev().addClass("itemGroupHeadLine").addClass("ui-state-default");
  
    //Itemgroupdata line
    $("form[name='"+FormOID+"'] table.ItemGroup").before(function(){

      width = $(this).find("tr:visible td[class='ItemDataInput']").size() * 105;
      htmlRet = "<div style='min-width:"+width+"px;'>";
      //Loop through itemDataInput
      $(this).find("td[class='ItemDataInput']").each(function(){
        if($(this).parent().is(':visible')){
          value = $(this).attr("lastvalue");
          if(value==""){
            value = " ";
          }
          if($.browser.msie * jQuery.browser.version.substr(0, 1)<="7"){
            htmlRet += "<span>&#160;"+value+"</span>";
          }else{
            htmlRet += "<div>&#160;"+value+"</div>";
          }
        }          
      });
      htmlRet += "</div>";
      return htmlRet;
      
    }).hide().prev().addClass("itemGroupLine").addClass("ui-widget-content ui-row-ltr").click(function(){$(this).next().slideToggle()});
   
    //Handle TransactionType 
    $(".itemGroupLine").each(function(){
      $(this).addClass($(this).next().attr('class'));
    });
    
    //If TransactionType = remove, no slide
    $(".itemGroupLine.TransactionTypeRemove").unbind('click');
    
    //remove unneeded H3
    $("form[name='"+FormOID+"'] h3").detach();    
  });
      
}