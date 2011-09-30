<?xml version="1.0" encoding="UTF-8"?>
<!--
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2011 Business & Decision Life Sciences                         *
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

<xsl:template name="pagination">
  <xsl:param name="pageNumber"/>
  <xsl:param name="recordsPerPage"/>
  <xsl:param name="numberOfRecords"/>
  <xsl:param name="url"/>
  <br/>
  <div class="pagination">
    <xsl:if test="$numberOfRecords &gt; 0">
      <xsl:value-of select="$numberOfRecords"/> records found, please select a page : 
      <span class="ui-widget-header">     
        <xsl:if test="$pageNumber &gt; 0">
          <!--<a href="{$url}&amp;page={$pageNumber -1}">&lt;&lt;&#160;</a>-->
        </xsl:if>
        <span>
          <xsl:call-template name="for.loop">
              <xsl:with-param name="i">1</xsl:with-param>
              <xsl:with-param name="page" select="$pageNumber +1"></xsl:with-param>
              <xsl:with-param name="count" select="ceiling($numberOfRecords div $recordsPerPage)"></xsl:with-param>
              <xsl:with-param name="url" select="$url"/>
          </xsl:call-template>
        </span>
        <span>
        <xsl:if test="(($pageNumber +1 ) * $recordsPerPage) &lt; ($numberOfRecords)">
          <!--<a href="{$url}&amp;page={$pageNumber +1}">&#160;&gt;&gt;</a>-->
        </xsl:if>
        </span>
      </span>
    </xsl:if>
  </div>
</xsl:template>

<xsl:template name="for.loop">
  <xsl:param name="i"/>
  <xsl:param name="count"/>
  <xsl:param name="page"/>
  <xsl:param name="url"/>
  
  <xsl:if test="$i &lt;= $count">
    <span>
      <xsl:if test="$page != $i">
        <a href="{$url}&amp;page={$i - 1}" >
          <xsl:value-of select="$i" />
        </a>
      </xsl:if>
      <xsl:if test="$page = $i">
        <xsl:attribute name="class">ui-state-highlight</xsl:attribute>
        <xsl:value-of select="$i" />
      </xsl:if>
    </span>
  </xsl:if>
  
  <xsl:if test="$i &lt;= $count">
    <xsl:call-template name="for.loop">
      <xsl:with-param name="i" select="$i + 1"/>
      <xsl:with-param name="count" select="$count"/>
      <xsl:with-param name="page" select="$page"/>
      <xsl:with-param name="url" select="$url"/>
    </xsl:call-template>
  </xsl:if>
</xsl:template>

</xsl:stylesheet>