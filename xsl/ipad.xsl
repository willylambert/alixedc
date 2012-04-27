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

<xsl:param name="CurrentApp"/>
<xsl:param name="SubjectKey"/>
<xsl:param name="OnlyLoadForm"/>

<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<xsl:template match="div[@id='mainForm']">
  <div data-role="page" id="bar">

      <div data-role="header">
          <h1><xsl:value-of select="div/span[@class='ui-dialog-title']"/></h1>
      </div><!-- /header -->

      <div data-role="content">   
          <xsl:apply-templates select="div[@class='ui-dialog-content ui-widget-content']"/>  
      </div><!-- /content -->

      <div data-role="footer" data-position="fixed">
          <div data-role="navbar">
            <ul>
              <li><a id='btnCancel' href="#" data-role="button">Cancel</a></li>
              <li><a id='btnSave' href="#" data-role="button">Save</a></li>
            </ul>
          </div>
      </div><!-- /footer -->
  </div><!-- /page -->  
</xsl:template>

<xsl:template match="body">
  <xsl:copy>
    <xsl:if test="$OnlyLoadForm='true'">
      <xsl:apply-templates select="//div[@id='mainForm']"/> 
    </xsl:if>
    <xsl:if test="$SubjectKey=''">
        <xsl:apply-templates/>
    </xsl:if>
    <xsl:if test="$SubjectKey!='' and $OnlyLoadForm=''">
        <div data-role="panel" data-id="menu" data-hash="crumbs">
            <!-- Flow chart -->
            <div data-role="page" id="flowChart">
                 
                <div data-role="header">
                    <h2>Visits</h2>
                </div><!-- /header -->
        
                <div data-role="content">   
                  <xsl:apply-templates select="//div[@id='subjectMenu']"/>
                </div><!-- /content -->
                
                <div data-role="footer" data-position="fixed">
                    <div data-role="navbar">
                      <ul>
                        <li><a href="index.php?menuaction=alixedc.uietude.subjectListInterface" data-role="button">Subjects List</a></li>
                      </ul>
                    </div>
                </div>                
            </div><!-- /page -->
            <!-- other side panel pages here -->
        </div>
        <div data-role="panel" data-id="main">
            <!-- Start of second page -->
            <div data-role="page" id="bar">
        
                <div data-role="header">
                    <h1><xsl:value-of select="//div[@id='mainForm']/div/span[@class='ui-dialog-title']"/></h1>
                </div><!-- /header -->
        
                <div data-role="content">   
                    <p><i>Please choose a visit.</i></p>        
                </div><!-- /content -->
            </div><!-- /page -->
            <!-- other main panel pages here -->
        </div>
    </xsl:if>
  </xsl:copy>
</xsl:template>

<!--Manage the vist list menu-->
<xsl:template match="div[@id='subjectMenu']">      	
  <ul data-role="listview">
    <xsl:for-each select="h3">
      <li data-role="list-divider">
        <xsl:value-of select="a"/>
          <ul data-role="listview">
            <xsl:for-each select="following-sibling::div[position()=1]/div">
              <li>
                <a href="{a/@href}&amp;OnlyLoadForm=true" data-panel="main"><xsl:value-of select="a"/></a>
              </li>
            </xsl:for-each>
          </ul>
      </li>
    </xsl:for-each>      
  </ul>      
</xsl:template>

<!--We don't use the Alix processing dialog, we use the jquery mobile loading one-->
<xsl:template match="div[@id='dialog-modal-save']">
</xsl:template>

<xsl:template match="div[@id='toolbar_ico']">
  <div data-role="navbar">
    <xsl:apply-templates/>
  </div>
</xsl:template>

<!--Removal of menu hover help text-->
<xsl:template match="div[@id='toolbar_ico']/ul/li">
  <xsl:copy>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--We rewrite the buttons - first we remove the old ones-->
<xsl:template match="button[@id='btnCancel' or @id='btnSave' or @id='btnRunChecks']">
</xsl:template>

<!-- Here we build the new buttons toolbars. 
Note that in the mobile version, the run checks button is next to save and cancel buttons-->
<xsl:template match="div[@id='ActionsButtons']"> 
</xsl:template>


<!--Removal of extra admin and test mode button-->
<xsl:template match="li[@id='testModeMenu' or @id='adminMenu']">
</xsl:template>

<!--Removal of extra scripts not needed-->
<xsl:template match="body/script">
</xsl:template>

<!--Removal of menu images-->
<xsl:template match="img">
</xsl:template>

<!--Declare ALIX as a webapp, add mobile css and js -->
<xsl:template match="head">
  <xsl:if test="$OnlyLoadForm=''">
    <xsl:copy>
      <xsl:apply-templates/>
  
      <link rel='stylesheet' type='text/css'>
        <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/jquery-mobile/jquery.mobile.css</xsl:attribute>
      </link>
      <link rel='stylesheet' type='text/css'>
        <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/jquery-mobile/jquery.mobile.splitview.css</xsl:attribute>
      </link>
      <link rel='stylesheet' type='text/css'>
        <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/jquery-mobile/jquery.mobile.grids.collapsible.css</xsl:attribute>
      </link>
  
      <link rel='stylesheet' type='text/css'>
        <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/ipad.css?reload=7</xsl:attribute>
      </link>
        <!--We disable ajax navigation feature of jquery mobile-->
      <script type="text/javascript">
        $(document).bind("mobileinit", function(){
          $.mobile.pushStateEnabled = false;
        });
      </script>  
      <!--Hack !!! inversion des inclusions, si l'on veut utiliser le plugin split-->
      <xsl:if test="$SubjectKey!=''">
        <script LANGUAGE='JavaScript'>
          <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-mobile/jquery.mobile.splitview.js</xsl:attribute> 
        </script>
        <script LANGUAGE='JavaScript'>
          <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-mobile/jquery.mobile.js</xsl:attribute> 
        </script>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/helpers.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/queries.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/query.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/deviations.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/deviation.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/annotations.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/audittrail.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/postit.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/alixcrf.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/custom/1.0.0/js/alixlib.js'></SCRIPT>
        <SCRIPT LANGUAGE='JavaScript' SRC='/demo/alixedc/js/jquery.jqAltBox.js'></SCRIPT>

      </xsl:if>
      <xsl:if test="$SubjectKey=''">
        <script LANGUAGE='JavaScript'>
          <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-mobile/jquery.mobile.js</xsl:attribute> 
        </script>
        <script LANGUAGE='JavaScript'>
          <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-mobile/jquery.mobile.splitview.js</xsl:attribute> 
        </script>
      </xsl:if>
      <script LANGUAGE='JavaScript'>
        <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-mobile/iscroll-wrapper.js</xsl:attribute> 
      </script>
      <script LANGUAGE='JavaScript'>
        <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-mobile/iscroll.js</xsl:attribute> 
      </script>
      <script LANGUAGE='JavaScript'>
        <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/ipad.js</xsl:attribute>
      </script>
      <link rel="apple-touch-icon">
        <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/images/AlixIcoIpad.png</xsl:attribute>  
      </link>

      <meta name="apple-mobile-web-app-capable" content="yes" />
      <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no" />  
    </xsl:copy>
  </xsl:if>
</xsl:template>

</xsl:stylesheet>