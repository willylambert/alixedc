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
<xsl:output method="html" encoding="UTF-8" indent="no"/>

<xsl:param name="SubjectKey"/>
<xsl:param name="CurrentApp"/>

<xsl:param name="SelDT"/>
<xsl:param name="IncDT"/>
<xsl:param name="DMAGE"/>

<!--Catch all-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--AE numerotation begin @ 1-->
<xsl:template match="div[@FormOID='FORM.AE' and @FormRepeatKey='0']">
  <div style="display:none">&#160;</div>
</xsl:template>

<xsl:template match="h3[@id='visit_1_0']/a/text()">
  <xsl:value-of select="concat(string(.),' [',$SelDT,']')"/>
</xsl:template>

<xsl:template match="h3[@id='visit_2_0']/a/text()">
    <xsl:value-of select="concat(string(.),' [',$IncDT,']')"/>
</xsl:template>

<xsl:template match="h3[@StudyEventOID='FW']/a/text()">
    <xsl:value-of select="concat(string(.),' #',../../@StudyEventRepeatKey)"/>
</xsl:template>

<xsl:template match="div[@FormOID='FORM.AE']/a/text()">
  <xsl:variable name="pos" select="count(../../preceding-sibling::*) + 1"/>
  <xsl:value-of select="concat(string(.),' ',$pos)"/>
</xsl:template>

<xsl:template match="div[@id='subjectMenu']">
  <xsl:if test="$SubjectKey!='BLANK'">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
       <!--Button to add Follow up visit--> 
       <h4 id="btnAddFollowUp" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ui-state-highlight">
         <xsl:variable name="newStudyEventRepeatKey"><xsl:value-of select="count(h3[@StudyEventOID='FW' and @StudyEventRepeatKey!='0'])+1"/></xsl:variable>
         <xsl:variable name="url">index.php?menuaction=<xsl:value-of select="$CurrentApp"/>.uietude.subjectInterface&amp;action=addVisit&amp;SubjectKey=<xsl:value-of select="$SubjectKey"/>&amp;StudyEventOID=FW&amp;StudyEventRepeatKey=<xsl:value-of select="$newStudyEventRepeatKey"/>&amp;FormOID=FORM.SV&amp;FormRepeatKey=0</xsl:variable>
         <a href="{$url}">Add Follow Up Visit</a>
       </h4>
   </xsl:copy> 
  </xsl:if>
</xsl:template>
        
<!--Link to add AE-->
<xsl:template match="div[preceding-sibling::h3[@id='visit_AE_0']][1]">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
    <div class="FormTitle">
      <span>&#160;</span>
      <xsl:variable name="newFormRepeatKey"><xsl:value-of select="count(../..//div[@FormOID='FORM.AE' and @FormRepeatKey!='0'])+1"/></xsl:variable>
      <xsl:variable name="url">index.php?menuaction=<xsl:value-of select="$CurrentApp"/>.uietude.subjectInterface&amp;action=addForm&amp;SubjectKey=<xsl:value-of select="$SubjectKey"/>&amp;StudyEventOID=AE&amp;StudyEventRepeatKey=0&amp;FormOID=FORM.AE&amp;FormRepeatKey=<xsl:value-of select="$newFormRepeatKey"/></xsl:variable>
      <a href="{$url}">Add a new Adverse Event...</a>
    </div>
  </xsl:copy>   
</xsl:template>

</xsl:stylesheet>