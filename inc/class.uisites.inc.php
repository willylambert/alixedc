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
    
/**
* @desc Class d'UI dédié à la gestion des centres
* @author WLT
**/ 
class uisites extends CommonFunctions
{
  /**
  * @desc Constructeur de class
  * @param array $configEtude tableau des constantes de configuration    
  * @param uietude $ctrlRef reference vers l'instance instanciation, où est délégué l'installation des objets (appel du type $this->m_ctrl->bcdiscoo() ) 
  * @author WLT
  * 
  **/ 
  function uisites($configEtude,$ctrlRef)
  {	
      CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * @desc fonction principale - retoure l'html à afficher, appelé depuis uietude
  * @return string HTML à afficher
  * @author WLT
  **/     
  public function getInterface()
  {
      $this->addLog("uisites->getInterface()",TRACE);
      $htmlRet = "";
      
      //Demande de creation d'un centre
      if(isset($_GET['action']) && $_GET['action']=='createSite'){
        $this->m_ctrl->bosites()->addSite($_POST['siteId'],$_POST['siteName'],$_POST['siteProfileId'],$_POST['siteCountry'],$_POST['checkOnSave']);
      }
    
      //Construction de la liste des centres
      $htmlSites = "
      <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
		    <div class='ui-grid-header ui-widget-header ui-corner-top'>Sites list</div>
        <table id='tblUsers' class='ui-grid-content ui-widget-content'>
  			<thead>
  				<tr>
  					<th class='ui-state-default'> Site Id</th>
  					<th class='ui-state-default'> Site name</th>
  					<th class='ui-state-default'> Site profile Id</th>
  					<th class='ui-state-default'> Country</th>
  					<th class='ui-state-default'> Check On Save</th>
  				</tr>
  			</thead>
        <tbody>";
			
			$tblSite = $this->m_ctrl->bosites()->getSites();
      foreach($tblSite as $site)
			{
			   $htmlSites .= "<tr id='".$site['siteId']."'>
                					<td class='ui-widget-content'>".$site['siteId']."</td>
                					<td class='ui-widget-content'>".$site['siteName']."</td>
                					<td class='ui-widget-content'>".$site['siteProfileId']."</td>
                					<td class='ui-widget-content'>".$site['siteCountry']."</td>
                					<td class='ui-widget-content'>".$site['checkOnSave']."</td>
                				</tr>";

			}
			$htmlSites .= "<tbody></table>
                  	</div>";

      $menu = $this->m_ctrl->etudemenu()->getMenu();

      $htmlRet = "<SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/alixcrf.sites.js') . "'></SCRIPT>
                  
                  $menu
                
                  <div id='mainFormOnly' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                    <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                      <span class='ui-dialog-title'>Admin</span>
                    </div>
                    <div class='ui-dialog-content ui-widget-content'>
                      $htmlSites
                    </div>
                  </div>                  
                    
                  <div id='dialog-form' title='Create new site'>
                  	<p class='validateTips'>All form fields are required.</p>
              
                  	<form id='createSite' action='index.php?menuaction=".$this->getCurrentApp(false).".uietude.sitesInterface&action=createSite' method='post'>
                    	<fieldset>
                    		<label for='siteId'>Site Identifiant</label>
                    		<input type='text' name='siteId' id='siteId' class='text ui-widget-content ui-corner-all' />
                    		<label for='siteName'>Site name</label>
                    		<input type='text' name='siteName' id='siteName' class='text ui-widget-content ui-corner-all' />
                    		<label for='siteProfileId'>Site profile Id</label>
                    		<select name='siteProfileId'>
                    		  <option value='CRT'>Technician</option>
                    		  <option value='INV'>Investigator</option>
                    		  <option value='CRA'>CRA</option>
                    		  <option value='DM'>Data Manager</option>
                          <option value='SPO'>Sponsor</option>
                        </select> 
                    		<label for='siteCountry'>Country</label>
                    		<select name='siteCountry' class='ui-widget-content ui-corner-all'>
                          <option value=''>...</option>
                          <option value='FRA'>France</option>
                          <option value='GER'>Germany</option>
                          <option value='ITA'>Italy</option>
                          <option value='UK'>United Kingdom</option>
                          <option value='ESP'>Spain</option>
                          <option value='POL'>Poland</option>
                          <option value='NDL'>Netherlands</option>
                          <option value='BEL'>Belgium</option>
                        </select>
                    		<label for='checkOnSave'>Check On Save</label>
                    		<select name='checkOnSave' class='ui-widget-content ui-corner-all'>
                          <option value=''>...</option>
                          <option value='1'>Yes</option>
                          <option value='2'>No</option>
                        </select>
                      </fieldset>
                  	</form>
                  
                  </div>
                  <button id='create-site'>Create new site</button>

                  <script>loadAlixCRFsitesJS();</script>";

  		return $htmlRet;
  }
} 
