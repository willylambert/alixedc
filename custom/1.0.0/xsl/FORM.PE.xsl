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

<!--Hide label PETEST-->
<xsl:template match="tr[@name='PE.PETEST' and td/@class ='ItemDataLabel']">
</xsl:template>

<!-- Delete "Add" button because parameters are predifined -->
<xsl:template match="button[@itemgroupoid='PE']">
</xsl:template>

<!--Add help label -->
<xsl:template match="td[../@name='PE.PEOTH' and @class='ItemDataLabel']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>
      Other Examination
  </xsl:copy>  	
</xsl:template>

<!-- Resizing of PEREASND -->
<xsl:template match="input[@itemoid='PE.PEREASND']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">width:200px;</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Change PETER format -->
<xsl:template match="select[@itemoid='PE.PETEST']">
	<xsl:attribute name="align">left</xsl:attribute>
	<xsl:value-of select="option[@selected='selected']/text()"/>
	<xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="option[@selected='true']/@value"/></xsl:attribute>
   </xsl:element>
</xsl:template>

<!--  Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
      function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
      {
        // grisage PE.PEREASND
        input_destination = 'PE.PEREASND'; /*ITEMOID=destination*/
        input_destination1 = 'PE.PECLSIG'; /*ITEMOID=destination*/
          
        if($(origin).attr('itemoid')=='PE.PERES'){
          action = $(origin).val();
          
          if(action == 1 || typeof(action)=="undefined" || action=='')
  				{
            freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
            freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);   
  				}
  				else
  				{
  				  if(action == 3)
  					{
  					 freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
  					 freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
  					}
  					else
  					{
  					 freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
  					 freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
  					}	   
          }  
        }  						
      }
      // compact item group repeat PEO
      compactItemGroup('PEO');
      </script>
  </div>
</xsl:template> 

<!-- Create table -->

<xsl:template match="form[@name='PE']">
  <xsl:call-template name="col2rowForm" select=".">
    <xsl:with-param name="ItemGroupOID" select="@name"/>
  </xsl:call-template>
</xsl:template>
			
</xsl:stylesheet>