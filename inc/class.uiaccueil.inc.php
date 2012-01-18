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
  
class uiaccueil extends CommonFunctions
{
  
  function __construct($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }
  
  function getInterface()
  {     
    $userSiteName = $GLOBALS['egw']->accounts->id2name($GLOBALS['egw_info']['user']['account_primary_group']);
    $userSiteId = $this->egwId2studyId($GLOBALS['egw_info']['user']['account_primary_group']);
    //$siteStats = $this->m_ctrl->bostats()->getStudyStats("('$userSiteId')");
    
    $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery.corner.js') . "'></SCRIPT>";
    
    //Accès à la page d'accueil d'un utilisateur ayant accès au CRF
    $htmlRet .= '<table width="100%"><tr><td valign="top" width="70%">';
    $menu = $this->m_ctrl->etudemenu()->getMenu();
    $htmlRet .= $menu;
  
    return $htmlRet;      
  }

}
