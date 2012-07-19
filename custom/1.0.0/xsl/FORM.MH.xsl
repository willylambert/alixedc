<?xml version="1.0" encoding="UTF-8"?>
<!--
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    *                                                                          *
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
-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" encoding="UTF-8" indent="no"/>
<xsl:include href="include/alixlib.xsl"/>

<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Suppression du bouton d'Ajout -->
<xsl:template match="button[@id='btnAddItemGroup']">
	<!-- Bouton d'ajout supprimé -->
</xsl:template>

<!-- Hide coding items -->
<xsl:template match="tr[@name='MH.MHLLT_C' or @name='MH.MHLLT_N' or @name='MH.MHPT_C' or @name='MH.MHDECOD' or @name='MH.MHSOC_C' 
    or @name='MH.MHBODSYS' or @name='MH.MHHLT_C' or @name='MH.MHHLT_N' or @name='MH.MHHLGT_C' or @name='MH.MHHLGT_N' or @name='MH.MEDDRA_V']">
   <xsl:copy>
        <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">display:none;</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Add help label for data entry-->
<xsl:template match="tr[@name='SUPPMH.MHYN']">  
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
  <tr id="TitleLst"><td colspan="5">
     <h3>List of Medical or Surgical History</h3>
    </td>
   </tr>
</xsl:template>

<!--Add tabs for conditional items -->
<xsl:template match="td[../@name='MH.MHENDTC' and @class='ItemDataLabel underCondition']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
		&#160;&#160;&#160;	
	  <xsl:value-of select="."/>
  </xsl:copy>  
	</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
			  function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{
         input_origin2 = 'select_SUPPMH@MHYN_'+ItemGroupRepeatKey;
           
					if(origin.name==input_origin2) 
					{            
           action2 = $("select[name='select_SUPPMH@MHYN_"+ItemGroupRepeatKey+"']").val();
           
           if(action2!=1||action2==''){
              $("tr[id='TitleLst']").hide();
            }
            else
            {
              $("tr[id='TitleLst']").show();
            }				
           }
      
          //// Disabled MH.MHENDTC
					input_destination = 'MH.MHENDTC'; /*ITEMOID=destination*/
					input_origin = 'select_MH@MHONGO_'+ItemGroupRepeatKey;
			
					if(origin.name==input_origin) 
					{
						action = $("select[name='select_MH@MHONGO_"+ItemGroupRepeatKey+"']").val();

            if(action != 0 || typeof(action)=="undefined" ||action=='')
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false); 
						}
						else
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}						
					}  

					//// Attribution of key and disabled MHONGO and MHENDTC
					input_origin1 = 'select_MH@MHCAT_'+ItemGroupRepeatKey;
					input_destination1 = 'MH.MHENDTC'; /*ITEMOID=destination*/
					input_destination2 = 'MH.MHONGO'; /*ITEMOID=destination*/

					if(origin.name==input_origin1) 
					{
					//	Cle = parseInt(ItemGroupRepeatKey) + 1; IGRK commence à 1	
						Cle = parseInt(ItemGroupRepeatKey);
						//alert(Cle);
						$("input[name='text_integer_MH@MHSEQ_"+ ItemGroupRepeatKey +"']").val(Cle);
						$("input[name='text_integer_MH@MHSEQ_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
					
           action = $("select[name='select_MH@MHONGO_"+ItemGroupRepeatKey+"']").val();					
					 action1 = $("select[name='select_MH@MHCAT_"+ItemGroupRepeatKey+"']").val();

            if(action1 != 1 || typeof(action1)=="undefined" || action1=='')
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}	
						
            if(action1 != 1 || typeof(action1)=="undefined" || action1==''||action=='')
						{
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}	
						
					}												
        }
$(document).ready(function(){ 
  // Create table to compact        
  tblCol = new Array();
  tblCol["MH.MHSEQ"] = true;
  tblCol["MH.MHTERM"] = true;
  tblCol["MH.MHCONTRT"] = true;
  tblCol["MH.MHSTDTC"] = true;
  tblCol["MH.MHONGO"] = true;
  tblCol["MH.MHENDTC"] = true;
  compactItemGroup('MH',tblCol);
});        
				</script>
	    </div>  
</xsl:template>

  
</xsl:stylesheet>
