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
               checkChildren : false,
 
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
