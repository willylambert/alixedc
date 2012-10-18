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
* UI Class dedicated to study dashboard
* @author TPI
**/ 
class uidashboard extends CommonFunctions
{
  /**
  * Class constructor
  * @param array $configEtude array of config values    
  * @param uietude $ctrlRef reference to instanciation object 
  * @author WLT
  **/ 
  function uidashboard($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * Get the main interface, called from uietude
  * @return string HTML
  * @author TPI
  **/     
  public function getInterface()
  {      
    $this->addLog("uidashboard->getInterface()",TRACE);
    
    if(isset($_GET['action'])){
      $action = $_GET['action'];
    }else{
      $action = "curve";
    }
    
    $topMenu = $this->m_ctrl->etudemenu()->getMenu();
    $dashboardMenu = $this->getMenu();
    
    $htmlRet = $topMenu;
    $htmlRet .= $dashboardMenu;
    
    switch($action){
    
      case "curve":
        $TITLE = "Inclusions curve";
        $CONTENT = $this->getCurve();
        break;
        
      case "inclusions":
        $TITLE = "Inclusions";
        $CONTENT = $this->getInclusions();
        break;
        
      default:
        //custom boards
        if(substr($action, 0, 7)=="custom-"){
          $id = substr($action, strpos($action, "-") + 1);
          //HOOK => uidashboard_getInterface_boardContent
          $this->callHook(__FUNCTION__,"boardContent",array($id,&$TITLE,&$CONTENT,$this));
        }
        else{
          //unknown action
        }
    }
    
    
    $htmlRet .= "<div id='dashboardContent' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>$TITLE
                    </span>
                  </div>
                  <div class='ui-dialog-content ui-widget-content'>$CONTENT
                  </div>
                </div>";
                
    /**/
    return $htmlRet; 
  }
  
  private function getMenu(){
    $htmlRet = "";
    
    $htmlRet .= "<div id='dashboardMenu' class='ui-dialog ui-widget ui-widget-content ui-corner-all'>
                  <div class='ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix'>
                    <span class='ui-dialog-title'>Dashboard</span>
                  </div>";
    
    //Default board items
    $htmlRet .= $this->getMenuItem("curve", "Inclusions curve", "clock");
    $htmlRet .= $this->getMenuItem("inclusions", "Inclusions", "person");
    
    //Custom board items
    //HOOK => uidashboard_getMenu_boardMenu
    $customBoards = $this->callHook(__FUNCTION__,"boardMenu",array($this));
    foreach($customBoards as $board){
      $htmlRet .= $this->getMenuItem("custom-".$board["id"], $board["title"]);
    }
                
    $htmlRet .= "</div>";
    	         
    $htmlRet .= '<script>
	               /*Setup buttons icons*/
              	$(function() {
                  $("#dashboardMenu a").each(function(){
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
  
  private function getMenuItem($action, $label, $icon="key"){
    return "<div>
              <a icon='ui-icon-$icon'
                  href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                   'action' => $action))."'
              >$label</a>
            </div>";
  }
  
  private function getInclusions(){
    $htmlRet = "";   

    $htmlRet .= "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
      <table class='ui-grid-content ui-widget-content'>
			<thead>
				<tr>
					<th class='ui-state-default'> Site Id</th>
					<th class='ui-state-default'> Site name</th>
					<th class='ui-state-default'> Number of screened subjects</th>
				</tr>
			</thead>
      <tbody>";
		
		$tblSite = $this->m_ctrl->bosites()->getSites();

    $query = "let \$SubjectsCol := collection('ClinicalData')/odm:ODM/odm:ClinicalData/odm:SubjectData
              for \$SubjectData in \$SubjectsCol
              let \$siteId := \$SubjectData/odm:SiteRef/@LocationOID
              return <subj subjectKey='{\$SubjectData/@SubjectKey}' siteId='{\$siteId}' />";
    $res = $this->m_ctrl->socdiscoo()->query($query);

    foreach($res as $subj){
     if(isset($tblSite["site{$subj['siteId']}"])){
       if($subj['siteId']!=""){
        $tblSite["site{$subj['siteId']}"]['screened'] ++; 
       }
     }
    }
                 
    foreach($tblSite as $site){		  
      $htmlRet .= "<tr id='".$site['siteId']."'>
              					<td class='ui-widget-content'>".$site['siteId']."</td>
              					<td class='ui-widget-content'>".$site['siteName']."</td>
              					<td class='ui-widget-content'>".$site['screened']."</td>
              				</tr>";

		}
		$htmlRet .= "</tbody></table>
                	</div>";
                	
    return $htmlRet;
  }
  
  private function getCurve(){    
    $htmlRet = "";
    
    //we need an excanvas for IE<9
    if(preg_match("/MSIE (\d+)\.(\d+);/", $_SERVER['HTTP_USER_AGENT'], $res)>0 && $res[1]<9){
      $htmlRet .= "<script language='javascript' type='text/javascript' src='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/excanvas.js') . "'></script>";
    }

    $htmlRet .= "
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqplot/jquery.jqplot.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqplot/jqplot.pointLabels.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqplot/jqplot.canvasTextRenderer.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqplot/jqplot.canvasAxisTickRenderer.min.js') . "'></SCRIPT>
                <SCRIPT LANGUAGE='JavaScript' SRC='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/jqplot/jqplot.barRenderer.min.js') . "'></SCRIPT>
                <link rel='stylesheet' type='text/css' href='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/templates/default/jquery.jqplot.css') . "' />
                <style>
                  /*Valeurs affichées à chaque point de la courbe*/
                  /*
                  .jqplot-point-label {
                    border: 1.5px solid #aaaaaa;
                    padding: 1px 3px;
                    background-color: #eeccdd;
                  }*/
                </style>";
    
    //Expected first enrolment: January 2011
    $year = 2010;
    $month = 1;
    $duration = 24; //months
    $subjects = 50; //50 patients in total.
    
    $xaxis = "";
    $values = "";
    $valuesByMonth = "";
    $valuesIVRS = "";
    $valuesByMonthIVRS = "";
    $countIVRS = 0;    
    $today = date("Y-m-d");
    $count = 0;
    for($i=0; $i<=$duration; $i++){
      $label = sprintf("%02d", $month) ."/". $year;
      if($xaxis!="") $xaxis .= ", ";
      $xaxis .= "[". ($i+1) .", '". $label ."']";
      
      //j'insère ici le calcul du nombre d'inclus => à déplacer ??
      //on ne calcule pas les valeurs du futur
      $toDate = $year."-".sprintf("%02d", $month)."-".date('d');
      if($toDate <= $today){
        $query = "let \$SubjectsCol := collection('ClinicalData')/odm:ODM/odm:ClinicalData
                  for \$SubjectData in \$SubjectsCol/odm:SubjectData
                  let \$INCLUSIONDATE := \$SubjectData/odm:StudyEventData[@StudyEventOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['SEOID']}' and @StudyEventRepeatKey='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['SERK']}']/
                                                odm:FormData[@FormOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['FRMOID']}' and @FormRepeatKey='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['FRMRK']}']/
                                                odm:ItemGroupData[@ItemGroupOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['IGOID']}' and @ItemGroupRepeatKey='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['IGRK']}']/
                                                odm:*[@ItemOID='{$this->m_tblConfig['SUBJECT_LIST']['COLS']['INCLUSIONDATE']['Value']['ITEMOID']}'][last()]
                  where \$INCLUSIONDATE!='' and \$INCLUSIONDATE lt '$toDate'
                  return
                  <Subject     
                       SubjectKey='{\$SubjectData/@SubjectKey}'
                       INCLUSIONDATE= '{\$INCLUSIONDATE}'
                       />";
        $res = $this->m_ctrl->socdiscoo()->query($query);
        
        $nbSubj = count($res);
        $nbThisMonth = $nbSubj - $count;
        $count = $nbSubj;
        
        if($values!="") $values .= ", ";
        $values .= "[". ($i+1) .", '". $count ."']";

        if($valuesByMonth!="") $valuesByMonth .= ", ";
        $valuesByMonth .= "[". ($i+1) .", '". $nbThisMonth ."']";
        
                      
      }
      
      //incrémentation du mois
      $month ++;
      if($month>12){
        $month = 1;
        $year++;
      }
    }
    $xaxis = "[". $xaxis ."]";
    $values = "[". $values ."]";
    $valuesByMonth = "[". $valuesByMonth ."]";
    
    $yaxis = "";
    $factor = 25; //la plupart des nombre de patients prévus seront multiples de 25 (je pense)
    for($i=0; $i<=($subjects/$factor); $i++){
      if($yaxis!="") $yaxis .= ", ";
      $yaxis .= ($i*$factor);
    }
    $yaxis = "[". $yaxis ."]";
    
    $htmlRet .= "<div id='conteneur'></div>
                <script>
                  $(document).ready(function(){
                    $.jqplot.config.enablePlugins = true;
                    var cumul = ".$values.";
                    var byMonth = "."[]".";
                    courbe = $.jqplot('conteneur', [cumul], {
                      height: 450,
                      
                      title: 'INCLUSIONS',
                      series:[ 
                          {label:'eCRF inclusions',lineWidth:2, markerOptions:{style:'dimaond'}}, 
                          {show:false,label:'Inclusions by month', renderer:$.jqplot.BarRenderer,rendererOptions:{barWidth : 12}}
                      ],
                      legend: {
                              show: true,
                              location: 'ne',  
                              xoffset: 12,  
                              yoffset: 12
                          },
                      axesDefaults:{
                        pad:1.3,
                        tickRenderer: $.jqplot.CanvasAxisTickRenderer ,
                        tickOptions: {
                          angle: -45,
                          fontSize: '8pt'
                        }
                      },
                      axes : {
                        xaxis : {
                           renderer: $.jqplot.CategoryAxisRenderer,
                           ticks: ".$xaxis."
                        },
                        yaxis : {
                           //autoscale:true,
                           tickOptions:{formatString:'%d'},
                           ticks: ".$yaxis."
                        }
                      }
                    });
                  });
                </script>";
    
    return $htmlRet;
  }
}
