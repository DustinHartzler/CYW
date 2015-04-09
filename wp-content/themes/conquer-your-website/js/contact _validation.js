jQuery.noConflict();

jQuery(document).ready(function ($) {

	jQuery("#frmcontact").validate(
	{ 
	    //Onblur Validation...
	   	onfocusout: function(element)
		{	
			$(element).valid();
		},		   
        rules:
		{ 
          name:
		  {// compound rule 
          	required: true,
			minlength: 5
          },
		  email:
		  {
			required: true,
			email: true
		  },		  
		 comment:
		  {
			required: true,
			minlength: 10
		  }
        }
	});
	
	$('#frmcontact').submit(function () {
	if($('#name').is('.valid') && $('#email').is('.valid') && $('#comment').is('.valid')) {
		
		var action = $(this).attr('action');

		$('#frmcontact #send').attr('disabled', 'disabled').after('');

		$("#ajax_message").slideUp(750, function () {
			$('#ajax_message').hide();

			$.post(action, {
				name: $('#name').val(),
				email: $('#email').val(),
				comment: $('#comment').val()
			}, function (data) {
				document.getElementById('ajax_message').innerHTML = data;
				$('#ajax_message').slideDown('slow');
				$('#frmcontact #send').attr('disabled', '');
				if (data.match('success') != null) $('#frmcontact').slideUp('slow');
			});
		});
	  }
      return false;		
    });
});