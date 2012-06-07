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

<!--Catch all non treated tags, print without treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Mask CMINC_C et CMDECOD et CMATC_C et CMATC_N-->
<xsl:template match="tr[@name='CM.CMINC_C' or @name='CM.CMDECOD' or @name='CM.CMATC_C' or @name='CM.CMATC_N']">
   <xsl:copy>
        <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">display:none;</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Reduce field length CMINDC-->
<xsl:template match="input[@itemoid='CM.CMINDC']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:attribute name="style">width:550px;</xsl:attribute>
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
          //// grisage CM.CMENDTC
					input_destination = 'CM.CMENDTC'; /*ITEMOID=destination*/
					input_origin = 'radio_CM@CMONGO_'+ItemGroupRepeatKey;
			
					if(origin.name==input_origin) 
					{
						action = $("input[name='radio_CM@CMONGO_"+ItemGroupRepeatKey+"']:checked").val();
            
            if(action != 0 || typeof(action)=="undefined" ||action=='')
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false); 
						}
						else
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}						
					}
          
          //// Attribution de la clé
					input_origin1 = 'text_text_CM@CMTRT_'+ItemGroupRepeatKey;

					if(origin.name==input_origin1) 
					{
					//	Cle = parseInt(ItemGroupRepeatKey) + 1; IGRK commence à 1	
						Cle = parseInt(ItemGroupRepeatKey);
						
						$("input[name='text_integer_CM@CMSEQ_"+ ItemGroupRepeatKey +"']").val(Cle);
						$("input[name='text_integer_CM@CMSEQ_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
          }
				}
      $(document).ready(function(){ 
        //// Create compact table for itemgroup repeat
      	compactItemGroup('CM');					
      });
	</script>
	    </div>  
</xsl:template>
      
</xsl:stylesheet>