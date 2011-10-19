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

class socdiscoo extends CommonFunctions
{ 
  var $m_con = array(); //XmlContainer instances array
  var $m_mgr; //XmlManager instance
  var $m_queryContext; //QueryContext instance
  var $m_dbxmlPath; //Path to dbxml files
  
  private $m_tblLocks; 
  
  //Container collection
  private $m_clinicalCollection;
  
  //Subjects Containers list
  private $m_subjectsContainers;
     
  //Constructeur
  function socdiscoo($tblConfig,$SubjectKey)
  {                
      CommonFunctions::__construct($tblConfig,null);
      $this->addLog("socdiscoo->socdiscoo('$SubjectKey')",INFO);
      
      $this->m_tblLocks = array();
                  
      $this->initContext($SubjectKey);
  }
  
  function __destruct()
  {
     $this->addLog("socdiscoo::destruct()",INFO);      
     $this->closeContext();
  }

  /*
    Intialize member parameters
    @param string $SubjectKey if specified, open only metadata and specified subject container. otherwise open all subjects containers
    @author wlt  
  */
  function initContext($SubjectKey){
    //Initialisation du manager
    $this->m_mgr = new XmlManager();

    //Gestion du mode test
    if(isset($_SESSION[$this->getCurrentApp(false)]['testmode']) && $_SESSION[$this->getCurrentApp(false)]['testmode']){
      $this->m_dbxmlPath = $this->m_tblConfig["DBXML_BASE_DEMO_PATH"];
    }else{
      $this->m_dbxmlPath = $this->m_tblConfig["DBXML_BASE_PATH"];
    }
        
    //Query context
    $this->m_queryContext = $this->m_mgr->createQueryContext();
    if ($this->m_queryContext)
    {
      $this->m_queryContext->setBaseURI("dbxml:///".$this->m_dbxmlPath);      
      $this->m_queryContext->setNamespace("odm", "http://www.cdisc.org/ns/odm/v1.3"); 
    }
    
    //Containers initialisation
    $this->addLog("Containers initialisation",TRACE);
    $this->m_clinicalCollection = "(";
    if($SubjectKey!=""){ //All queries will be run only on one subject
      $this->initDB($SubjectKey . ".dbxml",$this->m_dbxmlPath,false);
      $this->m_clinicalCollection .= "collection('" . $SubjectKey . ".dbxml')";
    }else{
      //we open all patients containers
      $this->m_subjectsContainers = $this->listSubjectsContainers();
      foreach($this->m_subjectsContainers as $container){
        $this->m_clinicalCollection .= "collection('" . $container . "') | ";
        $this->initDB($container,$this->m_dbxmlPath,false); 
      }
      $this->m_clinicalCollection = substr($this->m_clinicalCollection,0,-2);
    }
    $this->initDB('MetaDataVersion.dbxml',$this->m_dbxmlPath,false);
    $this->initDB('BLANK.dbxml',$this->m_dbxmlPath,false);
    $this->initDB('SubjectsList.dbxml',$this->m_dbxmlPath,false);
    
    //Set default collection
    $this->m_clinicalCollection .= ")"; 
  }
  
  /*
  Accesor of $this->m_clinicalCollection
  */
  public function getClinicalDataCollection(){
    return $this->m_clinicalCollection; 
  }
  
  /*
  Accesor of $this->m_subjectsContainers
  */
  public function getSubjectsContainers(){
    if(!$this->m_subjectsContainers){
      $this->m_subjectsContainers = $this->listSubjectsContainers();
    }
    return $this->m_subjectsContainers; 
  }
  
  private function closeContext(){
     foreach($this->m_con as $containerName=>$container)
     {
      $this->addLog("socdiscoo->__closeContext() : $containerName",TRACE);
      unset($this->con[$containerName]);
     } 
     
     unset($this->m_mgr);
     
     foreach($this->m_tblLock as $dbxmlFile => $lock){
        $this->unlock($dbxmlFile);
     } 
  }
          
  //Retourne un document sous la forme d'un objet SimpleXMLelement ou DOMDocument
  //Si le patient n'est pas trouvé, l'erreur doit être gérée par un try catch dans la fonction appelante
  function getDocument($containerName,$name, $useSimpleXML = true)
  {   
    if($useSimpleXML)
    {
      $xmlResult = new SimpleXMLElement($this->m_con[$containerName]->getDocument($name)->getContentAsString());
    }
    else
    {
      $xmlResult = new DOMDocument();
      $xmlResult->loadXML($this->m_con[$containerName]->getDocument($name)->getContentAsString());
    }
    return $xmlResult;
  }

  /*
  Perform xQuery query on opened containers
  @return array of SimpleXML objects, DOMDocument object or XMLResult object
  */   
  function query($query, $useSimpleXML = true, $raw = false) 
  {
    $this->addLog("socdiscoo->query($query,$useSimpleXML,$raw)",TRACE);
    
    try{
      if($raw)
      {
        $xmlResult = $this->m_mgr->query($query,$this->m_queryContext);
      }
      else
      {
        if($useSimpleXML)
        {
          $xmlResult = array();

          $results = $this->m_mgr->query($query,$this->m_queryContext);
          if(isset($results)){
            while($val=$results->next()){
              $xmlResult[] = new SimpleXMLElement($val->asString());
            }
          }else{
            $this->addLog("Echec de la requête ".$query." (". __METHOD__ ." with \$useSimpleXML=". $useSimpleXML .") ",FATAL);
          }
        }
        else
        {
          $results = $this->m_mgr->query($query,$this->m_queryContext);
          if(isset($results)){
            if($val=$results->next()){
              $xmlResult = new DOMDocument();
              $xmlResult->loadXML($val->asString());
            }
          }else{
            $this->addLog("Echec de la requête ".$query." (". __METHOD__ ." with \$useSimpleXML=". $useSimpleXML .") ",FATAL);
          }
        }
      }
    }catch(xmlexception $e){
      throw $e;
    }

    return $xmlResult;
  }

  //Ajoute un document dans le container indiquée
  //if container is not specified, we use the FileOID to determine it
  function addDocument($doc,$isString=false,$containerName="",$schemaValidate=true)
  {
    //Validation du schéma CDISC et Extraction du FileOID
    if(is_object($doc))
    {
      $xml = $doc;
    }
    else
    {
      //Soit c'est une string, soit c'est un fichier
      if($isString)
      {
        $xml = new DOMDocument();
        $xml->loadXML($doc);    
      }
      else
      {
        $xml = new DOMDocument();
        $xml->load($doc);
      }
     } 
     
    if($schemaValidate && !$xml->schemaValidate($this->m_tblConfig["ODM_1_3_SCHEMA"]))
    {
     throw new Exception("Le fichier $doc n'est pas valide.");
    }
    $fileOID = $xml->documentElement->getAttribute("FileOID");
    if($containerName==""){
      $containerName = $fileOID . ".dbxml";
    }
    try
    {
      $this->addLog("addDocument sur $containerName de $fileOID",INFO);
      $this->initDB($containerName,$this->m_dbxmlPath,false);

      //Lock container in write mode
      $this->lock($containerName,LOCK_EX);
      $this->m_con[$containerName]->putDocument($fileOID,$xml->saveXML()); //Insertion
    }
    catch(xmlexception $e)
    {
      $eMsg = $e->getMessage();
      $this->m_con[$containerName]->sync();
      $message = "Le fichier avec le FileOID $fileOID est déjà présent dans la base.";
      $this->addLog($message ." ". $eMsg . " (". __METHOD__ .")",ERROR);
      throw new Exception($message);
    }
    $this->m_con[$containerName]->sync();
  }

  //Supprime un document dans le container indiquée
  function deleteDocument($containerName,$name)
  {
    try
    {
      $this->addLog("deleteDocument sur $containerName de $name",TRACE);
      $this->lock($containerName,LOCK_EX);
      $this->m_con[$containerName]->deleteDocument($name); //Suppression
    }
    catch(xmlexception $e)
    {
      $this->addLog("Erreur deleteDocument sur $containerName de $name (". __METHOD__ .")",FATAL);
      throw new Exception("Erreur dans  deleteDocument pour $name");
    } 
  }

  private function setIndexes($containerName){
    if($containerName=='MetaDataVersion.dbxml'){
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","OID","node-attribute-equality-string");
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","StudyEventOID","unique-node-attribute-equality-string");
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","FormOID","unique-node-attribute-equality-string");
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","ItemGroupOID","unique-node-attribute-equality-string");
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","ItemOID","unique-node-attribute-equality-string");
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","CodeListOID","unique-node-attribute-equality-string");
      $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","CollectionExceptionConditionOID","unique-node-attribute-equality-string");  
    }else{
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","SubjectKey","node-attribute-substring-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","CodeListOID","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","StudyEventOID","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","StudyEventRepeatKey","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","FormOID","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","FormRepeatKey","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","ItemGroupOID","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","ItemGroupRepeatKey","node-attribute-equality-string");
        $this->m_con[$containerName]->addIndex("http://www.cdisc.org/ns/odm/v1.3","ID","unique-node-attribute-equality-string");          
    }
  }
  
  function replaceDocument($doc,$isString=false,$containerName="",$schemaValidate=true)
  {        
    //Validation du schéma CDISC et Extraction du FileOID     
    if(is_object($doc))
    {
      $xml = $doc;
    }
    else
    {
      //Soit c'est une string, soit c'est un fichier
      if($isString)
      {
        $xml = new DOMDocument();
        $xml->loadXML($doc);    
      }
      else
      {
        $xml = new DOMDocument();
        $xml->load($doc);
      }
     } 

    libxml_use_internal_errors(true);
    if($schemaValidate && !$xml->schemaValidate($this->m_tblConfig["ODM_1_3_SCHEMA"]))
    {
      $errors = libxml_get_errors();
      $error = $errors[0];
      $lines = explode("\r", $xml->saveXML());
      $line = $lines[($error->line)-1];
      $message = "Le fichier n'est pas valide au schéma ODM : ". $error->message.' at line '.$error->line.':<br />'.htmlentities($line);
      
      $this->addLog("Erreur lors de la validation xml. ".$message,FATAL);
      throw new Exception($message);
    }
    $fileOID = $xml->documentElement->getAttribute("FileOID");
    if($containerName==""){
      $containerName = $fileOID . ".dbxml";
    }
    try{
      $this->addLog("socdiscoo->replaceDocument() sur $containerName de $fileOID",TRACE);
      
      //Lock container in write mode
      $this->lock($containerName,LOCK_EX);    

      $document = $this->m_con[$containerName]->getDocument($fileOID);
      $xml->formatOutput = true;
      $string = $xml->saveXML();
      if($string!="")
      {     
        $document->setContent($string);
        
        $this->m_con[$containerName]->updateDocument($document);

        $this->m_con[$containerName]->sync();
        
        unset($document); //cf "The Definitive Guide Berkeley DB XML p.180 > Caution It’s a good idea to unset() document objects before closing containers—and always before deleting/renaming them—even if the PHP API tries hard to know when object destruction is needed.

        //By security - we save the XML file on hard drive
        $xml->save($this->m_tblConfig["CDISCOO_PATH"] . "/xml/$fileOID" . ".xml");
        if($this->m_user!="CLI"){
          chmod($this->m_tblConfig["CDISCOO_PATH"] . "/xml/$fileOID" . ".xml",664);
        }
      }
      else
      {
        unset($document);
        $this->addLog("Erreur replaceDocument sur $containerName de $fileOID : le document xml est vide (". __METHOD__ .")",FATAL);
        throw new Exception("Erreur dans replaceDocument pour FileOID $fileOID : le document xml est vide");
      }
    }catch(xmlexception $e){
      unset($document);
      $this->addLog("Erreur replaceDocument sur $containerName de $fileOID (". __METHOD__ .") " . $e->getMessage(),FATAL);
      throw new Exception("Erreur dans replaceDocument pour FileOID $fileOID. ". $e->getMessage());  
    }
  }

  /*
    Acquire a lock on a subject
    @param string $dbxmlFile dbxml filename to lock
    @param int $lockType LOCK_EX (write) or LOCK_SH (read)
    @author wlt
  */
  public function lock($dbxmlFile,$lockType){
    $this->addLog("socdiscoo()->lock($dbxmlFile,$lockType)",TRACE);
    
    $lockFile = $this->m_tblConfig["LOCK_FILE"] . $dbxmlFile;

    //If in this thread we have an ongoing read lock, we upgrade it to a write lock
    if(isset($this->m_tblLock["$dbxmlFile"])){
      if($this->m_tblLock["$dbxmlFile"]["LOCK_TYPE"]==LOCK_SH && $lockType==LOCK_EX){
        $this->unlock($dbxmlFile);
      } 
    }

    //To avoid self deadlock, we check for an ongoing lock in the same thread
    if(!isset($this->m_tblLock["$dbxmlFile"])){    
      //c => Open the file for writing only. If the file does not exist, it is created. 
      $lockFileHandle = fopen($lockFile, 'c');
      if($this->m_user!="CLI"){
        chmod($lockFile, 0664);
      } 
      $start_time = microtime(true);
      if(!flock($lockFileHandle, $lockType)){
        $this->addLog("socdiscoo()->lock($lockFile) => Unable to get lock",FATAL);
      }    
      ftruncate($lockFileHandle,0);
      fwrite($lockFileHandle,$this->m_user . "@" . date("c") . " : " . $lockType);
      
      $stop_time = microtime(true);
  
      //Store lock
      $this->m_tblLock["$dbxmlFile"] = array("LOCK_TYPE" => $lockType, "LOCK_HANDLE" => $lockFileHandle);
  
      //We trace waiting time, for statistical purpose
      $wait_time = $stop_time - $start_time;
      if($wait_time > 1){ 
        $sql = "INSERT INTO egw_alix_lock (study,start_time,stop_time,wait_time,lock_dt,who) 
                VALUES ('".$dbxmlFile."',$start_time,$stop_time,$wait_time,now(),'".$GLOBALS['egw']->accounts->data['account_lid']."')";
        $GLOBALS['egw']->db->query($sql);
      }
    }   
  }

  /*
  release lock acquired with lockSubject()
  @param string $dbxmlFile dbxml filename to lock
  @author wlt 
  */
  public function unlock($dbxmlFile){
    $this->addLog("socdiscoo()->unlock($dbxmlFile)",TRACE);

    if(isset($this->m_tblLock["$dbxmlFile"])){    
      if(!flock($this->m_tblLock["$dbxmlFile"]["LOCK_HANDLE"], LOCK_UN)){
        $this->addLog("socdiscoo()->releaseLock() => Unable to latch lock",FATAL);
      }else{
        ftruncate($this->m_tblLock["$dbxmlFile"]["LOCK_HANDLE"],0);
        fclose($this->m_tblLock["$dbxmlFile"]["LOCK_HANDLE"]);
        unset($this->m_tblLock["$dbxmlFile"]);
      }
    }      
  }  


/****************************************************/
//Private methods
/****************************************************/ 
  
  private function initDB($containerName,$dbxmlPath,$bReadOnly)
  {
    $this->addLog("socdiscoo->initDB('$containerName','$dbxmlPath','$bReadOnly')",TRACE);
    try{
      /*
      * DB_CREATE - If the container does not currently exist, create it.
      * DB_EXCL - Return an error if the container already exists. The DB_EXCL flag is only meaningful when specified with the DB_CREATE flag
      * DB_RDONLY - Open the container for reading only. Any attempt to modify items in the container will fail, regardless of the actual permissions of any underlying files.
      */      
      if($bReadOnly){                  
        $flags = DB_RDONLY;
      }else{
        $flags = 0;
      }
      //Lock container in read mode
      $this->lock($containerName,LOCK_SH);
      
      $this->m_con[$containerName] = $this->m_mgr->openContainer($dbxmlPath . $containerName ,$flags);
    }catch(xmlexception $e){
        $this->addLog("socdiscoo->initDB() error",INFO);
        if($e->getCode()==17){
          try{
            $flags = DB_CREATE | DB_EXCL;
            $this->m_con[$containerName] = $this->m_mgr->createContainer($dbxmlPath . $containerName, $flags);
            $this->setIndexes($containerName);
            //set container writable for www-data group
            if($this->m_user!="CLI"){
              chmod($dbxmlPath . $containerName, 0664);
            }
          }catch(xmlexception $e){
            $this->addLog("socdiscoo->initDB() => xmlexception : " . $e->getMessage() ." (". __METHOD__ .")",FATAL);
            throw($e);            
          }
        }else{       
          //autre erreur que fichier non trouvé
          $this->addLog("socdiscoo->initDB() => xmlexception : " . $e->getMessage() ." (". __METHOD__ .")",FATAL);
          throw($e);
        }
    }
  }
  
/* return containers file
@return array of string 
*/
public function listSubjectsContainers(){
    // create an array to hold directory list
    $results = array();

    // create a handler for the directory
    $handler = opendir($this->m_tblConfig["DBXML_BASE_PATH"]);

    // open directory and walk through the filenames
    while ($file = readdir($handler)) {

      // if file isn't this directory or its parent, add it to the results
      if ($file != "." && $file != ".." && $file != "SubjectsList.dbxml" && $file != "MetaDataVersion.dbxml" && $file != "ClinicalData.dbxml"  && $file != "BLANK.dbxml" && substr_compare($file, ".dbxml", -6, 6) === 0 && substr_count($file,".")==1 ) {
        $results[] = $file;
      }
    }
    closedir($handler);

    return $results;
  }   
}
