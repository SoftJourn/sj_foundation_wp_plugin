jQuery(function () {
  jQuery( '#submitdiv' ).on( 'click', '#publish', function( e ) {
    var $checked = jQuery( '#sj_project_metabox input[name="sj_project_can_donate_more"]' ).attr('checked');
    var $price = jQuery( '#sj_project_metabox input[name="sj_project_price"]' ).val();
    var $title = jQuery('#title').val();

    if (!$title) {
      e.preventDefault();
      alert( "Title cnn't be empty" );
      return false;
    }
    if( $checked != 'checked' && parseInt($price) <= 0) {
      e.preventDefault();
      alert( "Fixed price can't be 0" );
      return false;
    }
  } );

  jQuery('#datepicker').datepicker({
    dateFormat: 'yy-mm-dd',
    minDate: 0,
  });
  jQuery('#timepicker').timepicker({
    'scrollDefault': 'now',
    'timeFormat': 'H:i',
  });
});