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
* @desc Class d'UI dédié à laffichage de tableaux et chiffres clés de l'étude, courbe d'inclusion, etc
* @author TPI
**/ 
class uidashboard extends CommonFunctions
{
  /**
  * @desc Constructeur de class
  * @param array $configEtude tableau des constantes de configuration    
  * @param uietude $ctrlRef reference vers l'instance instanciation, où est délégué l'installation des objets (appel du type $this->m_ctrl->bcdiscoo() ) 
  * @author WLT
  * 
  **/ 
  function uidashboard($configEtude,$ctrlRef)
  {	
    CommonFunctions::__construct($configEtude,$ctrlRef);
  }

  /**
  * @desc fonction principale - retoure l'html à afficher, appelé depuis uietude
  * @return string HTML à afficher
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
    
    $topMenu = $this->m_ctrl->etudemenu()->getMenu($SiteId);
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
      case "ageDistribution":
        $TITLE = "Age distribution";
        $CONTENT = $this->getAgeDistribution();
        break;
      case "weightDistribution":
        $TITLE = "Weight distribution";
        $CONTENT = $this->getWeightDistribution();
        break;              
      case "keys":
        $TITLE = "Key figures";
        $CONTENT = $this->getKeyFigures();
        break;
      case "saeList":
        $TITLE = "List of Serious Adverse Event";
        $CONTENT = $this->getSAEList();
        break;
      default:
        $TITLE = "";
        $CONTENT = "";
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
                  </div>
                  <div class='ui-dialog-content ui-widget-content'>
                    <div>
                      <a icon='ui-icon-clock'
                          href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                           'action' => 'curve'))."'
                      >Inclusions curve</a>
                    </div>
                    <div>
                      <a icon='ui-icon-person'
                          href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                           'action' => 'inclusions'))."'
                      >Inclusions</a>
                    </div>
                    <div>
                      <a icon='ui-icon-person'
                          href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                           'action' => 'weightDistribution'))."'
                      >Weight distribution</a>
                    </div>
                    <div>
                      <a icon='ui-icon-person'
                          href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                           'action' => 'ageDistribution'))."'
                      >Age distribution</a>
                    </div>
                    <div>
                      <a icon='ui-icon-key'
                          href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                           'action' => 'keys'))."'
                      >Key figures</a>
                    </div>
                    <div>
                      <a icon='ui-icon-person'
                          href='".$GLOBALS['egw']->link('/index.php',array('menuaction' => $this->getCurrentApp(false).'.uietude.dashboardInterface',
                                                                           'action' => 'saeList'))."'
                      >List of SAE</a>
                    </div>
                  </div>                
                </div>";
    	         
    $htmlRet .= '<script>
	               /*Mise en place des icones sur les boutons*/
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
  
  private function getKeyFigures(){
    $htmlRet = "";
  
    $stats = array(
                    array(
                          "label" => "Total number of screened subjects",
                          "query" =>" let \$value := count(\$SubjCol/subjects/subject)
                                      return <result value='{\$value}' />"
                          ),
                    array(
                          "label" => "Total number of screening failure",
                          "query" =>" let \$value := count(\$SubjCol/subjects/subject[SUBJECTSTATUS='Screening Failure'])
                                      return <result value='{\$value}' />"
                          ),
                    array(
                          "label" => "Total number of enrolled subjects",
                          "query" =>" let \$value := count(\$SubjCol/subjects/subject[colRDNUM/string()!=''])
                                      return <result value='{\$value}' />"
                          ),
                    array(                                          
                          "label" => "Subjects out of study",
                          "query" =>" let \$value := count(\$SubjCol/subjects/subject[SUBJECTSTATUS='Withdrawal'])
                                      return <result value='{\$value}' />"
                          ),
                    array(
                          "label" => "Subjects who completed the study",
                          "query" =>" let \$value := count(\$SubjCol/subjects/subject[SUBJECTSTATUS='Completed'])
                          return <result value='{\$value}' />"
                          ),
                    array(
                          "label" => "SAE",
                          "query" =>" let \$value := sum(\$SubjCol/subjects/subject/colNBSAE)
                                      return <result value='{\$value}' />"
                          ),
                    );
    
    $htmlRet .= " <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
                    <table class='ui-grid-content ui-widget-content'>
                			<thead>
                				<tr>
                					<th></th>
                					<th class='ui-state-default'> Number</th>
                				</tr>
                			</thead>
                      <tbody>";
    
    foreach($stats as $stat){
      $query = "let \$SubjCol := doc('SubjectsList')";
      $query .= $stat['query'];


      try{
        $res = $this->m_ctrl->socdiscoo()->query($query);
      }catch(xmlexception $e){
        $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
        $this->addLog($str,FATAL);
        die($str);
      }

      $res = (string)$res[0]['value'];
      
      $class = ($class=="row_off"?"row_on":"row_off");
      $htmlRet .= "
                      <tr class='". $class ."'>
                        <td class='ui-widget-content'>". $stat['label'] ."</td>
                        <td class='ui-widget-content'>". $res ."</td>
                      </tr>";
    }
    
    $htmlRet .= "
                      </tbody>
                    </table>
                  </div>";
                  
    return $htmlRet;
  }
  
  private function getSAEList(){
    $htmlRet = "";    
    
    $htmlRet .= " <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
                    <table class='ui-grid-content ui-widget-content'>
                			<thead>
                				<tr>
                					<th class='ui-state-default'> Site Id</th>
                					<th class='ui-state-default'> Site Name</th>
                          <th class='ui-state-default'> Subject identifiant</th>
                					<th class='ui-state-default'> Diagnosis</th>
                					<th class='ui-state-default'> Action taken</th>
                					<th class='ui-state-default'> Outcome</th>
                					<th class='ui-state-default'> Causal relationship</th>
                				</tr>
                			</thead>
                      <tbody>";  
    
    $saes = $this->m_ctrl->bostats()->getSAElist();
    
    $nbSAE = 0;
    $class = "";
    foreach($saes[0] as $sae)
    { 
        $nbSAE++;
        $class = ($class=="row_off"?"row_on":"row_off");
        $htmlRet .= "
                        <tr class='". $class ."'>
                          <td class='ui-widget-content'>". $sae['siteId'] ."</td>
                          <td class='ui-widget-content'>". $sae['siteName'] ."</td>
                          <td class='ui-widget-content'>". $sae['subjId'] ."</td>
                          <td class='ui-widget-content'>". $sae['diag'] ."</td>
                          <td class='ui-widget-content'>". $sae['actionDecode'] ."</td>
                          <td class='ui-widget-content'>". $sae['outcomeDecode'] ."</td>
                          <td class='ui-widget-content'>". $sae['relationDecode'] ."</td>
                        </tr>";
    }
    
    if($nbSAE==0){
      $htmlRet .= "<tr><td class='ui-widget-content'>No SAE.</td></tr>";
    }
    
    $htmlRet .= "
                      </tbody>
                    </table>
                  </div>";
        
    return $htmlRet;
  }
  
  private function getAgeDistribution(){
    $htmlRet = "";
    
    $query = "let \$SubjectsCol := doc('SubjectsList')
              for \$SubjectData in \$SubjectsCol/subjects/subject
              let \$siteId := \$SubjectData/colSITEID
              let \$age := \$SubjectData/colDMAGE
              return <subj siteId='{\$siteId}' age='{\$age}'/>"; 

    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    $nb3_7 = 0;
    $nb7_11 = 0;
    $nb11_18 = 0;
    $nb18_25 = 0;
        
    foreach($res as $subj)
    {        
      $age = (int)($subj['age']);
      if($age>0){
        if($age <= 7){
          $nb3_7++;
        }else{
          if($age <= 11){
            $nb7_11++;
          }else{
            if($age <= 18){
              $nb11_18++;  
            }else{
              $nb18_25++;  
            }
          }
        }  
      }else{
        $nbNoAge++;
      }
    }    

    $htmlRet .= "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
      <table id='tblUsers' class='ui-grid-content ui-widget-content'>
			<thead>
				<tr>
					<th class='ui-state-default'> Age class</th>
					<th class='ui-state-default'> Number of subjects</th>
				</tr>
			</thead>
      <tbody>
      <tr>
				<td class='ui-widget-content'>3-7 years</td>
				<td class='ui-widget-content'>$nb3_7</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>7-11 years</td>
				<td class='ui-widget-content'>$nb7_11</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>11-18 years</td>
				<td class='ui-widget-content'>$nb11_18</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>18+ years</td>
				<td class='ui-widget-content'>$nb18_25</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>Unfilled DOB</td>
				<td class='ui-widget-content'>$nbNoAge</td>
			</tr>
      </table>
    </div>";
    return $htmlRet;
  }

  private function getWeightDistribution(){
    $htmlRet = "";
    
    $query = "let \$SubjectsCol := doc('SubjectsList')
              for \$SubjectData in \$SubjectsCol/subjects/subject
              let \$siteId := \$SubjectData/colSITEID
              let \$weight := \$SubjectData/colWEIGHT
              return <subj siteId='{\$siteId}' weight='{\$weight}'/>";
    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    $nb10_22 = 0;
    $nb22_45 = 0;
    $nb45_67 = 0;
    $nb67_90 = 0;
    $nb90_112 = 0;
    $nbNoWeight = 0;
        
    foreach($res as $subj)
    {        
      $weight = (double)($subj['weight']);
      if($weight>0){
        if($weight <= 22.5){
          $nb10_22++;
        }else{
          if($weight <= 45){
            $nb22_45++;
          }else{
            if($weight <= 67.5){
              $nb45_67++;  
            }else{
              if($weight <= 90){
                $nb67_90++;  
              }else{
                $nb90_112++;
              }
            }
          }
        }  
      }else{
        $nbNoWeight++;
      }
    }    

    $htmlRet .= "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
      <table id='tblUsers' class='ui-grid-content ui-widget-content'>
			<thead>
				<tr>
					<th class='ui-state-default'> Weight class</th>
					<th class='ui-state-default'> Number of subjects</th>
				</tr>
			</thead>
      <tbody>
      <tr>
				<td class='ui-widget-content'>0-22.5 Kg</td>
				<td class='ui-widget-content'>$nb10_22</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>22.5-45 Kg</td>
				<td class='ui-widget-content'>$nb22_45</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>45-67.5 Kg</td>
				<td class='ui-widget-content'>$nb45_67</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>67.5-90 Kg</td>
				<td class='ui-widget-content'>$nb67_90</td>
			</tr>
      <tr>
				<td class='ui-widget-content'>Unfilled Weight</td>
				<td class='ui-widget-content'>$nbNoWeight</td>
			</tr>
      </table>
    </div>";
    return $htmlRet;
  }
  
  private function getInclusions(){
    $htmlRet = "";   
        
    //Construction de la liste des centres
    $htmlRet .= "
    <div class='ui-grid ui-widget ui-widget-content ui-corner-all'>
      <table id='tblUsers' class='ui-grid-content ui-widget-content'>
			<thead>
				<tr>
					<th class='ui-state-default'> Site Id</th>
					<th class='ui-state-default'> Site name</th>
					<th class='ui-state-default'> Number of screened subjects</th>
					<th class='ui-state-default'> Number of enrolled subjects</th>
					<th class='ui-state-default'> SMA Type 2</th>
					<th class='ui-state-default'> SMA Type 3</th>
					<th class='ui-state-default'> Number of SAE</th>
				</tr>
			</thead>
      <tbody>";
		
		$tblSite = $this->m_ctrl->bosites()->getSites();

    $query = "let \$SubjectsCol := doc('SubjectsList')
              for \$SubjectData in \$SubjectsCol/subjects/subject
              let \$siteId := \$SubjectData/colSITEID
              let \$numRando := \$SubjectData/colRDNUM
              let \$nbSAE := \$SubjectData/colNBSAE
              let \$typeSMA := \$SubjectData/colTYPESMA
              return <subj subjectKey='{\$SubjectData/@SubjectKey}' siteId='{\$siteId}' numRando='{\$numRando}' nbSAE='{\$nbSAE}' typeSMA='{\$typeSMA}'/>";
    try{
      $res = $this->m_ctrl->socdiscoo()->query($query);
    }catch(xmlexception $e){
      $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
      die($str);
    }

    foreach($res as $subj){
     if(isset($tblSite["site{$subj['siteId']}"])){
       if($subj['siteId']!=""){
        $tblSite["site{$subj['siteId']}"]['screened'] ++; 
       } 
       if($subj['numRando']!=""){
        $tblSite["site{$subj['siteId']}"]['enrolled'] ++; 
       }    
       $tblSite["site"]['nbsae'] += $subj['nbSAE']; 
       if($subj['typeSMA']=="2"){
        $tblSite["site{$subj['siteId']}"]['nbsma2'] ++; 
       }else{
         if($subj['typeSMA']=="3"){
          $tblSite["site{$subj['siteId']}"]['nbsma3'] ++; 
         }
       }
     }
    }
                 
    foreach($tblSite as $site)
		{		  
      $htmlRet .= "<tr id='".$site['siteId']."'>
              					<td class='ui-widget-content'>".$site['siteId']."</td>
              					<td class='ui-widget-content'>".$site['siteName']."</td>
              					<td class='ui-widget-content'>".$site['screened']."</td>
              					<td class='ui-widget-content'>".$site['enrolled']."</td>
              					<td class='ui-widget-content'>".$site['nbsma2']."</td>
              					<td class='ui-widget-content'>".$site['nbsma3']."</td>
              					<td class='ui-widget-content'>".$site['nbsae']."</td>
              				</tr>";

		}
		$htmlRet .= "</tbody></table>
                	</div>";
                	
    return $htmlRet;
  }
  
  private function getCurve(){    


    //style des chiffres de la courbe
    $htmlRet = "
                <!--[if IE]><script language='javascript' type='text/javascript' src='" . $GLOBALS['egw']->link('/'.$this->getCurrentApp(false).'/js/excanvas.js') . "'></script><![endif]--> 
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
    
    //Expected first enrolment: September 2010 - Expected completion date: August 2013.
    $year = 2011;
    $month = 1;
    $duration = 24; //months
    $subjects = 50; //50 patients in total, 40 in the olesoxime group, and 10 in the placebo group. This includes a hypothetical 5% drop-out rate over 2 years.
    
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
        $query = "let \$SubjectsCol := doc('SubjectsList')
                  for \$SubjectData in \$SubjectsCol/subjects/subject
                  let \$SVSVSTDTC := \$SubjectData/colSVSVSTDTC
                  where \$SVSVSTDTC!='' and \$SVSVSTDTC lt '$toDate'
                  return
                  <Subject     
                       SubjectKey='{\$SubjectData/@SubjectKey}'
                       SVSVSTDTC= '{\$SVSVSTDTC}'
                       />";
        try{
          $res = $this->m_ctrl->socdiscoo()->query($query);
        }catch(xmlexception $e){
          $str = __METHOD__." Erreur de la requete : " . $e->getMessage() . " " . $query ." (". __METHOD__ .")";
          $this->addLog($str,FATAL);
          die($str);
        }
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
                      
                      title: 'ALIX EDC',
                      series:[ 
                          {label:'eCRF inclusions',lineWidth:2, markerOptions:{style:'dimaond'}}, 
                          {show:false,label:'Inclusions by month', renderer:$.jqplot.BarRenderer,rendererOptions:{barWidth : 12}}
                      ],
                      legend: {
                              show: true,
                              location: 'ne',  
                              xoffset: 12,  
                              yoffset: 12,
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
