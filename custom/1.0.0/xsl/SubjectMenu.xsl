<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="xml" encoding="UTF-8" indent="no"/>

<xsl:param name="SubjectKey"/>
<xsl:param name="CurrentApp"/>

<xsl:param name="SelDT"/>
<xsl:param name="IncDT"/>
<xsl:param name="DMAGE"/>

<!--Catch all des balise non traitées, on les restitue tel quel-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Le FormRepeatKey=0 n'existe pas - il est ajouté automatiquement par le generateur de menu bocdiscoo->getSubjectTblForm
    Nous allons commencer la numérotation des AE à 1-->
<xsl:template match="div[@FormOID='FORM.AE' and @FormRepeatKey='0']">
  <div style="display:none">&#160;</div>
</xsl:template>

<xsl:template match="h3[@id='visit_1_0']/a/text()">
  <xsl:value-of select="concat(string(.),' [',$SelDT,']')"/>
</xsl:template>

<xsl:template match="h3[@id='visit_2_0']/a/text()">
    <xsl:value-of select="concat(string(.),' [',$IncDT,']')"/>
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
   </xsl:copy>
  </xsl:if>
</xsl:template>
        
<!--Insertion du lien d'ajout d'un form AE-->
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