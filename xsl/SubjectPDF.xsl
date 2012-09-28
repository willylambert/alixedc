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
<xsl:output method="xml" encoding="UTF-8" indent="yes"/>
  <xsl:param name="STUDYNAME"/>
  <xsl:param name="siteId"/>
  <xsl:param name="subjId"/>
  <xsl:param name="siteName"/>
  <xsl:param name="DateOfGeneration"/>
  
  <xsl:template match="SubjectData">
    <html>
      <head>
        <title>
          <xsl:value-of select="$STUDYNAME" /> - Site <xsl:value-of select="$siteId" /> (<xsl:value-of select="$siteName" />) Subject <xsl:value-of select="$subjId" />
        </title>
      </head>
      <body>
        <center>
          <br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
          <font face="Arial" size="32"><b>Case Report Form</b></font>
          <br /><br />
          <font face="Arial" size="3">Date of generation : <xsl:value-of select="$DateOfGeneration" /></font>
        </center>
        <font face="Arial">
          <xsl:apply-templates/>
        </font>
      </body>
    </html>
  </xsl:template>
  
  <xsl:template match="StudyEvent">
    
    <xsl:if test="position()>1">
      <xsl:text disable-output-escaping="yes">&lt;!-- PAGE BREAK --&gt;</xsl:text>
    </xsl:if>
    
    <h1>
      <font size="6">
        <xsl:value-of select="@Title" /><font size="0" color="white">  / Date of generation : <xsl:value-of select="$DateOfGeneration" /></font>
      </font>
    </h1>
    
    <font size="2">
      <xsl:apply-templates/>
    </font>
  </xsl:template>
  
  <xsl:template match="Form">
    <h2>
      <font size="4">
        <xsl:value-of select="@Title" />
      </font>
    </h2>
    <xsl:apply-templates/>
  </xsl:template>
  
  <xsl:template match="ItemGroup[@Repeating='No']">
    <xsl:variable name="NbItems" select="count(Item)" />
    
    <xsl:text disable-output-escaping="yes">&lt;!--</xsl:text> NEED <xsl:value-of select="1 + 1.3 * (1 + number($NbItems))" /> <xsl:text disable-output-escaping="yes">--&gt;</xsl:text>
    
    <table border="1" width="100%">
      <tr>
        <td colspan="2">
          <b>
            <xsl:value-of select="@Title" />
          </b>
        </td>
      </tr>
      <xsl:for-each select="Item">
        <xsl:variable name="OID" select="@OID" />
        <xsl:variable name="Value" select="../ItemGroupData/ItemData[@OID=$OID]/@Value" />
        <tr>
          <td>
            <xsl:value-of select="@Title" />
          </td>
          <td>
            <xsl:choose>
              <xsl:when test="count(CodeList/CodeListItem)>0">
                <xsl:variable name="Decode" select="CodeList/CodeListItem[@CodedValue=$Value]/@Decode" />
                <xsl:value-of select="$Decode" />
                <xsl:if test="$Decode='' or not($Decode)">&#0160;</xsl:if>
              </xsl:when>
              <xsl:otherwise>
                <xsl:variable name="Unit" select="MeasurementUnit/MeasurementUnitItem/@Symbol" />
                <xsl:choose>
                    <xsl:when test="$Value!='' and(@DataType='date' or @DataType='partialDate')">
                    <xsl:value-of select="substring($Value,9,2)"/>/<xsl:value-of select="substring($Value,6,2)"/>/<xsl:value-of select="substring($Value,1,4)"/>
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:value-of select="$Value" />
                  </xsl:otherwise>
                </xsl:choose>
                <xsl:if test="$Value='' or not($Value)">&#0160;</xsl:if>
                <xsl:if test="not($Value='' or not($Value)) and not($Unit='' or not($Unit))">&#0160;<xsl:value-of select="$Unit" /></xsl:if>
              </xsl:otherwise>
            </xsl:choose>
          </td>
        </tr>
      </xsl:for-each>
    </table>
    <br />
  </xsl:template>
  
  <xsl:template match="ItemGroup[@Repeating='Yes']">
    <xsl:variable name="NbItems" select="count(Item)" />
    <xsl:variable name="NbItemGroups" select="count(ItemGroupData)" />
    
    <xsl:text disable-output-escaping="yes">&lt;!--</xsl:text> NEED <xsl:value-of select="2 + 1.5 * (2 + number($NbItemGroups))" /> <xsl:text disable-output-escaping="yes">--&gt;</xsl:text>
    
    <table border="1" width="100%">
      <tr>
        <td colspan="{$NbItems}">
          <b>
            <xsl:value-of select="@Title" />
          </b>
        </td>
      </tr>
      <tr>
        <xsl:for-each select="Item">
          <td>
            <b>
              <xsl:value-of select="@Title" />
            </b>
          </td>
        </xsl:for-each>
      </tr>
      <xsl:for-each select="ItemGroupData">
        <xsl:variable name="ItemGroupRepeatKey" select="@ItemGroupRepeatKey" />
        <tr>
          <xsl:for-each select="../Item">
            <xsl:variable name="OID" select="@OID" />
            <xsl:variable name="Value" select="../ItemGroupData[@ItemGroupRepeatKey=$ItemGroupRepeatKey]/ItemData[@OID=$OID]/@Value" />
            <td>
              <xsl:choose>
                <xsl:when test="count(CodeList/CodeListItem)>0">
                  <xsl:variable name="Decode" select="CodeList/CodeListItem[@CodedValue=$Value]/@Decode" />
                  <xsl:value-of select="$Decode" />
                  <xsl:if test="$Decode='' or not($Decode)">&#0160;</xsl:if>
                </xsl:when>
                <xsl:otherwise>
                  <xsl:variable name="Unit" select="MeasurementUnit/MeasurementUnitItem/@Symbol" />
                  <xsl:choose>
                    <xsl:when test="$Value!='' and(@DataType='date' or @DataType='partialDate')">
                      <xsl:value-of select="substring($Value,9,2)"/>/<xsl:value-of select="substring($Value,6,2)"/>/<xsl:value-of select="substring($Value,1,4)"/>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:value-of select="$Value" />
                    </xsl:otherwise>
                  </xsl:choose>
                  <xsl:if test="$Value='' or not($Value)">&#0160;</xsl:if>
                  <xsl:if test="not($Value='' or not($Value)) and not($Unit='' or not($Unit))">&#0160;<xsl:value-of select="$Unit" /></xsl:if>
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </xsl:for-each>
        </tr>
      </xsl:for-each>
    </table>
    <br />
  </xsl:template>
  
</xsl:stylesheet>