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

<xsl:param name="ItemGroupRepeatKey"/>
	
<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='DS.SIGNDTC']">
  <tr>
  <td></td>
  <td colspan="3">
     <b>INVESTIGATOR STATEMENT</b>
    </td>
   </tr>
   <tr><td></td><td colspan="3" class ="ItemDataLabel">
     I have personnaly reviewed all data recorded on this Case Report Form for completeness and accuracy.
    </td>
   </tr>
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Call eraseterm funtion-->
<xsl:template match="select[@name='select_DS@DSTERMN_0']">
   <xsl:copy>
       <xsl:attribute name="onChange">EraseTerm(this);</xsl:attribute>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Hide DSCAT -->
<xsl:template match="tr[@name='DS.DSCAT']">
   <xsl:copy>
        <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">display:none;</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>   

<!-- Resizing DSTERM-->
<xsl:template match="input[@itemoid='DS.DSTERM']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:attribute name="style">width:450px;</xsl:attribute>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Add tabs -->
<xsl:template match="td[(../@name='DS.DSTERM' or ../@name='DS.DSCONDTC' or ../@name='DS.DTHDTC') and @class='ItemDataLabel underCondition']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
		&#160;&#160;
	  <xsl:value-of select="."/>
  </xsl:copy>  	
</xsl:template>

<!--Add help label -->
<xsl:template match="td[(../@name='EXDA.EXENDTC') and @class='ItemDataLabel']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
Date of last study drug administration
  </xsl:copy>  	
</xsl:template>

<!--Add help label -->
<xsl:template match="td[(../@name='DS.DSSTDTC') and @class='ItemDataLabel underCondition']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
Date of completion/withdrawal
  </xsl:copy>  	
</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
		<style>.ItemDataLabel{width:30%;}</style>
			<script language="JavaScript">
			function EraseTerm(origin) {
       	 // Erase DSTERM in case of value changed
         input_origind = 'select_DS@DSTERMN_0';
       	
       	if (origin.name==input_origind) {
          document.getElementsByName('text_string_DS@DSTERM_0')[0].value = '';
         }
      }
			
       	 // Main function
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{   
          //// Disabled DS.DSTERMN
					input_destination = 'DS.DSTERMN'; /*ITEMOID=destination*/
					input_destination1 = 'DS.DSTERM'; /*ITEMOID=destination*/
					input_origin = 'radio_DS@DSCONT_0';
			
					if(origin.name==input_origin) 
					{
						action = $("input[name='radio_DS@DSCONT_0']:checked").val();
			
						if(action == 1||typeof(action)=="undefined")
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination1,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}
					
					//// Disable DSCONDTC et DTHDTC
					input_destination2 = 'DS.DSCONDTC'; /*ITEMOID=destination*/
					input_origin = 'radio_DS@DSCONT_0';
					input_origin1 = 'select_DS@DSTERMN_0';
			
					if(origin.name==input_origin||origin.name==input_origin1) 
					{
						action = $("input[name='radio_DS@DSCONT_0']:checked").val();
						action1 = $("select[name='select_DS@DSTERMN_0']").val();
						if(action == 1||typeof(action)=="undefined"||typeof(action1)=="undefined"||action1!=5)
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}	

					//// Disable DSCONDTC et DTHDTC
          input_destination3 = 'DS.DTHDTC'; /*ITEMOID=destination*/
					input_origin = 'radio_DS@DSCONT_0';
					input_origin1 = 'select_DS@DSTERMN_0';
			
					if(origin.name==input_origin||origin.name==input_origin1) 
					{
						action = $("input[name='radio_DS@DSCONT_0']:checked").val();
						action1 = $("select[name='select_DS@DSTERMN_0']").val();
						if(action == 1||typeof(action)=="undefined"||typeof(action1)=="undefined"||action1!=7)
						{
							freezeFields(input_destination3,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination3,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}	          

					//// Completion of DSTERM and DSTERMN
					input_origin1 = 'select_DS@DSTERMN_0';

					if(origin.name==input_origin1) 
					{
          	$("select[name='select_DS@DSTERMN_0'] option").each(function(){
              if($(this).attr('value')=='0'){
                $(this).detach();  
              }
            });
          	
            action1 = $("select[name='select_DS@DSTERMN_0']").val();
            $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);          	
						if(typeof(action1)!="undefined"){					
							if(action1!='')
							{
								switch (action1)
                {
                case '1':
                  document.getElementsByName('text_string_DS@DSTERM_0')[0].value = 'INCLUSION/EXCLUSION CRITERIA NOT MET';
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);                  
                  break;
                case '2':
                  document.getElementsByName('text_string_DS@DSTERM_0')[0].value = 'NON COMPLICANCE WITH INVESTIGATIONAL MEDICINAL PRODUCT';
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);
                  break;
                case '3':
                  document.getElementsByName('text_string_DS@DSTERM_0')[0].value = 'ADVERSE EVENT';
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);
                  break;
                case '5':
                  document.getElementsByName('text_string_DS@DSTERM_0')[0].value = 'LOST TO FOLLOW-UP';
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);                  
                  break;                                      
                case '6':
                  document.getElementsByName('text_string_DS@DSTERM_0')[0].value = 'CONSENT WITHDRAWN';
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);                  
                  break;  
                case '7':
                  document.getElementsByName('text_string_DS@DSTERM_0')[0].value = 'DEATH';
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);
                  break;                   
                default:
                
                  $("input[name='text_string_DS@DSTERM_0']").attr("readonly","");
                  break;
                }
							}
						}
						else
						{
						//	document.getElementsByName('text_string_DS@DSTERM_0')[0].value = '';
							$("input[name='text_string_DS@DSTERM_0']").attr("readonly",true);
						}
					}						
				}
	</script>
	    </div>  
</xsl:template>
    
</xsl:stylesheet>