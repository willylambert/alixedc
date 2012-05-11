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

<xsl:param name="StudyEventOID"/>
<xsl:param name="AGE"/>
<xsl:param name="SEX"/>

<!--Catch all non treated tags, print them with no treatment-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Delete "Add" button because parameters are predifined -->
<xsl:template match="button[@itemgroupoid='VS']">
</xsl:template>
                      
<!-- Format BMI field -->
<xsl:template match="select[@itemoid='VS.VSTEST']">
	<xsl:attribute name="align">left</xsl:attribute>
	<xsl:if test="@name='select_VS@VSTEST_3'">
    <xsl:attribute name="style">
      color: blue;
    </xsl:attribute>	
	</xsl:if>

  <xsl:value-of select="option[@selected='selected']/text()"/>
	<xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="option[@selected='true']/@value"/></xsl:attribute>
   </xsl:element>

</xsl:template>

<!-- Call Bmi calculation--> 
<xsl:template match="input[@type='text']">
  <xsl:copy>
    <xsl:attribute name="onBlur">getBMI();</xsl:attribute>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Create convertion calculator -->      

<xsl:template match="input[@name='text_dec_VS@VSORRES_1']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>  
  <img src="alixedc/templates/default/images/calc.png" onClick='toggleConvert("text_int_VS@VSORRES_1","text_dec_VS@VSORRES_1","int_weight_kg","dec_weight_kg");' style='cursor:pointer' />  
  <div id='convert' class="divBorder" style="position:absolute;width:300px;background-color:white;display:none;">
      <table style="width:300px;" class="ItemGroupTable" cellspacing="0">
        <tr class="th"><td colspan="3"><b>Convert</b></td></tr>
        <tr><td>Weight : </td>
        <td>
          <input type="text" name="int_weight_kg" size="5" maxlength="5"/>.
          <input type="text" name="dec_weight_kg" size="2" maxlength="3"/>
        </td>
        <td>g</td></tr>
        <tr><td colspan='3' align='center'><input type="button" value="Convert" onClick="getConvertResult();"/>&#160;&#160;<input type="button" value="Close" onClick="toggleConvert();"/></td></tr>
      </table>
  </div> 
</xsl:template>       

<!-- Hide VSORRESU--> 
<xsl:template match="label">
</xsl:template>
<xsl:template match="input[@itemoid='VS.VSORRESU']">
	<xsl:if test="@checked='checked'">
		<xsl:value-of select="@value"/>
		<xsl:element name="input">
		  <xsl:attribute name="type">hidden</xsl:attribute>
		  <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
		  <xsl:attribute name="value"><xsl:value-of select="@value"/></xsl:attribute>
		</xsl:element>
  </xsl:if>
</xsl:template>
 
<!--Add help label and create TABLEAU -->
<xsl:template match="form[@name='VS']">
  <xsl:call-template name="col2rowForm" select=".">
    <xsl:with-param name="ItemGroupOID" select="@name"/>	
  </xsl:call-template>
  <xsl:if test="@position=1">
   <xsl:if test="$StudyEventOID='1'">
      <B>*Any abnormality clinically relevant must be reported in medical history form</B>
      </xsl:if>
   <xsl:if test="$StudyEventOID!='1'">
      <B>*Any abnormality clinically relevant must be reported in adverse event form</B>
      </xsl:if>      
  </xsl:if>
</xsl:template>

<!-- Javascript treatement -->

<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{    						
          //on n'efface le champ qu'au load
          if (loading){
            action = $("input[name='text_string_VS@VSPOS_"+ItemGroupRepeatKey+"']").val();
            if(typeof(action)=="undefined"||action=='')
						{         	           				
        			$("input[name='text_string_VS@VSPOS_"+ ItemGroupRepeatKey +"']").hide();
			      }
            else
            {
              $("input[name='text_string_VS@VSPOS_"+ ItemGroupRepeatKey +"']").attr("readonly",true);
            }    	  
				  }           
        }
		// Convertion		
      function getConvertResult(){
        valR = document.getElementsByName('int_weight_kg')[0].value + '.' + document.getElementsByName('dec_weight_kg')[0].value;
        if(!isNaN(valR)){
          result = parseFloat(valR)/1000;
          result = Math.round(result*100)/100;
          var res = result.toString().split(".");       
          document.getElementsByName('text_int_VS@VSORRES_1')[0].value = res[0];
          if (res[1]){
          document.getElementsByName('text_dec_VS@VSORRES_1')[0].value = res[1];
          }
          else
          {
          document.getElementsByName('text_dec_VS@VSORRES_1')[0].value = 0;
          }
        }else{
          document.getElementsByName('text_int_VS@VSORRES_1')[0].value = '';
          document.getElementsByName('text_dec_VS@VSORRES_1')[0].value = '';      
        }  
       
        toggleConvert();
      }

	// BMI calculation			
			function getBMI()
				{
	var SEOID = '<xsl:value-of select="$StudyEventOID"/>';
          if (SEOID=='1'){
          //Calcul du BMI et de height si ulna renseigné
					weightint = parseInt(document.getElementsByName('text_int_VS@VSORRES_1')[0].value);
					weightdec = parseInt(document.getElementsByName('text_dec_VS@VSORRES_1')[0].value);
          weight = weightint +"."+ weightdec;
          weight = parseFloat(weight);
            
          heightint = parseInt(document.getElementsByName('text_int_VS@VSORRES_2')[0].value);
          heightdec = parseInt(document.getElementsByName('text_dec_VS@VSORRES_2')[0].value);
          height = heightint +"."+ heightdec;
          height = parseFloat(height);          

          //test pour visit différente de SCREENING
					if (document.getElementsByName('StudyEventOID')[0].value == 1)
          {
            document.getElementsByName('text_int_VS@VSORRES_3')[0].value = '';
					  document.getElementsByName('text_dec_VS@VSORRES_3')[0].value = '';
  					bmi='';
  					if(!isNaN(weight)){		
  						if(!isNaN(height)){
  								
  							taille = height/100;
  							taille = taille * taille;
  							if(taille!=0)
  							{
  						    bmi = weight/taille;
                  bmi = Math.round(bmi*10)/10;
               
                  var Bm = bmi.toString().split(".");
               
                bmiint =Bm[0];
                bmidec =Bm[1];
               }
  							document.getElementsByName('text_int_VS@VSORRES_3')[0].value = bmiint;
                if (typeof(bmidec)!='undefined'){  							
  							 document.getElementsByName('text_dec_VS@VSORRES_3')[0].value = bmidec;
  							}
  						}
  					}
  				$("input[name='text_int_VS@VSORRES_3']").attr("readonly",true);
          $("input[name='text_dec_VS@VSORRES_3']").attr("readonly",true);   					
          }
				}	
        }					
		// convertion	
			function toggleConvert(srcInt,srcDec,destInt,destDec){
        if(document.getElementById('convert').style.display=='none'){
           val = document.getElementsByName(srcInt)[0].value + "." + document.getElementsByName(srcDec)[0].value;
        	if(!isNaN(val)){
            result = parseFloat(val)*1000;
            result = Math.round(result*100)/100;
            document.getElementsByName(destInt)[0].value = parseInt(result);
            document.getElementsByName(destDec)[0].value = String(parseFloat(result) - parseInt(result)).substring(2,5);
          }else{
            document.getElementsByName(destInt)[0].value = '';
            document.getElementsByName(destDec)[0].value = '';        
          }              
          document.getElementById('convert').style.display = 'inline';
        }
        else{
          document.getElementById('convert').style.display = 'none';        
          }
      }
				
		getBMI();

	</script>
	    </div>  
</xsl:template>
    
</xsl:stylesheet>