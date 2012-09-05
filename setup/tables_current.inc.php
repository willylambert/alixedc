<?
	$phpgw_baseline = array(
	'egw_alix_acl' => array(
		'fd' => array(
			'CURRENTAPP' => array('type' => 'varchar','precision' => '15','nullable' => False),
			'USERID' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'SITEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
			'PROFILEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
			'DEFAULTPROFILE' => array('type' => 'varchar','precision' => '1','nullable' => False),
		),
		'pk' => array('CURRENTAPP','USERID','SITEID'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	'egw_alix_deviations' => array(
		'fd' => array(
			'DEVIATIONID' => array('type' => 'auto','nullable' => False),
      'SEOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'SERK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'FRMOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'FRMRK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'IGOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'IGRK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'ITEMOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'ITEMTITLE' => array('type' => 'longtext'),
      'CURRENTAPP' => array('type' => 'varchar','precision' => '20','nullable' => False),
			'DESCRIPTION' => array('type' => 'longtext'),
      'BYWHO' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'BYWHOGROUP' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'UPDATEDT' => array('type' => 'datetime'),
      'SUBJKEY' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'SITEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'ISLAST' => array('type' => 'varchar','precision' => '1','nullable' => False),
      'STATUS' => array('type' => 'varchar','precision' => '1','nullable' => False),
		),
		'pk' => array('DEVIATIONID'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),		
	'egw_alix_export_log' => array(
		'fd' => array(
			'logid' => array('type' => 'auto','nullable' => False),
			'exportfilename' => array('type' => 'varchar','precision' => '200','nullable' => False),
			'exporttype' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'exportdate' => array('type' => 'datetime'),
      'exportpassword' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'exportuser' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'currentapp' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'exportpath' => array('type' => 'varchar','precision' => '255','nullable' => False),
      'exportid' => array('type' => 'varchar','precision' => '20','nullable' => False),
		),
		'pk' => array('logid'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	'egw_alix_export_def' => array(
		'fd' => array(
			'exportid' => array('type' => 'int','precision' => '11','nullable' => False),
			'studyeventoid' => array('type' => 'varchar','precision' => '50','nullable' => False),
			'formoid' => array('type' => 'varchar','precision' => '50','nullable' => False),
			'itemgroupoid' => array('type' => 'varchar','precision' => '50','nullable' => False),
			'fields' => array('type' => 'text','nullable' => False),
			'updateDT' => array('type' => 'datetime'),
		),
		'pk' => array('exportid','studyeventoid','formoid','itemgroupoid'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	'egw_alix_export' => array(
		'fd' => array(
			'id' => array('type' => 'auto','nullable' => False),
			'name' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'description' => array('type' => 'text','nullable' => False),
			'user' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'creationDate' => array('type' => 'datetime'),
      'share' => array('type' => 'varchar','precision' => '1','nullable' => False),
      'currentapp' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'raw' => array('type' => 'varchar','precision' => '1','nullable' => False),
		),
		'pk' => array('id'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	'egw_alix_import' => array(
		'fd' => array(
			'IMPORTID' => array('type' => 'auto','nullable' => False),
			'DATE_IMPORT_FILE' => array('type' => 'date'),
      'USER' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'STATUS' => array('type' => 'varchar','precision' => '10','nullable' => False),
			'ERROR_COUNT' => array('type' => 'int','precision' => '11','nullable' => False),
			'IMPORT_FILE' => array('type' => 'varchar','precision' => '200','nullable' => False),
			'REPORT_FILE' => array('type' => 'varchar','precision' => '200','nullable' => False),
      'IMPORT_TYPE' => array('type' => 'varchar','precision' => '100','nullable' => False),
			'DATE_IMPORT' => array('type' => 'datetime'),
      'currentapp' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'importpath' => array('type' => 'varchar','precision' => '300','nullable' => False),
		),
		'pk' => array('IMPORTID'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	'egw_alix_lock' => array(
		'fd' => array(
			'ID' => array('type' => 'auto','nullable' => False),
			'STUDY' => array('type' => 'varchar','precision' => '30','nullable' => False),
			'STARTTIME' => array('type' => 'int','precision' => '12','nullable' => False),
			'STOPTIME' => array('type' => 'int','precision' => '12','nullable' => False),
			'WAITTIME' => array('type' => 'float','nullable' => False),
			'LOCKDT' => array('type' => 'datetime','nullable' => False),
			'WHO' => array('type' => 'varchar','precision' => '100','nullable' => False),
		),
		'pk' => array('ID'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	'egw_alix_postit' => array(
		'fd' => array(
			'POSTITID' => array('type' => 'int','precision' => '11','nullable' => False),
      'SEOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'SERK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'FRMOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'FRMRK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'IGOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'IGRK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'CURRENTAPP' => array('type' => 'varchar','precision' => '20','nullable' => False),
			'TXT' => array('type' => 'longtext'),
      'BYWHO' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'BYWHOGROUP' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'ITEMOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'SUBJKEY' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'SITEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'DT' => array('type' => 'datetime'),
      'ISREAD' => array('type' => 'varchar','precision' => '1','nullable' => False),
		),
		'pk' => array('SEOID','SERK','FRMOID','FRMRK','IGOID','IGRK','CURRENTAPP','ITEMOID','SUBJKEY'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),		
	'egw_alix_queries' => array(
		'fd' => array(
			'QUERYID' => array('type' => 'auto','nullable' => False),
      'SEOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'SERK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'FRMOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'FRMRK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'IGOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'IGRK' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'POSITION' => array('type' => 'int','precision' => '2','nullable' => False),
      'CURRENTAPP' => array('type' => 'varchar','precision' => '20','nullable' => False),
			'DESCRIPTION' => array('type' => 'longtext'),
      'ITEMTITLE' => array('type' => 'longtext'),
      'ISMANUAL' => array('type' => 'varchar','precision' => '1','nullable' => False),
      'BYWHO' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'BYWHOGROUP' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'UPDATEDT' => array('type' => 'datetime'),
      'QUERYTYPE' => array('type' => 'varchar','precision' => '2','nullable' => False),
      'QUERYSTATUS' => array('type' => 'varchar','precision' => '1','nullable' => False),
      'ANSWER' => array('type' => 'longtext'),
      'ITEMOID' => array('type' => 'varchar','precision' => '50','nullable' => False),
      'SUBJKEY' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'SITEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'ISLAST' => array('type' => 'varchar','precision' => '1','nullable' => False),
      'VALUE' => array('type' => 'varchar','precision' => '255','nullable' => False),
      'DECODE' => array('type' => 'longtext'),
      'CONTEXTKEY' => array('type' => 'longtext'),      
		),
		'pk' => array('QUERYID'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),    
	'egw_alix_sites' => array(
		'fd' => array(
			'SITEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
			'SITENAME' => array('type' => 'varchar','precision' => '50','nullable' => False),
			'SITEPROFILEID' => array('type' => 'varchar','precision' => '10','nullable' => False),
      'CURRENTAPP' => array('type' => 'varchar','precision' => '15','nullable' => False),
      'COUNTRY' => array('type' => 'varchar','precision' => '100','nullable' => False),
		),
		'pk' => array('SITEID','CURRENTAPP'),
		'fk' => array(),
		'ix' => array(),
		'uc' => array()
		),
	);
?>