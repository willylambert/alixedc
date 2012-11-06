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
    
function loadAlixCRFqueriesJS(CurrentApp)
{
  //Préchargement complet des données dans un chache local
  //loadQueries(CurrentApp,'',null,true);
  
  loadQueriesGrid(CurrentApp);
}

function loadQueriesGrid(CurrentApp){
  
  //Chargement du tableau
  var mygrid = jQuery("#listQueries").jqGrid({
    url: "index.php?menuaction="+CurrentApp+".ajax.getQueriesDataList&queryStatus=O,A,P,R",
    mtype: "POST",
  	datatype: "json",
  	height: "auto",
   	colNames:['Date','Site','Subject','Visit','Form','Section','Item','Description','Type','Comment','Status','Histo','Edit','See'],
   	colModel:[
   		{name:'UPDATEDT',
       index:'UPDATEDT',
       width: 70,
       search:false
      },
   		{name:'SITEID',
       index:'SITEID',
       width: 30
      },
   		{name:'SUBJKEY',
       index:'SUBJKEY',
       width: 60
      },
   		{name:'SEOID',
       index:'SEOID',
       sortable:false,
       width: 115,
       search:true
      },
   		{name:'FRMOID',
       index:'FRMOID',
       sortable:false,
       width: 115,
       search:true
      },
   		{name:'IGOID',
       index:'IGOID',
       sortable:false,
       width: 115,
       search:false
      },		
   		{name:'ITEMTITLE',
       index:'ITEMTITLE',
       width: 140,
       search:false
      },		
   		{name:'LABEL',
       index:'LABEL',
       width: 180,
       search:false
      },		
   		{name:'QUERYTYPE',
       index:'QUERYTYPE',
       width: 35,
       align:'center',
       search:false
      },		
   		{name:'ANSWER',
       index:'ANSWER',
       sortable:false,
       width: 160,
       search:false
      },		
   		{name:'QUERYSTATUS',
       index:'QUERYSTATUS',
       width: 35,
       align:'center',
       search:false
      },		
   		{name:'History',
       index:'History',
       sortable:false,
       width: 35,
       align:'center',
       search:false
      },		
   		{name:'Edit',
       index:'Edit',
       sortable:false,
       width: 35,
       align:'center',
       search:false
      },		
   		{name:'See',
       index:'See',
       sortable:false,
       width: 35,
       align:'center',
       search:false
      }
   	],
   	rowNum:10,
   	rowList:[10,20,30],
   	pager: '#pagerQueries',
   	sortname: 'UPDATEDT',
    viewrecords: true,
    gridview : true,
    sortorder: "desc",
    caption: "Queries"
  });
  jQuery("#listQueries").jqGrid('navGrid','#pagerQueries',{edit:false,add:false,del:false,search:false,refresh:false});
  jQuery("#listQueries").jqGrid('navButtonAdd',"#pagerQueries",{caption:"Toggle",title:"Toggle Search Toolbar", buttonicon :'ui-icon-pin-s',
  	onClickButton:function(){
  		mygrid[0].toggleToolbar()
  	} 
  });
  jQuery("#listQueries").jqGrid('navButtonAdd',"#pagerQueries",{caption:"Clear",title:"Clear Search",buttonicon :'ui-icon-refresh',
  	onClickButton:function(){
  		mygrid[0].clearToolbar()
  	} 
  });
  jQuery("#listQueries").jqGrid('filterToolbar');
}

//Affiche l'historique d'une query
function toggleQueryHistory(CurrentApp,QueryId){
  if($("[id^='query_"+ QueryId +"_']").length>0){ //masquer (supprimer du DOM même !)
    $("[id^='query_"+ QueryId +"_']").each(function(){
      //$(this).slideUp(250, function(){ //ici, le slideUp entraine un bug de mise en page sous google chrome (au changement de page)
        $(this).remove();
      //})
    });
  }else{ //charger + afficher
    dataString = "QUERYID="+QueryId;
    $.ajax({
      type: "POST",
      url: "index.php?menuaction="+CurrentApp+".ajax.getQueryHistory",
      async:true,
      data: dataString,
      dataType: "json",
      error: function(data) {
          helper.displayError("An error occured while saving", data);
        },
      success: function(data) {
          html = "";
          for(i=0;i<data.length;i++){
            data[i].QUERYID;
            queriesList[QueryId] = data[i]; //mise en cache
            
            html += getQueryRowHistory(CurrentApp,QueryId,data[i].QUERYID);
          };
          $("#query_"+QueryId).after(html);
        }
    });
  }
}

//retourne la ligne à insérer dans le tableau pour une query
function getQueryRowHistory(CurrentApp,QueryId,HistoryQueryId){ //je donne des noms fabuleux à mes fonctions, manque d'inspiration :(
  historyQuery = getQuery(CurrentApp,HistoryQueryId);
  date = historyQuery.UPDATEDT.substr(5,2) +"/"+ historyQuery.UPDATEDT.substr(8,2) +"/"+ historyQuery.UPDATEDT.substr(0,4) +" "+ historyQuery.UPDATEDT.substr(10,8);
  html = "<tr id='query_"+ QueryId +"_query_"+ HistoryQueryId +"' class='ui-widget-content jqgrow ui-row-ltr listQueriesHistoryRow'>";
  html += "<td>"+ date +"</td>";
  /*
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  */
  html += "<td colspan='6'>"+ "&nbsp;" +"</td>";
  html += "<td>"+ historyQuery.LABEL +"</td>";
  html += "<td style='text-align: center;'>"+ getQueryTypeIcon(historyQuery.QUERYTYPE) +"</td>";
  html += "<td>"+ historyQuery.ANSWER +"</td>";
  html += "<td style='text-align: center;'>"+ getQueryStatusIcon(historyQuery.QUERYSTATUS, historyQuery.QUERYORIGIN) +"</td>";
  /*
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  html += "<td>"+ "" +"</td>";
  */
  html += "<td colspan='3'>"+ "&nbsp;" +"</td>";
  html += "</tr>";
  return html;
}

function getQueryTypeIcon(QueryType){
  return "<div class='QueryType"+ QueryType +" imageOnly image16' altbox='"+ getQueryTypeLabel(QueryType) +"'></div>";
}
function getQueryStatusIcon(QueryStatus, QueryOrigin){
  return "<div class='QueryStatus"+ QueryStatus +" QueryOrigin"+ QueryOrigin +" imageOnly image16' altbox='"+ helper.ucfirst(getQueryStatusLabel(QueryStatus).toLowerCase()) +"'></div>";
}

//Recharger la grille avec filtrage
function filterQueriesList(CurrentApp){
  
  datastring = getQueriesFilterStringParameters();
  
  //rechargement
  jQuery("#listQueries").jqGrid('setGridParam',{url:"index.php?menuaction="+CurrentApp+".ajax.getQueriesDataList"+ datastring, page:1}).trigger("reloadGrid");
}

function getQueriesFilterStringParameters(){
  datastring = "";
  
  //filtre global
  search = $("#queriesFilter").val();
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
    datastring += "&queryStatus="+ statuses;
  }
  
  //filtre du type
  types = "";
  $("input[name='typeFilter']:checked").each( function(){
    if(types!='') types += ",";
    types += $(this).val();
  });
  if(types!=''){
    datastring += "&queryType="+ types;
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
