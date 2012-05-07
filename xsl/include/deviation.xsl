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

  <xsl:template name="Deviation">
    <xsl:param name="CurrentApp"/>
  	<xsl:param name="CurrentItemGroupOID"/>
  	<xsl:param name="CurrentItemGroupRepeatKey"/>
    <xsl:param name="ItemOID"/>
    <xsl:param name="DataType"/>
    <xsl:param name="Title"/>
    <xsl:param name="ProfileId"/>
  	<xsl:param name="SiteId"/>
  	<xsl:param name="SubjectKey"/>
  	<xsl:param name="StudyEventOID"/>
  	<xsl:param name="StudyEventRepeatKey"/>
  	<xsl:param name="FormOID"/>
  	<xsl:param name="FormRepeatKey"/>
    
    <xsl:if test="$ProfileId='INV' or $ProfileId='DM'">
  
      <!--On doit modifier les OID, car à la soumission d'un formulaire les navigateurs remplacent les "." par des "_" -->
    	<xsl:variable name="ItemOID" select="translate($ItemOID,'.','-')"/>
  
  	  <!--Valeurs modifiables-->          
      <xsl:variable name="DivId" select="concat('deviation_div_',$ItemOID,'_',$CurrentItemGroupRepeatKey)"/>
      <a href="javascript:void(0)">
        <xsl:element name='span'>
          <xsl:attribute name='id'><xsl:value-of select="concat($DivId,'_picture')"/></xsl:attribute>
          <xsl:attribute name='class'>imageOnly image16</xsl:attribute>
          <xsl:attribute name="style">background-image: url('<xsl:value-of select="$CurrentApp" />/templates/default/images/delta_add_16.png');</xsl:attribute>
          <xsl:attribute name="onclick">toggleDeviation('<xsl:value-of select="$CurrentApp"/>','<xsl:value-of select="$SiteId"/>','<xsl:value-of select="$SubjectKey"/>','<xsl:value-of select="$StudyEventOID"/>','<xsl:value-of select="$StudyEventRepeatKey"/>','<xsl:value-of select="$FormOID"/>','<xsl:value-of select="$FormRepeatKey"/>','<xsl:value-of select="$ProfileId"/>','<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>');</xsl:attribute>
          <xsl:attribute name="altbox">Add a deviation on this item</xsl:attribute>
          &#0160;
        </xsl:element>
      </a>
      <!--ancienne img, ne marche pas sous IE-->
      <!--a href="javascript:void(0)">
        <xsl:element name='img'>
          <xsl:attribute name='id'><xsl:value-of select="concat($DivId,'_picture')"/></xsl:attribute>
          <xsl:attribute name="src"><xsl:value-of select="$CurrentApp" />/templates/default/images/delta_add_16.png</xsl:attribute>
          <xsl:attribute name="onClick">toggleDeviation('<xsl:value-of select="$DivId"/>');</xsl:attribute>
          <xsl:attribute name="altbox">Add a deviation on this item</xsl:attribute>
        </xsl:element>
      </a-->
      <div id="{$DivId}" initialized='false' class='dialog-deviation' title='{$Title}' style="display:none;" itemgroupoid='{$CurrentItemGroupOID}' itemgrouprepeatkey='{$CurrentItemGroupRepeatKey}' itemoid='{$ItemOID}' itemtitle='{$Title}'>
        Please fill this field in case of deviation :<br /> <br />                 
        <xsl:element name="textarea">
          <xsl:attribute name="cols">60</xsl:attribute>
          <xsl:attribute name="rows">6</xsl:attribute>
.</xsl:element> <!--indentation : laisser cette balise fermante avec un seul '.' à gauche-->
      </div>
      
    </xsl:if>
  </xsl:template>

</xsl:stylesheet>
