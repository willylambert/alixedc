<?php
    /**************************************************************************\
    * ALIX EDC SOLUTIONS                                                       *
    * Copyright 2012 Business & Decision Life Sciences                         *
    * http://www.alix-edc.com                                                  *
    * ------------------------------------------------------------------------ *
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

class boconfig extends CommonFunctions
{  
  //Definition of parameters : identifier, label and acceptable values
  //All available parameter must be defined here
  var $parameters = array(
      "maintenance" => array( 
                            "location" => "other",
                            "category" => "Maintenance",
                            "label" => "Maintenance mode (only the administrator has access)",
                            "values" => array("true" => "On",
                                              "false" => "Off"),
                            "default" => "false"
      ),
      "password_min_length" => array( 
                            "location" => "sql",
                            "category" => "Password",
                            "label" => "Minimum length",
                            "values" => "",
                            "default" => "6"
      ),
      "password_upper_lower_case" => array( 
                            "location" => "sql",
                            "category" => "Password",
                            "label" => "Must contain at least on upper case and one lower case letter",
                            "values" => array("Y" => "Yes",
                                              "N" => "No"),
                            "default" => "Y"
      ),
      "password_change_after" => array( 
                            "location" => "sql",
                            "category" => "Password",
                            "label" => "Expiration delay (days)",
                            "values" => "",
                            "default" => "90"
      ),
    );

  //Constructor
  function __construct(&$tblConfig,$ctrlRef)
  {                
      CommonFunctions::__construct($tblConfig,$ctrlRef);
  }
  
  /**
   * @desc Save a parameter value
   * @author TPI
   */
  public function setParameter($id, $value){
    switch($this->parameters[$id]['location']){
      case "sql":
        $sql = "REPLACE INTO egw_alix_config(currentapp,parameter,value) 
                  VALUES('". $this->getCurrentApp(true) ."','$id','$value')";
        $GLOBALS['egw']->db->query($sql);
        break;
      case "other":
        if($id=="maintenance"){
          $oldValue = $this->getParameter($id);
          $pathOldValue = EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/maintenance.$oldValue";
          $pathNewValue = EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/maintenance.$value";
          if($value!=$oldValue && file_exists($pathOldValue)) unlink($pathOldValue);
          $size = file_put_contents($pathNewValue, "Maintenance mode activated: $value (". date('Y-m-d H:i:s') ." by ". $this->getUserId() .")");
          if($size===false){
            $this->addLog("Couldn't create file '$pathNewValue'", FATAL);
          }
        }
        break;
      default:
        $this->addLog("Unknown location for parameter '$id'",ERROR);
        break;
    }
  }
  
  /**
   * @desc Get a parameter value
   * @author TPI
   */
  public function getParameter($id){
    $value = "";
    switch($this->parameters[$id]['location']){
      case "sql":
        $sql = "SELECT value FROM egw_alix_config
                WHERE currentapp='". $this->getCurrentApp(true) ."' AND parameter='$id'";
        $GLOBALS['egw']->db->query($sql);
        if($GLOBALS['egw']->db->next_record()){
          $value = $GLOBALS['egw']->db->f('value');
        }
        break;
      case "other":
        if($id=="maintenance"){
          $pathTrue = EGW_SERVER_ROOT . "/".$GLOBALS['egw_info']['flags']['currentapp']."/maintenance.true";
          if(file_exists($pathTrue)){
            $value = 'true';
          }else{
            $value = 'false';
          }
        }
        break;
      default:
        $this->addLog("Unknown location for parameter '$id'",ERROR);
        break;
    }
    return $value;
  }
  
  /**
   * @desc return parameters with label, accepted values and current value
   * @author TPI
   */
  public function getParameters(){
    //retrieving values of parameters
    $sql = "SELECT * FROM egw_alix_config WHERE currentapp='". $this->getCurrentApp(true) ."'";
    $GLOBALS['egw']->db->query($sql);
    while($GLOBALS['egw']->db->next_record()){
      $this->parameters[$GLOBALS['egw']->db->f('parameter')]['value'] = $GLOBALS['egw']->db->f('value');
    }
    //set default settings for unset parameters
    foreach($this->parameters as $id => $param){
      switch($this->parameters[$id]['location']){
        case "sql":
          if(!isset($param['value']) && $param['default']!=""){
            $this->parameters[$id]['value'] = $param['default'];
            $this->setParameter($id, $param['default']);
          }
          break;
        case "other":
          $this->parameters[$id]['value'] = $this->getParameter($id);
          break;
        default:
          $this->addLog("Unknown location for parameter '$id'",ERROR);
          break;
      }
    }
    
    //we return these settings
    return $this->parameters;
  }
  
  /**
   * @desc check if the configuration parameters have to be set for the current application
   * @return boolean
   */
  public function configurationNeeded(){
    $bRet = false;
    //look for settings
    $sql = "SELECT * FROM egw_alix_config WHERE currentapp='". $this->getCurrentApp(true) ."'";
    $GLOBALS['egw']->db->query($sql);
    if(!$GLOBALS['egw']->db->next_record()){ //no setting found
      $bRet = true;
    }
    return $bRet;
  }
}