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

<xsl:param name="GENOTYP"/>
	
<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Add "month" label -->
<xsl:template match="input[@name='text_mm_YN@VTSTDTC_0']">
		month:
    <xsl:copy>
		<xsl:copy-of select="@*"/>  
	  <xsl:value-of select="."/>
  </xsl:copy>  
</xsl:template>

<!--Add "day" label -->
<xsl:template match="input[@name='text_dd_YN@VTSTDTC_0']">
		day:
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
	  <xsl:value-of select="."/>
  </xsl:copy>  
</xsl:template>

<!--Delete  information-->
<xsl:template match="span[(@class='optionalText inputItem' or @class='optionalText') and (../@name = 'YN.VTSTDTC')]">
</xsl:template>

<!--Add help label-->
<xsl:template match="tr[@name='YN.GENSMA']">  
  <tr id="TitleGeno"><td colspan="5">
     <h3>Genotyping</h3>
    </td>
   </tr>
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Add tabs -->
<xsl:template match="td[../@name='YN.SNM1MUT' and @class='ItemDataLabel underCondition']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
		&#160;&#160;&#160;	
	  <xsl:value-of select="."/>
  </xsl:copy>  
	</xsl:template>

<!--Add help label -->
<xsl:template match="td[../@name='YN.GENSMA' and @class='ItemDataLabel']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
If SMN1 genotyping done between V-1 and V0
  </xsl:copy>
</xsl:template>

<!-- Resizing -->
<xsl:template match="input[@itemoid='YN.SNM1MUT']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:attribute name="style">width:250px;</xsl:attribute>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{  
          //// grisage YN.SNM1DEL et YN.GENDTC
					input_destination1 = 'YN.GENDTC'; /*ITEMOID=destination*/	          
					input_destination3 = 'YN.SNM1DEL'; /*ITEMOID=destination*/
					input_origin1 = 'select_YN@GENSMA_0';
			
					if(origin.name==input_origin1) 
					{
						action1 = $("select[name='select_YN@GENSMA_0']").val();
			
						if(typeof(action1)=="undefined"||action1!=1||action1=='')
						{
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
              freezeFields(input_destination3,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
              freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination3,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}	        
           
				//// Disable YN.SNM1MUT 
					input_destination = 'YN.SNM1MUT'; /*ITEMOID=destination*/
					input_origin = 'select_YN@SNM1DEL_0';
					input_origin1 = 'select_YN@GENSMA_0';
			
					if(origin.name==input_origin||origin.name==input_origin1) 
					{
						action = $("select[name='select_YN@SNM1DEL_0']").val();
						action1 = $("select[name='select_YN@GENSMA_0']").val();
			
						if(typeof(action)=="undefined"||typeof(action1)=="undefined"||action1==''||action1!=1)
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
						switch (action)
							{
								case '0':
									freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
									break;
								case '1':
									freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);								
									break;
								default:
									freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
									break;
							}			
						}
					}	

				
					//// Disable YN.VTSTDTC and YN.VTTIME and YN.VTTYPE
					input_destination14 = 'YN.VTSTDTC'; /*ITEMOID=destination*/		
					input_destination15 = 'YN.VTTIME'; /*ITEMOID=destination*/		
					input_destination16 = 'YN.VTTYPE'; /*ITEMOID=destination*/		
					input_origin11 = 'select_YN@VTYN_0';
			
					if(origin.name==input_origin11) 
					{
						action11 = $("select[name='select_YN@VTYN_0']").val();

						if(typeof(action11)=="undefined"||action11!=1||action11=='')
						{
							freezeFields(input_destination14,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination15,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination16,ItemGroupOID,ItemGroupRepeatKey,true,false,false);						
						}
						else
						{
							freezeFields(input_destination14,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination15,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination16,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}							
					// hide genotyping
					  genotyp = '<xsl:value-of select="$GENOTYP"/>';			  			  
            if(genotyp==1){
              $("tr[id='TitleGeno']").hide();
              $("tr[name='YN.GENSMA']").hide();
              $("tr[name='YN.SNM1DEL']").hide();
              $("tr[name='YN.SNM1MUT']").hide();
              $("tr[name='YN.GENDTC']").hide();
            }
            else
            {
              $("tr[id='TitleGeno']").show();
              $("tr[name='YN.GENSMA']").show();
              $("tr[name='YN.SNM1DEL']").show();
              $("tr[name='YN.SNM1MUT']").show();
              $("tr[name='YN.GENDTC']").show();
            }				
					
				}		
	</script>
	    </div>  
</xsl:template>
   
</xsl:stylesheet>