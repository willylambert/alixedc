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

<xsl:param name="FormRepeatKey"/>

<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Mask all coding items -->
<xsl:template match="input[@itemoid='AE.AELLT_C' or @itemoid='AE.AELLT_N' or @itemoid='AE.AEPT_C' or @itemoid='AE.AEDECOD' or @itemoid='AE.AEHLGT_N' 
                        or @itemoid='AE.AESOC_C' or @itemoid='AE.AEBODSYS' or @itemoid='AE.AEHLT_C' or @itemoid='AE.AEHLT_N' or @itemoid='AE.AEHLGT_C' or @itemoid='AE.MEDDRA_V']">
  <xsl:copy>
    <xsl:copy-of select="@* [name()!='type']"/>
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Keep only coding input items -->
<xsl:template match="tr[@name='AE.AELLT_C' or @name='AE.AELLT_N' or @name='AE.AEPT_C' or @name='AE.AEDECOD' or @name='AE.AEHLGT_N' 
    or @name='AE.AESOC_C' or @name='AE.AEBODSYS' or @name='AE.AEHLT_C' or @name='AE.AEHLT_N' or @name='AE.AEHLGT_C' or @name='AE.MEDDRA_V']">
   <xsl:apply-templates select="td/input"/> 
</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
		<script language="JavaScript">
		
			function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
			{  
	         //// Attribution de la clé AESEQ
					input_origin1 = 'text_text_AE@AETERM_0';

					if(origin.name==input_origin1) 
					{
					var FormRepeatKey = '<xsl:value-of select="$FormRepeatKey"/>';
					
						Cle = parseInt(FormRepeatKey);
						$("input[name='text_integer_AE@AESEQ_0']").val(Cle);
						$("input[name='text_integer_AE@AESEQ_0']").attr("readonly",true);
					}
			}		
	 </script>
  </div>  
</xsl:template>
   
</xsl:stylesheet>