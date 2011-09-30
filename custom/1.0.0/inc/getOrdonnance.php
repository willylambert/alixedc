<?
//Appellé depuis FORM.ELIG.XSL

$country = $_GET['country'];
$siteId = $_GET['siteid'];
$subjId = $_GET['subjid'];
$weight = $_GET['subjWeight'];
$poso = $_GET['poso'];
$subjInit = $_GET['subjinit'];
$visit = $_GET['visit']; 
$ut1 = $_GET['ut1'];
$ut2 = $_GET['ut2'];
$ut3 = $_GET['ut3'];
$ut4 = $_GET['ut4'];
$ut5 = $_GET['ut5'];
$nbUT = $_GET['nbut'];

//Il nous faut tous les paramètres
if($country=="" || $siteId=="" || $subjId==""){
  die("missing parameters |$country| |$siteId| |$subjId|");
}
  
//Ouverture du fichier template du fax de confirmation de l'inclusion
$template = "templates/ordonnance_".$country.".htm";
if(!file_exists($template)){
  die("No prescription found for country/visit $country/$visit");
}
$handle = fopen($template, "r");
$htmlString = fread ($handle, filesize ($template));
fclose ($handle);

if($country=="FRA" ){
  switch($visit){
    case 'D0' : 
      $visitTitle = "<u>Visite d'Inclusion</u> (V0)"; 
      break;
    case 'W4' : 
      $visitTitle = "<u>Visite de la semaine 4</u> (V1)"; 
      break;
    case 'W13' : 
      $visitTitle = "<u>Visite du Mois 3</u> (V2)"; 
      break;
    case 'W26' : 
      $visitTitle = "<u>Visite du Mois 6</u> (V3)"; 
      break;
    case 'W39' :
      $visitTitle = "<u>Visite du Mois 9</u> (V4)"; 
      break;
    case 'W52' : 
      $visitTitle = "<u>Visite du Mois 12</u> (V5)"; 
      break;
    case 'W66' : 
      $visitTitle = "<u>Visite du Mois 15</u> (V6)"; 
      break;
    case 'W78' : 
      $visitTitle = "<u>Visite du Mois 18</u> (V7)"; 
      break;
    case 'W91' : 
      $visitTitle = "<u>Visite du Mois 21</u> (V8)"; 
      break;
  }
}else{
  if($country=="UK" ){
    switch($visit){
      case 'D0' : 
        $visitTitle = "<u>Inclusion Visit</u> (V0)"; 
        break;
      case 'W4' : 
        $visitTitle = "<u>Week 4 Visit</u> (V1)"; 
        break;
      case 'W13' : 
        $visitTitle = "<u>Month 3 Visit</u> (V2)"; 
        break;
      case 'W26' : 
        $visitTitle = "<u>Month 6 Visit</u> (V3)"; 
        break;
      case 'W39' :
        $visitTitle = "<u>Month 9 Visit</u> (V4)"; 
        break;
      case 'W52' : 
        $visitTitle = "<u>Month 12 Visit</u> (V5)"; 
        break;
      case 'W66' : 
        $visitTitle = "<u>Month 15 Visit</u> (V6)"; 
        break;
      case 'W78' : 
        $visitTitle = "<u>Month 18 Visit</u> (V7)"; 
        break;
      case 'W91' : 
        $visitTitle = "<u>Month 21 Visit</u> (V8)"; 
        break;
    }
  }else{
    if($country=="GER" ){
      switch($visit){
        case 'D0' : 
          $visitTitle = "<u>Aufnahme-Visite </u> (V0)"; 
          break;
        case 'W4' : 
          $visitTitle = "<u>Visite Monat 1</u> (V1)"; 
          break;
        case 'W13' : 
          $visitTitle = "<u>Visite Monat 3</u> (V2)"; 
          break;
        case 'W26' : 
          $visitTitle = "<u>Visite Monat 6</u> (V3)"; 
          break;
        case 'W39' :
          $visitTitle = "<u>Visite Monat 9</u> (V4)"; 
          break;
        case 'W52' : 
          $visitTitle = "<u>Visite Monat 12</u> (V5)"; 
          break;
        case 'W66' : 
          $visitTitle = "<u>Visite Monat 15</u> (V6)"; 
          break;
        case 'W78' : 
          $visitTitle = "<u>Visite Monat 18</u> (V7)"; 
          break;
        case 'W91' : 
          $visitTitle = "<u>Visite Monat 21</u> (V8)"; 
          break;
      }
    }else{
      if($country=="ITA" ){
        switch($visit){
          case 'D0' : 
            $visitTitle = "<u>Visita d’Inclusione </u> (V0)"; 
            break;
          case 'W4' : 
            $visitTitle = "<u>Visita della settimana 4</u> (V1)"; 
            break;
          case 'W13' : 
            $visitTitle = "<u>Visita del mese 3</u> (V2)"; 
            break;
          case 'W26' : 
            $visitTitle = "<u>Visita del mese 6</u> (V3)"; 
            break;
          case 'W39' :
            $visitTitle = "<u>Visita del mese 9</u> (V4)"; 
            break;
          case 'W52' : 
            $visitTitle = "<u>Visita del mese 12</u> (V5)"; 
            break;
          case 'W66' : 
            $visitTitle = "<u>Visita del mese 15</u> (V6)"; 
            break;
          case 'W78' : 
            $visitTitle = "<u>VVisita del mese 18</u> (V7)"; 
            break;
          case 'W91' : 
            $visitTitle = "<u>Visita del mese 21</u> (V8)"; 
            break;
        }
      }    
    }  
  }  
}

$tblUT = $ut1;
if($ut2!="") $tblUT .= ", " . $ut2; 
if($ut3!="") $tblUT .= ", " . $ut3;
if($ut4!="") $tblUT .= ", " . $ut4;
if($ut5!="") $tblUT .= ", " . $ut5;
if($ut6!="") $tblUT .= ", " . $ut6;
  
//Remplacement dans le fichier html
$code = array("VISITTITLE","SITEID","SUBJ_WEIGHT","SUBJ_POSO", "SUBJID","SUBJINIT", "TBLUT","NBUT");
$value = array($visitTitle,$siteId,$weight,$poso, $subjId, $subjInit,$tblUT,$nbUT);

$htmlTemp = tempnam("/tmp","htmlDoc");
$tmpHandle = fopen($htmlTemp,"w");
fwrite($tmpHandle,str_replace($code, $value, $htmlString));
fclose($tmpHandle);

$filename = "Ordonnance_".$siteId."_".$subjId."_".$visit.".pdf";

# Tell HTMLDOC not to run in CGI mode...
putenv("HTMLDOC_NOCGI=1");
//Generation et affichage sur la sortie standard
$cmd = "htmldoc -t pdf --quiet --color --webpage --jpeg  --left 30 --top 20 --bottom 20 --right 20 --footer c.: --fontsize 10 --textfont {helvetica}";
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Expires: 0");
header("Pragma: public"); 
header("Cache-Control: private, must-revalidate");
header("Content-Type: application/pdf");
passthru("$cmd '$htmlTemp'");

unlink($htmlTemp);