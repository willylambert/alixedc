<?
  $configEtude['EXPORT']['TYPE'] = array(
                "dsmb" => array("index"=>"DSMB_VAR",
                                "name"=>"DSMB export",
                                "contextVars" => array(
                                  'STUDYID' => array('name'=>'Study ID',
                                                     'type'=>'string',
                                                     'length'=>'22'),
                                  'COUNTID' => array('name'=>'Country',
                                                     'type'=>'string',
                                                     'length'=>'3',
                                                     'codelist'=>'CL.$COUNT',
                                                     'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.COUNTID')),
                                  'SITEID' => array('name'=>'Site number',
                                                    'type'=>'string',
                                                    'length'=>'2',
                                                    'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITEID')),
                                  'SITENAME' => array('name'=>'Site name',
                                                      'type'=>'string',
                                                      'length'=>'50',
                                                      'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITENAME')),
                                  'PATID' => array('name'=>'Patient number',
                                                   'type'=>'string',
                                                   'length'=>'3',
                                                   'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.PATID')),
                                  'SUBJID' => array('name'=>'Subject ID',
                                                    'type'=>'string',
                                                    'length'=>'32',
                                                    'value'=>'SUBJID'),
                                  'VISITNUM' => array('name'=>'Visit number',
                                                      'type'=>'string',
                                                      'value'=>'VISITNUM'),
                                  'VISITNAME' => array('name'=>'Visit name',
                                                       'length'=>'string',
                                                       'value'=>'VISITNAME'),
                                ),
                               ),
                "full" => array("index"=>"EXPORT_VAR",
                                "name"=> "Full Export", 
                                "contextVars" => array(
                                  'STUDYID' => array('name'=>'Study ID',
                                                     'type'=>'string',
                                                     'length'=>'22',
                                                     'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.STUDYID')),
                                  'COUNTID' => array('name'=>'Country',
                                                     'type'=>'string',
                                                     'length'=>'3',
                                                     'codelist'=>'CL.$COUNT',
                                                     'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.COUNTID')),
                                  'SITEID' => array('name'=>'Site number',
                                                    'type'=>'string',
                                                    'length'=>'2',
                                                    'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITEID')),
                                  'SITENAME' => array('name'=>'Site name',
                                                      'type'=>'string',
                                                      'length'=>'50',
                                                      'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITENAME')),
                                  'PATID' => array('name'=>'Patient number',
                                                   'type'=>'string',
                                                   'length'=>'3',
                                                   'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.PATID')),
                                  'SUBJID' => array('name'=>'Subject ID',
                                                    'type'=>'string',
                                                    'length'=>'32',
                                                    'value'=>'SUBJID'),
                                  'VISITNUM' => array('name'=>'Visit number',
                                                      'type'=>'string',
                                                      'value'=>'VISITNUM'),
                                  'VISITNAME' => array('name'=>'Visit name',
                                                       'length'=>'string',
                                                       'value'=>'VISITNAME'),
                                ),
                               ),
                "coding" => array("index"=>"CODING_VAR",
                                  "name"=>"Coding Export",
                                  "contextVars" => array(
                                  'SUBJID' => array('name'=>'Subject ID',
                                                    'type'=>'string',
                                                    'length'=>'32',
                                                    'value'=>'SUBJID'),
                                  'PATID' => array('name'=>'Patient number',
                                                   'type'=>'string',
                                                   'length'=>'3',
                                                   'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.PATID')),
                                  'SITEID' => array('name'=>'Site number',
                                                    'type'=>'string',
                                                    'length'=>'2',
                                                    'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SITEID')),
                                  'SUBJINIT' => array('name'=>'Site number',
                                                    'type'=>'string',
                                                    'length'=>'2',
                                                    'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.SUBJINIT')),
                                  'COUNTID' => array('name'=>'Country',
                                                     'type'=>'string',
                                                     'length'=>'3',
                                                     'codelist'=>'CL.$COUNT',
                                                     'value'=>array('SEOID'=>'1','SERK'=>'0','FRMOID'=>'FORM.ENROL','FRMRK'=>'0','IGOID'=>'ENROL','IGRK'=>'0','ITEMOID'=>'ENROL.COUNTID')),
                                ),
                               ),

             );
   
  $configEtude['DSMB_VAR']['QSCMAP'] = array('FILEDEST'=>'QSCMAP','FIELDLIST'=>array('QSCMAP.CMAP','QSCMAP.MUNE'));
  $configEtude['DSMB_VAR']['QSPEDA'] = array('FILEDEST'=>'QSPEDCT','FIELDLIST'=>array('QSPEDCT.PEDCAT','QSPEDCT.PEDTEST','QSPEDCT.PEDORRES'));
  $configEtude['DSMB_VAR']['QSPEDC'] = array('FILEDEST'=>'QSPEDCT','FIELDLIST'=>array('QSPEDCT.PEDCAT','QSPEDCT.PEDTEST','QSPEDCT.PEDORRES'));
  $configEtude['DSMB_VAR']['QSPEDCT'] = array('FILEDEST'=>'QSPEDCT','FIELDLIST'=>array('QSPEDCT.PEDCAT','QSPEDCT.PEDTEST','QSPEDCT.PEDORRES'));

  $configEtude['DSMB_VAR']['QSPEDPA'] = array('FILEDEST'=>'QSPEDP','FIELDLIST'=>array('QSPEDP.PEDCAT','QSPEDP.PEDTEST','QSPEDP.PEDORRES'));
  $configEtude['DSMB_VAR']['QSPEDPC'] = array('FILEDEST'=>'QSPEDP','FIELDLIST'=>array('QSPEDPC.PEDCAT','QSPEDPC.PEDTEST','QSPEDPC.PEDORRES'));
  $configEtude['DSMB_VAR']['QSPEDPN'] = array('FILEDEST'=>'QSPEDP','FIELDLIST'=>array('QSPEDPN.PEDCAT','QSPEDPN.PEDTEST','QSPEDPN.PEDORRES'));

  $configEtude['DSMB_VAR']['QSPEDYC'] = array('FILEDEST'=>'QSPEDYC','FIELDLIST'=>array('QSPEDYC.PEDCAT','QSPEDYC.PEDTEST','QSPEDYC.PEDORRES'));
  $configEtude['DSMB_VAR']['EG'] = array('FILEDEST'=>'EG','FIELDLIST'=>array('EG.EGSEQ','EG.EGTEST','EG.EGORRES','EG.EGORRESU'));

  $configEtude['DSMB_VAR']['HAE'] = array('FILEDEST'=>'HAE','FIELDLIST'=>array('HAE.LBCAT','HAE.LBSEQ','HAE.LBTEST','HAE.LBORRES','HAE.LBORRESU','HAE.LBORNRLO','HAE.LBORNRHI','HAE.LBCLSIG'));
 
  $configEtude['DSMB_VAR']['LBBIO'] = array('FILEDEST'=>'LBBIO','FIELDLIST'=>array('LB.LBCAT','LB.LBSEQ','LB.LBTEST','LB.LBORRES','LB.LBORRESU','LB.LBORNRCL','LB.LBORNRLO','LB.LBORNRCH','LB.LBORNRHI','LB.LBCLSIG','LB.LBDTC'));
  $configEtude['DSMB_VAR']['LBHAE'] = array('FILEDEST'=>'LBHAE','FIELDLIST'=>array('LB.LBCAT','LB.LBSEQ','LB.LBTEST','LB.LBORRES','LB.LBORRESU','LB.LBORNRCL','LB.LBORNRLO','LB.LBORNRCH','LB.LBORNRHI','LB.LBCLSIG','LB.LBDTC'));
  $configEtude['DSMB_VAR']['AE'] = array('FILEDEST'=>'AE','FIELDLIST'=>array('AE.AESEQ','AE.AETERM','AE.AESTDTC','AE.AEENDTC','AE.AESEV','AE.AECONTR','AE.AEACN','AE.AEOUT','AE.AEREL','AE.AESER','AE.AECOM','AE.AELLT_C','AE.AELLT_N','AE.AEPT_C','AE.AEDECOD','AE.AESOC_C','AE.AEBODSYS','AE.AEHLT_C','AE.AEHLT_N','AE.AEHLGT_C','AE.AEHLGT_N','AE.MEDDRA_V'));
  $configEtude['DSMB_VAR']['DSSS'] = array('FILEDEST'=>'DSSS','FIELDLIST'=>array('DS.DSCONT','DS.DSTERM'));

  $configEtude['DSMB_VAR']['AE'] = array('FILEDEST'=>'AE','FIELDLIST'=>array('AE.AESEQ','AE.AETERM','AE.AESTDTC','AE.AEENDTC','AE.AESEV','AE.AECONTR','AE.AEACN','AE.AEOUT','AE.AEREL','AE.AESER','AE.AECOM','AE.AELLT_C','AE.AELLT_N','AE.AEPT_C','AE.AEDECOD','AE.AESOC_C','AE.AEBODSYS','AE.AEHLT_C','AE.AEHLT_N','AE.AEHLGT_C','AE.AEHLGT_N','AE.MEDDRA_V'));
  
  //Export Coding                                                                            
  $configEtude['CODING_VAR']['CM'] = array('FILEDEST'=>'CM','FIELDLIST'=>array('CM.CMSEQ','CM.CMTRT','CM.CMINDC','CM.CMINC_C','CM.CMDECOD','CM.CMATC_C','CM.CMATC_N'));
  $configEtude['CODING_VAR']['MH'] = array('FILEDEST'=>'MH','FIELDLIST'=>array('MH.MHSEQ','MH.MHTERM','MH.MHLLT_C','MH.MHLLT_N','MH.MHPT_C','MH.MHDECOD','MH.MHSOC_C','MH.MHBODSYS','MH.MHHLT_C','MH.MHHLT_N','MH.MHHLGT_C','MH.MHHLGT_N','MH.MEDDRA_V'));
  $configEtude['CODING_VAR']['AE'] = array('FILEDEST'=>'AE','FIELDLIST'=>array('AE.AESEQ','AE.AETERM','AE.AELLT_C','AE.AELLT_N','AE.AEPT_C','AE.AEDECOD','AE.AESOC_C','AE.AEBODSYS','AE.AEHLT_C','AE.AEHLT_N','AE.AEHLGT_C','AE.AEHLGT_N','AE.MEDDRA_V'));
  
  //Export FULL
  //On ne precise pas FIELDLIST => Tous les champs sont exportés 
  $configEtude['EXPORT_VAR']['AE'] = array('FILEDEST'=>'AE');
  $configEtude['EXPORT_VAR']['CM'] = array('FILEDEST'=>'CM');
 
  $configEtude['EXPORT_VAR']['DA'] = array('FILEDEST'=>'DA','POOL'=>'H');
  $configEtude['EXPORT_VAR']['DAC'] = array('FILEDEST'=>'DA','POOL'=>'H');		
  
  $configEtude['EXPORT_VAR']['DC'] = array('FILEDEST'=>'DC','POOL'=>'H');
  $configEtude['EXPORT_VAR']['DCH'] = array('FILEDEST'=>'DC','POOL'=>'H');		
  $configEtude['EXPORT_VAR']['DCP'] = array('FILEDEST'=>'DC','POOL'=>'H');		
  
  $configEtude['EXPORT_VAR']['DM'] = array('FILEDEST'=>'DM');
  
  $configEtude['EXPORT_VAR']['DS'] = array('FILEDEST'=>'DS','POOL'=>'H');
  $configEtude['EXPORT_VAR']['DSSS'] = array('FILEDEST'=>'DS','POOL'=>'H');		
  
  $configEtude['EXPORT_VAR']['SUPPEG'] = array('FILEDEST'=>'SUPPEG');
  $configEtude['EXPORT_VAR']['EG'] = array('FILEDEST'=>'EG');
  $configEtude['EXPORT_VAR']['EVA'] = array('FILEDEST'=>'EVA');
  
  $configEtude['EXPORT_VAR']['EXDA'] = array('FILEDEST'=>'EXDA','POOL'=>'H');
  $configEtude['EXPORT_VAR']['EXDAI'] = array('FILEDEST'=>'EXDA','POOL'=>'H');		
  $configEtude['EXPORT_VAR']['EXDAN'] = array('FILEDEST'=>'EXDA','POOL'=>'H');		
  
  $configEtude['EXPORT_VAR']['EXDS'] = array('FILEDEST'=>'EXDS');
  $configEtude['EXPORT_VAR']['EXI'] = array('FILEDEST'=>'EXI');
  $configEtude['EXPORT_VAR']['EXN'] = array('FILEDEST'=>'EXN');
  $configEtude['EXPORT_VAR']['HAE'] = array('FILEDEST'=>'HAE');
  $configEtude['EXPORT_VAR']['HAEDT'] = array('FILEDEST'=>'HAEDT');
  
  $configEtude['EXPORT_VAR']['IE'] = array('FILEDEST'=>'IE','POOL'=>'V');
  $configEtude['EXPORT_VAR']['IEX'] = array('FILEDEST'=>'IE','POOL'=>'V');		
  
  $configEtude['EXPORT_VAR']['IEE'] = array('FILEDEST'=>'IEE');			 
  
  $configEtude['EXPORT_VAR']['IEEL'] = array('FILEDEST'=>'IEEL');			
  
  $configEtude['EXPORT_VAR']['SUPPLB'] = array('FILEDEST'=>'SUPPLB');
  
  $configEtude['EXPORT_VAR']['SUPPLBB'] = array('FILEDEST'=>'SUPPLBB');
  
  $configEtude['EXPORT_VAR']['LBNORM'] = array('FILEDEST'=>'LBNORM');
  
  $configEtude['EXPORT_VAR']['TEMPLB'] = array('FILEDEST'=>'TEMPLB');		

  $configEtude['EXPORT_VAR']['LBBIO'] = array('FILEDEST'=>'LB','POOL'=>'V');		
  $configEtude['EXPORT_VAR']['LBHAE'] = array('FILEDEST'=>'LB','POOL'=>'V');		
  
  $configEtude['EXPORT_VAR']['SUPPMH'] = array('FILEDEST'=>'SUPPMH');
  $configEtude['EXPORT_VAR']['MH'] = array('FILEDEST'=>'MH');
  $configEtude['EXPORT_VAR']['PC'] = array('FILEDEST'=>'PC');
  
  $configEtude['EXPORT_VAR']['PE'] = array('FILEDEST'=>'PE','POOL'=>'V');
  $configEtude['EXPORT_VAR']['PEO'] = array('FILEDEST'=>'PEO','POOL'=>'V');		
  
  $configEtude['EXPORT_VAR']['QSCMAP'] = array('FILEDEST'=>'QSCMAP');
  
  $configEtude['EXPORT_VAR']['QSGCI'] = array('FILEDEST'=>'QSGCI','POOL'=>'H');
  $configEtude['EXPORT_VAR']['QSGCIP'] = array('FILEDEST'=>'QSGCI','POOL'=>'H');
  
  $configEtude['EXPORT_VAR']['QSHFMS'] = array('FILEDEST'=>'QSHFMS');
  
  $configEtude['EXPORT_VAR']['QSMFM'] = array('FILEDEST'=>'QSMFM','POOL'=>'H');
  $configEtude['EXPORT_VAR']['QSMFMP'] = array('FILEDEST'=>'QSMFM','POOL'=>'H');
  $configEtude['EXPORT_VAR']['QSMFMSC'] = array('FILEDEST'=>'QSMFM','POOL'=>'H');
  
  $configEtude['EXPORT_VAR']['QSPEDCT'] = array('FILEDEST'=>'QSPEDCT','POOL'=>'V');
  $configEtude['EXPORT_VAR']['QSPEDC'] = array('FILEDEST'=>'QSPEDCT','POOL'=>'V');
  $configEtude['EXPORT_VAR']['QSPEDA'] = array('FILEDEST'=>'QSPEDCT','POOL'=>'V');
  
  $configEtude['EXPORT_VAR']['QSPEDP'] = array('FILEDEST'=>'QSPEDP_');
  $configEtude['EXPORT_VAR']['QSPEDPN'] = array('FILEDEST'=>'QSPEDP','POOL'=>'V');
  $configEtude['EXPORT_VAR']['QSPEDPC'] = array('FILEDEST'=>'QSPEDP','POOL'=>'V');
  $configEtude['EXPORT_VAR']['QSPEDPA'] = array('FILEDEST'=>'QSPEDP','POOL'=>'V');
  
  $configEtude['EXPORT_VAR']['QSPEDYC'] = array('FILEDEST'=>'QSPEDYC');
  $configEtude['EXPORT_VAR']['SC'] = array('FILEDEST'=>'SC');
  $configEtude['EXPORT_VAR']['SV'] = array('FILEDEST'=>'SV');
  $configEtude['EXPORT_VAR']['VC'] = array('FILEDEST'=>'VC');
  $configEtude['EXPORT_VAR']['VS'] = array('FILEDEST'=>'VS');
 
  $configEtude['EXPORT_VAR']['YN'] = array('FILEDEST'=>'YN','POOL'=>'V','CUSTOM_HEADLINE'=>array('GENSMA','SNM1DEL','SNM1MUT','GENDTC','AEYN','VTYN','VTSTDTC','VTTIME','VTTYPE','CMYN','PHONE','PHONEREA','STYN','STYNREA'));
  $configEtude['EXPORT_VAR']['YNV'] = array('FILEDEST'=>'YN','POOL'=>'V');		
  $configEtude['EXPORT_VAR']['YNLV'] = array('FILEDEST'=>'YN','POOL'=>'V');		
  $configEtude['EXPORT_VAR']['YNSLV'] = array('FILEDEST'=>'YN','POOL'=>'V');		
  $configEtude['EXPORT_VAR']['YNTL'] = array('FILEDEST'=>'YN','POOL'=>'V');		
