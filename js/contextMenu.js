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
    
$(document).ready( function() {
 
    $(".ItemDataInput input, .ItemDataInput select, .ItemDataInput textarea").contextMenu({
      menu: "inputContextMenu"
    },
    function(action, el, pos) {
      /*
      alert(
        "Action: " + action + "\n\n" +
        "Element ID: " + $(el).attr("id") + "\n\n" +
        "X: " + pos.x + "  Y: " + pos.y + " (relative to element)\n\n" +
        "X: " + pos.docX + "  Y: " + pos.docY+ " (relative to document)"
      );
      */
      switch(action){
        case "contextMenuAuditTrail":
          //generation a call looking like toggleAuditTrail('ENROL.SUBJINIT','ENROL','0');
          var keys = $(el).closest("td[id],tr[id]").attr("id").split("_");
          var itemoid = keys[0];
          var igoid = keys[1];
          var igrk = keys[2];
          itemoid = itemoid.replace(new RegExp("(@)"),".");
          toggleAuditTrail(itemoid,igoid,igrk);
          break;
        default:
          alert("Context Menu : unknown action '"+ action +".'");
      }
    });
    
});
