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
<xsl:param name="BRTHDTC"/>
	
<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!-- Delete AGEM reported on previous line -->
<xsl:template match="tr[@name='DC.AGEM']">
</xsl:template>

<!--All label and format for calculated field -->
<xsl:template match="td[../@name='DC.AGEY' and @class='ItemDataLabel']">
  <xsl:copy>
    <xsl:attribute name="style">
      color: blue;
    </xsl:attribute>
		<xsl:copy-of select="@*"/>  
      Age at Weakness Onset
  </xsl:copy>  	
</xsl:template>

<!--Compute agey et agem on the same line -->
<xsl:template match="td[@name='DC.AGEY' and @class='ItemDataInput']">
  <xsl:copy>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
    &#160;    
		<input>
		  <xsl:copy-of select="../following-sibling::tr[1]/td[4]/input/@*"/>
		</input>
		Months      
  </xsl:copy>	
</xsl:template>	

<!--Add help label-->
<xsl:template match="td[@name='DC.PERFS' and @class='ItemDataInput']">
 	    	<xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>

   		 <input type="image" src="alixcrf_demobd/templates/default/images/bulb_on.gif"
  				onclick='return showInfo("&lt;table&gt;
  				&lt;tr&gt;
  				  &lt;td&gt;
              0 : Capable d une activité identique à celle précédant la maladie sans aucune restriction.
            &lt;/td&gt;  
            &lt;td&gt;
              &lt;img src=\"alixcrf_demobd/templates/default/images/ps0.png\"/&gt;
            &lt;/td&gt;
            &lt;/tr&gt;
                        &lt;tr&gt;    
                        &lt;td&gt;      
                              1 : Activité physique diminuée mais ambulatoire et capable de mener un travail.
                          &lt;/td&gt; 
                                                  &lt;td&gt;
                        &lt;img src=\"alixcrf_demobd/templates/default/images/ps1.png\"/&gt;
                        &lt;/td&gt;
                          &lt;/tr&gt;  
                          &lt;tr&gt;   
                        &lt;td&gt;                                
                              
                              2 : Ambulatoire et capable de prendre soin de soi-même, incapable de travailler.Alité moins de 50 % de son temps.
                                                      &lt;/td&gt; 
                                                                              &lt;td&gt;
                        &lt;img src=\"alixcrf_demobd/templates/default/images/ps2.png\"/&gt;
                        &lt;/td&gt;
                           &lt;/tr&gt;
                                                      &lt;tr&gt;     
                        &lt;td&gt;    
                              3 : Capable seulement de quelques soins. Alité ou en chaise plus de 50 % du temps.
                                                      &lt;/td&gt; 
                                                                                                                                    &lt;td&gt;
                        &lt;img src=\"alixcrf_demobd/templates/default/images/ps3.png\"/&gt;
                        &lt;/td&gt;
                &lt;/tr&gt;
                                                      &lt;tr&gt;     
                        &lt;td&gt;    
                              4 : Capable de prendre soins de soi-même. Alité ou en chaise en permanence.
                                                                                                            &lt;td&gt;
                        &lt;img src=\"alixcrf_demobd/templates/default/images/ps4.png\"/&gt;
                        &lt;/td&gt;
                &lt;/td&gt;
                &lt;/tr&gt;
                              &lt;/table&gt;
          ",500,550); '/>  
   </xsl:copy>
</xsl:template>

<!--Add Help label on screen -->
<xsl:template match="tr[@name='DC.UPMEMBER']">
   <tr><td></td><td colspan="3">
     <b>History of surgery</b>
    </td>
   </tr>
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>

<!--Add tabs before label -->
<xsl:template match="td[(../@name='DC.CORSET' or ../@name='DC.SURGERY') and @class='ItemDataLabel underCondition']">
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
		&#160;&#160;
	  <xsl:value-of select="."/>
  </xsl:copy>  
</xsl:template>

<!--Call function getAge-->
<xsl:template match="input[@type='text']">
  <xsl:copy>
    <xsl:attribute name="onBlur">getAge();</xsl:attribute>
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Add label "month" -->
<xsl:template match="input[@name='text_mm_DC@DCSTDTC_0' or @name='text_mm_DC@DIAGDTC_0' or @name='text_mm_DC@MEMBDTC_0' or @name='text_mm_DC@HIPSDTC_0' or @name='text_mm_DC@KNEESDTC_0'
                     or @name='text_mm_DC@FEETDTC_0' or @name='text_mm_DC@SPINEDTC_0']">
		month:
  <xsl:copy>
		<xsl:copy-of select="@*"/>  
	  <xsl:value-of select="."/>
  </xsl:copy>  
</xsl:template>

<!--Delete day -->
<xsl:template match="input[@name='text_dd_DC@DCSTDTC_0' or @name='text_dd_DC@DIAGDTC_0' or @name='text_dd_DC@MEMBDTC_0' or @name='text_dd_DC@HIPSDTC_0' or @name='text_dd_DC@KNEESDTC_0'
 or @name='text_dd_DC@FEETDTC_0' or @name='text_dd_DC@SPINEDTC_0' or @name='text_dd_YN@VTSTDTC_0']">
</xsl:template>

<!--Delete information-->
<xsl:template match="span[(@class='optionalText inputItem' or @class='optionalText') and (../@name = 'DC.DCSTDTC' or ../@name = 'DC.DIAGDTC' or ../@name = 'DC.MEMBDTC' or ../@name = 'DC.HIPSDTC' or ../@name = 'DC.KNEESDTC'
 or ../@name = 'DC.FEETDTC' or ../@name = 'DC.SPINEDTC')]">
</xsl:template>

<!-- Javascript treatment -->
<xsl:template match="div[@id='Form']">
  <div id="Form">
  <style>.ItemDataLabel{width:40%;}</style>
		<xsl:apply-templates/>
			<script language="JavaScript">
	   		function getAge()
				{
					//Calcul de l'âge of Wk 
			 
			 
					Dnaiss = '<xsl:value-of select="$BRTHDTC"/>';	
					AnneeNaissance = parseInt(Dnaiss.substr(0,4));
          MoisNaissance = parseInt(Dnaiss.substr(5,2),10);
					MoisWk = parseInt(document.getElementsByName('text_mm_DC@DCSTDTC_0')[0].value,10);
					AnneeWk = parseInt(document.getElementsByName('text_yy_DC@DCSTDTC_0')[0].value);
			 		//document.getElementsByName('text_integer_DC@AGEY_0')[0].value = '';
					//document.getElementsByName('text_integer_DC@AGEM_0')[0].value = '';
					
					if(!isNaN(MoisNaissance)){
					 if(!isNaN(AnneeNaissance)){
            if(!isNaN(MoisWk)){
              if(!isNaN(AnneeWk)){
								if (Math.min(MoisWk,MoisNaissance)==MoisWk) 
                {
                  if (MoisWk != MoisNaissance){
                    MoisWk = MoisWk + 12;
                    AnneeNaissance = AnneeNaissance + 1;
                  }
                }
                nbrAnnee = AnneeWk - AnneeNaissance;
                nbrMois = MoisWk - MoisNaissance;

				      	document.getElementsByName('text_integer_DC@AGEY_0')[0].value = nbrAnnee;
				      	document.getElementsByName('text_integer_DC@AGEM_0')[0].value = nbrMois;
					   		}
              }
            }
				  }		
				$("input[name='text_integer_DC@AGEY_0']").attr("readonly",true);
				$("input[name='text_integer_DC@AGEM_0']").attr("readonly",true);
			  }
			 
			 
				function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{                                                                  
           					//// grisage DC.DRUG et DC.DRUGDTC
					input_destination2 = 'DC.DRUG'; /*ITEMOID=destination*/
					input_destination3 = 'DC.DRUGDTC'; /*ITEMOID=destination*/					
					input_origin1 = 'select_DC@OTHTRIAL_0';
			
					if(origin.name==input_origin1) 
					{
						action1 = $("select[name='select_DC@OTHTRIAL_0']").val();

						if(typeof(action1)=="undefined"||action1!=1||action1=='')
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination3,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination2,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination3,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}
         if(ItemGroupOID =='DCH'){
  					//// grisage DC.COBBANGL
  					input_destination4 = 'DC.COBBANGL'; /*ITEMOID=destination*/		
  					input_origin2 = 'select_DC@SCOLIOS_0';
  			
  					if(origin.name==input_origin2) 
  					{
  						action2 = $("select[name='select_DC@SCOLIOS_0']").val();
  
  						if(typeof(action2)=="undefined"||action2!=1||action2=='')
  						{
  							freezeFields(input_destination4,ItemGroupOID,ItemGroupRepeatKey,true,true,false);
  						}
  						else
  						{
  							freezeFields(input_destination4,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
  						}
  					}	
          }
					//// grisage DC.CORSET and DC.SURGERY
					input_destination5 = 'DC.CORSET'; /*ITEMOID=destination*/	
					input_destination6 = 'DC.SURGERY'; /*ITEMOID=destination*/						
					input_origin2 = 'select_DC@SCOLIOS_0';
					input_origin3 = 'text_integer_DC@COBBANGL_0';
			
					if(origin.name==input_origin2||origin.name==input_origin3) 
					{
						action2 = $("select[name='select_DC@SCOLIOS_0']").val();
						action3 = $("input[name='text_integer_DC@COBBANGL_0']").val();
						
						if(typeof(action2)=="undefined"||action2!=1||action2=='')
						{
							freezeFields(input_destination5,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination6,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{					
							if(Math.max(action3,19)==19||Math.min(action3,41)==41)
							{
								freezeFields(input_destination5,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							}
							else
							{
								freezeFields(input_destination5,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							}
							
							if(Math.max(action3,45)==45)
							{
								freezeFields(input_destination6,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							}
							else
							{						
								freezeFields(input_destination6,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							}
              
              if(action3==''||typeof(action3)=="undefined") 
              {
							freezeFields(input_destination5,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination6,ItemGroupOID,ItemGroupRepeatKey,false,false,false);              
              }							
  					}	
					}	
					
					//// grisage DC.MEMBDTC
					input_destination7 = 'DC.MEMBDTC'; /*ITEMOID=destination*/		
					input_origin4 = 'select_DC@UPMEMBER_0';
			
					if(origin.name==input_origin4) 
					{
						action4 = $("select[name='select_DC@UPMEMBER_0']").val();

						if(typeof(action4)=="undefined"||action4!=1||action4=='')
						{
							freezeFields(input_destination7,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination7,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}	

					//// grisage DC.HIPSDTC
					input_destination8 = 'DC.HIPSDTC'; /*ITEMOID=destination*/		
					input_origin5 = 'select_DC@HIPS_0';
			
					if(origin.name==input_origin5) 
					{
						action5 = $("select[name='select_DC@HIPS_0']").val();

						if(typeof(action5)=="undefined"||action5!=1||action5=='')
						{
							freezeFields(input_destination8,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination8,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}						

					//// grisage DC.KNEESDTC
					input_destination9 = 'DC.KNEESDTC'; /*ITEMOID=destination*/		
					input_origin6 = 'select_DC@KNEES_0';
			
					if(origin.name==input_origin6) 
					{
						action6 = $("select[name='select_DC@KNEES_0']").val();

						if(typeof(action6)=="undefined"||action6!=1||action6=='')
						{
							freezeFields(input_destination9,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination9,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}							
					
					//// grisage DC.FEETDTC
					input_destination10 = 'DC.FEETDTC'; /*ITEMOID=destination*/		
					input_origin7 = 'select_DC@FEET_0';
			
					if(origin.name==input_origin7) 
					{
						action7 = $("select[name='select_DC@FEET_0']").val();

						if(typeof(action7)=="undefined"||action7!=1||action7=='')
						{
							freezeFields(input_destination10,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination10,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}								

					//// grisage DC.SPINEDTC
					input_destination11 = 'DC.SPINEDTC'; /*ITEMOID=destination*/		
					input_origin8 = 'select_DC@SPINE_0';
			
					if(origin.name==input_origin8) 
					{
						action8 = $("select[name='select_DC@SPINE_0']").val();

						if(typeof(action8)=="undefined"||action8!=1||action8=='')
						{
							freezeFields(input_destination11,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination11,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}		

					//// grisage DC.WAGEY and DC.WAGEM
					input_destination12 = 'DC.WAGEY'; /*ITEMOID=destination*/		
					input_destination13 = 'DC.WAGEM'; /*ITEMOID=destination*/		
					input_origin9 = 'select_DC@OUTWALK_0';
					input_origin10 = 'select_DC@INDWALK_0';
			
					if(origin.name==input_origin9||origin.name==input_origin10) 
					{
						action9 = $("select[name='select_DC@OUTWALK_0']").val();
						action10 = $("select[name='select_DC@INDWALK_0']").val();

						if(typeof(action9)=="undefined"||action9!=0||action9=='')
						{
						if(typeof(action10)=="undefined"||action10!=0||action10=='')
						{
							freezeFields(input_destination12,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
							freezeFields(input_destination13,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
					{
							freezeFields(input_destination12,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination13,ItemGroupOID,ItemGroupRepeatKey,false,false,false);			
					}
						}
						else
						{
							freezeFields(input_destination12,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
							freezeFields(input_destination13,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}	
			
					
					//// grisage DC.APPARPY
					input_destination17 = 'DC.APPARPY'; /*ITEMOID=destination*/		
					input_origin12 = 'select_DC@APPARYN_0';
			
					if(origin.name==input_origin12) 
					{
						action12 = $("select[name='select_DC@APPARYN_0']").val();

						if(typeof(action12)=="undefined"||action12!=1||action12=='')
						{
							freezeFields(input_destination17,ItemGroupOID,ItemGroupRepeatKey,true,false,false);
						}
						else
						{
							freezeFields(input_destination17,ItemGroupOID,ItemGroupRepeatKey,false,false,false);
						}
					}	
          if (loading==false){
					 getAge();
					}
				}

	</script>
	    </div>  
</xsl:template>
   
</xsl:stylesheet>