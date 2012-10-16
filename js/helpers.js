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
    
//Message d'erreur
sMsgError = " If the error persists please contact svp.clinical@businessdecision.com";

//Utile pour les select jQuery => identifiant qui contiennent un '.'
//The following function takes care of escaping these characters and places a "#" at the beginning of the ID string:
function jq(myid) {
  return '#' + myid.replace(/(:|\.)/g,'\\$1');
}

//équivalent de la fonction PHP : remplace les retour à la ligne par un <br />
function nl2br (str, is_xhtml) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Philip Peterson
    // +   improved by: Onno Marsman
    // +   improved by: Atli Þór
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Maximusya
    // *     example 1: nl2br('Kevin\nvan\nZonneveld');
    // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
    // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
    // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
    // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
    // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'

    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';

    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

var helperIE = {
  //a patch to clone element with ie7
  clone : function(srcElement){
    newElement = document.createElement(srcElement.prop("tagName"));
    //todo : clone attributes (be careful, name must be copied as element.Name)
    newElement.innerHTML = srcElement.html();
    return $(newElement);
  }
}

var helper = {
  addslashes : function(str){
    return (str + '').replace(/[\\"']/g, '\\$&').replace(/\u0000/g, '\\0');
  },
  
  displayError : function(msg, data, bShowData){
    if(typeof(data)!="undefined"){
      if(typeof(data)=="object"){
        response = data.responseText;
      }else{
        response = data.toString();
      }
      if(response=="MAINTENANCE"){
        this.showInfoBox("Site is under maintenance",true);
        if(this.displayedError!="maintenance"){ //do not bother the user with the same message many times
          alert("The site is currently down for maintenance. Please try again later.");
          this.displayedError = "maintenance";
        }
      }else{
        if(response.substr(0,250).indexOf("[Login]", 0) != -1){
          this.showInfoBox("Session closed",true);
          if(this.displayedError!="session"){ //do not bother the user with the same message many times, do not redirect to login many times
            alert("Your session has ended. Please sign in again.");
            this.displayedError = "session";
            window.location.reload();
          }
        }else{
          this.showInfoBox(msg,true);
          if(bShowData){
            msg += ".\n\n"+ response +"\n\n";
          }else{
            msg += ".";
          }
          msg += sMsgError;
          //alert(msg);
          this.showPrompt(msg, "function noon(){}", 1);
        }
      }
    }
  },
  
  getFirstParentId : function(e) {
    test = false;
    path = "e";
    while(!test){
      path += ".parentNode";
      
      eval("id = "+ path +".id");
      if(typeof(id)!="undefined" && id!=""){
        test = true;
      }
    }
    return id;
  },
  
  ucfirst : function(str) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: ucfirst('kevin van zonneveld');
    // *     returns 1: 'Kevin van zonneveld'

    str += '';
    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1);
  },
  
  setLoader : function(id,size,info){
    var html = "<div class='helper_loader' style='width: "+ $(jq(id)).width() +"px;'><div class='helper_loader_image image_"+size+"'>&nbsp;</div></div>";
    $(jq(id)).addClass("helper_loading_element");
    $(jq(id)).append(html);
    
    this.showInfoBox(info);
  },
  
  unsetLoader : function(id,info){
    $(jq(id)).find(".helper_loader").remove();
    $(jq(id)).removeClass("helper_loading_element");
    
    this.showInfoBox(info,true);
  },
  
  showInfoBox : function(info,bHide){
    if($("#helper_infobox").size()==0){
      $("body").append("<div id='helper_infobox'></div>");
    }
    if(info!=""){
      $("#helper_infobox").stop(true, true);
      if(bHide){
        $("#helper_infobox").delay(500).fadeIn(0, function(){
                                          $(this).html(info)
                                                 .fadeOut(3000);
                                        });
      }else{
        $("#helper_infobox").html(info)
                          .fadeIn(300);
      }
    }
  },
  
  /*
  *@desc : display a prompt containing html string => display ok and cancel buttons, result is stored in input id='helper_prompt_result', value='ok' or value='cancel'
  *@param string html
  *@param string callback, function to call after ok or cancel
  *@param integer buttons, 0:ok+cancel, 1:ok only
  *@author : tpi
  */
  showPrompt : function(html, callback, buttons){
    html = "<div id='helper_prompt_bg'></div><div id='helper_prompt'>"+ html;
    html += "";
    html += "";
    html += "";
    html += "";
    html += "<div id='helper_prompt_buttons'><input type='button' id='helper_prompt_ok' value='Ok' />";
    if(typeof(buttons)=="undefined" || buttons<1){
      html += "<input type='button' id='helper_prompt_cancel' value='Cancel' /></div>";
    }
    html += "<input type='hidden' id='helper_prompt_result' value='' />";
    html += "</div>";
    
    $("#helper_prompt").remove();
    $("body").append(html);
    $("#helper_prompt_cancel").die('click');
    $("#helper_prompt_cancel").live('click', function(){
      $("#helper_prompt_result").val('cancel');
      $("#helper_prompt_bg").fadeOut('slow');
      $("#helper_prompt").fadeOut('slow');
      setTimeout(callback, 300);
    });
    $("#helper_prompt_ok").die('click');
    $("#helper_prompt_ok").live('click', function(){
      $("#helper_prompt_result").val('ok');
      $("#helper_prompt_bg").fadeOut('slow');
      $("#helper_prompt").fadeOut('slow');
      setTimeout(callback, 300);
    });
    $("#helper_prompt_bg").fadeIn('slow');
    $("#helper_prompt").fadeIn('slow');
  },
  
  /**
   *@desc : true if browser is an old version of IE
   *
   **/
  isOldIE : function(version){
    sVersion = "7";
    if(typeof(version)!="undefined") sVersion = version.toString();
    return ($.browser.msie * jQuery.browser.version.substr(0, 1) <= sVersion );
  }        
}
