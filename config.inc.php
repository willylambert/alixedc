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

  $configEtude["ODM_1_3_SCHEMA"] = "/var/www/alix/docs/demo/$moduleName/xsd/ODM1-3-0-foundation.xsd";
  $configEtude["ODM_1_2_SCHEMA"] = "/var/www/alix/docs/demo/$moduleName/xsd/ODM1-2-1-foundation.xsd";
  
  $configEtude["EMAIL_PV"] = "svp.clinical@businessdecision.com";
  $configEtude["EMAIL_ERROR"] = "svp.clinical@businessdecision.com";

  $configEtude["LOG_FILE"] = "$basePath/$moduleName/log/prod_".$GLOBALS['egw_info']['user']['userid'].".log";
  $configEtude["LOG_LEVEL"] = INFO;

  $configEtude["LOCK_STUDY_ID"] = $moduleName; 
  $configEtude["LOCK_FILE"] = "$basePath/$moduleName/lock/$moduleName.lock"; 
  
  $configEtude["CACHE_ENABLED"] = false;
  
  $configEtude["LOG_LONG_EXECUTION"] = true;
  $configEtude["LONG_EXECUTION_VALUE"] = 30;
  
  //Default language
  $configEtude["lang"] = "en";

  $configEtude["SUBJID_FORMAT"] = "%04d";
  
  $configEtude["APP_CSS"] = "cocktail";
  
  //Where is the enrolment form ?  
  $configEtude['BLANK_OID'] = "BLANK";
  $configEtude['ENROL_SEOID'] = "1";
  $configEtude['ENROL_SERK'] = "0";
  $configEtude['ENROL_FORMOID'] = "FORM.ENROL";
  $configEtude['ENROL_FORMRK'] = "0";
    
  //SiteId = mandatory column
  $configEtude['SUBJECT_LIST']['COLS']['COUNTRY'] = array('Visible' => true,
                                                 'Title'=>'Country',
                                                 'Width'=>60,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.COUNTID')); 
  $configEtude['SUBJECT_LIST']['COLS']['SITEID'] = array('Visible' => true,
                                                 'Title'=>'Site<br/>N°',
                                                 'Width'=>35,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITEID'));
  $configEtude['SUBJECT_LIST']['COLS']['SITENAME'] = array('Visible' => true,
                                                 'Title'=>'Site name',
                                                 'Width'=>110,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITENAME'));
  $configEtude['SUBJECT_LIST']['COLS']['SUBJINIT'] = array('Visible' => true,
                                                 'Title'=>'Initials',
                                                 'Width'=>55,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SUBJINIT'));
  $configEtude['SUBJECT_LIST']['COLS']['SUBJID'] = array('Visible' => true,
                                                 'Title'=>'Patient<br/>Number',
                                                 'Width'=>60,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SUBJID'));

  $configEtude['SUBJECT_LIST']['COLS']['SCREENINGSTATUS'] = array('Visible' => true,
                                                 'Title'=>'Screening visit',
                                                 'ShortTitle' => 'SC',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['V0'] = array('Visible' => true,
                                                 'Title'=>'Inclusion Visit (V0)',
                                                 'ShortTitle' => 'V0',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'2','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['V1'] = array('Visible' => true,
                                                 'Title'=>'Visit V1',
                                                 'ShortTitle' => 'V1',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'3','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['V2'] = array('Visible' => true,
                                                 'Title'=>'Visit V1',
                                                 'ShortTitle' => 'V1',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'4','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['ENDSTUDY'] = array('Visible' => true,
                                                 'Title'=>'End of study',
                                                 'ShortTitle' => 'End',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'5','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['CM'] = array('Visible' => true,
                                                 'Title'=>'Concomitant TT',
                                                 'ShortTitle' => 'TT',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'CM','SERK'=>'0'));
  $configEtude['SUBJECT_LIST']['COLS']['AE'] = array('Visible' => true,
                                                 'Title'=>'Adverse Events',
                                                 'ShortTitle' => 'AE',
                                                 'Orientation'=>'V',
                                                 'Type' => 'VISITSTATUS',
                                                 'Width'=>20,
                                                 'Value'=>array('SEOID'=>'AE','SERK'=>'0'));
                                                                                                                                                   
  $configEtude['SUBJECT_LIST']['COLS']['BRTHDTC'] = array('Visible' => false,
                                                 'Title'=>'DOB',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.IC','FRMRK'=>'0','IGOID'=>'DM','IGRK'=>'0','ITEMOID'=>'DM.BRTHDTC'));
  $configEtude['SUBJECT_LIST']['COLS']['DMAGE'] = array('Visible' => false,
                                                 'Title'=>'Age',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.IC','FRMRK'=>'0','IGOID'=>'DM','IGRK'=>'0','ITEMOID'=>'DM.AGE'));
  $configEtude['SUBJECT_LIST']['COLS']['WEIGHT'] = array('Visible' => false,
                                                 'Title'=>'Weight',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.VS','FRMRK'=>'0','IGOID'=>'VS','IGRK'=>'1','ITEMOID'=>'VS.VSORRES'));
  $configEtude['SUBJECT_LIST']['COLS']['SVSVSTDTC'] = array('Visible' => false,
                                                 'Title'=>'Inclusion Date',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'2','SERK'=>'0','FRMOID'=>'FORM.SV','FRMRK'=>'0','IGOID'=>'SV','IGRK'=>'0','ITEMOID'=>'SV.SVSTDTC'));
  $configEtude['SUBJECT_LIST']['COLS']['IEELIG'] = array('Visible' => false,
                                                 'Title'=>'IEELIG',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.IEE','FRMRK'=>'0','IGOID'=>'IEE','IGRK'=>'0','ITEMOID'=>'IE.IEELIG'));
  $configEtude['SUBJECT_LIST']['COLS']['IEYN'] = array('Visible' => false,
                                                 'Title'=>'IEYN',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'2','SERK'=>'0','FRMOID'=>'FORM.ELIG','FRMRK'=>'0','IGOID'=>'IEEL','IGRK'=>'0','ITEMOID'=>'IE.IEYN'));
  $configEtude['SUBJECT_LIST']['COLS']['RDNUM'] = array('Visible' => false,
                                                 'Title'=>'RDNUM',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'2','SERK'=>'0','FRMOID'=>'FORM.ELIG','FRMRK'=>'0','IGOID'=>'EXI','IGRK'=>'0','ITEMOID'=>'EXI.RDNUM'));
  $configEtude['SUBJECT_LIST']['COLS']['CONT'] = array('Visible' => false,
                                                 'Title'=>'CONT',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'13','SERK'=>'0','FRMOID'=>'FORM.SS','FRMRK'=>'0','IGOID'=>'DSSS','IGRK'=>'0','ITEMOID'=>'DS.CONT'));
  $configEtude['SUBJECT_LIST']['COLS']['DSTERMN'] = array('Visible' => false,
                                                 'Title'=>'DSTERMN',
                                                 'Width'=>0,
                                                 'Value'=>array('SEOID'=>'13','SERK'=>'0','FRMOID'=>'FORM.SS','FRMRK'=>'0','IGOID'=>'DSSS','IGRK'=>'0','ITEMOID'=>'DS.DSTERMN'));

  //Forms for which we enable pagination
  //$browser = get_browser(null, true);
  //$maxIGperPage = ($browser['browser']!="IE" ? 10 : 5);
  $maxIGperPage = 10;
  $configEtude['FORM_PAGINATE']['FORM.CM'] = array('FRMOID'=>'FORM.CM','IG_PER_PAGE'=>$maxIGperPage);


  //Downloadable docmuments for all countries
  $configEtude['DOCS']['INT'] = array(
                                      "User Guide INV" => "Draft_guide_utilisateur_ALIX.pdf",
                                     );

  require_once("config.export.inc.php");

  $GLOBALS['configEtude'] = $configEtude;

