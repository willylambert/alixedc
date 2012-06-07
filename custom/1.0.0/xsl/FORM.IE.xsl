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

<!--Hide IECAT-->
<xsl:template match="div[@id='Form']">
   <xsl:copy>   
    <xsl:copy-of select="@*"/>
    <style>
      th[name='IE.IECAT'], td[name='IE.IECAT']{
        display : none;      
      }
      table td{
        color:black;
        font-weight:normal;
      }
      table th{
        color:black;
        font-weight:normal;
      }    
    </style>  
    <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Delete "Add" button because record are predifined for IG IE -->
<xsl:template match="button[@itemgroupoid='IE']">
</xsl:template>

<!--Delete "Add" button because record are predifined for IG IEX -->
<xsl:template match="button[@itemgroupoid='IEX']">
</xsl:template>

<!--Size and print format for IETEST change predifined input by label (keep input hidden for form saving) -->
<xsl:template match="select[@itemoid='IE.IETEST']">
     <xsl:attribute name="align">left</xsl:attribute>
	   <xsl:attribute name="style">width:60%;</xsl:attribute>
	   <xsl:value-of select="option[@selected='selected']/text()"/>
  <xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="option[@selected='selected']/@value"/></xsl:attribute>
   </xsl:element>
</xsl:template>

<!-- Add header and create table pour IG IE-->
<xsl:template match="form[@name='IE']">
  <xsl:if test="@position=1">
    <H3><xsl:value-of select="fieldset/legend"/></H3>
  </xsl:if>
  <xsl:call-template name="col2rowForm" select=".">
    <xsl:with-param name="ItemGroupOID" select="@name"/>	
  </xsl:call-template>
</xsl:template>

<!-- Add header and create table pour IG IEX-->
<xsl:template match="form[@name='IEX']">
  <xsl:if test="@position=1">
    <H3><xsl:value-of select="fieldset/legend"/></H3>
  </xsl:if>
  <xsl:call-template name="col2rowForm" select=".">
    <xsl:with-param name="ItemGroupOID" select="@name"/>	
  </xsl:call-template>
</xsl:template>
    
</xsl:stylesheet>