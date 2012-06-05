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

<xsl:param name="SiteId"/>
<xsl:param name="SiteName"/>
<xsl:param name="SubjectKey"/>

<!--Catch all non treated tags, print them without processing-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Add help label -->
<xsl:template match="tr[@name='ENROL.COUNTID']">
  <xsl:if test="$SubjectKey='' or $SubjectKey='BLANK'">
   <tr><td></td><td colspan="3" class ="ItemDataLabel">
     To enrol a new patient :
     <ul>
     <li>
     Complete Patient's Initials (if needed)
     </li>
     <li>
      Click Enroll Button
     </li>
     </ul> 
    </td>
   </tr>
  </xsl:if>    
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<xsl:template match="input[@name='text_string_ENROL@SITEID_0']">
  <xsl:copy>
    <xsl:attribute name="value">
      <xsl:value-of select="$SiteId"/>
    </xsl:attribute>
    <xsl:attribute name="readonly">readonly</xsl:attribute>
    <xsl:for-each select="@*">
      <xsl:if test="name()!='value'">
        <xsl:attribute name="{name()}">
          <xsl:value-of select="string()"/>
        </xsl:attribute>
      </xsl:if>
    </xsl:for-each>
  </xsl:copy>
</xsl:template>

  <!-- Format readonly fields -->
<xsl:template match="td[(../@name='ENROL.STUDYID' or ../@name='ENROL.SUBJID' or ../@name='ENROL.PATID' or ../@name='ENROL.SITENAME') and @class='ItemDataLabel']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">color: blue;</xsl:attribute>
       &#160;&#160;
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Disabled STUDYID -->  
<xsl:template match="input[@itemoid='ENROL.STUDYID']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="readonly">readonly</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Set patient number -->
<xsl:template match="input[@itemoid='ENROL.PATID']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="readonly">readonly</xsl:attribute>
       <xsl:if test="$SubjectKey!='' and $SubjectKey!='BLANK'">
        <xsl:attribute name="value"><xsl:value-of select="substring($SubjectKey,3)"/></xsl:attribute>
       </xsl:if>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Set subject number -->
<xsl:template match="input[@itemoid='ENROL.SUBJID']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="readonly">readonly</xsl:attribute>
       <xsl:if test="$SubjectKey!='' and $SubjectKey!='BLANK' and not(@value)">
        <xsl:attribute name="value"><xsl:value-of select="$SubjectKey"/></xsl:attribute>
       </xsl:if>
       <xsl:apply-templates/>
   </xsl:copy>
   <xsl:if test="$SubjectKey!='' and $SubjectKey!='BLANK' and not(@value)">
    <script>
    $(document).ready(function(){                    
      setTimeout("bForceSave=true;$('#btnSave').click();",1000);
    });
    </script>
   </xsl:if> 
</xsl:template>

<!-- Set site name -->
<xsl:template match="input[@itemoid='ENROL.SITENAME']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="readonly">true</xsl:attribute>
       <xsl:if test="$SubjectKey!='' and $SubjectKey!='BLANK'">
        <xsl:attribute name="value"><xsl:value-of select="$SiteName"/></xsl:attribute>
       </xsl:if>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Customize the save button-->
<xsl:template match="button[@id='btnSave']">
   <xsl:copy>
     <xsl:copy-of select="@*"/> 
       <xsl:if test="$SubjectKey='BLANK'">
          Enroll
       </xsl:if>
        <xsl:if test="$SubjectKey!='BLANK' and //input[@itemoid='ENROL.SUBJID']/@value">
          Save
       </xsl:if>
        <xsl:if test="not(//input[@itemoid='ENROL.SUBJID']/@value) and $SubjectKey!='BLANK'">
          Confirm Enrolment of subject <xsl:value-of select="$SubjectKey"/>
       </xsl:if>
       </xsl:copy>
</xsl:template>

</xsl:stylesheet>