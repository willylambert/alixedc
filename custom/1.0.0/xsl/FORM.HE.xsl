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

   		 <input type="image" src="alixedc/templates/default/images/bulb_on.gif"
  				onclick='return showInfo("&lt;table&gt;
  				&lt;tr&gt;
  				  &lt;td&gt;
              0 : Capable d une activité identique à celle précédant la maladie sans aucune restriction.
            &lt;/td&gt;  
            &lt;td&gt;
              &lt;img src=\"alixedc/templates/default/images/ps0.png\"/&gt;
            &lt;/td&gt;
            &lt;/tr&gt;
                        &lt;tr&gt;    
                        &lt;td&gt;      
                              1 : Activité physique diminuée mais ambulatoire et capable de mener un travail.
                          &lt;/td&gt; 
                                                  &lt;td&gt;
                        &lt;img src=\"alixedc/templates/default/images/ps1.png\"/&gt;
                        &lt;/td&gt;
                          &lt;/tr&gt;  
                          &lt;tr&gt;   
                        &lt;td&gt;                                
                              
                              2 : Ambulatoire et capable de prendre soin de soi-même, incapable de travailler.Alité moins de 50 % de son temps.
                                                      &lt;/td&gt; 
                                                                              &lt;td&gt;
                        &lt;img src=\"alixedc/templates/default/images/ps2.png\"/&gt;
                        &lt;/td&gt;
                           &lt;/tr&gt;
                                                      &lt;tr&gt;     
                        &lt;td&gt;    
                              3 : Capable seulement de quelques soins. Alité ou en chaise plus de 50 % du temps.
                                                      &lt;/td&gt; 
                                                                                                                                    &lt;td&gt;
                        &lt;img src=\"alixedc/templates/default/images/ps3.png\"/&gt;
                        &lt;/td&gt;
                &lt;/tr&gt;
                                                      &lt;tr&gt;     
                        &lt;td&gt;    
                              4 : Capable de prendre soins de soi-même. Alité ou en chaise en permanence.
                                                                                                            &lt;td&gt;
                        &lt;img src=\"alixedc/templates/default/images/ps4.png\"/&gt;
                        &lt;/td&gt;
                &lt;/td&gt;
                &lt;/tr&gt;
                              &lt;/table&gt;
          ",500,550); '/>  
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


<!--Delete day -->
<xsl:template match="input[@name='text_dd_DC@DCSTDTC_0' or @name='text_dd_DC@DIAGDTC_0']">
</xsl:template>

<!--Delete information-->
<xsl:template match="span[(@class='optionalText inputItem' or @class='optionalText') and (../@name = 'DC.DCSTDTC' or ../@name = 'DC.DIAGDTC' or ../@name = 'DC.MEMBDTC' or ../@name = 'DC.HIPSDTC')]">
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

          if (loading==false){
					 getAge();
					}
				}

	</script>
	    </div>  
</xsl:template>
   
</xsl:stylesheet>