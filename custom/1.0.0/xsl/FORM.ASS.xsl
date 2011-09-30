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
<xsl:param name="POSV0"/>
<xsl:param name="POSV1"/>
<xsl:param name="POSV2"/>
<xsl:param name="POSV3"/>
<xsl:param name="POSV4"/>
<xsl:param name="POSV5"/>
<xsl:param name="POSV6"/>
<xsl:param name="POSV7"/>
<xsl:param name="POSV8"/>

<!--Catch all des balise non traitées, on les restitue tel quel-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Mask EXCAT-->
<xsl:template match="tr[@name='EXDA.EXCAT']">
   <xsl:copy>
        <xsl:copy-of select="@*"/>
       <xsl:attribute name="style">display:none;</xsl:attribute>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Add format for calculated fields-->
<xsl:template match="td[../@name='DA.DACOMP' and @class='ItemDataLabel']">
  <xsl:copy>
    <xsl:attribute name="style">color: blue;</xsl:attribute>
		<xsl:copy-of select="@*"/>  
		&#160;&#160;
	  <xsl:value-of select="."/>
  </xsl:copy>  	
</xsl:template>

<!--Delete Fields for specific StudyEvent -->
<xsl:template match="tr[(@name='EXDA.EXLOT3' or @name='EXDA.EXLOT4' or @name='EXDA.EXLOT5')]">
  <xsl:if test="$StudyEventOID!='3'">
    <xsl:copy>
      <xsl:copy-of select="@*"/>
      <xsl:apply-templates/>
    </xsl:copy>     
  </xsl:if>
</xsl:template>

<!--Add help label on screen-->
<xsl:template match="tr[@name='EXDA.EXLOT1' and ../../@name='EXDA']">
   <tr><td></td>
	 <td colspan = "3">
Treatment number dispensed at the last visit
    </td>
		</tr>
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>	 
</xsl:template>

<!--Add tab before fields -->
<xsl:template match="td[(../@name='EXDA.EXLOT1' or ../@name='EXDA.EXLOT2' or ../@name='EXDA.EXLOT3' or ../@name='EXDA.EXLOT4' or ../@name='EXDA.EXLOT5') and @class='ItemDataLabel' and ../../../@name='EXDAN']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
  	&#160;&#160;
	  <xsl:value-of select="."/>
  </xsl:copy>  
</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
		<xsl:apply-templates/>
			<script language="JavaScript">
			
	<!-- Compliance calculation -->						
        function getCompl()
				{
           // calcul de la compliance
           // V1 (250 x  Number of brought back bottles at V1) - (250 x Number of non opened bottles at V1) -  (29.734 x Sum of drug left in used bottles (cm) at V1 – 23.905) 
         document.getElementsByName('text_integer_DA@DACOMP_0')[0].value = ''; 
         var SEOID = '<xsl:value-of select="$StudyEventOID"/>';
         var DAPOSV0 = '<xsl:value-of select="$POSV0"/>';
         var DAPOSV1 = '<xsl:value-of select="$POSV1"/>';
         var DAPOSV2 = '<xsl:value-of select="$POSV2"/>';
         var DAPOSV3 = '<xsl:value-of select="$POSV3"/>';
         var DAPOSV4 = '<xsl:value-of select="$POSV4"/>';
         var DAPOSV5 = '<xsl:value-of select="$POSV5"/>';
         var DAPOSV6 = '<xsl:value-of select="$POSV6"/>';
         var DAPOSV7 = '<xsl:value-of select="$POSV7"/>';
         var DAPOSV8 = '<xsl:value-of select="$POSV8"/>';                               
         
         switch (SEOID) {
          case "3":
           Volpresc = 28 * DAPOSV0;
           break;
          case "5":
           Volpresc = 63 * DAPOSV1;
           break;
          case "6":
           Volpresc = 91 * DAPOSV2;
           break;
          case "7":
           Volpresc = 91 * DAPOSV3;
           break;
          case "8":
           Volpresc = 91 * DAPOSV4;
           break;
          case "9":
           Volpresc = 91 * DAPOSV5;
           break;
          case "10":
           Volpresc = 91 * DAPOSV6;
           break;  
          case "11":
           Volpresc = 91 * DAPOSV7;
           break;   
          case "12":
           Volpresc = 91 * DAPOSV8;
           break;                                                                   
           default: 
           Volpresc = 0;
           break;
        }
        if (!isNaN(Volpresc)){
          Cmrestint = parseInt(document.getElementsByName('text_int_DA@DAVOL_0')[0].value);
          if (!isNaN(Cmrestint)){
            Cmrestdec = parseInt(document.getElementsByName('text_dec_DA@DAVOL_0')[0].value);
            if (!isNaN(Cmrestdec)){
              Cmrest = Cmrestint +"."+ Cmrestdec;
              Volrest = (29.734*Cmrest)- 23.905 ;  
                
              NbrRet = parseInt(document.getElementsByName('text_integer_DA@DARET_0')[0].value);
              if (!isNaN(NbrRet)){
                VolRet =  NbrRet * 250;
                Nbrnonopened = parseInt(document.getElementsByName('text_integer_DA@DANOP_0')[0].value);
                if (!isNaN(Nbrnonopened)){               
                  VolNonopened =  Nbrnonopened * 250;
                  Voltaken = VolRet - VolNonopened - Volrest;
                  if(Volpresc != 0){
                    Compl = (Voltaken /Volpresc)* 100; 
                    Compl = Math.round(Compl); 
                    document.getElementsByName('text_integer_DA@DACOMP_0')[0].value = Compl;
                  }
                }
              }
            }
          }
        }          
			}		
			
        function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey){  
        // verouillage des champs lots
        
					
           if (ItemGroupOID = 'DAC'){       
        	   $("input[name='text_integer_DA@DACOMP_0']").attr('readonly', 'true'); 	  
        	   //calcul de la compliance
              input_origin3 = 'text_integer_DA@DACOMP_0';
              input_origin4 = 'text_integer_DA@DADISP_0';
              input_origin5 = 'text_integer_DA@DANOP_0';
              input_origin6 = 'text_int_DA@DAVOL_0';
              input_origin7 = 'text_dec_DA@DAVOL_0';              
              
    				if(origin.name==input_origin3||origin.name==input_origin4||origin.name==input_origin5||origin.name==input_origin6||origin.name==input_origin7) 
  					{
  	             getCompl();				
            }  
       	
          }
         
         
					//// grisage EXDA.EXSTDTCN
					
					if (ItemGroupOID = 'EXDAN'){
  					input_destination = 'EXDA.EXSTDTCN'; /*ITEMOID=destination*/
            input_destination1 = 'EXDA.EXLOT1'; /*ITEMOID=destination*/
            input_destination2 = 'EXDA.EXLOT2'; /*ITEMOID=destination*/
            input_destination3 = 'EXDA.EXLOT3'; /*ITEMOID=destination*/
            input_destination4 = 'EXDA.EXLOT4'; /*ITEMOID=destination*/
            input_destination5 = 'EXDA.EXLOT5'; /*ITEMOID=destination*/
  
  					input_origin = 'select_EXDA@EXYN_0';
					
  					if(origin.name==input_origin) 
  					{
  						action = $("select[name='select_EXDA@EXYN_0']").val();
  
  						if(typeof(action)=="undefined"||action==''||action!=1)
  						{
  							freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
  							$("form[name='EXDAN'] input[name='text_string_EXDA@EXLOT1_0']").attr('disabled', 'disabled');
  							$("form[name='EXDAN'] input[name='text_string_EXDA@EXLOT2_0']").attr('disabled', 'disabled');	
                $("form[name='EXDAN'] input[name='text_string_EXDA@EXLOT3_0']").attr('disabled', 'disabled');	
                $("form[name='EXDAN'] input[name='text_string_EXDA@EXLOT4_0']").attr('disabled', 'disabled');	
                $("form[name='EXDAN'] input[name='text_string_EXDA@EXLOT5_0']").attr('disabled', 'disabled');	
                
  						}
  						else
  						{
                freezeFields(input_destination,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
  							$("input[name='text_string_EXDA@EXLOT1_0']").attr('disabled', '');
  							$("input[name='text_string_EXDA@EXLOT2_0']").attr('disabled', '');
  							$("input[name='text_string_EXDA@EXLOT3_0']").attr('disabled', '');
  							$("input[name='text_string_EXDA@EXLOT4_0']").attr('disabled', '');
  							$("input[name='text_string_EXDA@EXLOT5_0']").attr('disabled', '');                  							
              }
  					}	
          }
          if (ItemGroupOID = 'DAC'){
   					//// grisage DA.DISREA et DA.DISDTC
  					input_destination6 = 'DA.DISREA'; /*ITEMOID=destination*/
            input_destination7 = 'DA.DISDTC'; /*ITEMOID=destination*/
            input_destination9 = 'DA.RENEDTC'; /*ITEMOID=destination*/				
  					input_origin1 = 'select_DA@TRTDISYN_0';
			
  					if(origin.name==input_origin1) 
  					{
  						action1 = $("select[name='select_DA@TRTDISYN_0']").val();
  
  						if(typeof(action1)=="undefined"||action1==''||action1!=1)
  						{
  							freezeFields(input_destination6,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
                freezeFields(input_destination7,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
                freezeFields(input_destination9,ItemGroupOID,ItemGroupRepeatKey,true,false,false);	  	              					
  						}
  						else
  						{
                freezeFields(input_destination6,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
                freezeFields(input_destination7,ItemGroupOID,ItemGroupRepeatKey,false,false,false);              
                freezeFields(input_destination9,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
              }
  					}	         

    					//// grisage DA.DASTPREA
  					input_destination8 = 'DA.DASTPREA'; /*ITEMOID=destination*/
            input_destination10 = 'DA.DAENDTC'; /*ITEMOID=destination*/
                      		
  					input_origin2 = 'select_DA@DAYN_0';
  			
  					if(origin.name==input_origin2) 
  					{
  						action2 = $("select[name='select_DA@DAYN_0']").val();
  
  						if(typeof(action2)=="undefined"||action2==''||action2!=1)
  						{
  							freezeFields(input_destination8,ItemGroupOID,ItemGroupRepeatKey,true,false,false);					
                freezeFields(input_destination10,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
  						}
  						else
  						{
                freezeFields(input_destination8,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
                freezeFields(input_destination10,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
              }
  					}	        
				  }		
        }
      </script>
  </div>
</xsl:template>   
 
</xsl:stylesheet>