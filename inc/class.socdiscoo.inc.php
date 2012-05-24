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
  /* Parameters of the database to connect to.
   * The default port is 5050. If Sedna server is listening
   * on the different port (say 5051) modify host value like:
   * $host = 'localhost:5051' */
  var $host;
  var $database;
  var $user;
  var $password;
  var $odm_declaration;
  var $collections = array("ClinicalData", "MetaDataVersion");
  
  function socdiscoo(&$tblConfig)
  {                
      CommonFunctions::__construct($tblConfig,null);
      $this->addLog("socdiscoo->socdiscoo()",INFO);
                  
      $this->initContext();
      //$this->initDB();
  }
  
  function __destruct()
  {
     $this->addLog("socdiscoo::destruct()",TRACE);      
     $this->closeContext();
  }

  /*
    Intialize database connection
    @author tpi  
  */
  function initContext(){
    /* Don't print anything. We'll handle errors ... */
    ini_set("sedna.verbosity","0");
    
    $this->host      = $this->m_tblConfig['SEDNA_HOST'];
    $this->database  = $this->m_tblConfig['SEDNA_DATABASE'];
    $this->user      = $this->m_tblConfig['SEDNA_USER'];
    $this->password  = $this->m_tblConfig['SEDNA_PASSWORD'];
    $this->odm_declaration = "declare namespace odm = '".$this->m_tblConfig['SEDNA_NAMESPACE_ODM']."';";
    
    $this->conn = sedna_connect($this->host,$this->database,$this->user,$this->password);
    
    if(!$this->conn){
      $str = "Could not connect to database: ".$this->database."\n" . sedna_error() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
  }
  
  private function closeContext(){
    /* Properly close the connection */
    if(!sedna_close($this->conn)){
      $str = "Could not close connection to database: ".$this->database."\n" . sedna_error() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
  }
          
  //Retourne un document sous la forme d'un objet SimpleXMLelement ou DOMDocument
  //Si le document n'est pas trouvé, l'erreur doit être gérée par un try catch dans la fonction appelante
  function getDocument($collection, $name, $useSimpleXML = true)
  {   
    /* Execute query: list all categories in the document */
    if($collection==""){
      $query = "let \$doc := doc('$name')
                return \$doc";
    }else{
      $query = "let \$doc := collection('$collection')[odm:ODM/@FileOID='$name']
                return \$doc";
    }
    //namespace declaration
    $query = $this->odm_declaration . $query;
    
    //$res = $this->query($query, $useSimpleXML);
    try{
      if(!sedna_execute($query)){
        $str = "Could not execute query: $query\n" . sedna_error() ." (". __METHOD__ .")";
        $this->addLog($str,ERROR);
      }
      
      $results = sedna_result_array();
      if(!$results){
        $str = "Could not get query result or result is empty for query: $query\n" . sedna_error() ." (". __METHOD__ .")";
        $this->addLog($str,ERROR);
      }
      
      if($useSimpleXML)
      {
        $xmlResult = array();
        
        foreach($results as $res){
          $xmlResult[] = new SimpleXMLElement($res);
        }
      }
      else
      {
        $xmlResult = new DOMDocument();
        $xmlResult->loadXML($results[0]);
      }
    }catch(xmlexception $e){
      throw $e;
    }
    
    if(count($xmlResult)>1){
      $str = "Found more than one document '$name' in collection '$collection' (". __METHOD__ .")";
      throw new Exception($str);
    }elseif(count($xmlResult)==0){
      $str = "Could not find document '$name' in collection '$collection' (". __METHOD__ .")";
      throw new Exception($str);
    }
    
    if($useSimpleXML){ //SimpleXmlElement
      return $xmlResult[0];
    }else{ //DOMDocument
      return $xmlResult;
    }
  }

  /*
  Perform xQuery query on opened containers
  @return array of SimpleXML objects, DOMDocument object or XMLResult object
  */   
  function query($query, $useSimpleXML = true, $raw = false,$throwException=false) 
  {
    $this->addLog("socdiscoo->query($query,$useSimpleXML,$raw)",TRACE);
    
    //namespace declaration
    $query = $this->odm_declaration . $query;
    
    try{
      if(!sedna_execute($query)){
        $str = "Could not execute query: $query\n" . sedna_error() ." (". __METHOD__ .")";
        if($throwException){
          throw new Exception($str);
        }else{
          $this->addLog($str,FATAL);
        }
      }
      
      $results = sedna_result_array();
     
      if($raw)
      {
        $xmlResult = $results;
      }
      else
      {
        if($useSimpleXML)
        {
          $xmlResult = array();
          
          foreach($results as $res){
            $xmlResult[] = new SimpleXMLElement($res);
          }
        }
        else
        {
          $xmlResult = new DOMDocument();
          $xmlResult->loadXML($results[0]);
        }
      }
    }catch(xmlexception $e){
      throw $e;
    }

    return $xmlResult;
  }

  //Ajoute un document dans le container indiquée
  //if container is not specified, we use the FileOID to determine it
  function addDocument($doc,$isString=false,$collection="",$schemaValidate=true)
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
    try
    {
      $this->addLog("addDocument sur $collection de $fileOID",INFO);
      
      //Insertion
      if($collection!=""){
        if(!sedna_load($xml->saveXML(), $fileOID, $collection)){
          $str = "Could not load the document '$fileOID' in collection '$collection': " . sedna_error() ." (". __METHOD__ .")";
          //$this->addLog($str,FATAL);
          throw new Exception($str);
        }
      }else{
        if(!sedna_load($xml->saveXML(), $fileOID)){
          $str = "Could not load the document '$fileOID': " . sedna_error() ." (". __METHOD__ .")";
          //$this->addLog($str,FATAL);
          throw new Exception($str);
        }
      }
    }
    catch(xmlexception $e)
    {
      throw new Exception($e->getMessage());
    }
  }

  //Supprime un document dans le container indiquée
  function deleteDocument($collection,$name)
  {
    /* Remove the document */
    if($collection!=""){
      $query = "DROP DOCUMENT '$name' IN COLLECTION '$collection'";
    }else{
      $query = "DROP DOCUMENT '$name'";
    }
    if(!sedna_execute($query)){
      $str = "Could not drop the document '$name' in collection '$collection': " . sedna_error() ." (". __METHOD__ .")";
      $this->addLog($str,FATAL);
    }
  }
  
  function replaceDocument($doc,$isString=false,$collection="",$schemaValidate=true)
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
    try{
      $this->addLog("socdiscoo->replaceDocument() sur $collection de $fileOID",TRACE);
      
      $xml->formatOutput = true;
      $string = $xml->saveXML();
      if($string!="")
      {     
        /* Remove the document */
        $this->deleteDocument($collection,$fileOID);
        
        //Insertion
        if($collection!=""){
          if(!sedna_load($string, $fileOID, $collection)){
            $str = "Could not load the document '$fileOID' in collection '$collection': " . sedna_error() ." (". __METHOD__ .")";
            $this->addLog($str,FATAL);
          }
        }else{
          if(!sedna_load($string, $fileOID)){
            $str = "Could not load the document '$fileOID': " . sedna_error() ." (". __METHOD__ .")";
            $this->addLog($str,FATAL);
          }
        }
        
        //By security - we save the XML file on hard drive
        $xml->save($this->m_tblConfig["CDISCOO_PATH"] . "/xml/$fileOID" . ".xml");
      }
      else
      {
        $this->addLog("Erreur replaceDocument sur $collection de $fileOID : le document xml est vide (". __METHOD__ .")",FATAL);
        throw new Exception("Erreur dans replaceDocument pour FileOID $fileOID : le document xml est vide");
      }
    }catch(xmlexception $e){
      $this->addLog("Erreur replaceDocument sur $collection de $fileOID (". __METHOD__ .") " . $e->getMessage(),FATAL);
      throw new Exception("Erreur dans replaceDocument pour FileOID $fileOID. ". $e->getMessage());  
    }
  }
  
  public function getDocumentsList($collection){
    if($collection!=""){
      $query = "<docs>
                {
                  for \$doc in document('\$documents')/documents/collection[@name='$collection']/document
                  return 
                  <doc>{\$doc/string(@name)}</doc>
                }
                </docs>
                ";
    }else{
      $query = "<docs>
                {
                  for \$doc in document('\$documents')/documents/document
                  return 
                  <doc>{\$doc/string(@name)}</doc>
                }
                </docs>
                ";
    }

    $docs = $this->query($query);
    $res = array();
    foreach($docs[0] as $doc){
      $res[] = (string)$doc;
    }
    
    return $res;
  }
  
  public function initDB(){
    //Create collections
    foreach($this->collections as $col){
      if(!sedna_execute("CREATE COLLECTION '$col'")){
        if(sedna_ercls() != "SE2002"){ //Collection with the same name already exists.
          $str = "Could create collection '$col': " . sedna_error() ." (". __METHOD__ .")";
          $this->addLog($str,FATAL);
        }
      }
    }
    
    $this->setIndexes();
    $this->SetXQLib();
  }
  
  private function setIndexes(){
    $query = $this->odm_declaration . " 
        CREATE INDEX \"SubjectODM\"
        ON collection('ClinicalData')/odm:ODM BY @FileOID
        AS xs:string";
    if(!sedna_execute($query)){
      if(sedna_ercls() != "SE2033"){ //Index with the same name already exists.
        $str = "Could create index '$query': " . sedna_error() ." (". __METHOD__ .")";
        $this->addLog($str,FATAL);
      }else{
        echo "index SubjectODM already created<br/>";
      }
    }
  }
  
  private function SetXQLib(){
    $strLib = implode("','",$this->m_tblConfig['XQUERY_LIB']);
    $query = "
        LOAD MODULE '$strLib'";
    if(!sedna_execute($query)){
      if(sedna_ercls() != "SE1073"){ //Module with the same name already exists.
        $str = "Could create module '$query': " . sedna_error() ." (". __METHOD__ .")";
        $this->addLog($str,FATAL);
      }else{
        echo "xquery module $strLib already loaded<br/>";
      }
    }    
  }
}