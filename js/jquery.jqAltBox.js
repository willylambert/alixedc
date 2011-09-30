/**
 * Copyright (c) 2011 Thomas Perraudin
 * jqAltBox is currently available for use in all personal or commercial projects 
 * under both the MIT (http://www.opensource.org/licenses/mit-license.php) and GPL 
 * version 2.0 (http://www.gnu.org/licenses/gpl-2.0.html) licenses. This means that you can 
 * choose the license that best suits your project and use it accordingly. 
 * 
 * 
 * jqAltBox adds a div box similar to the box created with the attribute "alt" for images, on every element with attribute altbox.
 * The text displayed in the box is the value of the attribute altbox.
 * 
 * It is also possible to specify your own css style to the altbox.
 * Example : <div altbox="{padding: 10px; border-radius: 10px;}My message in a bubble.">My div box and its content here.</div>
 *  
 */

if(typeof(jqAltBox)=="undefined")
jqAltBox = {
  cssModified: false,
  
  initCSS: function(){
    $("#altbox").attr("style", "");
    $("#altbox").css({'position': 'absolute', 'z-index': '999999999', 'display': 'none', 'border': '1px solid #000', 'padding': '2px', 'font-family': 'Verdana, Arial', 'font-size': '10px', 'background-color': '#ffffe1', 'color': '#000', 'font-weight': 'normal'});
  },
  
  initEvents: function(){
    $("*[altbox]")
      .live("mousemove", this.mousemove)
      .live("mouseover", this.mouseover)
      .live("mouseout", this.mouseout);
    
    $(document).ajaxComplete(this.BindEvents);
  },
  
  BindEvents: function(){
    $("*[altbox]").each(function(){
      $(this)
        .bind("mousemove", jqAltBox.mousemove)
        .bind("mouseover", jqAltBox.mouseover)
        .bind("mouseout", jqAltBox.mouseout);
    });
  },
  
  mousemove: function(e){
    if($("#altbox").css('display')!='none') {  // if bubble is visible : set its position
      abOffset = $(this).offset();
      abOffset.left = e.pageX;
      abOffset.top = e.pageY + 20;
      $("#altbox").offset(abOffset);
    }
  },
  
  mouseover: function(){
    if($("#altbox").css('display')=='none') {
  	  if(jqAltBox.cssModified){
        jqAltBox.initCSS();
      }
      msg = $(this).attr("altbox");
      if(msg.indexOf("{")==0){
        msg = jqAltBox.extractCSS(msg);
        jqAltBox.cssModified = true;
      }else{
        jqAltBox.cssModified = false;
      }
  	  $("#altbox")
        .css("display", "block") // if altbox is hidden, then show it
        .html(msg);
    }
  },
  
  mouseout: function(){
  	if($("#altbox").css('display')!='none') {
  	  $("#altbox").css("display", "none"); // if altbox is visible, then hide it
  	}
  },
  
  extractCSS: function(msg){
    cssEnd = msg.indexOf("}");
    css = msg.substring(1,cssEnd-1);
    $("#altbox").attr("style", $("#altbox").attr("style") +"; "+ css);
    return msg.substring(cssEnd+1);
  }
  
}

jqAltBox.initEvents();

$(document).ready(function(){
  $("body:not(:has(div#altbox))").append("<div id='altbox'></div>");
  
	jqAltBox.initCSS();
	
  jqAltBox.initEvents();
});
