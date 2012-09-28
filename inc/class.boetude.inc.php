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

class boetude extends CommonFunctions
{      
  //Constructeur
  function boetude(&$configEtude,$ctrlRef)
  {                
      CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  //Retourne la liste des "Sites" de l'utilisateur (Groupe commençant par Site_XX_XXX)
  //Retourne une liste séparée par des espaces
  function getUserSitesList()
  {
    $sitesList = "";

    $usergroups = $GLOBALS['egw']->accounts->membership($GLOBALS['egw']->accounts->account_id);
    if(@is_array($usergroups))
    {
        while (list(,$group) = each($usergroups))
        {
            $sitesList .= $this->egwId2studyId($group['account_id']) . " ";
        }
    }
    return $sitesList;
  }
 
  //brtDt : format YYYY-MM-DD
  //retourne l'age (valeur entière)
  function getAge($brtDt)
  {
    list($year,$month,$day) = explode("-",$brtDt);
    $year_diff  = date("Y") - $year;
    $month_diff = date("m") - $month;
    $day_diff   = date("d") - $day;
    if ($month_diff < 0) $year_diff--;
    elseif (($month_diff==0) && ($day_diff < 0)) $year_diff--;
    return $year_diff;
  }
 
  //Destructeur
  function __destruct()
  {
      //$this->addLog("end  bescorthu()",TRACE);     
  }
  
}
?>
