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
                            "category" => "Maintenance",
                            "label" => "Maintenance mode (only the administrator has access)",
                            "values" => array("Y" => "On",
                                              "N" => "Off"),
                            "default" => "N"
      ),
      "password_min_length" => array( 
                            "category" => "Password",
                            "label" => "Minimum length",
                            "values" => "",
                            "default" => "6"
      ),
      "password_upper_lower_case" => array( 
                            "category" => "Password",
                            "label" => "Must contain at least on upper case and one lower case letter",
                            "values" => array("Y" => "Yes",
                                              "N" => "No"),
                            "default" => "Y"
      ),
      "password_change_after" => array( 
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
  public function setParameter($label, $value){
    $sql = "REPLACE INTO egw_alix_config(currentapp,parameter,value) 
              VALUES('". $this->getCurrentApp(true) ."','$label','$value')";
    $GLOBALS['egw']->db->query($sql);
  }
  
  /**
   * @desc Get a parameter value
   * @author TPI
   */
  public function getParameter($label){
    $value = "";
    $sql = "SELECT value FROM egw_alix_config
            WHERE currentapp='". $this->getCurrentApp(true) ."' AND parameter='$label'";
    $GLOBALS['egw']->db->query($sql);
    if($GLOBALS['egw']->db->next_record()){
      $value = $GLOBALS['egw']->db->f('value');
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
      if(!isset($param['value']) && $param['default']!=""){
        $this->parameters[$id]['value'] = $param['default'];
        $this->setParameter($id, $param['default']);
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