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
    
function loadAlixCRFdeviationsJS(CurrentApp)
{
  //Préchargement complet des données dans un chache local
  //loadDeviations(CurrentApp,'',null,true);
  
  loadDeviationsGrid(CurrentApp);
}

function loadDeviationsGrid(CurrentApp){
  
  //Chargement du tableau
  var mygrid = jQuery("#listDeviations").jqGrid({
    url: "index.php?menuaction="+CurrentApp+".ajax.getDeviationsDataList&deviationStatus=O,U,C",
    mtype: "POST",
  	datatype: "json",
  	height: 460,
   	colNames:['Date','Site','Subject','Visit','Form','Section','Item','Description','Status','Histo','Edit','See'],
   	colModel:[
   		{name:'UPDATEDT',
       index:'UPDATEDT',
       width: 70,
       search:false
      },
   		{name:'SITEID',
       index:'SITEID',
       width: 40
      },
   		{name:'SUBJKEY',
       index:'SUBJKEY',
       width: 80
      },
   		{name:'SEOID',
       index:'SEOID',
       sortable:false,
       width: 130
      },
   		{name:'FRMOID',
       index:'FRMOID',
       sortable:false,
       width: 130
      },
   		{name:'IGOID',
       index:'IGOID',
       sortable:false,
       width: 130
      },		
   		{name:'ITEMTITLE',
       index:'ITEMTITLE',
       width: 150
      },		
   		{name:'DESCRIPTION',
       index:'DESCRIPTION',
       width: 185
      },	
   		{name:'STATUS',
       index:'STATUS',
       width: 50,
       align:'center',
       search:false
      },		
   		{name:'History',
       index:'History',
       sortable:false,
       width: 40,
       align:'center',
       search:false
      },		
   		{name:'Edit',
       index:'Edit',
       sortable:false,
       width: 40,
       align:'center',
       search:false
      },		
   		{name:'See',
       index:'See',
       sortable:false,
       width: 40,
       align:'center',
       search:false
      }
   	],
   	rowNum:10,
   	rowList:[10,20,30],
   	pager: '#pagerDeviations',
   	sortname: 'UPDATEDT',
    viewrecords: true,
    gridview : true,
    sortorder: "desc",
    caption: "Deviations"
  });
  jQuery("#listDeviations").jqGrid('navGrid','#pagerDeviations',{edit:false,add:false,del:false,search:false,refresh:false});
  jQuery("#listDeviations").jqGrid('navButtonAdd',"#pagerDeviations",{caption:"Toggle",title:"Toggle Search Toolbar", buttonicon :'ui-icon-pin-s',
  	onClickButton:function(){
  		mygrid[0].toggleToolbar()
  	} 
  });
  jQuery("#listDeviations").jqGrid('navButtonAdd',"#pagerDeviations",{caption:"Clear",title:"Clear Search",buttonicon :'ui-icon-refresh',
  	onClickButton:function(){
  		mygrid[0].clearToolbar()
  	} 
  });
  jQuery("#listDeviations").jqGrid('filterToolbar');
}

//Affiche l'historique d'une deviation
function toggleDeviationHistory(CurrentApp,DeviationId){
  if($("[id^='deviation_"+ DeviationId +"_']").length>0){ //masquer (supprimer du DOM même !)
    $("[id^='deviation_"+ DeviationId +"_']").each(function(){
      //$(this).slideUp(250, function(){ //ici, le slideUp entraine un bug de mise en page sous google chrome (au changement de page)
        $(this).remove();
      //})
    });
  }else{ //charger + afficher
    dataString = "DEVIATIONID="+DeviationId;
    $.ajax({
      type: "POST",
      url: "index.php?menuaction="+CurrentApp+".ajax.getDeviationHistory",
      async:true,
      data: dataString,
      dataType: "json",
      error: function(data) {
          helper.displayError("An error occured while saving", data);
        },
      success: function(data) {
          html = "";
          for(i=0;i<data.length;i++){
            data[i].DEVIATIONID;
            deviationsList[DeviationId] = data[i]; //mise en cache
            
            html += getDeviationRowHistory(CurrentApp,DeviationId,data[i].DEVIATIONID);
          };
          $("#deviation_"+DeviationId).after(html);
        }
    });
  }
}

//retourne la ligne à insérer dans le tableau pour une deviation
function getDeviationRowHistory(CurrentApp,DeviationId,HistoryDeviationId){ //je donne des noms fabuleux à mes fonctions, manque d'inspiration :(
  historyDeviation = getDeviation(CurrentApp,HistoryDeviationId);
  date = historyDeviation.UPDATEDT.substr(5,2) +"/"+ historyDeviation.UPDATEDT.substr(8,2) +"/"+ historyDeviation.UPDATEDT.substr(0,4) +" "+ historyDeviation.UPDATEDT.substr(10,8);
  html = "<tr id='deviation_"+ DeviationId +"_deviation_"+ HistoryDeviationId +"' class='ui-widget-content jqgrow ui-row-ltr listDeviationsHistoryRow'>";
  html += "<td>"+ date +"</td>";
  /*
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  */
  html += "<td colspan='6'>"+ "&nbsp;" +"</td>";
  html += "<td>"+ historyDeviation.DESCRIPTION +"</td>";
  html += "<td style='text-align: center;'>"+ getDeviationStatusIcon(historyDeviation.STATUS) +"</td>";
  /*
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  */
  html += "<td colspan='3'>"+ "&nbsp;" +"</td>";
  html += "</tr>";
  return html;
}

function getDeviationStatusIcon(Status){
  //return "<div class='DeviationStatus"+ DeviationStatus +" imageOnly image16' ></div>";
  return helper.ucfirst(getDeviationStatusLabel(Status).toLowerCase());
}

//Recharger la grille avec filtrage
function filterDeviationsList(CurrentApp){
  
  datastring = getDeviationsFilterStringParameters();
  
  //rechargement
  jQuery("#listDeviations").jqGrid('setGridParam',{url:"index.php?menuaction="+CurrentApp+".ajax.getDeviationsDataList"+ datastring, page:1}).trigger("reloadGrid");
}

function getDeviationsFilterStringParameters(){
  datastring = "";
  
  //filtre global
  search = $("#deviationsFilter").val();
  if(typeof(search)!="undefined"){
    if(search.length == 0 || search.length > 2){
        datastring += "&search="+ escape(search);
      }
  }
  
  //filtre du statut
  statuses = "";
  $("input[name='statusFilter']:checked").each( function(){
    if(statuses!='') statuses += ",";
    statuses += $(this).val();
  });
  if(statuses!=''){
    datastring += "&deviationStatus="+ statuses;
  }
  
  //filtre de la date
  datePos = $("#dateFilter").val();
  if(datePos!="any"){
    dateRef = $("#dateFilterPicker").val();
    if(dateRef!=""){
      day = dateRef.substr(3,2);
      month = dateRef.substr(0,2);
      year = dateRef.substr(6,4);
      dateRef = year+"-"+month+"-"+day;
      datastring += "&datePos="+ datePos +"&dateRef="+ dateRef;
    }
  }
  
  //filtres de colonnes
  bColSearch = false;
  $("input[id^='gs_']").each(function(){
    if($(this).val()!=""){
      bColSearch = true;
      datastring += "&"+ $(this).attr("name") +"="+ $(this).val();
    }
  });
  if(bColSearch){
    datastring += "&_search=true";
  }
  
  return datastring;
}
