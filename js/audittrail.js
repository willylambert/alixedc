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
    
//////////////////////////////////////////////////////////////
// Audit Trails 

function initAuditTrail(ItemOID,IGRK){

  var elementId = "#auditTrail_div_"+ItemOID+"_"+IGRK;
  var elementIdEsc = elementId.replace(".","\\.");
  $(elementIdEsc).dialog({
    	autoOpen: false,
    	height: 300,
    	width: 800,
    	modal: false,
    	buttons: {
    		'Close': function() {
          $(this).dialog('close');
    		}
    	},
    	close: function() {
    	}
  });
  
  $(elementIdEsc).attr('initialized','true');

}


function toggleAuditTrail(ItemOID,IGRK){
  var elementId = "#auditTrail_div_"+ItemOID+"_"+IGRK;
  var elementIdEsc = elementId.replace(".","\\.");
  
  //dialog initialized ?
  if($(elementIdEsc).attr('initialized')=='false'){
    //Initialisation of AuditTrail
    if(typeof(initAuditTrail)=="function"){
      initAuditTrail(ItemOID,IGRK);
    }
  }
  
  $(elementIdEsc).dialog('open');
  $(elementIdEsc +"[keys]").each(function(index){
    var keys = $(this).attr("keys").split(",");
    var CurrentApp = keys[0];
    var dataString = "SubjectKey="+keys[1]+"&StudyEventOID="+keys[2]+"&StudyEventRepeatKey="+keys[3]+"&FormOID="+keys[4]+"&FormRepeatKey="+keys[5]+"&ItemGroupOID="+keys[6]+"&ItemGroupRepeatKey="+keys[7]+"&ItemOID="+keys[8];
    $.ajax({
      type: "POST",
      url: "index.php?menuaction="+CurrentApp+".ajax.getAuditTrail",
      data: dataString,
      dataType: "json",
      success: function(data) {
        //alert(data.toString());
        
        //On créé le tableau html pour afficher le résultat
        var html = "<table class='AuditTrail'><tr><th>Date</th><th>Type</th><th>User</th><th>Transaction</th><th>Value</th><th>Flag</th><th>Comment</th></tr>";
        for(var i=0; i<data.length; i++){
          transaction = data[i]['transaction'];
          transactionlabel = transaction;
          stype = data[i]['type'];
          switch(stype){
            case "Deviation":
              if(transaction=='O'){
                transactionlabel = "Add";
              }else if(transaction=='U'){
                transactionlabel = "Update";
              }else if(transaction=='C'){
                transactionlabel = "Delete";
              }
              break;
            case "Query":
              if(typeof(getQueryStatusLabel)!="undefined"){
                transactionlabel = getQueryStatusLabel(transaction).toLowerCase();
                f = transactionlabel.charAt(0).toUpperCase();
                transactionlabel = f + transactionlabel.substr(1);
              }else{
                if(transaction=='O'){
                  transactionlabel = "Open";
                }else if(transaction=='C'){
                  transactionlabel = "Close";
                }
              }
              break;
            default:
              //
          }
          html += "<tr class='AuditTrailRow Audit"+stype+" Audit"+stype+transaction+"'>";
          html += "<td>"+ data[i]['date'] +"</td>";
          html += "<td>"+ stype +"</td>";
          html += "<td>"+ data[i]['user'] +"</td>";
          html += "<td>"+ transactionlabel +"</td>";
          html += "<td>"+ data[i]['value'] +"</td>";
          html += "<td>"+ data[i]['flagvalue'] +"</td>";
          html += "<td>"+ data[i]['flagcomment'] +"</td>";
          html += "</tr>";
        }
        html += "</table>";
        
        
        //affichage
        $(elementIdEsc).html(html);
      }
    });
  });
}
