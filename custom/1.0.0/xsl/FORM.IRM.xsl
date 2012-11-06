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

<xsl:param name="ZoomData"/>
<xsl:param name="ImageNumber"/>
<xsl:param name="CurrentApp"/>
<xsl:param name="PixelSpacingH"/>
<xsl:param name="PixelSpacingV"/>

<!--Catch all non processed tags, print them with no treatment-->
<xsl:template match="*">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<xsl:template match="tr[@name='IRM.OBS']">
  <tr><td colspan="4" style="text-align:left;">
    <a href="#" onClick="toggleMeasure()">
      <img id='toggleRuler'>
        <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/templates/default/images/ruler.png</xsl:attribute>
      </img>
    </a>
    <div id="ajax-zoom">
      <xsl:if test="$ImageNumber != '0'">
        Loading...
      </xsl:if>
      <xsl:if test="$ImageNumber = '0'">
        No image to display
      </xsl:if>
    </div></td></tr>
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>  
</xsl:template>

<xsl:template match="div[@id='Form']">
  <div id="Form">
    <xsl:if test="$ImageNumber != '0'">
    	<script type="text/javascript">
        //Create new object
        var ajaxZoom = {};
	    	    
        //Define the path to the axZm folder
        ajaxZoom.path = "/ajaxzoom/axZm/";
	 
        //define Your custom parameter query string
        ajaxZoom.parameter = "zoomData=<xsl:value-of select="$ZoomData"/>";
	 
        //The ID of the element where ajax-zoom has to be inserted into
        ajaxZoom.divID = "ajax-zoom";
	 
  	    // No options, see api jQuery.fn.axZm (options)
        ajaxZoom.opt = {};
        
        //Add the ruler
        $(document).ready(function(){
          setTimeout(function(){
            var paper = $("#paper");
            paper[0].ruler = new Ruler(paper[0],<xsl:value-of select="$PixelSpacingH"/>,<xsl:value-of select="$PixelSpacingV"/>);            
            //$('#zoomLayer div').hide();
          },2000);
        });
        
        function toggleMeasure(){
          currentZindex = $("#paper").css("z-index");
          if(currentZindex==10){
            //disable Rule
            $("#paper").css("z-index","-10");
          }else{
            jQuery.fn.axZm.zoomIn({pZoom: 100});        
            $("#paper").css("z-index","10");
          }
        }
  	 </script>
	   <!-- Include the loader file -->
	   <script type="text/javascript" src="/ajaxzoom/axZm/jquery.axZm.loader.js"></script>
     <script LANGUAGE='JavaScript'>
       <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/custom/1.0.0/js/raphael-min.js</xsl:attribute> 
     </script>
     <script LANGUAGE='JavaScript'>
       <xsl:attribute name="src"><xsl:value-of select="$CurrentApp"/>/custom/1.0.0/js/ruler.js</xsl:attribute> 
     </script>
    </xsl:if>
    <xsl:apply-templates/>
    <div id='paper'></div>
    <style>
      #paper { 
        width: 500px;
        height: 480px;
        margin: 20px 50px 20px 50px;
        background-color: none;
        color: white;
        cursor: pointer;
        position:absolute;
        top : 32px;
        left: 10px;
        z-index:-10;
      }
    </style>
  </div>
</xsl:template>

</xsl:stylesheet>