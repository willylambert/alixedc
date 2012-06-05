<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *                                                                       *
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
    
require_once("class.CommonFunctions.php");

class bostats extends CommonFunctions
{  

  //Constructeur
  function __construct(&$tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }
  
  function getStudyStats($sitelist)
  {

    $this->addLog("bocstats->getSiteStats($sitelist)",INFO);
    $query = "for \$site in $sitelist
                let \$NbPatSite := count(collection('ClinicalData')/odm:ODM/odm:ClinicalData/odm:SubjectData
                                         [odm:StudyEventData[@StudyEventOID='".$this->m_tblConfig['SITEID']['SEOID']."']/
                                          odm:FormData[@FormOID='".$this->m_tblConfig['SITEID']['FRMOID']."']/
                                          odm:ItemGroupData[@ItemGroupOID='".$this->m_tblConfig['SITEID']['IGOID']."']/
                                          odm:ItemDataString[@ItemOID='".$this->m_tblConfig['SITEID']['ITEMOID']."']=\$site])
                let \$NbPatWithQueries := count(collection('ClinicalData')/odm:ODM/odm:ClinicalData/odm:SubjectData
                                         [odm:StudyEventData[@StudyEventOID='".$this->m_tblConfig['SITEID']['SEOID']."']/
                                          odm:FormData[@FormOID='".$this->m_tblConfig['SITEID']['FRMOID']."']/
                                          odm:ItemGroupData[@ItemGroupOID='".$this->m_tblConfig['SITEID']['IGOID']."']/
                                          odm:ItemDataString[@ItemOID='".$this->m_tblConfig['SITEID']['ITEMOID']."']=\$site 
                                          and 
                                          odm:StudyEventData/odm:FormData/odm:Annotation/odm:Flag/odm:FlagValue/text()='INCONSISTENT'])
                order by \$site
                return
                      <Stats SiteId='{\$site}'
                             NbPatWithQueries='{\$NbPatWithQueries}'
                             NbPatSite='{\$NbPatSite}'/>

             ";
    try{
    $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . "</html>";
      $this->addLog($str,FATAL);
      die($str);
    }
    return $doc;      
  }

  function getSAElist(){
    $this->addLog("bostats->getAElist()",INFO);
    
    //L'audit trail engendre plusieurs ItemData avec le même ItemOID, ce qui nous oblige
    //pour chaque item à rechercher le dernier en regardant l'attribut AuditRecordID qui est le plus grand, et ce pour chaque item
    $query = "  
      declare function local:getLastValue(\$ItemData as node()*) as xs:string?
      {
        let \$MaxAuditRecordID := max(\$ItemData/string(@AuditRecordID))
        return \$ItemData[@AuditRecordID=\$MaxAuditRecordID]/string()      
      };
      
          <aes>
              { 
                let \$SubjCol := collection('ClinicalData')
                for \$ItemGroupDataAE in \$SubjCol/odm:ODM/odm:ClinicalData/odm:SubjectData/odm:StudyEventData[@StudyEventOID='AE']/odm:FormData[@FormOID='FORM.AE']/odm:ItemGroupData[@ItemGroupOID='AE' and @TransactionType!='Remove']
                let \$SubjectData := \$ItemGroupDataAE/../../../../odm:SubjectData
                let \$MetaDataVersion := collection('MetaDataVersion')/odm:ODM/odm:Study/odm:MetaDataVersion[@OID=\$SubjectData/../@MetaDataVersionOID]
                let \$ItemGroupDataENROL := \$SubjectData/odm:StudyEventData[@StudyEventOID='1']/odm:FormData[@FormOID='FORM.ENROL']/odm:ItemGroupData[@ItemGroupOID='ENROL']

                let \$SiteId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITEID'])                
                let \$SiteName := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SITENAME'])
                let \$SubjId := local:getLastValue(\$ItemGroupDataENROL/odm:ItemDataString[@ItemOID='ENROL.SUBJID'])
  							
                let \$Serious := local:getLastValue(\$ItemGroupDataAE/odm:*[@ItemOID='AE.AESER'])
                
                let \$Diag := local:getLastValue(\$ItemGroupDataAE/odm:*[@ItemOID='AE.AETERM'])
                let \$Action := local:getLastValue(\$ItemGroupDataAE/odm:*[@ItemOID='AE.AEACN'])
                let \$Outcome :=  local:getLastValue(\$ItemGroupDataAE/odm:*[@ItemOID='AE.AEOUT'])
                let \$Relation :=  local:getLastValue(\$ItemGroupDataAE/odm:*[@ItemOID='AE.AEREL'])
  							
                let \$ActionDecode := \$MetaDataVersion/odm:CodeList[@OID='CL.\$AEACN']/odm:CodeListItem[@CodedValue=\$Action]/odm:Decode/odm:TranslatedText[@xml:lang='en']/string()                
  							let \$OutcomeDecode := \$MetaDataVersion/odm:CodeList[@OID='CL.\$OUT']/odm:CodeListItem[@CodedValue=\$Outcome]/odm:Decode/odm:TranslatedText[@xml:lang='en']/string()
                let \$RelationDecode := \$MetaDataVersion/odm:CodeList[@OID='CL.\$REL']/odm:CodeListItem[@CodedValue=\$Relation]/odm:Decode/odm:TranslatedText[@xml:lang='en']/string()
 														  
 							  where \$Serious='1'
                return
                  <ae   siteId='{\$SiteId}'
                        siteName='{\$SiteName}'
                        subjId='{\$SubjId}'
                        subjectKey='{\$SubjectData/@SubjectKey}'
                        diag='{\$Diag}'
                        action='{\$Action}'
                        outcome='{\$Outcome}'
                        relation='{\$Relation}'
                        actionDecode='{\$ActionDecode}'
                        outcomeDecode='{\$OutcomeDecode}'
                        relationDecode='{\$RelationDecode}'  
                        />
              }
              </aes>    
              "; 

    try{
      $doc = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = "xQuery error : " . $e->getMessage() ." (".__METHOD__.")";
      $this->addLog($str,FATAL);
    }
    return $doc;                
  }
}

?>
