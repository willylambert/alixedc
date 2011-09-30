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

<xsl:param name="AGE"/>

<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:apply-templates/>
  </xsl:copy>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='QSSF36.Q1']">
    <tr><td></td>
    <td colspan="4">
     <a href="alixcrf_demobd/documents/SF36.pdf" target="new"><img src="alixcrf_demobd/templates/default/images/folder.png" width="20" height="20"/> <b>SF36 Scale</b></a>
    </td>
   </tr> 
     <tr style="background-color:#F4F8FF;">
     <td colspan="2"></td>
	 <td colspan = "3">
	 <table>
<tr><td class="ItemDataLabel underCondition">
This survey asks for your views about your health. This information will help you keep track of how you feel and how well you are able to do your usual activities.
</td></tr>
   <tr><td class="ItemDataLabel underCondition">
Please answer every question. Some questions may look like others, but each one is different. Please take the time to read and answer each question carefully, and choose item that best describes your answer.
</td></tr>	
		</table>
		</td>
   </tr>
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
     </xsl:copy>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='QSSF36.Q2']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
     </xsl:copy>
   <tr><td></td>
    <td colspan = "3" style="color:#0094FF;" class="ItemDataLabel">During the past 4 weeks
		</td>
   </tr>
   <tr><td></td>
    <td colspan = "3" style="color:#0094FF;" class="ItemDataLabel underCondition">  
     How much of the time have you had any of the following problems with your work or other regular daily activities as a result of your physical health?
		</td>
   </tr>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='QSSF36.Q6']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
     </xsl:copy>
   <tr><td></td>
    <td colspan = "3" style="color:#0094FF;" class="ItemDataLabel underCondition">How much of the time have you had any of the following problems with your work or other regular daily activities as a result of any emotional problems (such as feeling depressed or anxious)?
		</td>
   </tr>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='QSSF36.Q13']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
     </xsl:copy>
   <tr><td></td>
    <td colspan = "3" style="color:#0094FF;" class="ItemDataLabel underCondition">The following questions are about activities you might do during a typical day. Does your health now limit you in these activities? If so, how much?
		</td>
   </tr>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='QSSF36.Q23']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
     </xsl:copy>
   <tr><td></td>
    <td colspan = "3" style="color:#0094FF;" class="ItemDataLabel underCondition"> These questions are about how you feel and how things have been with you during the past 4 weeks. For each question, please give the one answer that comes closest to the way you have been feeling. How much of the time during the past 4 weeks...</td>
   </tr>
</xsl:template>

<!--Add help for data entry -->
<xsl:template match="tr[@name='QSSF36.Q32']">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
     </xsl:copy>
   <tr><td></td>
    <td colspan = "3" style="color:#0094FF;" class="ItemDataLabel underCondition">How TRUE or FALSE is each of the following statements for you.
		</td>
   </tr>
</xsl:template>
                  
<!-- Format for calculated fields -->
<xsl:template match="td[(../@name='QSSF36.SF36MCS' or ../@name='QSSF36.SF36PCS') and @class='ItemDataLabel']">
  <xsl:copy>
    <xsl:attribute name="style">
      color: blue;
    </xsl:attribute>
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
				// update UI
        function updateUI(origin,loading,ItemGroupOID,ItemGroupRepeatKey)
				{
  				 if (loading == false)
           {
            getScore();  
           }     				      
				}
        			
			 // Calculation of SF36 score
				function getScore()
				{
        //Calcul du score 
        $("input[name='text_integer_QSSF36@SF36MCS_0']").val();
        $("input[name='text_integer_QSSF36@SF36PCS_0']").val();
        
        PF1 = parseInt($("select[name='select_QSSF36@Q14_0']").val());
        PF2 = parseInt($("select[name='select_QSSF36@Q15_0']").val());
        PF3 = parseInt($("select[name='select_QSSF36@Q16_0']").val());
        PF4 = parseInt($("select[name='select_QSSF36@Q17_0']").val());
	   		PF5 = parseInt($("select[name='select_QSSF36@Q18_0']").val());
	   		PF6 = parseInt($("select[name='select_QSSF36@Q19_0']").val());
	   		PF7 = parseInt($("select[name='select_QSSF36@Q20_0']").val());
	   		PF8 = parseInt($("select[name='select_QSSF36@Q21_0']").val());
	   		PF9 = parseInt($("select[name='select_QSSF36@Q22_0']").val());
	   		PF10 = parseInt($("select[name='select_QSSF36@Q23_0']").val());
                                  
        scorePF = NaN;

        if(!isNaN(PF1)){
          if(!isNaN(PF2)){
            if(!isNaN(PF3)){
						  if(!isNaN(PF4)){
                if(!isNaN(PF5)){
                  if(!isNaN(PF6)){
                    if(!isNaN(PF7)){
                      if(!isNaN(PF8)){
                        if(!isNaN(PF9)){
                          if(!isNaN(PF10)){
                            scorePF = PF1 + PF2 + PF3 + PF4 + PF5 + PF6 + PF7 + PF8 + PF9 + PF10;
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
  				 
  			scoreRP = NaN;
  				
  			RP1 = parseInt($("select[name='select_QSSF36@Q3_0']").val());
        RP2 = parseInt($("select[name='select_QSSF36@Q4_0']").val());
        RP3 = parseInt($("select[name='select_QSSF36@Q5_0']").val());
        RP4 = parseInt($("select[name='select_QSSF36@Q6_0']").val());
                  					
  			if(!isNaN(RP1)){
          if(!isNaN(RP2)){
            if(!isNaN(RP3)){
              if(!isNaN(RP4)){
                scoreRP = RP1 + RP2 + RP3 + RP4;
              }
            }
          }
        }     
  				
  			scoreBP = NaN;

  			BP1 = parseInt($("select[name='select_QSSF36@Q11_0']").val());
        BP2 = parseInt($("select[name='select_QSSF36@Q12_0']").val());
                  					
  			if(!isNaN(BP1)){
          if(!isNaN(BP2)){
            scoreBP = BP1 + BP2;
          }
        }      			
  			
        scoreGH = NaN;

				GH1 = parseInt($("select[name='select_QSSF36@Q1_0']").val());
        GH2 = parseInt($("select[name='select_QSSF36@Q33_0']").val());
        GH3 = parseInt($("select[name='select_QSSF36@Q34_0']").val());
        GH4 = parseInt($("select[name='select_QSSF36@Q35_0']").val());
        GH5 = parseInt($("select[name='select_QSSF36@Q36_0']").val());
                   					
        if(!isNaN(GH1)){
          if(!isNaN(GH2)){
            if(!isNaN(GH3)){
              if(!isNaN(GH4)){
                if(!isNaN(GH5)){
                  scoreGH = GH1 + GH2 + GH3 + GH4 + GH5;
                }
              }
            }
          }
        }     
            
        scoreVT = NaN;
        
 				VT1 = parseInt($("select[name='select_QSSF36@Q24_0']").val());
        VT2 = parseInt($("select[name='select_QSSF36@Q28_0']").val());
        VT3 = parseInt($("select[name='select_QSSF36@Q30_0']").val());
        VT4 = parseInt($("select[name='select_QSSF36@Q32_0']").val());
                   					
        if(!isNaN(VT1)){
          if(!isNaN(VT2)){
            if(!isNaN(VT3)){
              if(!isNaN(VT4)){
                scoreVT = VT1 + VT2 + VT3 + VT4;
              }
            }
          }
        }
 
        scoreSF = NaN;
        
  			SF1 = parseInt($("select[name='select_QSSF36@Q10_0']").val());
        SF2 = parseInt($("select[name='select_QSSF36@Q13_0']").val());
                  					
  			if(!isNaN(SF1)){
          if(!isNaN(SF2)){
            scoreSF = SF1 + SF2;
          }
        }  

        scoreRE = NaN;

 				RE1 = parseInt($("select[name='select_QSSF36@Q7_0']").val());
        RE2 = parseInt($("select[name='select_QSSF36@Q8_0']").val());
        RE3 = parseInt($("select[name='select_QSSF36@Q9_0']").val());
                   					
        if(!isNaN(RE1)){
          if(!isNaN(RE2)){
            if(!isNaN(RE3)){
              scoreRE = RE1 + RE2 + RE3;
            }
          }
        }
                
        scoreMH = NaN;
        
   			MH1 = parseInt($("select[name='select_QSSF36@Q25_0']").val());
        MH2 = parseInt($("select[name='select_QSSF36@Q26_0']").val());
        MH3 = parseInt($("select[name='select_QSSF36@Q27_0']").val());
        MH4 = parseInt($("select[name='select_QSSF36@Q29_0']").val());
        MH5 = parseInt($("select[name='select_QSSF36@Q31_0']").val());
                   					
        if(!isNaN(MH1)){
          if(!isNaN(MH2)){
            if(!isNaN(MH3)){
              if(!isNaN(MH4)){
                if(!isNaN(MH5)){
                  scoreMH = MH1 + MH2 + MH3 + MH4 + MH5;
                }
              }
            }
          }
        }    
          
        scorePCS = NaN;
         // PCS_z = (PF_Z * .42402) + (RP_Z * .35119) + (BP_Z * .31754) + (GH_Z * .24954) + (EF_Z * .02877) + (SF_Z * -.00753) + (RE_Z * -.19206) + (EW_Z * -.22069)
        //MCS_z = (PF_Z * -.22999) + (RP_Z * -.12329) + (BP_Z * -.09731) + (GH_Z * -.01571) + (EF_Z * .23534) + (SF_Z * .26876) + (RE_Z * .43407) + (EW_Z * .48581)
        // PCS = (PCS_z*10) + 50
        // MCS = (MCS_z*10) + 50

        
        scoreMCS = NaN;
             
   	  	if(!isNaN(scorePF)){
          if(!isNaN(scoreRP)){
            if(!isNaN(scoreBP)){
              if(!isNaN(scoreGH)){
                if(!isNaN(scoreVT)){
                  if(!isNaN(scoreSF)){
                    if(!isNaN(scoreRE)){
                      if(!isNaN(scoreMH)){
                        scorePCS = (scorePF * 0.42402) + (scoreRP * 0.35119) + (scoreBP * 0.31754)  + (scoreGH * 0.24954) + (scoreVT * 0.02877) + (scoreSF * -0.00753) + (scoreRE * -0.19206) + (scoreMH * -0.22069);
                        scorePCS = (scorePCS*10)+ 50;
                        scorePCS = Math.round(scorePCS);
                        $("input[name='text_integer_QSSF36@SF36PCS_0']").val(scorePCS);
										    $("input[name='text_integer_QSSF36@SF36PCS_0']").attr("readonly",true);
										    
										    scoreMCS = (scorePF * 0.22999) + (scoreRP * -0.12329) + (scoreBP * -0.09731)  + (scoreGH * -0.01571) + (scoreVT * 0.23534) + (scoreSF * 0.26876) + (scoreRE * 0.43407) + (scoreMH * 0.48581);
                        scoreMCS = (scoreMCS*10)+ 50;
                        scoreMCS = Math.round(scoreMCS);
                        $("input[name='text_integer_QSSF36@SF36MCS_0']").val(scoreMCS);
										    $("input[name='text_integer_QSSF36@SF36MCS_0']").attr("readonly",true);
										    
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
                 
    </script>
  </div>  
</xsl:template>
   
</xsl:stylesheet>