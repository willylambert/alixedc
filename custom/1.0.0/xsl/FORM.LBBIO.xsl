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

<!--Catch all non treated tags, print them without treatment-->
<xsl:template match="*">
   <xsl:copy>
       <xsl:copy-of select="@*"/>
       <xsl:apply-templates/>
   </xsl:copy>
</xsl:template>    

<!--Disabled BHCG for man -->
<xsl:template match="input[@name='text_integer_LB@LBSEQ_17' or @name='text_string_LB@LBORRES_17' or @name='text_string_LB@LBORNRHI_17' or @name='text_string_LB@LBORNRLO_17' or @name='text_string_LB@LBORRESU_17']">
  <xsl:copy>
    <xsl:if test="$SEX='1'">
      <xsl:attribute name="disabled">disabled</xsl:attribute>
    </xsl:if>   
  <xsl:copy-of select="@*"/>
  </xsl:copy>
</xsl:template>      

<xsl:template match="select[@name='select_LB@LBCLSIG_17' or @name ='select_LB@LBORNRCL_17' or @name = 'select_LB@LBORNRCH_17' or @name = 'select_LB@LBNAM_17']">
  <xsl:copy>
    <xsl:if test="$SEX='1'">
      <xsl:attribute name="disabled">disabled</xsl:attribute>
    </xsl:if> 
    <xsl:copy-of select="@*"/>
    <xsl:apply-templates/>
  </xsl:copy>
</xsl:template>        

<!--Cath select value for LBTEST and hide dropdown list -->
<xsl:template match="select[starts-with(@name,'select_LB@LBTEST')]">
   <xsl:value-of select="option[@selected='selected']/text()"/>
   <!--Pour les besoins de l'enregistrement, un input doit être présent, on le met dans un input hidden-->
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
 </div>  
</xsl:template>
    
</xsl:stylesheet>