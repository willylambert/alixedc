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
	
<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{  
        //// Disable YN.VTSTDTC et YN.VTTIME et YN.VTTYPE
					input_destination = 'YN.VTSTDTC'; /*ITEMOID=destination*/
					input_destination1 = 'YN.VTTIME'; /*ITEMOID=destination*/
          input_destination2 = 'YN.VTTYPE'; /*ITEMOID=destination*/	          					
					input_origin = 'select_YN@VTYN_0';
								
					if(origin.name==input_origin) 
					{
						action = $("select[name='select_YN@VTYN_0']").val();
			
						if(typeof(action)=="undefined"||action==''||action!=1)
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}						
				}		
	</script>
	    </div>  
</xsl:template>
   
</xsl:stylesheet>