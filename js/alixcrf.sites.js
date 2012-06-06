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
    
  /*
  @desc point d'entrée - appelée pour initialiser le comportement AJAX
  @author wlt
  */
  function loadAlixCRFsitesJS()
  {                   
    //Bind des buttons
  	$('#create-site')
			.button()
			.click(function() {
				//$("form#createSite").submit();
        $('#dialog-form').dialog('open');
	});

  var siteName = $("#siteName"),
      siteId = $("#siteId"),
    	tips = $(".validateTips"),
      allFields = $([]).add(siteName).add(siteId);
  
  function updateTips(t){
  	tips
  		.text(t)
  		.addClass('ui-state-highlight');
  	setTimeout(function() {
  		tips.removeClass('ui-state-highlight', 1500);
  	}, 500);
  }
  
  function checkLength(o,n,min,max){
  	if ( o.val().length > max || o.val().length < min ) {
  		o.addClass('ui-state-error');
  		updateTips("Length of " + n + " must be between "+min+" and "+max+".");
  		return false;
  	} else {
  		return true;
  	}
  
  }
  
  function checkRegexp(o,regexp,n){
  	if ( !( regexp.test( o.val() ) ) ) {
  		o.addClass('ui-state-error');
  		updateTips(n);
  		return false;
  	} else {
  		return true;
  	}
  
  }
  
  //Formulaire d'ajout d'un centre
  $("#dialog-form").dialog({
  	autoOpen: false,
  	height: 400,
  	width: 350,
  	modal: true,
  	buttons: {
  		'Create a site': function() {
  			var bValid = true;
  			allFields.removeClass('ui-state-error');
        
  			bValid = bValid && checkLength(siteId,"siteId",1,10);
  			bValid = bValid && checkLength(siteName,"siteName",3,50);
  
  			bValid = bValid && checkRegexp(siteId,/^([0-9])+$/i,"Site Id must be a number");
  			bValid = bValid && checkRegexp(siteName,/^[a-z]([0-9a-z_])+$/i,"Site name may consist of a-z, 0-9, underscores, begin with a letter.");
  			
  			if (bValid) {
            //Soumission du formulaire
            $("form#createSite").submit();
  			}
  		},
  		Cancel: function() {
  			$(this).dialog('close');
  		}
  	},
  	close: function() {
  		allFields.val('').removeClass('ui-state-error');
  	}
  });

}