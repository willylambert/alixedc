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
<xsl:output method="html" encoding="UTF-8" indent="yes"/>
<xsl:include href="include/item.xsl"/>
<xsl:include href="include/annotation.xsl"/>
<xsl:include href="include/query.xsl"/>
<xsl:include href="include/deviation.xsl"/>
<xsl:include href="include/pagination.xsl"/>

<xsl:param name="lang"/>
<xsl:param name="ReadOnly"/>
<xsl:param name="CodeListForceSelect"/> <!--liste d'element séparé par un espace qui seront dans tous les cas affiché sous la forme de select-->
<xsl:param name="ProfileId"/>
<xsl:param name="ShowDeviations"/>

<xsl:param name="CurrentApp"/>
<xsl:param name="SubjectKey"/>
<xsl:param name="StudyEventOID"/>
<xsl:param name="StudyEventRepeatKey"/>
<xsl:param name="FormOID"/>
<xsl:param name="FormRepeatKey"/>

<!--Pagination-->
<xsl:param name="Paginate"/>
<xsl:param name="CurrentPage"/>
<xsl:param name="NumberOfRecords"/>
<xsl:param name="IGperPage"/>

<xsl:template match="ItemGroup">
  <xsl:variable name="ItemGroup" select="."/>
  <xsl:for-each select="$ItemGroup/ItemGroupData">
    <xsl:variable name="ItemGroupData" select="."/>  
    <xsl:variable id="{$ItemGroup/@OID}" name="ItemGroupPos" select="position()"/>
    <form name="{$ItemGroup/@OID}" position="{$ItemGroupPos}">
      <!--Dans le cas des itemggroup repeating, le premier form est notre template d'ajout - il ne faut pas le sauvegarder-->
      <xsl:if test="$ItemGroupData/@ItemGroupRepeatKey='0' and $ItemGroup/@Repeating='Yes'">
        <xsl:attribute name="style">display:none;</xsl:attribute>
      </xsl:if>
      <!--Insertion du contexte-->
      <input type="hidden" name="MetaDataVersionOID" value="{/StudyEvent/@MetaDataVersionOID}"/>
      <input type="hidden" name="SubjectKey" value="{$SubjectKey}"/>
      <input type="hidden" name="StudyEventOID" value="{$StudyEventOID}"/>
      <input type="hidden" name="StudyEventRepeatKey" value="{$StudyEventRepeatKey}"/>
      <input type="hidden" name="FormOID" value="{$ItemGroup/../@OID}"/>
      <input type="hidden" name="FormRepeatKey" value="{$ItemGroup/../@FormRepeatKey}"/>
      <input type="hidden" name="ItemGroupOID" value="{$ItemGroup/@OID}"/>
      <input type="hidden" name="ItemGroupRepeatKey" value="{$ItemGroupData/@ItemGroupRepeatKey}"/>
      <xsl:if test="$Paginate='true'">
        <input type="hidden" name="NewItemGroupRepeatKey" value="{$NumberOfRecords+1}"/>
      </xsl:if>
      <xsl:if test="$Paginate='false'">
        <input type="hidden" name="NewItemGroupRepeatKey" value="{count($ItemGroup/ItemGroupData[@ItemGroupRepeatKey!='0'])+1}"/>
      </xsl:if>
      <h3><xsl:value-of select="$ItemGroup/@Title"/></h3>
        <table class="ItemGroup TransactionType{$ItemGroupData/@TransactionType}" cellspacing="0" cellpadding="0">
        <xsl:for-each select="$ItemGroup/Item">
          <xsl:variable name="Item" select="."/>
          <!-- Ici on peut avoir plusieurs ItemData à cause de l'audit trail, 
               l'order by de notre xquery s'occupant de mettre le plus récent en premier  -->
          <xsl:variable name="ItemValue" select="$ItemGroupData/ItemData[@OID=$Item/@OID]/@Value"/>
          <xsl:variable name="ItemDecode" select="$ItemGroupData/ItemData[@OID=$Item/@OID]/@Decode"/>
          <xsl:variable name="Annotation" select="$ItemGroupData/ItemData[@OID=$Item/@OID]/Annotation"/>
          <tr class="ItemData" id="{$Item/@OID}_{$ItemGroupData/@ItemGroupRepeatKey}" name="{$Item/@OID}">
            <!--Audi Trail-->
            <td class="ItemDataAudit" name="{$Item/@OID}">
  	          <!--On n'affiche l'icône que s'il y a du contenu d'audit trail-->
              <xsl:if test="$ItemGroupData/ItemData[@OID=$Item/@OID]/@TransactionType!='Insert'">
              	<a href="javascript:void(0)">
                  <xsl:element name='image'>
                    <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/templates/default/images/clock-history.png</xsl:attribute>
                    <xsl:attribute name="onClick">toggleAuditTrail('<xsl:value-of select="concat('auditTrail_div_',$Item/@OID,'_',$ItemGroupData/@ItemGroupRepeatKey)"/>');</xsl:attribute>
                    <xsl:attribute name="altbox">Edit the history of this item</xsl:attribute>
                  </xsl:element>
                </a>
                <div id="{concat('auditTrail_div_',$Item/@OID,'_',$ItemGroupData/@ItemGroupRepeatKey)}" class='dialog-auditTrail' title='{@Title}' style="display:none;" keys="{$CurrentApp},{$SubjectKey},{$StudyEventOID},{$StudyEventRepeatKey},{$FormOID},{$FormRepeatKey},{$ItemGroup/@OID},{$ItemGroupData/@ItemGroupRepeatKey},{$Item/@OID}">
                  Loading ...
                </div>
              </xsl:if>
            </td>
            <td>
            <xsl:if test="@Mandatory='Yes' or @CollectionExceptionConditionOID!=''">
              <span class="ItemDataRequired">*</span>
            </xsl:if>
            &#160;
            </td>
            <td>
            <xsl:choose>
              <xsl:when test="@CollectionExceptionConditionOID!=''">
                <xsl:attribute name="class">ItemDataLabel underCondition</xsl:attribute>
              </xsl:when>
              <xsl:otherwise>
                <xsl:attribute name="class">ItemDataLabel</xsl:attribute>
              </xsl:otherwise>
            </xsl:choose>              
            <xsl:value-of select="@Title"/>&#160;</td>
            <td class="ItemDataInput" name="{$Item/@OID}" lastvalue="{$ItemDecode}">
                <xsl:call-template name="Item">                                                                            
                   <xsl:with-param name="Item" select="."/>
                   <xsl:with-param name="ItemValue" select="$ItemValue"/>
                   <xsl:with-param name="FlagValue" select="$Annotation/@FlagValue"/>
                   <xsl:with-param name="TabIndex" select="concat($ItemGroupPos,position())"/>
                   <xsl:with-param name="CurrentItemGroupRepeatKey" select="$ItemGroupData/@ItemGroupRepeatKey"/>
                   <xsl:with-param name="ForceSelect" select="contains($CodeListForceSelect,./CodeList/@OID)"/>
                </xsl:call-template>
            </td>
            <td class="ItemDataAnnot" name="{$Item/@OID}">
                <xsl:call-template name="Annotation">
                    <xsl:with-param name="ItemOID" select="$Item/@OID"/>
                    <xsl:with-param name="FlagValue" select="$Annotation/@FlagValue"/>
                    <xsl:with-param name="Comment" select="$Annotation/@Comment"/>
                    <xsl:with-param name="CurrentItemGroupOID" select="$ItemGroupData/@ItemGroupOID"/>
                    <xsl:with-param name="CurrentItemGroupRepeatKey" select="$ItemGroupData/@ItemGroupRepeatKey"/>
                    <xsl:with-param name="ShowFlag" select="true()"/>
                    <xsl:with-param name="Role" select="$Item/@Role"/>
                    <xsl:with-param name="DataType" select="$Item/@DataType"/>
                    <xsl:with-param name="Title" select="@Title"/>
                    <xsl:with-param name="ProfileId" select="$ProfileId"/>
                    <xsl:with-param name="CurrentApp" select="$CurrentApp"/>
                </xsl:call-template>
            </td>
            <td class="ItemDataQuery" name="{$Item/@OID}">
                <xsl:call-template name="Query">
                    <xsl:with-param name="CurrentApp" select="$CurrentApp"/>
                    <xsl:with-param name="ItemOID" select="$Item/@OID"/>
                    <xsl:with-param name="CurrentItemGroupOID" select="$ItemGroupData/@ItemGroupOID"/>
                    <xsl:with-param name="CurrentItemGroupRepeatKey" select="$ItemGroupData/@ItemGroupRepeatKey"/>
                    <xsl:with-param name="DataType" select="$Item/@DataType"/>
                    <xsl:with-param name="Title" select="@Title"/>
                    <xsl:with-param name="ProfileId" select="$ProfileId"/>
                </xsl:call-template>
            </td>
            <td class="ItemDataDevia" name="{$Item/@OID}">
                <xsl:if test="$ShowDeviations='true'">
                  <xsl:call-template name="Deviation">
                      <xsl:with-param name="CurrentApp" select="$CurrentApp"/>
                      <xsl:with-param name="ItemOID" select="$Item/@OID"/>
                      <xsl:with-param name="CurrentItemGroupOID" select="$ItemGroupData/@ItemGroupOID"/>
                      <xsl:with-param name="CurrentItemGroupRepeatKey" select="$ItemGroupData/@ItemGroupRepeatKey"/>
                      <xsl:with-param name="DataType" select="$Item/@DataType"/>
                      <xsl:with-param name="Title" select="@Title"/>
                      <xsl:with-param name="ProfileId" select="$ProfileId"/>
                  </xsl:call-template>
                </xsl:if>
            </td>
          </tr>
        </xsl:for-each>
        <!--Un bouton permet de supprimer l'ItemGroup-->
        <xsl:if test="$ReadOnly='false' and $ItemGroup/@Repeating='Yes' and $ItemGroupData/@TransactionType!='Remove'">
          <tr><td colspan='5'>
            <button name="btnRemoveItemGroup" class="ui-state-default ui-corner-all">Remove <xsl:value-of select="$ItemGroup/@Title"/> #<xsl:value-of select="position()"/></button>
            <!--Boite de dialogue de confirmation de suppression-->
          </td></tr>
        </xsl:if>
        <!--Un bouton permet de supprimer le FormData-->
        <xsl:if test="$ReadOnly='false' and $ItemGroup/../@Repeating='Yes'">
          <tr><td colspan='5'>
            <button name="btnRemoveFormData" class="ui-state-default ui-corner-all">Remove <xsl:value-of select="$ItemGroup/@Title"/></button>
            <!--Boite de dialogue de confirmation de suppression-->
          </td></tr>
        </xsl:if>         
      </table>
    </form>
    <!-- Boutons d'ajout nécessaire en ReadOnly=false -->
    <!-- Gestion des ItemGroup Repeating-->
    <xsl:if test="$ReadOnly='false' and $ItemGroup/@Repeating='Yes' and position()=last()">
      <!--Un bouton permet d'ajouter un ItemGroup-->
      <br/>
      <button id="btnAddItemGroup" itemgroupoid="{$ItemGroup/@OID}" class="ui-state-default ui-corner-all">Add <xsl:value-of select="$ItemGroup/@Title"/></button>
      <!-- C'est jQuery qui va s'occuper d'afficher le nouveau formulaire, en prenant l'IGRK=0, en affectant le nouveau IGRK, et en réinitialisant ce nouveau formulaire-->
    </xsl:if>
  </xsl:for-each>
</xsl:template>

<xsl:template match="Form">

  <!--Pagination navigation-->
  <xsl:if test="$Paginate='true'">
    <xsl:variable name="url">index.php?menuaction=<xsl:value-of select="$CurrentApp"/>.uietude.subjectInterface&amp;action=view&amp;SubjectKey=<xsl:value-of select="$SubjectKey"/>&amp;StudyEventOID=<xsl:value-of select="$StudyEventOID"/>&amp;StudyEventRepeatKey=<xsl:value-of select="$StudyEventRepeatKey"/>&amp;FormOID=<xsl:value-of select="$FormOID"/>&amp;FormRepeatKey=<xsl:value-of select="$FormRepeatKey"/></xsl:variable>

    <xsl:call-template name="pagination">
      <xsl:with-param name="pageNumber" select="$CurrentPage"/>
      <xsl:with-param name="recordsPerPage" select="$IGperPage" />
      <xsl:with-param name="numberOfRecords" select="$NumberOfRecords" />
      <xsl:with-param name="url" select="$url"/>
    </xsl:call-template>
  </xsl:if>
  
  <div id="Form">
    <xsl:apply-templates/>
  </div>
  
  <!-- Boutons de modification nécessaire en ReadOnly=false -->
  <div id="ActionsButtons">
    <xsl:if test="$ReadOnly='false'">
      <button id="btnCancel" class="ui-state-default ui-corner-all">Cancel</button>
      <button id="btnSave" class="ui-state-default ui-corner-all">Save</button>
    </xsl:if>
  </div>  

  <div id="dialog-modal-save" title="Processing...">
	 <p>Please wait while your request is processed...</p>
	 <div style="text-align: center;"><img src="{$CurrentApp}/templates/default/images/ajax_loader_77.gif" alt="Loading" /></div>
  </div>

  <div id="dialog-modal-info" title="Information">
	 <p>Information on study</p>
  </div>  
</xsl:template>

</xsl:stylesheet>
