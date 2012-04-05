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

require_once(dirname(__FILE__). "/../config.inc.php");
/*
@desc gère les instanciations à la volée des classe uiXXXXX et boXXXX
@author wlt
*/
class instanciation extends CommonFunctions
{
  protected $m_bocdiscoo;
  protected $m_socdiscoo;
  
  protected $m_boeditor;  
  protected $m_boetude;  
  protected $m_boacl; 

  protected $m_etudemenu;
  
  protected $m_bodeviations;
  protected $m_bopostit;
  protected $m_boqueries;
  protected $m_bosubjects;

  protected $m_boexport;
  protected $m_boimport;

  public function __construct()
  {
    global $configEtude;

    CommonFunctions::__construct($configEtude,null);
  }

  /*
  @desc accesseur de l'instance de la classe bocdiscoo, permet de ne pas instancier inutilement la classe
  @param boolean $modeRW true si il faut ouvrir le container en lecture/ecriture
  @return retourne la référence de notre objet bocdiscoo
  */
  public function bocdiscoo()
  {
    require_once("class.bocdiscoo.inc.php");
    $this->addLog("uietude->bocdiscoo()",TRACE);
    if(!isset($this->m_bocdiscoo)){
      $this->addLog("uietude->bocdiscoo() : instanciation of bocdiscoo",TRACE);
      $this->m_bocdiscoo = new bocdiscoo($this->m_tblConfig,$this);
    }
    
    return $this->m_bocdiscoo;   
  }

  /*
  socdiscoo accessor prevent to do not instanciate object if not needed
  @param string $SubjectKey if specified, open only metadata and specified subject container. otherwise open all subjects containers
  @param bool $bForceNew if socdiscoo is already instanciated force a new instanciation, preceded by destruction of the existing instance
  @return socdiscoo object reference
  @author wlt
  */
  public function socdiscoo()
  {
    require_once("class.socdiscoo.inc.php");
    $this->addLog("uietude->socdiscoo()",TRACE);
    
    if(!isset($this->m_socdiscoo)){
      $this->addLog("uietude->socdiscoo() : instanciation of socdiscoo",TRACE);
      $this->m_socdiscoo = new socdiscoo($this->m_tblConfig);
    }    
    return $this->m_socdiscoo;   
  }

  /*
  @desc accesseur de l'instance de la classe bostats, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet bostats
  */
  public function bostats()
  {
    require_once("class.bostats.inc.php");
    if(!isset($this->m_bostats)){
      $this->addLog("uietude->bostats() : instanciation de bostats",TRACE);
      $this->m_bostats = new bostats($this->m_tblConfig,$this);
    }
    
    return $this->m_bostats;   
  }

  /*
  @desc accesseur de l'instance de la classe boacl, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet boacl
  */
  public function boacl()
  {
    require_once("class.boacl.inc.php");
    if(!isset($this->m_boacl)){
      $this->addLog("uietude->boacl() : instanciation de boacl",TRACE);
      $this->m_boacl = new boacl($this->m_tblConfig,$this);
    }
    
    return $this->m_boacl;   
  }

  /*
  @desc accesseur de l'instance de la classe boeditor, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet boeditor
  */
  public function boeditor()
  {
    require_once("class.boeditor.inc.php");
    if(!isset($this->m_boeditor)){
      $this->addLog("uietude->boeditor() : instanciation de boeditor",TRACE);
      $this->m_boeditor = new boeditor($this->m_tblConfig,$this);
    }
    
    return $this->m_boeditor;   
  }

  /*
  @desc accesseur de l'instance de la classe boetude, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet boetude
  */
  public function boetude()
  {
    require_once("class.boetude.inc.php");
    //$this->addLog("uietude->boetude()",TRACE);
    if(!isset($this->m_boetude)){
      $this->addLog("uietude->boetude() : instanciation de boetude",TRACE);
      $this->m_boetude = new boetude($this->m_tblConfig,$this);
    }
    
    return $this->m_boetude;   
  }

  /*
  @desc accesseur de l'instance de la classe bodeviations, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet bodeviations
  */
  public function bodeviations()
  {
    require_once("class.bodeviations.inc.php");
    if(!isset($this->bodeviations)){
      $this->addLog("uietude->bopostit() : instanciation de bodeviations",TRACE);
      $this->m_bodeviations = new bodeviations($this->m_tblConfig,$this);
    }
    
    return $this->m_bodeviations;   
  }

  /*
  @desc accesseur de l'instance de la classe bopostit, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet bopostit
  */
  public function bopostit()
  {
    require_once("class.bopostit.inc.php");
    if(!isset($this->bopostit)){
      $this->addLog("uietude->bopostit() : instanciation de bopostit",TRACE);
      $this->m_bopostit = new bopostit($this->m_tblConfig,$this);
    }
    
    return $this->m_bopostit;   
  }

  /*
  @desc accesseur de l'instance de la classe boqueries, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet boqueries
  */
  public function boqueries()
  {
    require_once("class.boqueries.inc.php");
    if(!isset($this->m_boqueries)){
      $this->addLog("uietude->boqueries() : instanciation de boqueries",TRACE);
      $this->m_boqueries = new boqueries($this->m_tblConfig,$this);
    }
    
    return $this->m_boqueries;   
  }

  /*
  @desc accesseur de l'instance de la classe bosubjects, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet bosubjects
  */
  public function bosubjects()
  {
    require_once("class.bosubjects.inc.php");
    if(!isset($this->m_bosubjects)){
      $this->addLog("uietude->bosubjects() : instanciation de bosubjects",TRACE);
      $this->m_bosubjects = new bosubjects($this->m_tblConfig,$this);
    }
    
    return $this->m_bosubjects;   
  }

  /*
  @desc accesseur de l'instance de la classe boexport, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet boexport
  */
  public function boexport()
  {
    require_once("class.boexport.inc.php");
    if(!isset($this->m_boexport)){
      $this->addLog("uietude->boexport() : instanciation de boexport",TRACE);
      $this->m_boexport = new boexport($this->m_tblConfig,$this);
    }
    
    return $this->m_boexport;   
  }

  /*
  @desc accesseur de l'instance de la classe boimport, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet boimport
  */
  public function boimport()
  {
    require_once("class.boimport.inc.php");
    if(!isset($this->m_boexport)){
      $this->addLog("uietude->boimport() : instanciation de boimport",TRACE);
      $this->m_boimport = new boimport($this->m_tblConfig,$this);
    }
    
    return $this->m_boimport;   
  }

  /*
  @desc accesseur de l'instance de la classe etudemenu, permet de ne pas instancier inutilement la classe
  @return retourne la référence de notre objet etudemenu
  */
  public function etudemenu()
  {
    require_once("class.etudemenu.inc.php");
    $this->addLog("uietude->etudemenu()",TRACE);
    if(!isset($this->m_etudemenu)){
      $this->addLog("uietude->etudemenu() : instanciation de etudemenu",TRACE);
      $this->m_etudemenu = new etudemenu($this->m_tblConfig,$this);
    }
    
    return $this->m_etudemenu;   
  }

}
