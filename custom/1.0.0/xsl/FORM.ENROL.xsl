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

<xsl:param name="sitesList"/>
<xsl:param name="SubjectKey"/>

<!--Catch all non treated tags, print them without treatment-->
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
     Choose your Country and Site 
     </li>
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

<xsl:template match="input[@id='ENROL.SITEID']">
  <xsl:value-of select="$sitesList" disable-output-escaping="yes" />
</xsl:template>

  <!-- Format for calculated fields -->
<xsl:template match="td[(../@name='ENROL.STUDYID' or ../@name='ENROL.SUBJID' or ../@name='ENROL.PATID' or ../@name='ENROL.SITENAME') and @class='ItemDataLabel']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">color: blue;</xsl:attribute>
       &#160;&#160;
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

  <!-- Disabled STUDYID -->
  
<xsl:template match="input[@id='ENROL.STUDYID']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:attribute name="readonly">true</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

  <!-- Attribution of patient number -->
<xsl:template match="input[@id='ENROL.PATID']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <!--<xsl:attribute name="readonly">true</xsl:attribute>-->
       <xsl:if test="$SubjectKey!='' and $SubjectKey!='BLANK'">
        <xsl:attribute name="value"><xsl:value-of select="substring($SubjectKey,3)"/></xsl:attribute>
       </xsl:if>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

  <!-- SUBJID creation -->
<xsl:template match="input[@id='ENROL.SUBJID']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <!--<xsl:attribute name="readonly">true</xsl:attribute>-->
       <xsl:if test="$SubjectKey!='' and $SubjectKey!='BLANK' and not(@value)">
        <script>
        $(document).ready(function(){
          
          sitename = $("select[name='text_string_ENROL@SITEID_0'] option:selected").text();		
					document.getElementsByName('text_string_ENROL@SITENAME_0')[0].value =	sitename;	
        
          document.getElementsByName('text_string_ENROL@SUBJID_0')[0].value =	"<xsl:value-of select="$SubjectKey"/>";
          
          setTimeout("$('#btnSave').click();",2000);	
        });
        </script>
       </xsl:if>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

  <!-- Javascript treatment -->
<xsl:template match="button[@id='btnSave']">
   <xsl:copy>
     <xsl:copy-of select="@*"/> 
       <xsl:if test="$SubjectKey='BLANK'">
          Enroll
       </xsl:if>
        <xsl:if test="$SubjectKey!='BLANK' and //input[@id='ENROL.SUBJID']/@value">
          Save
       </xsl:if>
        <xsl:if test="not(//input[@id='ENROL.SUBJID']/@value) and $SubjectKey!='BLANK'">
          Confirm Enrolment of subject <xsl:value-of select="$SubjectKey"/>
       </xsl:if>
       </xsl:copy>
</xsl:template>

<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
        function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
        {
					if (origin.name == 'text_string_ENROL@SITEID_0'){
						/*
            sitename = $("select[name='text_string_ENROL@SITEID_0'] option:selected").text();		
						document.getElementsByName('text_string_ENROL@SITENAME_0')[0].value =	sitename;	
						*/
          $("input[name='text_string_ENROL@SITEID_0']").attr("readonly",true);
          $("input[name='text_string_ENROL@SITENAME_0']").attr("readonly",true);
          $("input[name='text_string_ENROL@SUBJID_0']").attr("readonly",true);
          $("input[name='text_string_ENROL@PATID_0']").attr("readonly",true);
					}
				}
      </script>
  </div>
</xsl:template>  

</xsl:stylesheet>