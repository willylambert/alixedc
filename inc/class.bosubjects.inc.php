<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2011 Business & Decision Life Sciences                         *
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

/*
@desc classe de gestion des droits utilisateurs
@author tpi
*/
class bosubjects extends CommonFunctions
{
  private static $m_subjectCols = array(); //cache for subjects values of keys defined in $configEtude['SUBJECT_LIST']['COLS']

  //Constructeur
  function bosubjects(&$tblConfig,$ctrlRef)
  {
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }

  
  /**
   * @description Retourne le statut du patient (Screened, Randomized, etc)
   * @todo à déplacer dans custom avec un hook 
   * @author tpi
   */  
  public function getSubjectStatus($subj){
    $this->addLog(__METHOD__."($subj)",INFO);
    
    if($subj['colCONT']=="1" && $subj['colDSTERMN']==""){
      return "Completed";
    }elseif($subj['colCONT']!="1" && $subj['colDSTERMN']!=""){
      return "Withdrawal";
    }elseif($subj['colIEYN']=="1" && $subj['colRDNUM']!=""){
      return "Randomized";
    }elseif($subj['colIEYN']=="2" && $subj['colRDNUM']==""){
      return "Randomization Failure";
    }elseif($subj['colIEELIG']=="2"){
      return "Screening Failure";
    }elseif($subj['colIEELIG']=="1"){
      return "Screened";
    }
    return "";
  }
  
  /**
   * @description return Subjects List
   * @param optional boolean $checkRights : return only the subjects for which the current user is auhtorized to access to
   * @return SimpleXMLElement $SubjectsList
   * @author tpi
   */
  public function getSubjectsList($checkRights=true, $order="SubjectKey ascending"){
    $this->addLog(__METHOD__."($addParameters,$checkRights)",INFO);
    
    $SitesFilter = false;
    if($checkRights){
      //we need to make a list of sites for which the current user can see the subjects
      $SitesFilter = array();
      
      //Is the user a Sponsor ?
      $defaultProfilId = $this->m_ctrl->boacl()->getUserProfileId();
      
      //first: we get the complete list of sites
      $sites = $this->m_ctrl->bosites()->getSites();
      
      //then: we keep the sites the user can see
      foreach($sites as $site){
        if($defaultProfilId=="SPO"){
          $SitesFilter[] = $site["siteId"];
        }else{
          //do the user has a profile defined for this site ?
          $profile = $this->m_ctrl->boacl()->getUserProfileId("",$site["siteId"]);
          if(!empty($profile)){
            $SitesFilter[] = $site["siteId"];
          }else{
            //no authorization for this site
          }
        }
      }
    }
    
    $SubjectsParams = $this->getSubjectsParams($SubjectKey, $SitesFilter, $order);
    $SubjectsList = $SubjectsParams[0]->children();
    
    foreach($SubjectsList as $Subject){
      $Subject['SubjectStatus'] = $this->getSubjectStatus($Subject);
      $Subject['CRFStatus'] = $this->m_ctrl->bocdiscoo()->getSubjectStatus($Subject['SubjectKey']);
    }
    
    //return the array of names
    return $SubjectsList;
  }

  /**
   * @desc Returns parameters for each subject (filtered by sites if $Sites is provided). These parameters are SUBJKEY, fileOID and those defined in config.inc.php
   * @param array/string $SubjectKeys : an array of SubjectKey, or list of SubjectKey separated by commas
   * @param optional array/string $Sites : an array of SiteId, or list of SiteId separated by commas
   * @param optional string $order : ordering of the resuts (ex: FileOID ascending)   
   * @return DOMDocument
   * @author wlt, tpi
   **/
  private function getSubjectsParams($SubjectKey=false, $Sites=false, $order="")
  {
    $this->addLog(__METHOD__ ."($SubjectKey,$Sites)",INFO);
    
    $xqSubjectCollection = "";
    if(!$SubjectKey){  //all the subjects (except BLANK)
      $xqSubjectCollection = "collection('ClinicalData')/odm:ODM[@FileOID!='". $this->m_tblConfig["BLANK_OID"] ."']/odm:ClinicalData";
    }else{ //only one subject
      $xqSubjectCollection = "index-scan('SubjectODM', '$SubjectKey', 'EQ')/odm:ClinicalData";
    }
    
    //Site filter if requested
    $whereSite = "";
    if($Sites!=false){
      if(is_array($Sites)){
        $siteList = "";
        foreach($Sites as $siteId){
          if($siteList!="") $siteList .= ",";
          $siteList .= "'$siteId'";
        }
      }
      $whereSite = "where exists(index-of(($siteList),\$colSITEID))";
    }
    
    //Order
    $orderBy = "";
    if($order!=""){
      $orderBy = "order by \$$order";
    }
    
    //The audit trail generates many ItemData with the same ItemOID. So we have to llok for the last item (in first position)
    $query = "
          <subjs>
              {
                let \$SubjectsCol := $xqSubjectCollection
                for \$SubjectData in \$SubjectsCol/odm:SubjectData
				        let \$SubjectKey := \$SubjectData/../../@FileOID
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
          $query .= "let \$col$key := \$FileOID \n";
        }else{
          $query .= "let \$col$key := " . $col['Value'] ." \n";
        }
      }
    }            

    $query .= " $whereSite
                $orderBy
                return 
                  <subj SubjectKey='{\$SubjectKey}'";
                
    foreach($this->m_tblConfig['SUBJECT_LIST']['COLS'] as $key=>$col){
      $query .= " col$key ='{\$col$key}' ";
    } 
  
    $query .= "/>";
      
    $query .= "}
          </subjs>";
    
    try{
      $this->addLog(__METHOD__."() Run query",TRACE);
      $doc = $this->m_ctrl->socdiscoo()->query($query);
      $this->addLog(__METHOD__."() Query OK",TRACE);
    }catch(xmlexception $e){
      $str = "Erreur de la requete : " . $e->getMessage() . "<br/><br/>" . $query . __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
    return $doc;
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
