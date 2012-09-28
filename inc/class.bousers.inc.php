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

/*
management of sites
@author tpi
*/
class bousers extends CommonFunctions
{
  //eGroupware accounts
  var $boaccounts = NULL;
  
  //Constructor
  function __construct(&$tblConfig,$ctrlRef)
  {
      parent::__construct($tblConfig,$ctrlRef);
      $this->boaccounts =& CreateObject('admin.boaccounts');
  }
  
  /*
  * @desc Function ti add a user in the eGroupware accounts
  * @param string $login : identifiant
  * @param string $password : password
  * @param optional string $firstname : First name
  * @param optional string $lastname : Last name
  * @param optional string $email : Email
  * @param optional string $primary_group : Primary group
  * @param optional array $groups : Groups for the user
  */
  public function addUser($login, $password, $firstname="", $lastname="", $email="", $primary_group="", $groups=array()){
    //Primary group ids
    if($primary_group==""){
      $primary_group = "Default";
    }
    $primary_group_id = $GLOBALS['egw']->accounts->name2id($primary_group);
    
    //Groups ids
    $groups_ids = array();
    foreach($groups as $group){
      $groups_ids[] = $GLOBALS['egw']->accounts->name2id($group);
    }
    
    $userData = array(
        'account_type'          => 'u',
        'account_lid'           => $login,
        'account_firstname'     => $firstname,
        'account_lastname'      => $lastname,
        'account_passwd'        => $password,
        'status'                => 'A',
        'account_status'        => 'A',
        'old_loginid'           => '',
        'account_id'            => '',
        'account_primary_group' => $primary_group_id,
        'account_passwd_2'      => $password,
        'account_groups'        => $groups_ids,
        'anonymous'             => '',
        'changepassword'        => '',
        'account_permissions'   => '',
        'homedirectory'         => '',
        'loginshell'            => '',
        'account_expires_never' => 'True',
        'account_email'         => $email
    );
    
    $results = $this->boaccounts->add_user($userData);
    if($results!==true && count($results>0)){
      $str = implode("<br />", $results);
      throw new Exception("Some errors occured while trying to create a new account: <br />". $str);
    }
  }
}
