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
  <xsl:output method="xml" encoding="UTF-8" indent="no"/>

  <xsl:template name="Item">
  	<xsl:param name="ItemValue" />
	  <xsl:param name="MaxAuditRecordID"/>
  	<xsl:param name="TabIndex"/>
  	<xsl:param name="CurrentItemGroupRepeatKey"/>
    <xsl:param name="Item"/>
    <xsl:param name="EditMode"/>
    <xsl:param name="ForceSelect"/> <!--Force l'utilisation d'un select, même si on a moins de 4 réponses-->
  
    <!--On doit modifier les OID, car à la soumission d'un formulaire les navigateurs remplacent les "." par des "_" -->
  	<xsl:variable name="ItemOID" select="translate($Item/@OID,'.','@')"/>

    <xsl:choose>
    	<!--Item associé à une codelist-->
      <xsl:when test="count($Item/CodeList/CodeListItem)!=0">
        <!--Si nous sommes en présence d'une codelist à 2,3 ou 4 réponses, et que les libellés sont courts, on présente des radios button-->
        <xsl:choose>
          <xsl:when test="$EditMode and $readOnly='false'">
            <xsl:choose>
            <!--count($Item/CodeList/CodeListItem)&lt;=3-->
       				<xsl:when test="$ForceSelect='' and($ItemOID='TRT@ACTION' or count($Item/CodeList/CodeListItem)&lt;=4 and string-length($Item/CodeList/CodeListItem[position()=1]/@Decode)&lt;5)">
      				    <xsl:for-each select="$Item/CodeList/CodeListItem">
      				      <xsl:element name="input">
      				       <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>0</xsl:attribute> -->
      				    	 <xsl:attribute name="type">radio</xsl:attribute>
      				    	 <xsl:attribute name="name">radio_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
      				    	 <xsl:attribute name="itemoid">
								<xsl:value-of select="$Item/@OID"/>
							</xsl:attribute>
							 <xsl:attribute name="value"><xsl:value-of select="@CodedValue"/></xsl:attribute>
							 <xsl:attribute name="oldvalue">
								<xsl:value-of select="$ItemValue"/>
							</xsl:attribute>
							<xsl:attribute name="MaxAuditRecordID">
								<xsl:value-of select="$MaxAuditRecordID"/>
							</xsl:attribute>
      				    	 <xsl:if test="@CodedValue=$ItemValue">
      				    	   <xsl:attribute name="checked">true</xsl:attribute>
                     </xsl:if>
                     <!--Si pas de Decode dispo, on affiche comme texte adjacent la valeur de 'base'-->
                     <xsl:if test="@Decode=''"><xsl:value-of select="@CodedValue"/></xsl:if>
      				       <xsl:value-of select="@Decode"/>
      				    	</xsl:element>
      				    </xsl:for-each>
      				</xsl:when>
      		    <xsl:otherwise> 
      				  <xsl:element name="select">
                <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>0</xsl:attribute>-->
				<xsl:attribute name="oldvalue">
					<xsl:value-of select="$ItemValue"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
				<xsl:attribute name="itemoid">
					<xsl:value-of select="$Item/@OID"/>
				</xsl:attribute>
                <xsl:attribute name="name">select_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
      				  	<option value="">...</option>
      				    <xsl:for-each select="$Item/CodeList/CodeListItem">
      				      <xsl:element name="option">
      				        <xsl:attribute name="value"><xsl:value-of select="@CodedValue"/></xsl:attribute>
      				        <xsl:if test="@CodedValue=$ItemValue">
      				        	<xsl:attribute name="selected">true</xsl:attribute>
      				        </xsl:if>
      				        <xsl:if test="@Decode=''"><xsl:value-of select="@CodedValue"/></xsl:if>
      				        <xsl:value-of select="@Decode"/>
      				      </xsl:element>
      				    </xsl:for-each>
      				  </xsl:element>
      				</xsl:otherwise>
      		  </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <xsl:if test="$Item/CodeList/CodeListItem[@CodedValue=$ItemValue]/@Decode=''">
              <xsl:value-of select="$ItemValue"/>
            </xsl:if>
            <xsl:value-of select="$Item/CodeList/CodeListItem[@CodedValue=$ItemValue]/@Decode"/>
          </xsl:otherwise>
        </xsl:choose>    
      </xsl:when>
      <!--Item de type date-->
      <xsl:when test="$Item/@DataType='date' or $Item/@DataType='partialDate'">
        <xsl:choose>
          <xsl:when test="$EditMode and $readOnly='false'">
            <xsl:element name="span">
              <xsl:if test="$Item/@DataType='partialDate'">
                <xsl:attribute name="class">optionalText</xsl:attribute>
              </xsl:if> 
              <xsl:choose>
                <xsl:when test="$lang='el'">?µ??a:</xsl:when>
                <xsl:when test="$lang='fr'">jour:</xsl:when>
                <xsl:when test="$lang='de'">Tag:</xsl:when>
                <xsl:otherwise>day:</xsl:otherwise>
              </xsl:choose>
            </xsl:element>                      	
            <xsl:element name="input">
              <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>1</xsl:attribute>-->
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText</xsl:attribute>
			  <xsl:attribute name="itemoid">
				<xsl:value-of select="$Item/@OID"/>
			  </xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring($ItemValue,9,2)"/></xsl:attribute>
				<xsl:attribute name="oldvalue">
					 <xsl:value-of select="substring($ItemValue,9,2)"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
              <xsl:attribute name="name">text_dd_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength">2</xsl:attribute>
              <xsl:attribute name="size">2</xsl:attribute>
            </xsl:element> 
            <xsl:element name="span">
              <xsl:if test="$Item/@DataType='partialDate'">
                <xsl:attribute name="class">optionalText</xsl:attribute>
              </xsl:if> 
              <xsl:choose>
                <xsl:when test="$lang='el'">???a?:</xsl:when>
                <xsl:when test="$lang='fr'">mois:</xsl:when>
                <xsl:when test="$lang='de'">Monat:</xsl:when>
                <xsl:otherwise>month:</xsl:otherwise>
              </xsl:choose>
            </xsl:element>
            <xsl:element name="input">
              <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>2</xsl:attribute>-->
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText</xsl:attribute>
			  <xsl:attribute name="itemoid">
				<xsl:value-of select="$Item/@OID"/>
			  </xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring($ItemValue,6,2)"/></xsl:attribute>
				<xsl:attribute name="oldvalue">
					 <xsl:value-of select="substring($ItemValue,6,2)"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
              <xsl:attribute name="name">text_mm_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength">2</xsl:attribute>
              <xsl:attribute name="size">2</xsl:attribute>
            </xsl:element> 
            <xsl:choose>
              <xsl:when test="$lang='el'">?t??:</xsl:when>
              <xsl:when test="$lang='fr'">Année:</xsl:when>
              <xsl:when test="$lang='de'">Jahr:</xsl:when>
              <xsl:otherwise>year:</xsl:otherwise>
            </xsl:choose>
            <xsl:element name="input">
              <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>3</xsl:attribute>-->
              <xsl:attribute name="type">text</xsl:attribute>
              <xsl:attribute name="class">inputText</xsl:attribute>
			  <xsl:attribute name="itemoid">
				<xsl:value-of select="$Item/@OID"/>
			  </xsl:attribute>
              <xsl:attribute name="value"><xsl:value-of select="substring($ItemValue,1,4)"/></xsl:attribute>
				<xsl:attribute name="oldvalue">
					 <xsl:value-of select="substring($ItemValue,1,4)"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
              <xsl:attribute name="name">text_yy_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:attribute name="maxlength">4</xsl:attribute>
              <xsl:attribute name="size">4</xsl:attribute>
            </xsl:element>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$ItemValue"/>
          </xsl:otherwise>
        </xsl:choose>            
      </xsl:when>
      <!--Item de type float : on décompose partie entière/partie décimale -->
      <xsl:when test="$Item/@DataType='float'">
        <xsl:choose>
          <xsl:when test="$EditMode and $readOnly='false'">
              <xsl:element name="input">
                <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>0</xsl:attribute>-->
                <xsl:attribute name="type">text</xsl:attribute>
                <xsl:attribute name="class">inputText</xsl:attribute>
				<xsl:attribute name="itemoid">
					<xsl:value-of select="$Item/@OID"/>
				</xsl:attribute>
				<xsl:attribute name="oldvalue">
					<xsl:value-of select="substring-before($ItemValue,'.')"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
                <xsl:attribute name="value"><xsl:value-of select="substring-before($ItemValue,'.')"/></xsl:attribute>
                <xsl:attribute name="name">text_int_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
                <xsl:attribute name="maxlength"><xsl:value-of select="@Length - @SignificantDigits - 1"/></xsl:attribute>
                <xsl:attribute name="size"><xsl:value-of select="@Length - @SignificantDigits - 1"/></xsl:attribute>
              </xsl:element>      	
              <strong>.</strong>
              <xsl:element name="input">
                <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>1</xsl:attribute>-->
                <xsl:attribute name="type">text</xsl:attribute>
                <xsl:attribute name="class">inputText</xsl:attribute>
				<xsl:attribute name="itemoid">
					<xsl:value-of select="$Item/@OID"/>
				</xsl:attribute>
				<xsl:attribute name="oldvalue">
					<xsl:value-of select="substring-before($ItemValue,'.')"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
                <xsl:attribute name="value"><xsl:value-of select="substring-after($ItemValue,'.')"/></xsl:attribute>
                <xsl:attribute name="name">text_dec_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
                <xsl:attribute name="maxlength"><xsl:value-of select="@SignificantDigits"/></xsl:attribute>
                <xsl:attribute name="size"><xsl:value-of select="@SignificantDigits"/></xsl:attribute>
              </xsl:element> 
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$ItemValue"/>
          </xsl:otherwise>
        </xsl:choose>       
      </xsl:when>
      <xsl:when test="$Item/@DataType='text'">
        <xsl:choose>
          <xsl:when test="$EditMode and $readOnly='false'">      
            <xsl:element name="textarea">
              <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>0</xsl:attribute>-->
              <xsl:attribute name="cols">55</xsl:attribute>
              <xsl:attribute name="rows">3</xsl:attribute>
              <xsl:attribute name="class">inputText</xsl:attribute>
				<xsl:attribute name="itemoid">
					<xsl:value-of select="$Item/@OID"/>
				</xsl:attribute>
				<xsl:attribute name="oldvalue">
					<xsl:value-of select="$ItemValue"/>
				</xsl:attribute>
				<xsl:attribute name="MaxAuditRecordID">
					<xsl:value-of select="$MaxAuditRecordID"/>
				</xsl:attribute>
              <xsl:attribute name="name">text_text_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
              <xsl:if test="string-length($ItemValue)=0">&#160;</xsl:if>
              <xsl:value-of select="$ItemValue"/>
            </xsl:element>      	
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$ItemValue"/>
          </xsl:otherwise>
        </xsl:choose> 
      </xsl:when>
      <xsl:otherwise>
        <xsl:choose>
          <xsl:when test="$EditMode and $readOnly='false'">
        	  <xsl:element name="input">
             <xsl:attribute name="type">text</xsl:attribute>
             <xsl:attribute name="class">inputText</xsl:attribute>
             <!--Il ne faut pas mettre l'attribut value si pas de valeur, sinon nous ne pourrons l'ajouter plus tard-->
             <xsl:if test="$ItemValue!=''">
               <xsl:attribute name="value"><xsl:value-of select="$ItemValue"/></xsl:attribute>
             </xsl:if>  
			<xsl:attribute name="oldvalue">
				<xsl:value-of select="$ItemValue"/>
			</xsl:attribute>
			<xsl:attribute name="MaxAuditRecordID">
				<xsl:value-of select="$MaxAuditRecordID"/>
			</xsl:attribute>
			<xsl:attribute name="itemoid">
				<xsl:value-of select="$Item/@OID"/>
			</xsl:attribute>
             <!--<xsl:attribute name="tabindex"><xsl:value-of select="$TabIndex"/>0</xsl:attribute> -->
             <xsl:attribute name="name">text_<xsl:value-of select="@DataType"/>_<xsl:value-of select="$ItemOID"/>_<xsl:value-of select="$CurrentItemGroupRepeatKey"/></xsl:attribute>
             <xsl:attribute name="id"><xsl:value-of select="$Item/@OID"/></xsl:attribute>
             <xsl:attribute name="size"><xsl:value-of select="@Length"/></xsl:attribute>
             <xsl:attribute name="maxlength"><xsl:value-of select="@Length"/></xsl:attribute>
            </xsl:element>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$ItemValue"/>
          </xsl:otherwise>
        </xsl:choose>          
      </xsl:otherwise>
    </xsl:choose>
    <!--On a une unité de disponible, on l'affiche, mais uniquement si l'on a une valeur-->
    <xsl:if test="MeasurementUnit/MeasurementUnitItem/@Symbol and ($ItemValue!='' or $EditMode='true')">
      &#160;<xsl:value-of select="MeasurementUnit/MeasurementUnitItem/@Symbol"/>
    </xsl:if>  
    </xsl:template>

</xsl:stylesheet>