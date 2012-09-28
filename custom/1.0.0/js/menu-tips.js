
//CSS map for bubbles
var jqabStyle = "{padding: 10px; border-radius: 15px;}";

//text for bubbles
var menuTips = {
  enroll:     'Create a new subject and start filling in its CRF.',
  subjects:   'List of subjects : select an existing subject and access to its CRF.',
  dashboard:  'Figures of the study : number of subjects, distribution, etc.',
  documents:  'List of documents you can download (i.e. protocol, etc).',
  testmode:   (testmode ? 'Come back to the real CRF : manage actual subjects.' : 'Switch to test mode : create virtual subjects and start discovering the CRF.'),
  queries:    'Consult and manage queries : suggest corrections, close resolved queries, open new ones.',
  deviations: 'Consult and manage protocol deviations.',
  audittrail: 'Explore the history of data.',
  tools:      'Acces to the administration board and manage:<ul><li>Users : create users and manage their rights</li><li>Sites : create and manage sites</li><li>ClinicalData : consult the list of subjects\' documents (XML CDISC)</li><li>MetaData : Access to the study design\'s document (XML CDISC)</li><li>Import Metadata/ClinicalData : import and replace documents (XML CDISC)</li><li>Import Clinical Data</li><li>Data export : export data for statistical purposes</li><li>Editor : edit CDSIC documents and modify specific scripts (user interface, behavior of pages)</li></ul>',
  logout:     'Close your session.'
};

$(document).ready( function(){
  $('#toolbar_ico a').each( function(){
    $(this).attr('altbox', jqabStyle + menuTips[$(this).attr('name')]);
  });
});