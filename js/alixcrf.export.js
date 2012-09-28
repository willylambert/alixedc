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

/*
Export Edit Entry point - called to initalize the JavaScript
*/
function loadAlixCRFexporteditJS(CurrentApp)
{
  jQuery(document).ready(function(){
     $('ul#treeItems').collapsibleCheckboxTree({
 
          // When checking a box, all parents are checked (Default: true)
               checkParents : true,
 
          // When checking a box, all children are checked (Default: false)
               checkChildren : true,
 
          // When unchecking a box, all children are unchecked (Default: true)
               uncheckChildren : true,
 
          // 'expand' (fully expanded), 'collapse' (fully collapsed) or 'default'
               initialState : 'default'
 
     });
     
    $('#btnCancel')
			.button()
			.click(function() {
        document.location = "index.php?menuaction=" + CurrentApp + ".uietude.exportInterface";
	  });

    $('#btnSave')
			.button()
			.click(function() {
        $('#defineExport').submit();
	  });

});

}

/*
Main Entry point - called to initalize the JavaScript
*/
function loadAlixCRFexportJS(CurrentApp)
{                   
	$('#btnAddExport').button();
	
}
