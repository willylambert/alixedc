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
  <xsl:param name="METADATAVERSION"/>
  
  <xsl:template match="MetaData">
    <html>
      <head>
        <title>
          <xsl:value-of select="$STUDYNAME" /> - Annotated Case Report Form
        </title>
      </head>
      <body>
        <center>
          <br /><br /><br /><br /><br /><br /><br /><br /><br /><br />
          <font face="Arial" size="32"><b>Annotated Case Report Form</b></font>
          <br />
          <br />
          <font face="Arial" size="12">Version <xsl:value-of select="$METADATAVERSION" /></font>
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
    
    <table bgcolor="#cccccc" border="1" width="100%">
      <tr>
        <td>
          <font size="6"><b><xsl:value-of select="@Description" />&#0160;<font color="blue">[<xsl:value-of select="@OID" />]</font></b></font>
        </td>
      </tr>
    </table>
    
    <font size="2">
      <xsl:apply-templates/>
    </font>
  </xsl:template>
  
  <xsl:template match="Form">
    <br />
    <table bgcolor="#ffffff" border="0" width="100%">
      <tr>
        <td>
          <font size="4"><b><xsl:value-of select="@Description" />&#0160;<font color="blue">[<xsl:value-of select="@OID" />]</font></b></font>
        </td>
      </tr>
    </table>
    <xsl:apply-templates/>
  </xsl:template>
  
  <!--Metadata for ItemGroup not repeated when there are no predifened data-->
  <xsl:template match="ItemGroup[@Repeating='No']">
    <xsl:variable name="ItemGroup" select="." />
    <xsl:variable name="NbItems" select="count(Item)" />
    <xsl:variable name="NbCodeLists" select="count(CodeList)" />
    <xsl:variable name="NbValues" select="count(ItemGroupData/ItemData)" />
    <xsl:variable name="MaxCodeListValues" select="count(CodeList[@OID=$ItemGroup/@CodeListMaxItemsOID]/CodeListItem)" />
    
    <xsl:text disable-output-escaping="yes">&lt;!--</xsl:text> NEED <xsl:value-of select="2 + number($NbItems) + number($MaxCodeListValues)" /> <xsl:text disable-output-escaping="yes">--&gt;</xsl:text>
    
    <table border="1" width="100%">
      <tr bgcolor="black">
        <td>
          <b><font color="white"><xsl:value-of select="@Description" />&#0160;<font color="yellow">[<xsl:value-of select="@OID" />]</font></font></b>
        </td>
      </tr>
    </table>
    <table border="1" width="100%">
      <!--Items details for this ItemGroup-->
      <xsl:for-each select="Item">
        <xsl:variable name="Item" select="." />
        <tr>
          <td>
            <xsl:value-of select="@Question" />
          </td>
          <xsl:if test="$NbValues>0"> <!--This cell (in a second column) is not needed if there is no Values in this ItemGroup-->
            <td>
              <xsl:choose> <!--Display the Value if it exists, otherwise display an blank space -->
                <xsl:when test="$ItemGroup/ItemGroupData/ItemData[@ItemOID=$Item/@OID]">
                  <font color="#5f9ea0"><xsl:value-of select="$ItemGroup/ItemGroupData/ItemData[@ItemOID=$Item/@OID]" /></font>
                </xsl:when>
                <xsl:otherwise>&#0160;
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </xsl:if>
          <td>
            <font color="blue"><xsl:value-of select="@OID" /></font>
          </td>
          <xsl:if test="$NbCodeLists>0"> <!--This last cell (in a last column) is not needed if there is no CodeList in this ItemGroup-->
            <td>
              <xsl:choose> <!--Display the CodeList OID if it exists, otherwise display an blank space -->
                <xsl:when test="@CodeListOID">
                  <font color="green"><xsl:value-of select="@CodeListOID" /></font>
                </xsl:when>
                <xsl:otherwise>&#0160;
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </xsl:if>
        </tr>
      </xsl:for-each>
    </table>
    
    <!--CodeLists details for this ItemGroup-->
    <xsl:if test="$NbCodeLists>0">
      <table border="1" width="100%">
        <tr valign="top">
          <xsl:for-each select="CodeList">
            <td>
              <font color="green"><xsl:value-of select="@OID" /></font>:
              <xsl:for-each select="CodeListItem">
                <br />
                <xsl:value-of select="@CodedValue" />=<xsl:value-of select="@Decode" />
              </xsl:for-each>
            </td>
          </xsl:for-each>
        </tr>
      </table>
    </xsl:if>
    
    <!--Add space between successive ItemGroups-->
    <br />
    
  </xsl:template>
  
  <!--Metadata for ItemGroup repeated-->
  <xsl:template match="ItemGroup[@Repeating='Yes']">
    <xsl:variable name="ItemGroup" select="." />
    <xsl:variable name="NbItems" select="count(Item)" />
    <xsl:variable name="NbCodeLists" select="count(CodeList)" />
    <xsl:variable name="NbItemGroupDatas" select="count(ItemGroupData)" />
    <xsl:variable name="MaxCodeListValues" select="count(CodeList[@OID=$ItemGroup/@CodeListMaxItemsOID]/CodeListItem)" />
    
    <xsl:text disable-output-escaping="yes">&lt;!--</xsl:text> NEED <xsl:value-of select="5 + number($NbItemGroupDatas) + number($MaxCodeListValues)" /> <xsl:text disable-output-escaping="yes">--&gt;</xsl:text>
    
    <!--Use of Landscape mode if too much items-->
    <xsl:if test="$NbItems>6">
      <xsl:text disable-output-escaping="yes">&lt;!-- MEDIA LANDSCAPE YES --&gt;</xsl:text>
    </xsl:if>
    <!--Reduce font size if too much items-->
    <font>
      <xsl:attribute name="size">
      <xsl:if test="$NbItems&gt;12 and $NbItems&lt;=16">1</xsl:if>
      <xsl:if test="$NbItems&gt;16">0</xsl:if>
      </xsl:attribute>
    
    <table border="1" width="100%">
      <tr bgcolor="black">
        <td>
          <b><font color="white"><xsl:value-of select="@Description" />&#0160;<font color="yellow">[<xsl:value-of select="@OID" />]</font></font></b>
        </td>
      </tr>
    </table>
    <table border="1" width="100%">
      <tr>
        <!--Items questions for this ItemGroup-->
        <xsl:for-each select="Item">
          <td>
            <xsl:value-of select="@Question" />
          </td>
        </xsl:for-each>
      </tr>
      <!--Items values for predefined data-->
      <xsl:for-each select="ItemGroupData">
        <xsl:variable name="ItemGroupData" select="." />
        <tr>
          <!--Items for this ItemGroup-->
          <xsl:for-each select="$ItemGroup/Item">
            <xsl:variable name="Item" select="." />
            <td>
              <xsl:choose> <!--Display the value if it exists, otherwise display an blank space -->
                <xsl:when test="$ItemGroupData/ItemData[@ItemOID=$Item/@OID]">
                  <font color="#5f9ea0"><xsl:value-of select="$ItemGroupData/ItemData[@ItemOID=$Item/@OID]" /></font>
                </xsl:when>
                <xsl:otherwise>&#0160;
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </xsl:for-each>
        </tr>
      </xsl:for-each>
        <!--Items OID for this ItemGroup-->
      <tr>
        <xsl:for-each select="$ItemGroup/Item">
          <td>
            <font color="blue"><xsl:value-of select="@OID" /></font>
          </td>
        </xsl:for-each>
      </tr>
        <!--Items codelists for this ItemGroup-->
      <xsl:if test="$NbCodeLists>0"> <!--This last row is not needed if there is no CodeList in this ItemGroup-->
        <tr>
          <xsl:for-each select="Item">
            <td>
              <xsl:choose> <!--Display the CodeList OID if it exists, otherwise display an blank space -->
                <xsl:when test="@CodeListOID">
                  <font color="green"><xsl:value-of select="@CodeListOID" /></font>
                </xsl:when>
                <xsl:otherwise>&#0160;
                </xsl:otherwise>
              </xsl:choose>
            </td>
          </xsl:for-each>
        </tr>
      </xsl:if>
    </table>
    
    <!--CodeLists details for this ItemGroup-->
    <xsl:if test="$NbCodeLists>0">
      <table border="1" width="100%">
        <tr valign="top">
          <xsl:for-each select="CodeList">
            <td>
              <font color="green"><xsl:value-of select="@OID" /></font>:
              <xsl:for-each select="CodeListItem">
                <br />
                <xsl:value-of select="@CodedValue" />=<xsl:value-of select="@Decode" />
              </xsl:for-each>
            </td>
          </xsl:for-each>
        </tr>
      </table>
    </xsl:if>
    
    <!--Add space between successive ItemGroups-->
    <br />
    
    <!--Use of Landscape mode if too much items-->
    <xsl:if test="$NbItems>6">
      <xsl:text disable-output-escaping="yes">&lt;!-- MEDIA LANDSCAPE NO --&gt;</xsl:text>
    </xsl:if>
    <!--Reduce font size if too much items-->
    </font>
    
  </xsl:template>
  
</xsl:stylesheet>