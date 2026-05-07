<script>

	(function($) { 

	    "use strict"; 
	    
	    @if(Session::has('success'))
	        Snackbar.show({
	            text: '{{ Session::get('success') }}', 
	            pos: 'bottom-center',
	            actionTextColor: getComputedStyle(document.documentElement).getPropertyValue('--bs-success-text-emphasis').trim() || '#8ace9d'
	        });
	    @endif

	    @if(Session::has('error'))
	        Snackbar.show({text: '{{ Session::get("error") }}', pos: 'bottom-center',backgroundColor: '#dc3545',actionTextColor: 'white'});
	    @endif

	    @if(Session::has('errors') || (isset($errors) && $errors->any()))
	        Snackbar.show({text: '{{ $errors->first() }}', pos: 'bottom-center',backgroundColor: '#dc3545',actionTextColor: 'white'});
	    @endif

	})(jQuery); 

</script>
