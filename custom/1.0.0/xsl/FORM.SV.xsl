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
<xsl:include href="include/alixlib.xsl"/>

<!-- Parameters recovery -->
<xsl:param name="StudyEventOID"/>
<xsl:param name="ProfileId"/>

<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Add JS Calendar CSS and JS in the form-->
<xsl:template match="div[@id='Form']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
       <link href="alixedc/custom/1.0.0/css/dhtmlgoodies_calendar.css" type="text/css" rel="StyleSheet" />
       <script language="JavaScript" src="alixedc/custom/1.0.0/js/dhtmlgoodies_calendar.js"></script>
   </xsl:copy>
</xsl:template>
<!--Add Calendar button after the input-->
<xsl:template match="input[@name='text_yy_SV@SVSTDTC_0']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
   &#0160;
   <xsl:if test="$ProfileId='INV'">
    <img src="alixedc/templates/default/images/calendar.gif" class="pointer" value="Calendar" onclick="showCalendarVisit(this)" />
   </xsl:if>
   <script>
     function showCalendarVisit(srcEl){
       var year = $("input[name='text_yy_SV@SVSTDTC_0']").get(0);
       var month = $("input[name='text_mm_SV@SVSTDTC_0']").get(0);
       var day = $("input[name='text_dd_SV@SVSTDTC_0']").get(0);
       displayCalendarInputBoxes(year,month,day,false,false,srcEl);
     }
     

    function displayCalendarInputBoxes(yearInput,monthInput,dayInput,hourInput,minuteInput,buttonObj)
    {
    	if(!hourInput)calendarDisplayTime=false; else calendarDisplayTime = true;
    
    	currentMonth = monthInput.value/1-1;
    	currentYear = yearInput.value;
    	if(hourInput){
    		currentHour = hourInput.value;
    		inputHour = currentHour/1;
    	}
    	if(minuteInput){
    		currentMinute = minuteInput.value;
    		inputMinute = currentMinute/1;
    	}
    
    	inputYear = yearInput.value;
    	inputMonth = monthInput.value/1 - 1;
    	inputDay = dayInput.value/1;
    
    	if(!calendarDiv){
    		initCalendar();
    	}else{
    		writeCalendarContent();
    	}
    
    
    
    	returnDateToYear = yearInput;
    	returnDateToMonth = monthInput;
    	returnDateToDay = dayInput;
    	returnDateToHour = hourInput;
    	returnDateToMinute = minuteInput;
    
    
    
    
    	returnFormat = false;
    	returnDateTo = false;
    	positionCalendar(buttonObj);
    	calendarDiv.style.visibility = 'visible';
    	calendarDiv.style.display = 'block';
    	if(iframeObj){
    		iframeObj.style.display = '';
    		iframeObj.style.height = calendarDiv.offsetHeight + 'px';
    		iframeObj.style.width = calendarDiv.offsetWidth + 'px';
    		//// fix for EI frame problem on time dropdowns 09/30/2006
    		iframeObj2.style.display = '';
    		iframeObj2.style.height = calendarDiv.offsetHeight + 'px';
    		iframeObj2.style.width = calendarDiv.offsetWidth + 'px'
    	}
    	setTimeProperties();
    	updateYearDiv();
    	updateMonthDiv();
    	updateHourDiv();
    	updateMinuteDiv();
    
    }
   </script>
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