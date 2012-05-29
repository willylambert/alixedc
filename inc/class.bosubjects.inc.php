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

class bosubjects extends CommonFunctions
{
  private static $m_subjectCols = array(); //cache for subjects values of keys defined in $configEtude['SUBJECT_LIST']['COLS']

  //Constructeur
  function bosubjects(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }
  
  /**
   * @description Return subject status (Screened, Randomized, etc)
   * @param SumpleXMLElement $subj : one subject as returned by bosubjects->getSubjectsParams
   * @author tpi
   */  
  public function getSubjectStatus($subj){
    $this->addLog(__METHOD__."($subj)",INFO);
    
    $SubjectStatus = "";
    //We can determined if the subject is screened with the mandatory parameter INCLUSIONDATE
    if($subj['colINCLUSIONDATE']!=""){
      $SubjectStatus = "Screened";
    }
    
    $this->callHook(__FUNCTION__,"customSubjectStatus",array($subj,&$SubjectStatus,$this));
    
    return $SubjectStatus;
  }
  
  /**
   * return Subjects List
   * @param optional siteFilter if a string, contains the requested patient's SiteID
   * @return array of SubjectKey
   * @author tpi,wlt
  **/
  public function getSubjectsList($siteId=false){
    $this->addLog(__METHOD__."($siteId)",INFO);
    
    $SitesFilter = $this->getUserSites();
    
    if($siteId!=false && in_array($siteId,$SitesFilter)){
      $SitesFilter = array($siteId);
    }

    $queryCol = array();
    foreach($SitesFilter as $siteId){
      $queryCol[] = "index-scan('SiteRef','$siteId','EQ')";  
    }
    
    $query = "let \$Subjects := " . implode(" union ",$queryCol) . "
              return
              <Subjects>
              {  
                for \$SubjectData in \$Subjects
                return
                  <SubjectData SubjectKey='{\$SubjectData/@SubjectKey}' />
              }
              </Subjects>";
    $subjectDatas = $this->m_ctrl->socdiscoo()->query($query);
    
    $tblSubjectKeys = array();
    foreach($subjectDatas[0] as $subjectData) {
      $tblSubjectKeys[] = $subjectData["SubjectKey"];
    }
    
    return $tblSubjectKeys;
  }
  
  /**
   * Get the Sites allowes to the current user - if sponsor, return the full sites list
   * @return array sites list    
   **/     
  public function getUserSites(){
    //we need to make a list of sites for which the current user can see the subjects
    $sitesFilter = array();
    
    //Is the user a Sponsor ?
    $defaultProfilId = $this->m_ctrl->boacl()->getUserProfileId();
    
    //first: we get the complete list of sites
    $sites = $this->m_ctrl->bosites()->getSites();
    
    //then: we keep the sites the user can see
    foreach($sites as $site){
      if($defaultProfilId=="SPO"){
        $sitesFilter[] = $site["siteId"];
      }else{
        //do the user has a profile defined for this site ?
        $profile = $this->m_ctrl->boacl()->getUserProfileId("",$site["siteId"]);
        if(!empty($profile)){
          $sitesFilter[] = (string)$site["siteId"];
        }else{
          //no authorization for this site
        }
      }
    }

    return $sitesFilter;
  }

  /**
   * @desc Returns parameters for a subject . These parameters are SUBJKEY, fileOID and those defined in config.inc.php
   * @param string $SubjectKey
   * @return DOMDocument
   * @author wlt, tpi
   **/
  public function getSubjectsParams($SubjectKey)
  {
    $this->addLog(__METHOD__ ."($SubjectKey)",INFO);
    
    //The audit trail generates many ItemData with the same ItemOID. So we have to look for the last item (in first position)
    $query = "  let \$SubjectsCol := index-scan('SubjectData', '$SubjectKey', 'EQ')
                for \$SubjectData in \$SubjectsCol
				        let \$SubjectKey := \$SubjectData/@SubjectKey
                ";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      if(is_array($col['Value'])){
        $query .= "let \$col$key := \$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['Value']['SEOID']}' and @StudyEventRepeatKey='{$col['Value']['SERK']}']/
                                                  odm:FormData[@FormOID='{$col['Value']['FRMOID']}' and @FormRepeatKey='{$col['Value']['FRMRK']}']/
                                                  odm:ItemGroupData[@ItemGroupOID='{$col['Value']['IGOID']}' and @ItemGroupRepeatKey='{$col['Value']['IGRK']}']/
                                                  odm:*[@ItemOID='{$col['Value']['ITEMOID']}'][1]
                  ";
      }else{
        if($key=="SUBJID"){
          $query .= "let \$col$key := \$SubjectKey \n";
        }else{
          $query .= "let \$col$key := " . $col['Value'] ." \n";
        }
      }
    }            

   $query .= "return 
              <subj SubjectKey='{\$SubjectKey}'";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      $query .= " col$key ='{\$col$key}' ";
    } 
  
    $query .= "/>";
    
    $doc = $this->m_ctrl->socdiscoo()->query($query);
    return $doc[0];
  }
  
  /**
   * @description returns a specified parameter for a subject. The paramter as to be defined in config.inc.php into $configEtude['SUBJECT_LIST']['COLS']
   * @param $SubjectKey => the subject
   * @param $key => the parameter for which a value is requested
   * @return the value   
   * @author tpi
   */ 
  public function getSubjectColValue($SubjectKey,$key,$useCache=true){
    $this->addLog(__METHOD__."($SubjectKey)",INFO);
    if(!$SubjectKey) throw new Exception("Error: SubjectKey is empty (". __METHOD__ .")");
    if(!$key) throw new Exception("Error: Requested Key is not specified (". __METHOD__ .")");
    if(!isset($this->m_tblConfig['SUBJECT_LIST']['COLS'][$key])) throw new Exception("Error: Requested Key is not defined in config.inc.php (". __METHOD__ .")");
    
    if(!$useCache || !isset(self::$m_subjectCols[$SubjectKey][$key])){ //get the value in the database (if asked to, or if the cache doesn't contain the value)
      //We will find the Key in the CRF
      $col = $this->m_tblConfig['SUBJECT_LIST']['COLS'][$key];
      
      $query = "
        declare function local:getLastValue(\$ItemData as node()*) as xs:string?
        {
          let \$v := ''
          return \$ItemData[1]/string()
        };
  
            <subjs>
                {
                  let \$SubjectsCol := collection('ClinicalData')[/odm:ODM/@FileOID='$SubjectKey']
                  for \$SubjectData in \$SubjectsCol/odm:ODM/odm:ClinicalData/odm:SubjectData
  				        let \$FileOID := \$SubjectData/../../@FileOID
                  let \$col$key := local:getLastValue(\$SubjectData/odm:StudyEventData[@StudyEventOID='{$col['Value']['SEOID']}' and @StudyEventRepeatKey='{$col['Value']['SERK']}']/
                                                          odm:FormData[@FormOID='{$col['Value']['FRMOID']}' and @FormRepeatKey='{$col['Value']['FRMRK']}']/
                                                          odm:ItemGroupData[@ItemGroupOID='{$col['Value']['IGOID']}' and @ItemGroupRepeatKey='{$col['Value']['IGRK']}']/
                                                          odm:*[@ItemOID='{$col['Value']['ITEMOID']}'])
                  return 
                    <subj col$key ='{\$col$key}' />
                }
            </subjs>";
      
      try{
        $this->addLog(__METHOD__."() Run query",TRACE);
        $doc = $this->m_ctrl->socdiscoo()->query($query);
        $this->addLog(__METHOD__."() Query OK",TRACE);
      }catch(xmlexception $e){
        $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . __METHOD__ .")";
        $this->addLog($str,FATAL);
      }
      
      //we update the cache self::$m_subjectCols
      self::$m_subjectCols[$SubjectKey][$key] = (string)$doc[0]->subj["col$key"];
    }
    
    $value = self::$m_subjectCols[$SubjectKey][$key];
    
    //HOOK => bosubjects_getSubjectColValue_customValue
    $this->callHook(__FUNCTION__,"customValue",array($SubjectKey,$key,&$value,$this));
    
    return $value;
  }
  
}
