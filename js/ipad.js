$( "[data-role='listview']" ).on( "listviewcreate", function() {

    //Make the visit menu an accordion
    $(this).find("li[data-role='list-divider']").each(function(index,accordion){
      var $accordion = $(accordion);
      $accordion.unbind('click');
      accordion.find('a').remove();
      $accordion.bind('click', function(){
        $accordion.next().toggle();
      });  
    });

});