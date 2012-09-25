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

<!-- Creation of table to compact repeated ItemGroup -->

<xsl:template name="col2rowTable">
<tr>
  <xsl:attribute name="id">_<xsl:value-of select="substring-after(./tr[1]/@id,'_')" /></xsl:attribute>
  <xsl:for-each select="tr/td[@class='ItemDataInput']">
    <td id="{../@id}" name="{../@name}" class="ItemDataInput"><xsl:apply-templates/></td>
    <xsl:apply-templates select="../td[@class='ItemDataAnnot']"/>
    <xsl:apply-templates select="../td[@class='ItemDataQuery']"/> 
  </xsl:for-each>
</tr> 
</xsl:template>  

<xsl:template name="col2rowForm">
  <xsl:param name="ItemGroupOID" />
  <xsl:if test="@position=1">
    <h3><xsl:copy-of select="h3"/></h3>
    <table name="{@name}" class="ItemGroup" inline="yes">
      <thead>
        <tr>
          <xsl:for-each select=".//td[@class='ItemDataLabel' or @class='ItemDataLabel underCondition']">
            <th colspan="3" name="{../@name}">
          <xsl:value-of select="string(.)"/>
            </th>
          </xsl:for-each>
        </tr>
      </thead>
      <tbody>
        <xsl:for-each select="../form[@name=$ItemGroupOID]/table">
          <form>
            <xsl:copy-of select="../@*"/>
            <xsl:apply-templates select="../input"/>
            <xsl:call-template name="col2rowTable" select="."/>
          </form>
        </xsl:for-each>
      </tbody>        
    </table>      
  </xsl:if>
</xsl:template>
	
</xsl:stylesheet>
    
