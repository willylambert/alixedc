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

<xsl:param name="ItemGroupRepeatKey"/>
	
<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Add label -->
<xsl:template match="td[../@name='DS.INVNAM' and @class='ItemDataLabel']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>
I, the undersigned Dr
</xsl:copy>  	
</xsl:template>

<!--Add help label-->
<xsl:template match="tr[@name='DS.INVNAM']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
   <tr><td colspan="2"></td><td colspan="3" class ="ItemDataLabel">
     certify I have obtained the patient's informed consent signed.
    </td>
   </tr>
</xsl:template>

<!-- Resizing DSTERM field -->
<xsl:template match="input[@itemoid='DS.DSTERM']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:attribute name="style">width:250px;</xsl:attribute>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Dynamism -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{   
          input_destination = 'DM.PUBES'; /*ITEMOID=destination*/
					input_origin = 'radio_DM@SEX_0';
			
					if(origin.name==input_origin || loading) 
					{
            action = $("input[name='radio_DM@SEX_0']:checked").val();
            if(action==1 || typeof(action)=="undefined")
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}
					
					input_destination1 = 'DM.CONTR'; /*ITEMOID=destination*/
					input_origin = 'radio_DM@SEX_0';
					input_origin1 = 'radio_DM@PUBES_0';
			
					if(origin.name==input_origin || origin.name==input_origin1 || loading) 
					{
						action = $("input[name='radio_DM@SEX_0']:checked").val();
						action1 = $("input[name='radio_DM@PUBES_0']:checked").val();

						if(action == 1||typeof(action)=="undefined"||typeof(action1)=="undefined"||action1!=1)
						{
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}		

					input_destination2 = 'DM.CONTROTH'; /*ITEMOID=destination*/
					input_origin = 'radio_DM@SEX_0';
					input_origin1 = 'radio_DM@PUBES_0';
					input_origin2 = 'select_DM@CONTR_0';
			
					if(origin.name==input_origin || origin.name==input_origin1 || origin.name==input_origin2 || loading) 
					{
						action = $("input[name='radio_DM@SEX_0']:checked").val();
						action1 = $("input[name='radio_DM@PUBES_0']:checked").val();
						action2 = $("select[name='select_DM@CONTR_0']").val();
						
						if(action == 1||typeof(action)=="undefined"||typeof(action1)=="undefined"||action1!=1||typeof(action2)=="undefined"||action2!=5)
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}						
				}

	</script>
  </div>  
</xsl:template>
    
</xsl:stylesheet>