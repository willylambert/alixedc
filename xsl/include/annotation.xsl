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

  <xsl:template name="Annotation">
  	<xsl:param name="CurrentItemGroupOID"/>
  	<xsl:param name="CurrentItemGroupRepeatKey"/>
  	<xsl:param name="CurrentTransactionType"/>
    <xsl:param name="ItemOID"/>
    <xsl:param name="FlagValue"/>
    <xsl:param name="Comment"/>
    <xsl:param name="ShowFlag"/>
    <xsl:param name="DataType"/>
    <xsl:param name="Role"/>
    <xsl:param name="Title"/>
    <xsl:param name="ProfileId"/>
    <xsl:param name="CurrentApp"/>
  
    <!--On doit modifier les OID, car à la soumission d'un formulaire les navigateurs remplacent les "." par des "_" -->
  	<xsl:variable name="ItemOID" select="translate($ItemOID,'.','-')"/>

    <!-- Les ARC/DM ne voient que les Annotations non vides-->
    <xsl:if test="$ProfileId='INV' or ($FlagValue!='' or $Comment!='')">
    
  	 <xsl:if test="not(contains($Role,'NOAN'))">
      	  <!--Valeurs précédentes-->
        	<xsl:element name="input">
          	 <xsl:attribute name="type">hidden</xsl:attribute>
          	 <xsl:attribute name="name">annotation_previousflag_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
          	 <xsl:attribute name="value"><xsl:value-of select="$FlagValue"/></xsl:attribute>
          </xsl:element>
        	<xsl:element name="input">
          	 <xsl:attribute name="type">hidden</xsl:attribute>
            <xsl:attribute name="name">annotation_previouscomment_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
          	 <xsl:attribute name="value"><xsl:if test="string-length($Comment)=0">&#160;</xsl:if><xsl:value-of select="$Comment"/></xsl:attribute>
          </xsl:element>
        	<!--Valeurs modifiables de recopie, car les autres sortes du dom du form avec le jquery dialog-->  
          <xsl:element name="input">
          	 <xsl:attribute name="type">hidden</xsl:attribute>
          	 <xsl:attribute name="name">annotation_flag_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
             <xsl:attribute name="value"><xsl:value-of select="$FlagValue"/></xsl:attribute>
          </xsl:element>
        	<xsl:element name="input">
          	 <xsl:attribute name="type">hidden</xsl:attribute>
             <xsl:attribute name="name">annotation_comment_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
             <xsl:attribute name="value"><xsl:if test="string-length($Comment)=0">&#160;</xsl:if><xsl:value-of select="$Comment"/></xsl:attribute>            
          </xsl:element>
      	  <!--Valeurs modifiables-->          
    	    <xsl:variable name="DivId" select="concat('annotation_div_',$ItemOID,'_',$CurrentItemGroupOID,'_',$CurrentItemGroupRepeatKey)"/>
          <xsl:if test="$ShowFlag">
            <xsl:element name='span'>
            	<xsl:attribute name="id"><xsl:value-of select="concat($DivId,'_flagvalue')"/></xsl:attribute>
              <xsl:value-of select="$FlagValue"/>&#160;
            </xsl:element>
          </xsl:if>
          <a href="javascript:void(0)">
            <xsl:element name='span'>
              <xsl:attribute name='id'><xsl:value-of select="concat($DivId,'_picture')"/></xsl:attribute>
              <xsl:attribute name='class'>imageOnly image16</xsl:attribute>
              <xsl:attribute name="style">background-image: url('<xsl:value-of select="$CurrentApp" />/templates/default/images/post_note<xsl:if test="string-length($Comment)=0 or $Comment='&#160;'">_empty</xsl:if>.gif');</xsl:attribute>
              <xsl:attribute name="onclick">toggleAnnotation('<xsl:value-of select="$CurrentApp"/>', '<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>', 'annotation_comment_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/>','<xsl:value-of select="concat($DivId,'_picture')"/>');</xsl:attribute>
              <xsl:attribute name="altbox">Add an annotation on this item</xsl:attribute>
              &#0160;
            </xsl:element>
          </a>
          <!--ancienne img, ne marche pas sous IE-->
          <!--a href="javascript:void(0)">
            <xsl:element name='img'>
              <xsl:attribute name='id'><xsl:value-of select="concat($DivId,'_picture')"/></xsl:attribute>
              <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/templates/default/images/post_note<xsl:if test="string-length($Comment)=0 or $Comment='&#160;'">_empty</xsl:if>.gif</xsl:attribute>
              <xsl:attribute name="onClick">updateAnnotPict('<xsl:value-of select="$CurrentApp"/>', 'annotation_comment_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>'_<xsl:value-of select="$CurrentItemGroupRepeatKey"/>','<xsl:value-of select="concat($DivId,'_picture')"/>');toggleAnnotation('<xsl:value-of select="$DivId"/>');</xsl:attribute>
              <xsl:attribute name="altbox">Add an annotation on this item</xsl:attribute>
            </xsl:element>
          </a-->
          <div id="{$DivId}" initialized='false' class='dialog-annotation TransactionType{$CurrentTransactionType}' title='{$Title}' style="display:none;">
            <input type="radio" name='annotation-flag' value="Ø">
               <xsl:attribute name="onClick">updateFlag('<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>',this.value,false,false)</xsl:attribute>
               <!--Mémo : l'utilisation du onChange a ici été abandonnée, le comportement étant différent selon les navigateurs-->
            	 <xsl:attribute name="value">Ø</xsl:attribute>
            	 <xsl:if test="'Ø'=$FlagValue">
            	   <xsl:attribute name="checked">true</xsl:attribute>
               </xsl:if>
            </input>
            Ø (No comment)
            <br />
          	<input type="radio" name='annotation-flag' value="UNK">
               <xsl:attribute name="onClick">updateFlag('<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>',this.value,false,false)</xsl:attribute>
            	 <xsl:if test="'UNK'=$FlagValue">
            	   <xsl:attribute name="checked">true</xsl:attribute>
               </xsl:if>
            </input>
            UNK (unknown)
            <br />
          	<input type="radio" name='annotation-flag' value="ND">
               <xsl:attribute name="onClick">updateFlag('<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>',this.value,false,<xsl:choose><xsl:when test="boolean($DataType='partialDate')">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>)</xsl:attribute>
            	 <xsl:if test="'ND'=$FlagValue">
            	   <xsl:attribute name="checked">true</xsl:attribute>
               </xsl:if>
            </input>
            ND (Not Done or Missing)
            <br />
          	<input type="radio" name='annotation-flag' value="NA">
               <xsl:attribute name="onClick">updateFlag('<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>',this.value,false,false)</xsl:attribute>
            	 <xsl:if test="'NA'=$FlagValue">
            	   <xsl:attribute name="checked">true</xsl:attribute>
               </xsl:if>
            </input>
               NA (Not Applicable)
            <br />                 
            <xsl:element name="textarea">
              <xsl:attribute name="cols">43</xsl:attribute>
              <xsl:attribute name="rows">3</xsl:attribute>
              <xsl:attribute name="class">inputText</xsl:attribute>
              <!-- Le textarea ne sort pas du DOM du form
              <xsl:attribute name="name">annotation_comment_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              -->
              <xsl:attribute name="onChange">$("input:[name='annotation_comment_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/>']").val(this.value);</xsl:attribute>              	              <xsl:if test="string-length($Comment)=0">&#160;</xsl:if>
              <xsl:value-of select="$Comment"/>
            </xsl:element>
          </div>
          
          <script type="text/javascript">            
            //sans attendre la fin de chargement de la page : on verrouille les champs de saisies si le flag est différent de Ø
            $(document).ready(function(){
                    updateFlag('<xsl:value-of select="$ItemOID"/>','<xsl:value-of select="$CurrentItemGroupOID"/>','<xsl:value-of select="$CurrentItemGroupRepeatKey"/>','<xsl:value-of select="$FlagValue"/>', true, <xsl:choose><xsl:when test="$FlagValue='ND' and boolean($DataType='partialDate')">true</xsl:when><xsl:otherwise>false</xsl:otherwise></xsl:choose>);
            });            
          </script>
     </xsl:if>
     
    </xsl:if>    
  </xsl:template>

</xsl:stylesheet>
