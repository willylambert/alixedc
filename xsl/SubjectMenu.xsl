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
<xsl:output method="html" encoding="UTF-8" indent="no"/>

<xsl:param name="SubjectKey"/>
<xsl:param name="CurrentApp"/>
<xsl:param name="AllowLock"/>

<xsl:template match="StudyEventData">
  <xsl:variable name="visitId">visit_<xsl:value-of select="@StudyEventOID"/>_<xsl:value-of select="@StudyEventRepeatKey"/></xsl:variable>
  <h3 id="{$visitId}" class="ui-accordion-header ui-helper-reset ui-state-default ui-corner-all">
    <!--span class="ui-icon ui-icon-triangle-1-e">&#160;</span-->
    <span class="StudyEventStatus StudyEventStatus{@Status}">&#160;</span>
    <xsl:if test="@Title!=''">
      <a href="#">
        <xsl:value-of select="@Title"/>
      </a>
    </xsl:if>
  </h3>
  <xsl:if test="count(FormData)>0">
    <div><xsl:apply-templates/></div>
  </xsl:if>
</xsl:template>

<xsl:template match="FormData">
  <xsl:variable name="formId">visit_<xsl:value-of select="../@StudyEventOID"/>_<xsl:value-of select="../@StudyEventRepeatKey"/>_form_<xsl:value-of select="@FormOID"/>_<xsl:value-of select="@FormRepeatKey"/></xsl:variable>
  <div id="{$formId}" class="FormTitle TransactionType{@TransactionType}" StudyEventOID="{./../@StudyEventOID}" StudyEventRepeatKey="{../@StudyEventRepeatKey}" FormOID="{@FormOID}" FormRepeatKey="{@FormRepeatKey}">
    <span class="FormStatus FormStatus{@Status}">&#160;</span>
    <xsl:variable name="url">index.php?menuaction=<xsl:value-of select="$CurrentApp"/>.uietude.subjectInterface&amp;action=view&amp;SubjectKey=<xsl:value-of select="$SubjectKey"/>&amp;StudyEventOID=<xsl:value-of select="./../@StudyEventOID"/>&amp;StudyEventRepeatKey=<xsl:value-of select="../@StudyEventRepeatKey"/>&amp;FormOID=<xsl:value-of select="@FormOID"/>&amp;FormRepeatKey=<xsl:value-of select="@FormRepeatKey"/></xsl:variable>
    <a>
      <xsl:attribute name="href"><xsl:value-of select="$url"/></xsl:attribute>      
      <xsl:if test="@TransactionType='Remove'">
        <xsl:attribute name="onClick">return false;</xsl:attribute>
      </xsl:if>
      <xsl:value-of select="@Title"/>
    </a>
    <div>
      <xsl:attribute name="class">FormLock FormLock<xsl:value-of select="@Status"/></xsl:attribute>
      <xsl:if test="@Status='FILLED' or @Status='FROZEN'">
        <xsl:if test="$AllowLock='true'">
          <xsl:variable name="urlLock">index.php?menuaction=<xsl:value-of select="$CurrentApp"/>.uietude.lockInterface&amp;action=view&amp;SubjectKey=<xsl:value-of select="$SubjectKey"/>&amp;StudyEventOID=<xsl:value-of select="./../@StudyEventOID"/>&amp;StudyEventRepeatKey=<xsl:value-of select="../@StudyEventRepeatKey"/>&amp;FormOID=<xsl:value-of select="@FormOID"/>&amp;FormRepeatKey=<xsl:value-of select="@FormRepeatKey"/>&amp;FormStatus=<xsl:value-of select="@Status"/></xsl:variable>
          <xsl:attribute name="onClick">changeLockStatus("<xsl:value-of select="$urlLock"/>","<xsl:value-of select="@Title"/>","<xsl:value-of select="./status"/>");</xsl:attribute>
        </xsl:if>
      </xsl:if>&#160;
    </div>
    <span class="FormPostIt">&#160;</span>
    <span class="FormDeviation">&#160;</span>
  </div>
</xsl:template>

<xsl:template match="SubjectData">
  <div id="subjectMenu" class="ui-accordion ui-widget ui-helper-reset ui-accordion-icons">
    <xsl:apply-templates/>
    <script>
      function changeLockStatus(formURL,formLabel,currentFormStatus){
        if(currentFormStatus=="FILLED"){
          lockLabel = "Do you want to lock the form "+formLabel+ "?";
        }else{
          lockLabel = "Do you want to unlock the form "+formLabel+ "?";
        }
        if(confirm(lockLabel)){
          document.location = formURL;
        }
      }
    </script>
  </div>
</xsl:template>

</xsl:stylesheet>
