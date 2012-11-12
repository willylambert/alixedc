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

<xsl:param name="SEX"/>
<xsl:param name="AGE"/>
<xsl:param name="WEIGHT"/>

<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>        

<!--Cath select value for LBTEST and hide dropdown list -->
<xsl:template match="select[starts-with(@name,'select_LB@LBTEST')]">
   <xsl:value-of select="option[@selected='selected']/text()"/>
   <!--For saving purpose, an input is needed, it will be hidden-->
   <xsl:element name="input">
    <xsl:attribute name="type">hidden</xsl:attribute>
    <xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
    <xsl:attribute name="value"><xsl:value-of select="option[@selected='selected']/@value"/></xsl:attribute>
   </xsl:element>
</xsl:template>     
             

<!-- Create table to compact list -->
<xsl:template match="form[@name='LBBIO']">
  <xsl:call-template name="col2rowForm" select=".">
    <xsl:with-param name="ItemGroupOID" select="@name"/>	
  </xsl:call-template>
</xsl:template>

<!-- Delete "Add" button because Parameters are predifined -->
<xsl:template match="button[@itemgroupoid='LBBIO']">
</xsl:template>

<!-- Hide SUPPLB category -->
<xsl:template match="tr[@name='SUPPLB.LBCAT' or @name='TEMPLB.LBCAT']">
   <xsl:copy>
        <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">display:none;</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
		  <style>
        th[name='LB.LBCAT'], th[name='LB.LBDTC'], th[name='LB.BRTHDTC'],th[name='LB.SEX'],th[name='LB.LBSTAT'],th[name='LB.LBNRIND'],th[name='LB.LBFAST'],th[name='LB.LBREFID'],th[name='LB.LBREASND'],
        td[name='LB.LBREFID'],td[name='LB.LBFAST'],td[name='LB.LBNRIND'],td[name='LB.LBREASND'],td[name='LB.SEX'],td[name='LB.LBSTAT'],td[name='LB.BRTHDTC'], td[name='LB.LBCAT'],td[name='LB.LBSEQ'][class='ItemDataAnnot']{
          display : none;      
        }    
      </style> 
			<script language="JavaScript">
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{        
        //        
        input_origin = 'text_integer_LB@LBSEQ_'+ ItemGroupRepeatKey ;
        input_origin1='select_SUPPLB@LBNORM_0';
        input_origin2='text_dd_TEMPLB@LBDTC_0';
        input_origin3='text_mm_TEMPLB@LBDTC_0';
        input_origin4='text_yy_TEMPLB@LBDTC_0';
        
        //Put "central" by default
        input_origin6 = 'select_LB@LBNAM_'+ ItemGroupRepeatKey ;
            
      	if(origin.name==input_origin || origin.name==input_origin1||origin.name==input_origin6)
        { 
          $("th[name='LB.LBSEQ']").attr("colspan","1");
          action6 = $("select[name='select_LB@LBNAM_"+ ItemGroupRepeatKey +"']").val();
          sex = '<xsl:value-of select="$SEX"/>';
          if (sex!=1)
          {
           	if(action6=='')
            {
              $("select[name='select_LB@LBNAM_"+ ItemGroupRepeatKey +"']").val('C');
            }
          }
          else
          {
            if(ItemGroupRepeatKey != 17)
            {
              $("select[name='select_LB@LBNAM_"+ ItemGroupRepeatKey +"']").val('C');
            }
            else
            {
              $("select[name='select_LB@LBNAM_"+ ItemGroupRepeatKey +"']").val('');
            }
          }
          // Don't lock if local laboratory
          if(ItemGroupRepeatKey!="0")
          { //un seul bilan bio
            if (action6=='C'||action6=='')
            {
              updateItemGroup(ItemGroupRepeatKey,true);
    			  }
            else
    				{
              updateItemGroup(ItemGroupRepeatKey,false);
            } 
    			}
          else
          { //il faut boucler car on n'est sur aucune ligne spécifique des bilans bios
            $("input[name='ItemGroupRepeatKey']").each(function()
            {
              ItemGroupRepeatKey = $(this).attr("value");
    					if(ItemGroupRepeatKey!="0")
              {
  					    if (action6=='C'||action6=='')
                {    					 
                  updateItemGroup(ItemGroupRepeatKey,true);
                }
                else
                {
                  updateItemGroup(ItemGroupRepeatKey,false);
                }  
    				  }
            });
    			}
  			}
       }
       // Lock fields (readonly) and attribute LBSEQ key
        function updateItemGroup(ItemGroupRepeatKey,bReadOnly){
  					
					action = $("select[name='select_SUPPLB@LBNORM_0']").val();
					action2 = $("input[name='text_dd_TEMPLB@LBDTC_0']").val();
					action3 = $("input[name='text_mm_TEMPLB@LBDTC_0']").val();
					action4 = $("input[name='text_yy_TEMPLB@LBDTC_0']").val();
					
  				Cle = parseInt(ItemGroupRepeatKey);
					$("input[name='text_integer_LB@LBSEQ_"+ ItemGroupRepeatKey +"']").val(Cle);
					if (bReadOnly==true){
  					$("input[name='text_integer_LB@LBSEQ_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
  					$("input[name='text_string_LB@LBTEST_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
  					$("input[name='text_string_LB@LBORRES_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
  					$("input[name='text_string_LB@LBORRESU_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
            $("select[name='select_LB@LBORNRCL_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
  	   	   	$("input[name='text_string_LB@LBORNRLO_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
  					$("select[name='select_LB@LBORNRCH_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
  					$("input[name='text_string_LB@LBORNRHI_"+ ItemGroupRepeatKey +"']").attr("readonly","readonly");
					}
					else
					{
   					$("input[name='text_integer_LB@LBSEQ_"+ ItemGroupRepeatKey +"']").removeAttr("readonly");
  					$("input[name='text_string_LB@LBTEST_"+ ItemGroupRepeatKey +"']").removeAttr("readonly");
  					$("input[name='text_string_LB@LBORRES_"+ ItemGroupRepeatKey +"']").removeAttr("readonly");
  					$("input[name='text_string_LB@LBORRESU_"+ ItemGroupRepeatKey +"']").removeAttr("readonly");
  	   	   	$("input[name='text_string_LB@LBORNRLO_"+ ItemGroupRepeatKey +"']").removeAttr("readonly");
  					$("input[name='text_string_LB@LBORNRHI_"+ ItemGroupRepeatKey +"']").removeAttr("readonly");        
          }
          
					sex = '<xsl:value-of select="$SEX"/>';
          action5 = $("select[name='select_LB@LBCLSIG_"+ ItemGroupRepeatKey +"']").val();					
			    if (action==1){
  	
			     if (sex!=1){
			      if (action5==''){
              $("select[name='select_LB@LBCLSIG_"+ ItemGroupRepeatKey +"']").val("0");
            }
          }
          else
          {
             if(ItemGroupRepeatKey != 17)
             {
  			      if (action5==''){
                $("select[name='select_LB@LBCLSIG_"+ ItemGroupRepeatKey +"']").val("0");
              }
             }
             else
             {
              $("select[name='select_LB@LBCLSIG_"+ ItemGroupRepeatKey +"']").val('');
           }            
          } 
         }        
         
       // Copy date from previous section          				
          $("input[name='text_dd_LB@LBDTC_"+ ItemGroupRepeatKey +"']").val(action2);
          $("input[name='text_mm_LB@LBDTC_"+ ItemGroupRepeatKey +"']").val(action3);
          $("input[name='text_yy_LB@LBDTC_"+ ItemGroupRepeatKey +"']").val(action4);
          $("td[name='LB.LBDTC']").hide();
        }
	</script>
	<script>
        //Add buttons for Cockroft and MDRD helpers
        function getCalcImage(onclick){
          var img = new Image();
          img.src = "alixedc/templates/default/images/calc.png";
          img.setAttribute("class", "pointer");
          img.setAttribute("onclick", onclick);
          return img;
        }
        $(document).ready(function(){
          //Cockroft
          $(":[name='text_string_LB@LBORRES_5']").before(getCalcImage("calcCockroft(false);"));
          $(":[name='text_string_LB@LBORRES_5']").css({width: "50px", marginLeft: "5px"});
          //MDRD
          $(":[name='text_string_LB@LBORRES_6']").before(getCalcImage("calcMDRD(false);"));
          $(":[name='text_string_LB@LBORRES_6']").css({width: "50px", marginLeft: "5px"});
          
          //Specifiy units
          $("#LB\\.LBORRESU_LBBIO_4").html("µMol/L");
          $("#LB\\.LBORRESU_LBBIO_5").html("mL/min");
          $("#LB\\.LBORRESU_LBBIO_6").html("mL/min");
        });
        //Cockroft
        function calcCockroft(hideAlert){
          var age = "<xsl:value-of select="$AGE" />"; // Age
          var sex = "<xsl:value-of select="$SEX" />"; // M/F
          var weight = "<xsl:value-of select="$WEIGHT" />"; // Weight in kg
          var srcInput = document.getElementsByName("text_string_LB@LBORRES_4")[0];
          var destInput = document.getElementsByName("text_string_LB@LBORRES_5")[0];
          var separator = "."; // The dot between integers and decimals
          
          var Crea = srcInput.value; // Creatinine µmol/L
          
          if(Crea==''){
            if(hideAlert!=true){
              alert("Creatinine value is unkonwn.");
            }
            return false;
          };
          if(weight==''){
            if(hideAlert!=true){
              alert("Subject's weight is unkonwn.");
            }
            return false;
          };
          if(sex==''){
            if(hideAlert!=true){
              alert("Subject's sex is unkonwn.");
            }
            return false;
          };
          if(age==''){
            if(hideAlert!=true){
              alert("Subject's age is unkonwn.");
            }
            return false;
          };
          if (Crea!=""){
              cr = parseInt(Crea);
              if(sex=='F'){
                var factor = 1.08;
               }else{
                var factor = 1.25;
               }
              
              result = ((140-parseInt(age))*parseInt(weight)/cr)*factor;
              
              ajustedResult = (Math.round(result*10)/10);
              
              intValue = parseInt(ajustedResult);
              decValue = String(Math.round((parseFloat(ajustedResult) - parseInt(ajustedResult)) * 10) / 10).substring(2,3);
              
              finalValue = intValue;
              if(decValue!=""){
                finalValue += separator + decValue;
              }
              
              destInput.value = finalValue;
              
              if(hideAlert!=true){
                alert("Creatinine clearance (Cockroft) : " + finalValue + " mL/min");
              }
          }
        }
        //MDRD
        function calcMDRD(hideAlert){
          var age = "<xsl:value-of select="$AGE" />"; // Age
          var sex = "<xsl:value-of select="$SEX" />"; // M/F
          var race = "<xsl:value-of select="$RACE" />"; // ~skin color // AF if Afroamerican
          var srcInput = document.getElementsByName("text_string_LB@LBORRES_4")[0];
          var destInput = document.getElementsByName("text_string_LB@LBORRES_6")[0];
          var separator = "."; // The dot between integers and decimals
          
          var Crea = srcInput.value; // Creatinine µmol/L
          
          if (Crea==''){
            if(hideAlert!=true){
              alert("Creatinine value is unkonwn.");
            }
            return false;
          }
          if(race==''){
            if(hideAlert!=true){
              alert("Subject's ethnic origin is unkonwn.");
            }
            return false;
          };
          if(sex==''){
            if(hideAlert!=true){
              alert("Subject's sex is unkonwn.");
            }
            return false;
          };
          if(age==''){
            if(hideAlert!=true){
              alert("Subject's age is unkonwn.");
            }
            return false;
          };
          
          cr = Crea * 0.011312;
          result = 186.3 * Math.pow(cr,-1.154) * 
                                Math.pow(age, -0.203) * 
                                (sex=='F' ? 0.742 : 1.0) * 
                                (race=='AF' ? 1.212 : 1.0);
                                
          intValue = parseInt(result);
          decValue = String(parseFloat(result) - parseInt(result)).substring(2,3);
              
          finalValue = intValue;
          if(decValue!=""){
            finalValue += separator + decValue;
          }
          
          destInput.value = finalValue;
          
          if(hideAlert!=true){
            alert("Creatinine clearance (MDRD) : " + finalValue + " mL/min");
          }
        }
	</script>
 </div>  
</xsl:template>
    
</xsl:stylesheet>