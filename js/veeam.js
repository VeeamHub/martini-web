$(document).ready(function(e) {
    /* Logout option */
    $('#logout').click(function(e) {
        e.preventDefault();
		
		const swalWithBootstrapButtons = Swal.mixin({
		  confirmButtonClass: 'btn btn-success btn-margin',
		  cancelButtonClass: 'btn btn-danger',
		  buttonsStyling: false,
		})
		
		swalWithBootstrapButtons.fire({
			type: 'question',
			title: 'Logout',
			text: 'You are about to logout. Are you sure you want to continue?',
			showCancelButton: true,
			confirmButtonText: 'Yes',
			cancelButtonText: 'No',
		}).then((result) => {
			if (result.value) {
				$.post('index.php', {'logout' : true}, function(data) {
					window.location.replace('index.php');
				});
			  } else {
				return;
			}
		})
    });  
});