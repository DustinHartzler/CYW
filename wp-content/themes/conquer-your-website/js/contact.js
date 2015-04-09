jQuery.noConflict();

jQuery(document).ready(function ($) {

	jQuery("#footer_contact").validate(
	{ 
	    //Onblur Validation...
	   	onfocusout: function(element)
		{	
			$(element).valid();
		},		   
        rules:
		{ 
          cname:
		  {// compound rule 
          	required: true,
			minlength: 5
          },
		  cemail:
		  {
			required: true,
			email: true
		  },		  
		 cmessage:
		  {
			required: true,
			minlength: 10
		  }
        }
	});
	
	//Footer ajax mail...
	$('#footer_contact').submit(function () {
	if($('#cname').is('.valid') && $('#cemail').is('.valid') && $('#cmessage').is('.valid')) {
		
		var action = $(this).attr('action');

		$('#footer_contact #submit').attr('disabled', 'disabled').after('');

		$("#contact_message").slideUp(750, function () {
			$('#contact_message').hide();

			$.post(action, {
				name: $('#cname').val(),
				email: $('#cemail').val(),
				message: $('#cmessage').val()
			}, function (data) {
				document.getElementById('contact_message').innerHTML = data;
				$('#contact_message').slideDown('slow');
				$('#footer_contact #submit').attr('disabled', '');
				if (data.match('success') != null) $('#footer_contact').slideUp('slow');
			});
		});
	  }
      return false;		
    });
	
	//Social Media animation...
	$('.social-widget ul li, .social-media ul li').each(function() {
		$(this).hover(
			function() {
			   $(this).stop().animate({ opacity: 0.6, queue: false });				
			},
		   function() {
      		   $(this).stop().animate({ opacity: 1.0, queue: false });			   
	   })
	});
		//Accordin Menu...
	initDo();
});

function initDo(){
	jQuery("#accordion div.holder").hide();
	jQuery("#accordion div.holder:first").show();
	jQuery('#accordion li a:first').addClass("active");
	
	jQuery("#accordion li a").click(function(){
		var checkElement = jQuery(this).next();									  
		if((checkElement.is('div.holder')) && (checkElement.is(':visible'))) {
	        return false;
        }
		if((checkElement.is('div.holder')) && (!checkElement.is(':visible'))) {
			 jQuery('#accordion div.holder:visible').slideUp('normal');
			 checkElement.slideDown('normal');
			 jQuery('#accordion li a').removeClass('active');		
	 		 jQuery(this).addClass('active');
			 return false;
		}
    });
}
