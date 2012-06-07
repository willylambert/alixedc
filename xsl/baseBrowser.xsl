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

<xsl:param name="CurrentApp"/>
<xsl:param name="METADATAVERSION"/>
<xsl:param name="UserId"/>
<xsl:param name="UserInfo"/>

<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Add User Info next to the app title -->
<xsl:template match="div[@id='mysite']">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
		<span id='userInfo'>
		  [ 
      <a>
		    <xsl:attribute name="href">index.php?menuaction=<xsl:value-of select="$CurrentApp"/>.uietude.usersInterface&amp;action=viewUser&amp;userId=<xsl:value-of select="$UserId"/></xsl:attribute>
        <xsl:value-of select="$UserId"/>
      </a>
       ]
      <xsl:value-of select="$UserInfo"/>
		</span>
  </xsl:copy>
</xsl:template>

<!--We remove scripts provided by egw but not needed by ALIX-->
<xsl:template match="head/script">
</xsl:template>

<!--Include needed JS and CSS-->
<xsl:template match="head">
  <xsl:copy>
    <xsl:apply-templates/>
    <!-- CSS -->
    <link rel='stylesheet' type='text/css'>
      <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/custom/<xsl:value-of select="$METADATAVERSION"/>/css/app.css</xsl:attribute>
    </link>
    <link rel='stylesheet' type='text/css'>
      <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/jquery-ui/jquery-ui-1.8.16.custom.css</xsl:attribute>
    </link>
    <link rel='stylesheet' type='text/css'> 
      <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/jquery-ui/app-custom.css</xsl:attribute>
    </link>
    <link rel='stylesheet' type='text/css'>
      <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/ui.jqgrid.css</xsl:attribute>
    </link>
    <link rel='stylesheet' type='text/css'>
      <xsl:attribute name="href"><xsl:value-of select="$CurrentApp"/>/templates/default/ui.jqgridex.css</xsl:attribute>
    </link>
    <!-- JavaScript -->
    <script language='JavaScript'>
      <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-1.7.1.min.js</xsl:attribute>
    </script>
    <script language='JavaScript'>
      <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/js/jquery-ui-1.8.16.custom.min.js</xsl:attribute>
    </script>
  </xsl:copy>
</xsl:template>

</xsl:stylesheet>