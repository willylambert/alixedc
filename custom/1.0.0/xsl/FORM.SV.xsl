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
<xsl:include href="include/alixlib.xsl"/>

<!-- Parameters recovery -->
<xsl:param name="StudyEventOID"/>

<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Add help label for data entry (visit name)-->
<xsl:template match="h3">
   <xsl:copy>
   <xsl:choose>
  <xsl:when test="$StudyEventOID = 1">
    Date of Screening Visit
  </xsl:when>
  <xsl:when test="$StudyEventOID = 2">
    Date of Inclusion Visit
  </xsl:when>
  <xsl:when test="$StudyEventOID = 3">
    Date of Visit V1
  </xsl:when>
  <xsl:when test="$StudyEventOID = 4">
     Date of Visit V2
  </xsl:when>
  <xsl:otherwise>   
  </xsl:otherwise>
</xsl:choose>

   </xsl:copy>
</xsl:template>

</xsl:stylesheet>