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
  //All avalable parameter must be defined here
  var $parameters = array(
      "maintenance" => array( "label" => "Maintenance",
                              "values" => array("Y" => "On",
                                                "N" => "Off")
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
    //retriving values of parameters
    $sql = "SELECT * FROM egw_alix_config WHERE currentapp='". $this->getCurrentApp(true) ."'";
    $GLOBALS['egw']->db->query($sql);
    while($GLOBALS['egw']->db->next_record()){
      $this->parameters[$GLOBALS['egw']->db->f('parameter')]['value'] = $GLOBALS['egw']->db->f('value');
    }
    
    //we return these settings
    return $this->parameters;
  } 
}