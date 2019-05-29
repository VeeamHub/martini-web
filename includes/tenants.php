<?php
session_start();

require_once(__DIR__ . '/../core/tenant.php');

$tenants = getTenants();
?>
<div class="main-container">
    <h1>Tenant overview</h1>
	<hr>
	<?php
	if (count($tenants) != '0') {
	?>
		<table class="table table-hover table-bordered table-padding table-striped" id="table-tenants">
			<thead>
				<tr>
					<th class="name">Name</th>
					<th class="email">E-mail</th>
					<th class="text-center options">Options</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($i = 0; $i < count($tenants); $i++) {
				?>
				<tr>
					<td><?php echo $tenants[$i]->name; ?></td>
					<td><?php echo $tenants[$i]->email; ?></td>
					<td class="text-center">
						<button class="btn btn-default btn-edit" data-id="<?php echo $tenants[$i]->id; ?>" title="Edit">Edit</button>
						<button class="btn btn-default btn-delete" data-id="<?php echo $tenants[$i]->id; ?>" title="Delete">Delete</button>
					</td>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	} else {
		echo 'No tenants found.';
	}
	?>
</div>

<div class="modal" id="sessionModalCenter" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header"><h1 class="modal-title">Update settings</h1></div>
      <div class="modal-body">
		<div class="row">
			<label class="col-md-2 control-label" for="tenantname">Name:</label>
			<div class="col-md-6">
			  <input type="text" id="tenantname" name="tenantname" class="form-control input-md">
			</div>
		</div>
		<br />
		<div class="row">
			<label class="col-md-2 control-label" for="tenantemail">E-mail:</label>  
			<div class="col-md-6">
			<input type="text" id="tenantemail" name="tenantemail" class="form-control input-md">
			</div>
		</div>
		<br />
		<div class="row">
			<label class="col-md-2 control-label" for="tenantemail">Password:</label>  
			<div class="col-md-6">
			<input type="checkbox" name="checkbox-password"> Generate new password
			</div>
		</div>
      </div>
	  <div class="modal-footer">
		<input type="hidden" id="tenantid" name="tenantid">
		<button type="button" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</button>
		<button type="button" class="btn btn-success btn-save">Save</button>
	  </div>
    </div>
  </div>
</div>

<script>
$('.btn-edit').click(function(e) {
	var id = $(this).data('id');
	
	$.get('core/veeam.php', {'action' : 'gettenant', 'id' : id}).done(function(data) {
		var tenant = JSON.parse(data);
		
		$('#tenantid').val(tenant.id);
		$('#tenantname').val(tenant.name);
		$('#tenantemail').val(tenant.email);
	});

	$('#sessionModalCenter').modal('show');
});
$('.btn-delete').click(function(e) {
	var id = $(this).data('id');
	var tr = $(this).parents('tr');
	
	const swalWithBootstrapButtons = Swal.mixin({
	  confirmButtonClass: 'btn btn-success btn-margin',
	  cancelButtonClass: 'btn btn-danger',
	  buttonsStyling: false,
	})
	
	swalWithBootstrapButtons.fire({
		type: 'question',
		title: 'Delete tenant?',
		html:
			'This will remove the tenant.<br /><br />' +
			'<div class="form-group row">' +
			'<input type="checkbox" name="checkbox-deleteinstance"> Remove all instances assigned to the tenant' +
			'</div>',			
		showCancelButton: true,
		confirmButtonText: 'Yes',
		cancelButtonText: 'No',
	}).then((result) => {
		if (result.value) {
			$.get('core/veeam.php', {'action' : 'deletetenant', 'id' : id}).done(function(data) {
				if ($("input[name='checkbox-deleteinstance']:checked").length == 0) {
					$.get('core/veeam.php', {'action' : 'updateinstances', 'id' : id}).done(function(data) {
						swalWithBootstrapButtons.fire({
							type: 'success',
							title: 'Success!',
							text: 'Tenant was removed.'
						});
					});
				} else {
					$.get('core/veeam.php', {'action' : 'deleteinstances', 'id' : id}).done(function(data) {
						swalWithBootstrapButtons.fire({
							type: 'success',
							title: 'Success!',
							text: 'Tenant was removed. Instances have been queued for removal.'
						});
					});
				}
				
				tr.addClass('hide');				
			});
		  } else {
			return;
		}
	})
});
$('.btn-save').click(function(e) {
	var id = $('#tenantid').val();
	var tenantname = $('#tenantname').val();
	var tenantemail = $('#tenantemail').val();
	
	if ($("input[name='checkbox-password']:checked").length == 0) {
		var parameter = { id:id, name:tenantname, email:tenantemail };
	} else {
		var parameter = { id:id, name:tenantname, email:tenantemail, password:'true' };
	}
	
	var json = JSON.stringify(parameter);
	
	$.get('core/veeam.php', {'action' : 'updatetenant', 'json' : json}).done(function(data) {
		$('#sessionModalCenter').modal('hide');
		
		var result = JSON.parse(data);
		
		if ($("input[name='checkbox-password']:checked").length == 0) {
			var text = 'Tenant has been updated.';
		} else {
			var text = 'Tenant has been updated. The new password has been set to: ' + result.password;
		}
		
		Swal.fire({
			type: 'success',
			title: 'Tenant updated',
			text: text
		}).then(function(e) {
			$('#main').load('includes/tenants.php');
		});
	});
});
</script>