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
    
/**
* @desc Class d'UI dÚdiÚ Ó laffichage de tableaux et chiffres clÚs de l'Etude, courbe d'inclusion
* @author TPI
**/ 
class uidocuments extends CommonFunctions
{
  /**
  * @desc Constructeur de class
  * @param array $configEtude tableau des constantes de configuration    
  * @param uietude $ctrlRef reference vers l'instance instanciation, o¨ est dÚlÚguÚ l'installation des objets (appel du type $this->m_ctrl->bcdiscoo() ) 
  * @author WLT
  * 
  **/ 
  function uidocuments($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * @desc fonction principale - retoure l'html Ó afficher, appelÚ depuis uietude
  * @return string HTML Ó afficher
  * @author WLT
  **/     
  public function getInterface()
  {      
    $this->addLog("uidocuments->getInterface()",TRACE);
    
    $action = "keys";
    if(isset($_GET['action'])){
      $action = $_GET['action'];
    }
    
    $topMenu = $this->m_ctrl->etudemenu()->getMenu($SiteId);
    $content = $this->getMenu();
    
    $htmlRet .= "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-1.4.2.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jquery-ui-1.8.5.custom.min.js') . "'></SCRIPT>
                ";
    
    $htmlRet .= $topMenu;
    $htmlRet .= $content;
    
                
    /**/
    return $htmlRet; 
  }
  
  private function getMenu(){
    $htmlRet = "";
    
    //Liste des documents, cf fichier config.inc.php
    
    $htmlRet .= "<div id='documentsMenu' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Documents</span>
                  </div>
                  <div class='ui-dialog-content ui-widget-content'>";
    
    foreach($this->m_tblConfig['DOCS']['INT'] as $name => $doc){
      $htmlRet .= "
                    <div>
                      <a icon='ui-icon-disk' target='_blank'
                          href='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/documents/'.$doc)."'
                      >$name</a>
                    </div>";
    }
    
    //Récupération de la langue de l'utilisateur connecté
    $profile = $this->m_ctrl->boacl()->getUserProfile();
    $country = $profile['siteCountry'];
    if(is_array($this->m_tblConfig['DOCS'][$country])){
      foreach($this->m_tblConfig['DOCS'][$country] as $name => $doc){
        $htmlRet .= "
                      <div>
                        <a icon='ui-icon-disk' target='_blank'
                            href='".$GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/documents/'.$country.'/'.$doc)."'
                        >$name</a>
                      </div>";
      }
    }
                  
    $htmlRet .= "</div>                
                </div>";
    	         
    $htmlRet .= '<script>
	               /*Mise en place des icones sur les boutons*/
              	$(function() {
                  $("#documentsMenu a").each(function(){
                    $(this).button({
                            icons: {
                                primary: $(this).attr("icon")
                            }
                        });
                  });
              	});
              	</script>';
              	
    return $htmlRet;
  }
}
