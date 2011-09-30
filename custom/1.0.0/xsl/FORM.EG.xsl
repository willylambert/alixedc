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

<xsl:param name="StudyEventOID"/>

<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--
NOTE : We need to filter printed records, 
       Du fait de l'utilisation du template col2rowForm, le fitrage dans l'xsl du form ne fonctionne pas
       Nous intégrons ainsi les templates col2rowForm et col2rowTable dans l'xsl du form, pour réaliser
       directement le fitrage dans ces templates - WLT 11/01/2001 
-->

<!-- Version custom og alixcrf.xsl -->
<xsl:template name="col2rowTable">
  <tr>
    <xsl:attribute name="id">_<xsl:value-of select="substring-after(./tr[1]/@id,'_')" /></xsl:attribute>
    <xsl:for-each select="tr/td[@class='ItemDataInput']">
      <td id="{../@id}" name="{../@name}" class="ItemDataInput">
 <!-- Enlarge field EGDTC -->     
      <xsl:if test="../@name='EG.EGDTC'">
        <xsl:attribute name="style">width:190px;</xsl:attribute>
      </xsl:if>    
      <xsl:apply-templates/></td>
     <xsl:apply-templates select="../td[@class='ItemDataAnnot']"/>
<!-- ajout du bouton query -->
     <xsl:apply-templates select="../td[@class='ItemDataQuery']"/> 
    </xsl:for-each>
  </tr> 
</xsl:template>  

<xsl:template name="col2rowForm">
  <xsl:param name="ItemGroupOID" />
  <xsl:if test="@position=1">
    <h3><xsl:copy-of select="h3"/></h3>
    <table name="{@name}" class="ItemGroup">
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
        <xsl:for-each select="../form[@name=$ItemGroupOID]/table[tr[@name='EG.EGTEST']/td[@name='EG.EGTEST']/select[starts-with(@name,'select_EG@EGTEST')]/option[@selected='true' and (@value='HR' or @value='PR' or @value='QRS' or @value='QTCB')] ] ">
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

<!--Create table because preprinted values -->
<xsl:template match="form[@name='EG']">
  <xsl:call-template name="col2rowForm" select=".">
    <xsl:with-param name="ItemGroupOID" select="@name"/>
  </xsl:call-template>
  <xsl:if test="@position=1">
   <xsl:if test="$StudyEventOID='1'">
      <B>*Any abnormality clinically relevant must be reported in medical history form</B>
      <br/>  <br/>
      <B>Patients with abnormal and clinically significant ECG are not eligible</B>
      </xsl:if>
   <xsl:if test="$StudyEventOID!='1'">
      <B>*Any abnormality clinically relevant must be reported in adverse event form</B>
      </xsl:if>      
  </xsl:if>  
</xsl:template>                                 

<!--delete non checked radio buttons -->
<xsl:template match="input[@itemoid='EG.EGORRESU' and not(@checked = 'true')]">
</xsl:template>

<!--Print checked radiobutton -->
<xsl:template match="input[@itemoid='EG.EGORRESU' and @checked = 'true']">
   <xsl:value-of select="text()"/>
   <!-- We need to add input for saving data in clinicaldata file, put on input hidden-->
   <xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="text()"/></xsl:attribute>
   </xsl:element>
</xsl:template>

<!--For the select, the value must be unmodifiable -->
<xsl:template match="select[starts-with(@name,'select_EG@EGTEST')]">
   <xsl:value-of select="option[@selected='true']/text()"/>
   <!-- We need to add input for saving data in clinicaldata file, put on input hidden-->
   <xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="option[@selected='true']/@value"/></xsl:attribute>
   </xsl:element>
</xsl:template>

<!--delete not checked radio button -->
<xsl:template match="input[@itemoid='EG.EGCLSIG' and not(@checked = 'true')]">
</xsl:template>

<!--For the select, the value must be unmodifiable -->
<xsl:template match="input[@itemoid='EG.EGCLSIG' and @checked = 'true']">
   <xsl:value-of select="text()"/>
   <!--Pour les besoins de l'enregistrement, un input doit être présent, on le met dans un input hidden-->
   <xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
   </xsl:element>
</xsl:template>

<!--Reduce field length EGNRIND -->
<xsl:template match="input[@itemoid='EG.EGNRIND']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:attribute name="style">width:80px;</xsl:attribute>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Delete button "Add" because records are predefined -->
<xsl:template match="button[@itemgroupoid='EG']">
</xsl:template>         

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
		
				  <style>
    td[name='EG.EGSEQ'],th[name='EG.EGSEQ'],th[name='EG.EGLOC'],th[name='EG.EGORNRLO'],th[name='EG.EGORNRHI'],td[name='EG.EGLOC'],
    td[name='EG.EGORNRLO'],td[name='EG.EGORNRHI'],td[name='EG.EGREASND'],th[name='EG.EGREASND'],td[name='EG.EGMETHOD'],th[name='EG.EGMETHOD'],
    td[name='EG.EGTIM'],th[name='EG.EGTIM'],td[name='EG.BRTHDTC'],th[name='EG.BRTHDTC'],td[name='EG.EGSTAT'],th[name='EG.EGSTAT'],
    td[name='EG.EGSEQ'][class='ItemDataAnnot'],td[name='EG.EGSEQ'][class='ItemDataQuery'],td[name='EG.EGTEST'][class='ItemDataAnnot'],
    td[name='EG.EGTEST'][class='ItemDataQuery'], td[name='EG.EGORRESU'][class='ItemDataAnnot'], td[name='EG.EGORRESU'][class='ItemDataQuery']{
      display : none;      
    }
    
  </style> 
			<script language="JavaScript">
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{    
        input_origin = 'text_string_EG@EGSEQ_'+ ItemGroupRepeatKey ;
            
        		if(origin.name==input_origin)
            { 
            $("th[name='EG.EGSEQ']").attr("colspan","1");
            $("th[name='EG.EGORRESU']").attr("colspan","1");
            $("th[name='EG.EGTEST']").attr("colspan","1");
 						Cle = parseInt(ItemGroupRepeatKey);
						
						$("input[name='text_string_EG@EGSEQ_"+ ItemGroupRepeatKey +"']").val(Cle);
						$("input[name='text_string_EG@EGSEQ_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
						$("input[name='text_string_EG@EGTEST_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
            $("input[name='text_string_EG@EGORRES_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
            $("input[name='text_integer_EG@EGORNRLO_"+ ItemGroupRepeatKey +"']").attr("readonly",true);                  
            $("input[name='text_integer_EG@EGORNRHI_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
            $("input[name='text_string_EG@EGLOC_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
            $("input[name='text_string_EG@EGNRIND_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
            $("input[itemoid='EG.EGDTC']").attr("readonly",true);
            }
          }
	</script>
 </div>  
</xsl:template>
    
</xsl:stylesheet>