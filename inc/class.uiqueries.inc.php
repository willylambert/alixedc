<?php
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

/**
* @desc Class d'UI dédié à l'edition des Queries
* @author TPI
**/ 
class uiqueries extends CommonFunctions
{
  /**
  * @desc Constructeur de class
  * @param array $configEtude tableau des constantes de configuration    
  * @param uietude $ctrlRef reference vers l'instance instanciation, où est délégué l'installation des objets (appel du type $this->m_ctrl->bcdiscoo() ) 
  * @author TPI
  * 
  **/ 
  function uiqueries($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * @desc fonction principale - retoure l'html à afficher, appelé depuis uietude
  * @return string HTML à afficher
  * @author TPI
  **/     
  public function getInterface()
  {
    $html = "";
    
    //Menu principal de l'eCRF
    $menu = $this->m_ctrl->etudemenu()->getMenu();

    //filtre global (texte libre)
    //$globalFilter = "<b>Global search :</b><input name=\"queriesFilter\" id=\"queriesFilter\" type=\"text\" value=\"\" onKeyUp=\"filterQueriesList('". $GLOBALS['egw_info']['flags']['currentapp'] ."');\" />";
    //filtre sur le statut
    $statusFilter = "<b>Status </b><div class='ArrowRight imageOnly image16 pointer' onClick=\"if($('#statusFilterOptions:hidden').length>0){ $('#statusFilterOptions:hidden').effect('slide'); $(this).addClass('ArrowLeft').removeClass('ArrowRight'); }else{ $('#statusFilterOptions:visible').effect('drop'); $(this).addClass('ArrowRight').removeClass('ArrowLeft');}\"></div>
                     <div style='display: inline-block;'><ul id='statusFilterOptions' style='display: none;'>";
    $statuses = $this->m_ctrl->boqueries()->getStatuses();
    foreach($statuses as $id => $status){
      $checked = "";
      if($id!="C"){
        $checked = "checked='checked'";
      }
      $statusFilter .= "<li><input type='checkbox' name='statusFilter' value='$id' $checked onClick=\"filterQueriesList('". $this->getCurrentApp(false)."');\" /> <div class='QueryStatus$id imageOnly image16'></div> $status</li>";
    }
    $statusFilter .= "</ul></div>";
    //filtre sur le type
    $typeFilter = "<b>Type </b><div class='ArrowRight imageOnly image16 pointer' onClick=\"if($('#typeFilterOptions:hidden').length>0){ $('#typeFilterOptions:hidden').effect('slide'); $(this).addClass('ArrowLeft').removeClass('ArrowRight'); }else{ $('#typeFilterOptions:visible').effect('drop'); $(this).addClass('ArrowRight').removeClass('ArrowLeft');}\"></div>
                     <div style='display: inline-block;'><ul id='typeFilterOptions' style='display: none;'>";
    $types = $this->m_ctrl->boqueries()->getTypes();
    foreach($types as $id => $type){
      $checked = "";
      $checked = "checked='checked'";
      $typeFilter .= "<li><input type='checkbox' name='typeFilter' value='$id' $checked onClick=\"filterQueriesList('". $this->getCurrentApp(false) ."');\" /> <div class='QueryType$id imageOnly image16'></div> $type</li>";
    }
    $typeFilter .= "</ul></div>";
    //filtre sur la date
    $dateFilter = "<b>Date </b><div class='ArrowRight imageOnly image16 pointer' onClick=\"if($('#dateFilterOptions:hidden').length>0){ $('#dateFilterOptions:hidden').effect('slide'); $(this).addClass('ArrowLeft').removeClass('ArrowRight'); }else{ $('#dateFilterOptions:visible').effect('drop'); $(this).addClass('ArrowRight').removeClass('ArrowLeft');}\"></div>
                     <div style='display: inline-block;'><ul id='dateFilterOptions' style='display: none;'>";
    $dateFilter .= "<li><select id='dateFilter' onChange=\"toggleDateFilterPicker(this); filterQueriesList('". $this->getCurrentApp(false) ."');\"><option value='any' selected='selected'>Any</option><option value='after'>After</option><option value='before'>Before</option></select></li>";
    $dateFilter .= "<li><span id='dateFilterPickers' style='display:none'><input type='text' id='dateFilterPicker' readonly='readonly' /></span></li>";
    $dateFilter .= "</ul></div>";
    $dateFilter .= "<script>
    function toggleDateFilterPicker(select){
      if(select.value!='any'){
        $(\"#dateFilterPickers:hidden\").effect('slide');
      } else {
        $(\"#dateFilterPickers\").effect('drop');
        filterQueriesList('". $this->getCurrentApp(false) ."');
      }
    }
    $(function() {
  		$( '#dateFilterPicker' ).datepicker({
  			showOn: 'both',
  			buttonImage: '". $this->getCurrentApp(false) ."/templates/default/images/calendar.gif',
  			buttonImageOnly: true,
  			onSelect: function(dateText, inst) { filterQueriesList('". $this->getCurrentApp(false) ."'); }
  		});
  	});
    </script>";
    $export = "<button class='ui-state-default ui-corner-all' onclick='exportQueries();'>Export results</button>";
    $export .= "<script>
    function exportQueries(){
      datastring = getQueriesFilterStringParameters();
      datastring += '&mode=CSV';
      url = 'index.php?menuaction=". $this->getCurrentApp(false) .".ajax.getQueriesDataList'+ datastring;
      window.location.href = url;
    }
    </script>";
    //fusion
    $htmlFilter = "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all' id='queriesFilters'><ul id='queriesFiltersList'><li style='background-color: #aaa; color: #fff; font-weight: bold; padding: 8px;'>Filters :</li><li>$dateFilter</li><li>$typeFilter</li><li>$statusFilter</li><li>$export</li></ul></div>";
    
    //tableau de queries
    $htmlQueries = "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>";
    
    $htmlQueries .= "
    <table id='listQueries'></table>
    <div id='pagerQueries'></div>
    <div id='filter' style='margin-left:30%;display:none'>Search Invoices</div>";
    
    $jsVersion = $this->m_tblConfig['JS_VERSION'];
    
    $html = " <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.7.1.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.16.custom.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqGrid/grid.locale-en.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqGrid/jquery.jqGrid.min.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.jqAltBox.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/helpers.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.queries.js') . "'></SCRIPT>
              <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/queries.js') . "'></SCRIPT>

              $menu

              <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                  <span class='ui-dialog-title'>Queries</span>
                </div> 
                <div class='ui-dialog-content ui-widget-content'>
                    $htmlFilter
                    $htmlQueries
                </div>                  
                
                <script>loadAlixCRFqueriesJS('".$this->getCurrentApp(false)."');</script>";
    
    return $html;
  }
  
}
