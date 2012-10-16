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
*  Abstract Class for all classes
* Features :
*   => Helpers function for logging
*   => Hook handle 
* @author WLT
**/ 

define("TRACE",1);
define("INFO",2);
define("WARN",3);
define("ERROR",4);
define("FATAL",5);

class CommonFunctions{

  //Array of config values - see config.inc.php
  public $m_tblConfig;

  //Reference to the instanciation class
  //Used everywhere to access methods of class boXXXXX
  public $m_ctrl;
  
  public $m_user;
  public $m_lang;
  
  protected static $nb_instance = 0;

  function __construct(&$tblConfig=array(),$ctrlRef)
  {
    $this->m_tblConfig =& $tblConfig;
    if(isset($GLOBALS['egw_info']['user']['userid'])){
      $userId = $GLOBALS['egw_info']['user']['userid'];
    }else{
      $userId = "CLI";
    }
    $this->m_user = $userId;
    $this->m_ctrl = $ctrlRef;
    if(isset($GLOBALS['egw_info']['user']['preferences']['common']['lang'])){
      $this->m_lang = $GLOBALS['egw_info']['user']['preferences']['common']['lang'];
    }else{
      $this->m_lang = "en";
    }
    if (defined('EGW_INCLUDE_ROOT')) {
      require_once(EGW_INCLUDE_ROOT . "/".$this->m_tblConfig['MODULE_NAME']."/custom/".$this->m_tblConfig['METADATAVERSION']."/inc/hookFunctions.php"); 
    }
    
    if(self::$nb_instance==0){  
      $this->addLog("******************************NEW REQUEST******************************",INFO);
      $this->addLog($_SERVER['HTTP_USER_AGENT'] . " user=" .$this->m_user,INFO);
    }
    self::$nb_instance++;
  }

  function __destruct()
  {
    self::$nb_instance--;   
    if(self::$nb_instance==0){  
      $this->addLog("******************************END OF REQUEST***************************",INFO);
    }
  }
  
  /**
   * Does the user browser is an iPad ?
   * @return boolean true if yes, false if no
   * @author wlt - 20/12/2011      
   **/     
  public function isIpad(){
    if(strstr($_SERVER['HTTP_USER_AGENT'],"iPad")){
      return true;
    }else{
      return false;
    }
  }
  
  public function addLog($message,$level){
    if($level>=$this->m_tblConfig['LOG_LEVEL']){
      $timeOffset = microtime(true) - $_SERVER['REQUEST_TIME']; 
      $dt = date('c') . " " . substr($timeOffset,0,7);
      error_log("$dt " . $message . "\n",3,$this->m_tblConfig['LOG_FILE']);
      if($level>=ERROR){
        $message .= "\nrequest = " . $this->dumpRet($_REQUEST);
        $message .= "\nbacktrace = " . $this->dumpRet(debug_backtrace(false));
        error_log("$dt " . $message . "\n",3,$this->m_tblConfig['LOG_FILE'].".error");
        mail($this->m_tblConfig['EMAIL_ERROR'],"ETUDE (".$this->m_tblConfig['APP_NAME'].") ERROR/FATAL : {$this->m_user}@$dt",$message);
        if($level==FATAL){
          die("<div class='debug_dump'><b>An error occured</b> (the administrator has been notified)<pre>$message</pre></div>");
        }
      }
      if($message=="socdiscoo::destruct()" && $this->m_tblConfig['LOG_LONG_EXECUTION']){
        if($timeOffset>$this->m_tblConfig['LONG_EXECUTION_VALUE']){
          mail($this->m_tblConfig['EMAIL_ERROR'],"ETUDE (".$this->m_tblConfig['APP_NAME'].") LONG EXECUTION : {$this->m_user}@$dt","execution time = ".substr($timeOffset,0,7) . "s @{$this->m_user}@$dt\n\nRequest = " . $this->dumpRet($_REQUEST));
        }
      }
    }
  }

  public function setUserId(){
          $this->m_user = "WLT";
  }

/*
@desc retourne le login, identifiant de connexion, de l'utilisateur connect?
@return string login
@author tpi
*/
  public function getUserId(){
    return $this->m_user;   
  }
  
  /**
  *@desc Retourne la string currentapp utilis? dans la base de donn?es egroupware pour diff?rencier les diff?rentes instances du module de CRF
  *      Si le mode test est activ?, un suffixe peut ?tre ajout? en fonction du param?tre $bIncludeTestModeSuffix
  *@param boolean $bIncludeTestModeSuffix ajouter le suffixe d'indication du mode de test si le mode de test est actif
  *@return string             
  **/  
  public function getCurrentApp($bIncludeTestModeSuffix){
    
    $currentApp = $GLOBALS['egw_info']['flags']['currentapp'];

    if($bIncludeTestModeSuffix){
      if(isset($_SESSION[$currentApp]['testmode']) && $_SESSION[$currentApp]['testmode']){
        $currentApp .= "_test";
      }
    }

    return $currentApp;
  }

  /**
  * @desc Tente d'appeler le hook demandé, si celui ci a été déclaré
  * @param string $methodName nom de la methode appelante  * @param string $hookName nom du hook
  * @param array tableau de param?tre pass? au hook     
  * @return valeur de retour du hook
  * @author WLT
  **/   
  protected function callHook($methodName,$hookName,$tblParam){
    $functionName = get_class($this)."_".$methodName."_".$hookName;
    if(function_exists($functionName)){
      $this->addLog("CommonFunctions->callHook() : functionName=$functionName",INFO);
      return call_user_func_array($functionName,$tblParam);       
    }else{
      return false;
    }
 
  }

  /**
  * @desc Retourne la date au format local
  * @param string datetime
  * @param boolean clean : return an empty string if no values are provided
  * @return string datetime
  * @author TPI
  **/   
  public function formatDate($datetime,$clean=false){
    if($clean && $datetime=="") return "";
    switch($this->m_tblConfig["lang"]){
      case "fr":
        return substr($datetime,8,2) ."/".substr($datetime,5,2) ."/".substr($datetime,0,4) ." ". substr($datetime,11,8);
        break;
      default:
        return substr($datetime,5,2) ."/".substr($datetime,8,2) ."/".substr($datetime,0,4) ." ". substr($datetime,11,8);
    }
  }
  
  public function dumpPre($mixed = null, $expandable=false)
  {
    $type = gettype($mixed);
    if(gettype($mixed)=="object"){
      $type = get_class($mixed);
    }
    echo "<div class='debug_dump'><b>Dump  ". $type ."</b>";
    echo '<pre>';
    if(!$expandable){
      if($type=="DOMDocument"){
        echo "". str_replace(array("<",">"),array("&lt;","&gt;"),$mixed->saveXml());
      }else{
        var_dump($mixed);
      }
    }else{
      echo $this->print_r_tree($mixed);
    }
    echo '</pre></div>';
    return null;
  }
  
  private function print_r_tree($data = null)
  {
      // capture the output of print_r
      $out = print_r($data, true);
  
      // replace something like '[element] => <newline> (' with <a href="javascript:toggleDisplay('...');">...</a><div id="..." style="display: none;">
      $out = preg_replace('/([ \t]*)(\[[^\]]+\][ \t]*\=\>[ \t]*[a-z0-9 \t_]+)\n[ \t]*\(/iUe',"'\\1<a href=\"javascript:toggleDisplay(\''.(\$id = substr(md5(rand().'\\0'), 0, 7)).'\');\">(+)\\2</a><div id=\"'.\$id.'\" style=\"display: block;\">'", $out);
  
      // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</div>
      $out = preg_replace('/^\s*\)\s*$/m', '</div>', $out);
  
      // print the javascript function toggleDisplay() and then the transformed output
      return '<script language="Javascript">function toggleDisplay(id) { document.getElementById(id).style.display = (document.getElementById(id).style.display == "block") ? "none" : "block"; }</script>'."\n$out";
  }
  
  public function dumpRet($mixed = null)
  {
    ob_start();
    var_dump($mixed);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
  }
  
  /**
   * @desc Get settings values (whether they are in a config.inc.php or in MySQL egw_alix_config)
   * @param string parameter   
   * @return mixed
   * @author TPI
   */
  protected function getConfig($parameter){
    if(isset($this->m_tblConfig[$parameter])){
      return $parameter;
    }
    else{
      if(!isset($this->m_settings)){ //load settings only once
        require_once('class.boconfig.inc.php');
        $boconfig = new boconfig($this->m_tblConfig,$this->m_ctrl);
        $this->m_settings = $boconfig->getParameters();
      }
      if(isset($this->m_settings[$parameter])){
        return $this->m_settings[$parameter]['value'];
      }else{
        $this->addLog("Unknown configuration parameter '$parameter'", ERROR);
      }
    }
  }   
}