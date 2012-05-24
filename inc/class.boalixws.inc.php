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
require_once("class.instanciation.inc.php");

require_once(dirname(__FILE__). "/../config.inc.php"); 
 /**
 * Class to access ALIX via XMLRPC
 * eGW's xmlrpc interface is documented at http://egroupware.org/wiki/xmlrpc
 * @link http://egroupware.org/wiki/xmlrpc
 */
class boalixws extends CommonFunctions
{
  function __construct(){
  	// are we called via xmlrpc?
		if (!is_object($GLOBALS['server']) || !$GLOBALS['server']->last_method)
		{
			die('not called via xmlrpc');
		}else{

      $configEtude = $GLOBALS['configEtude'];
      CommonFunctions::__construct($configEtude,null);
      
      //Instance controler
      $this->m_ctrl = new instanciation();
    }
  }

	/**
	 * This handles introspection or discovery by the logged in client,
	 * in which case the input might be an array.  The server always calls
	 * this function to fill the server dispatch map using a string.
	 *
	 * @param string/array $_type='xmlrpc' xmlrpc or soap
	 * @return array
	 */  
  function list_methods($_type='xmlrpc')
	{
		if(is_array($_type))
		{
			$_type = $_type['type'] ? $_type['type'] : $_type[0];
		}
		switch($_type)
		{
			case 'xmlrpc':
				return array(
					'importDoc' => array(
						'function'  => 'importDoc',
						'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
						'docstring' => lang('Add or Update Doc.')
					),
					'testXQuery' => array(
						'function'  => 'testXQuery',
						'signature' => array(array(xmlrpcStruct,xmlrpcStruct)),
						'docstring' => lang('Test xQuery.')
					),
				);
				break;
			
      default:
				return array();
		}
	}
  	
	/**
	 * Add or update a document
	 *
	 * @params array (docContent => base64 of metadata, shortFileName)
	 * @return array (importLog => string)
	 */
	function importDoc($params)
	{     
    //Check user right to import metadata - need to be an admin
    if($GLOBALS['egw_info']['user']['apps']['admin']){
      //decode base64
      $xmlContent = base64_decode($params['docContent']);
      $containerName = $params['containerName'];

   	  $uploaddir = $this->m_tblConfig['CDISCOO_PATH'] . "/import/";
      $uploadfile = $uploaddir . $params['shortFileName'];

      file_put_contents($uploaddir . $params['shortFileName'],$xmlContent);
      try{
        $this->m_ctrl->socdiscoo()->addDocument($uploadfile,false,$containerName);
      }catch(Exception $e){
        $this->m_ctrl->socdiscoo()->replaceDocument($uploadfile,false,$containerName);
      }

      return array("result"=>"ok");
    }else{
   		$GLOBALS['server']->xmlrpc_error($GLOBALS['xmlrpcerr']['no_access'],$GLOBALS['xmlrpcstr']['no_access']);  
    }   
	}
  	
	/**
	 * Test an xQuery
	 *
	 * @params array (docContent => base64 of metadata, shortFileName)
	 * @return array (importLog => string)
	 */
	function testXQuery($params)
	{     
    //Check user right to import metadata - need to be an admin
    if($GLOBALS['egw_info']['user']['apps']['admin']){
    
      $SubjectKey = $params['SubjectKey'];
      $StudyEventOID = $params['StudyEventOID'];
      $StudyEventRepeatKey = $params['StudyEventRepeatKey'];
      $FormOID = $params['FormOID'];
      $FormRepeatKey = $params['FormRepeatKey'];
      $ItemgroupOID = $params['ItemgroupOID'];
      $ItemGroupRepeatKey = $params['ItemGroupRepeatKey'];
      $ItemOID = $params['ItemOID'];
      $Value = base64_decode($params['Value']);
      if($Value=="%CRF%"){
        $Value = false;
      }
      $SoftHard = $params['SoftHard'];
      $xQuery = base64_decode($params['xQuery']);
      $xQueryDecode = base64_decode($params['xQueryDecode']);
      $ErrorMessage = base64_decode($params['ErrorMessage']);
      
      
      $strRes = "";
      $result = $this->m_ctrl->bocdiscoo()->RunXQuery($SubjectKey,$StudyEventOID,$StudyEventRepeatKey,$FormOID,$FormRepeatKey,$ItemOID,$Value,$ErrorMessage,$xQuery,$xQueryDecode,$SoftHard);
      
      //$result = print_r($result,true);
      if(is_array($result)){
        if(count($result)>0){
          $strRes = (string)$result[0]['Description'];
          if($strRes==""){
            $strRes = "Description is empty!";
          }
        }else{
          $strRes = "No error.";
        }
      }else{
        if(!$result){
          $strRes = "No result!";
        }else{
          //result is an error message
          $strRes = $result;
        }
      }
      
      return array("result"=>$strRes);
    }else{
   		$GLOBALS['server']->xmlrpc_error($GLOBALS['xmlrpcerr']['no_access'],$GLOBALS['xmlrpcstr']['no_access']);  
    }   
	}

}