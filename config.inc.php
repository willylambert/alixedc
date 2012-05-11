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

  $basePath = "/var/www/alix/data";
  //No more than 20 characters
  $moduleName = "alixedc";
  $moduleTitle = "alixedc";

  $configEtude = array();

  $configEtude["MODULE_NAME"] = $moduleName;

  $configEtude["APP_NAME"] = "DEMO BD STUDY";
  
  $configEtude["JS_VERSION"] = "1706";
  $configEtude["METADATAVERSION"] = "1.0.0";
  $configEtude["META_TO_EXPORT"] = $configEtude["METADATAVERSION"];
  
  $configEtude["CODE_PROJET"] = "BDSLF999P01";
  $configEtude["CLIENT"] = "BD";

  $configEtude["CDISCOO_PATH"] = "$basePath/$moduleName";
  $configEtude["DBXML_BASE_PATH"] = "$basePath/$moduleName/dbxml/";
  $configEtude["EXPORT_BASE_PATH"] = "$basePath/$moduleName/export/";
  $configEtude["IMPORT_BASE_PATH"] = "$basePath/alixdb_dev/import/";
  $configEtude["DBXML_BASE_DEMO_PATH"] = "$basePath/$moduleName/dbxmlDemoBase/";
  
  //Sedna database configuration
  $configEtude["SEDNA_HOST"] = "localhost";
  $configEtude["SEDNA_DATABASE"] = "alixedc";
  $configEtude["SEDNA_USER"] = "SYSTEM";
  $configEtude["SEDNA_PASSWORD"] = "MANAGER";
  $configEtude["SEDNA_NAMESPACE_ODM"] = "http://www.cdisc.org/ns/odm/v1.3";

  $configEtude["ODM_1_3_SCHEMA"] = "/var/www/alix/docs/demo/$moduleName/xsd/ODM1-3-0-foundation.xsd";
  $configEtude["ODM_1_2_SCHEMA"] = "/var/www/alix/docs/demo/$moduleName/xsd/ODM1-2-1-foundation.xsd";
  
  $configEtude["EMAIL_PV"] = "svp.clinical@businessdecision.com";
  $configEtude["EMAIL_ERROR"] = "svp.clinical@businessdecision.com,hotline@cyber-nova.com";
  $configEtude["EMAIL_CONTACT"] = "willy.lambert@businessdecision.com"; 

  $configEtude["LOG_FILE"] = "$basePath/$moduleName/log/prod_".$GLOBALS['egw_info']['user']['userid'].".log";
  $configEtude["LOG_LEVEL"] = INFO;

  $configEtude["LOCK_STUDY_ID"] = $moduleName; 
  $configEtude["LOCK_FILE"] = "$basePath/$moduleName/lock/$moduleName.lock"; 
  
  $configEtude["CACHE_ENABLED"] = false;
  
  $configEtude["LOG_LONG_EXECUTION"] = true;
  $configEtude["LONG_EXECUTION_VALUE"] = 30;
  
  //Default language
  $configEtude["lang"] = "en";

  //Number of digits for the SUBJID
  $configEtude["SUBJID_FORMAT"] = "%04d";
  
  //Where is the enrolment form ?  
  $configEtude['BLANK_OID'] = "BLANK";
  $configEtude['ENROL_SEOID'] = "ENROL";
  $configEtude['ENROL_SERK'] = "0";
  $configEtude['ENROL_FORMOID'] = "FORM.SC";
  $configEtude['ENROL_FORMRK'] = "0";
    
  //SiteId = mandatory column
  $configEtude['SUBJECT_LIST']['COLS']['SITEID'] = array('Visible' => true,
                                                 'Title'=>'Site<br/>N°',
                                                 'Width'=>35,
                                                 'Value'=>array('SEOID'=>'ENROL','SERK'=>'0','FRMOID'=>'FORM.SC','FRMRK'=>'0','IGOID'=>'SC','IGRK'=>'0','ITEMOID'=>'SC.SITEID')); 
  $configEtude['SUBJECT_LIST']['COLS']['SITENAME'] = array('Visible' => true,
                                                 'Title'=>'Site name',
                                                 'Width'=>110,
                                                 'Value'=>array('SEOID'=>'ENROL','SERK'=>'0','FRMOID'=>'FORM.SC','FRMRK'=>'0','IGOID'=>'SC','IGRK'=>'0','ITEMOID'=>'SC.SITENAME'));
  $configEtude['SUBJECT_LIST']['COLS']['SUBJID'] = array('Visible' => true,
                                                 'Title'=>'Subject Id',
                                                 'Width'=>55,
                                                 'Value'=>'SUBJID');
  
  $configEtude['SUBJECT_LIST']['COLS']['INCDT'] = array('Visible' => true,
                                                 'Title'=>'Enrolment',
                                                 'Width'=>100,
                                                 'Value'=>array('SEOID'=>'ENROL','SERK'=>'0','FRMOID'=>'FORM.SC','FRMRK'=>'0','IGOID'=>'SC','IGRK'=>'0','ITEMOID'=>'SC.INCDT'));
  $configEtude['SUBJECT_LIST']['COLS']['V0'] = array('Visible' => true,
                                                 'Title'=>'Inclusion Visit',
                                                 'ShortTitle' => 'V0',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'ENROL','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['V1'] = array('Visible' => true,
                                                 'Title'=>'Year 1 - V1',
                                                 'ShortTitle' => 'Y1 - V1',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'1'));
  $configEtude['SUBJECT_LIST']['COLS']['V2'] = array('Visible' => true,
                                                 'Title'=>'Year 1 - V2',
                                                 'ShortTitle' => 'V2',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'2'));
  $configEtude['SUBJECT_LIST']['COLS']['V3'] = array('Visible' => true,
                                                 'Title'=>'Year 1 - V3',
                                                 'ShortTitle' => 'V3',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'3'));
  $configEtude['SUBJECT_LIST']['COLS']['V4'] = array('Visible' => true,
                                                 'Title'=>'Year 2 - V4',
                                                 'ShortTitle' => 'V4',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'4'));
  $configEtude['SUBJECT_LIST']['COLS']['V5'] = array('Visible' => true,
                                                 'Title'=>'Year 2 - V5',
                                                 'ShortTitle' => 'V5',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'5'));
  $configEtude['SUBJECT_LIST']['COLS']['V6'] = array('Visible' => true,
                                                 'Title'=>'Year 2 - V6',
                                                 'ShortTitle' => 'V6',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'6'));
  $configEtude['SUBJECT_LIST']['COLS']['V7'] = array('Visible' => true,
                                                 'Title'=>'Year 3 - V7',
                                                 'ShortTitle' => 'V7',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'7'));
  $configEtude['SUBJECT_LIST']['COLS']['V8'] = array('Visible' => true,
                                                 'Title'=>'Year 3 - V8',
                                                 'ShortTitle' => 'V8',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'8'));
  $configEtude['SUBJECT_LIST']['COLS']['V9'] = array('Visible' => true,
                                                 'Title'=>'Year 3 - V9',
                                                 'ShortTitle' => 'V9',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'9'));
  $configEtude['SUBJECT_LIST']['COLS']['V10'] = array('Visible' => true,
                                                 'Title'=>'Year 4 - V10',
                                                 'ShortTitle' => 'V10',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'10'));
  $configEtude['SUBJECT_LIST']['COLS']['V11'] = array('Visible' => true,
                                                 'Title'=>'Year 4 - V11',
                                                 'ShortTitle' => 'V11',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'11'));
  $configEtude['SUBJECT_LIST']['COLS']['V12'] = array('Visible' => true,
                                                 'Title'=>'Year 4 - V12',
                                                 'ShortTitle' => 'V12',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'12'));
  $configEtude['SUBJECT_LIST']['COLS']['V13'] = array('Visible' => true,
                                                 'Title'=>'Year 5 - V13',
                                                 'ShortTitle' => 'V13',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'13'));
  $configEtude['SUBJECT_LIST']['COLS']['V14'] = array('Visible' => true,
                                                 'Title'=>'Year 5 - V14',
                                                 'ShortTitle' => 'V14',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'14'));
  $configEtude['SUBJECT_LIST']['COLS']['V15'] = array('Visible' => true,
                                                 'Title'=>'Year 5 - V15',
                                                 'ShortTitle' => 'V15',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'15'));
  $configEtude['SUBJECT_LIST']['COLS']['V16'] = array('Visible' => true,
                                                 'Title'=>'Year 6 - V16',
                                                 'ShortTitle' => 'V16',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'16'));
  $configEtude['SUBJECT_LIST']['COLS']['V17'] = array('Visible' => true,
                                                 'Title'=>'Year 6 - V17',
                                                 'ShortTitle' => 'V17',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'17'));
  $configEtude['SUBJECT_LIST']['COLS']['V18'] = array('Visible' => true,
                                                 'Title'=>'Year 6 - V18',
                                                 'ShortTitle' => 'V18',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'18'));

  $configEtude['SUBJECT_LIST']['COLS']['V19'] = array('Visible' => true,
                                                 'Title'=>'Year 7 - V19',
                                                 'ShortTitle' => 'V19',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'19'));
  $configEtude['SUBJECT_LIST']['COLS']['V20'] = array('Visible' => true,
                                                 'Title'=>'Year 7 - V20',
                                                 'ShortTitle' => 'V20',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'20'));
  $configEtude['SUBJECT_LIST']['COLS']['V21'] = array('Visible' => true,
                                                 'Title'=>'Year 7 - V21',
                                                 'ShortTitle' => 'V21',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'21'));
  $configEtude['SUBJECT_LIST']['COLS']['V22'] = array('Visible' => true,
                                                 'Title'=>'Year 8 - V22',
                                                 'ShortTitle' => 'V22',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'22'));
  $configEtude['SUBJECT_LIST']['COLS']['V23'] = array('Visible' => true,
                                                 'Title'=>'Year 8 - V23',
                                                 'ShortTitle' => 'V23',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'23'));
  $configEtude['SUBJECT_LIST']['COLS']['V24'] = array('Visible' => true,
                                                 'Title'=>'Year 8 - V24',
                                                 'ShortTitle' => 'V24',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'FOLLOWUP','SERK'=>'24'));
/*                                                 
  $configEtude['SUBJECT_LIST']['COLS']['ENDSTUDY'] = array('Visible' => true,
                                                 'Title'=>'Appendices',
                                                 'ShortTitle' => 'End',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'APPENDICES','SERK'=>'0'));
*/

  //Forms for which we enable pagination
  $configEtude['FORM_PAGINATE']['FORM.CM'] = array('FRMOID'=>'FORM.CM','IG_PER_PAGE'=>10);

  //Forms for which a deviation can be entered
  $configEtude['FORM_DEVIATIONS'][] =  array('SEOID'=>'ENROL','SERK'=>'0','FRMOID'=>'FORM.VDT','FRMRK'=>'0');
  
 //Security                                                
  $configEtude['PASSWORD']['MIN_LENGTH'] = 6;         
  $configEtude['PASSWORD']['UPPER_LOWER_CASE'] = true;
  $configEtude['PASSWORD']['CHANGE_AFTER'] = 90;

  //Downloadable docmuments for all countries
  $configEtude['DOCS']['INT'] = array(
                                      "User Guide INV" => "Draft_guide_utilisateur_ALIX.pdf",
                                     );

  require_once("config.export.inc.php");

  $GLOBALS['configEtude'] = $configEtude;

