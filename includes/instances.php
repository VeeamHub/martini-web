<?php
session_start();

require_once(__DIR__ . '/../core/tenant.php');
?>
<div class="main-container">
	<h1>Instance overview</h1>
	<hr />
<?php
if ($_SESSION['portaltoken'] != 'tenant') { /* Admin view */
	$tenants = getTenants();
	$orphaned = getOrphanedInstances();
		
	for ($i = 0; $i < count($tenants); $i++) {
		echo '<h3>Instances for: ' . $tenants[$i]->name . '</h3>';
		echo '<hr />';
		
		$total = getTenantInstanceCounter($tenants[$i]->id);
		
		if ($total != '0') {
			try {
				$instances = getTenantAllInstances($tenants[$i]->id);
			} catch (Exception $e) {
			}
			
			if (isset($instances) && !is_null($instances)) {
			?>
			<table class="table table-hover table-bordered table-padding table-striped" id="table-instances-<?php echo $tenants[$i]->id; ?>">
				<thead>
					<tr>
						<th class="name">Name</th>
						<th class="hostname">Hostname</th>
						<th class="text-center provider">Provider</th>
						<th class="text-center status">Deployment status</th>
						<th class="text-center options">Options</th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ($x = 0; $x < count($instances); $x++) {
					?>
					<tr>
						<td><?php echo $instances[$x]['name']; ?></td>
						<td>
							<?php 
							if (!is_null($instances[$x]['hostname'])) {
								echo $instances[$x]['hostname'];
							} else {
								echo 'N/A';
							}
							?>
						</td>
						<td class="text-center">
							<?php
							if ($instances[$x]['type'] == 'aws') {
								echo '<i class="fab fa-aws fa-2x" title="Amazon Web ervices"></i>';
							} elseif ($instances[$x]['type'] == 'azure') {
								echo '<i class="fab fa-microsoft fa-2x" title="Microsoft Azure"></i>';
							} elseif ($instances[$x]['type'] == 'gcp') {
								echo '<i class="fab fa-google fa-2x" title="Google Cloud Platform"></i>';
							} else {
								echo '<i class="fa fa-cloud fa-2x" title="Other"></i>';
							}
							?>
						</td>
						<td class="text-center">
							<?php
							if ($instances[$x]['status'] == 0) {
								echo '<span class="label label-info">Scheduled for deployment</span>';
							} elseif ($instances[$x]['status'] == 1) {
								echo '<span class="label label-success">Deployed</span>';
							} elseif ($instances[$x]['status'] == 2) {
								echo '<span class="label label-warning">Deployment in progress</span>';
							} elseif ($instances[$x]['status'] == -1) {
								echo '<span class="label label-success">Unmanaged</span>';
							} elseif ($instances[$x]['status'] == -100) {
								echo '<span class="label label-danger">Marked for removal</span>';
							} else {
								echo 'N/A';
							}
							?>
						</td>
						<td class="text-center">
							<?php
							if ($instances[$x]['status'] == 2 || $instances[$x]['status'] == -100) {
								echo 'N/A';
							} else {
								if (!is_null($instances[$x]['hostname'])) {
								?>
								<button class="btn btn-default btn-connect" data-hostname="<?php echo $instances[$x]['hostname']; ?>" data-id="<?php echo $instances[$x]['id']; ?>" title="Connect">Connect</button>
								<?php
								}
								?>
								<button class="btn btn-default btn-edit" data-id="<?php echo $instances[$x]['id']; ?>" title="Edit">Edit</button>
								<button class="btn btn-default btn-delete" data-id="<?php echo $instances[$x]['id']; ?>" title="Delete">Delete</button>
							<?php
							}
							?>
						</td>
					</tr>
					<?php
					}
					?>
				</tbody>
			</table>
			<br />
			<?php
			}
		} else {
			echo 'No instances found for this tenant.<br /><br />';
		}
	}

	if (count($orphaned) != 0) {
		echo '<br /><hr />';
		echo '<h3 class="text-danger">Orphaned instances found:</h3>';
		?>
		<hr />
		<table class="table table-hover table-bordered table-padding table-striped" id="table-instances-orphaned">
			<thead>
				<tr>
					<th class="name">Name</th>
					<th class="hostname">Hostname</th>
					<th class="text-center provider">Provider</th>
					<th class="text-center status">Deployment status</th>
					<th class="text-center options">Options</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($x = 0; $x < count($orphaned); $x++) {
				?>
				<tr>
					<td><?php echo $orphaned[$x]['name']; ?></td>
					<td>
						<?php 
						if (!is_null($orphaned[$x]['hostname'])) {
							echo $orphaned[$x]['hostname'];
						} else {
							echo 'N/A';
						}
						?>
					</td>
					<td class="text-center">
						<?php
						if ($orphaned[$x]['type'] == 'aws') {
							echo '<i class="fab fa-aws fa-2x" title="Amazon Web ervices"></i>';
						} elseif ($orphaned[$x]['type'] == 'azure') {
							echo '<i class="fab fa-microsoft fa-2x" title="Microsoft Azure"></i>';
						} elseif ($orphaned[$x]['type'] == 'gcp') {
							echo '<i class="fab fa-google fa-2x" title="Google Cloud Platform"></i>';
						} else {
							echo '<i class="fa fa-cloud fa-2x" title="Other"></i>';
						}
						?>
					</td>
					<td class="text-center">
						<?php
						if ($orphaned[$x]['status'] == 0) {
							echo '<span class="label label-info">Scheduled</span>';
						} elseif ($orphaned[$x]['status'] == 1) {
							echo '<span class="label label-success">Deployed</span>';
						} elseif ($orphaned[$x]['status'] == 2) {
							echo '<span class="label label-warning">Deployment in progress</span>';
						} elseif ($orphaned[$x]['status'] == -1) {
							echo '<span class="label label-success">Unmanaged</span>';
						} elseif ($orphaned[$x]['status'] == -100) {
							echo '<span class="label label-danger">Marked for removal</span>';
						} else {
							echo 'N/A';
						}
						?>
					</td>
					<td class="text-center">
						<?php
						if ($orphaned[$x]['status'] == 2 || $orphaned[$x]['status'] == -100) {
							echo 'N/A';
						} else {
							if (!is_null($orphaned[$x]['hostname'])) {
							?>
							<button class="btn btn-default btn-connect" data-hostname="<?php echo $orphaned[$x]['hostname']; ?>" data-id="<?php echo $orphaned[$x]['id']; ?>" title="Connect">Connect</button>
							<?php
							}
							?>
							<button class="btn btn-default btn-edit" data-id="<?php echo $orphaned[$x]['id']; ?>" title="Edit">Edit</button>
							<button class="btn btn-default btn-delete" data-id="<?php echo $orphaned[$x]['id']; ?>" title="Delete">Delete</button>
						<?php
						}
						?>
					</td>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
	<?php
	}
} else {
	$tenantid = $_SESSION['tenantid'];
	$total = getTenantInstanceCounter($tenantid);
		
	if ($total != '0') {
		try {
			$instances = getTenantAllInstances($tenantid);
		} catch (Exception $e) {
		}
		
		if (isset($instances) && !is_null($instances)) {
		?>
		<table class="table table-hover table-bordered table-padding table-striped" id="table-instances-<?php echo $tenantid; ?>">
			<thead>
				<tr>
					<th class="name">Name</th>
					<th class="hostname">Hostname</th>
					<th class="text-center provider">Provider</th>
					<th class="text-center status">Deployment status</th>
					<th class="text-center options">Options</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ($x = 0; $x < count($instances); $x++) {
				?>
				<tr>
					<td><?php echo $instances[$x]['name']; ?></td>
					<td>
						<?php 
						if (!is_null($instances[$x]['hostname'])) {
							echo $instances[$x]['hostname'];
						} else {
							echo 'N/A';
						}
						?>
					</td>
					<td class="text-center">
						<?php
						if ($instances[$x]['type'] == 'aws') {
							echo '<i class="fab fa-aws fa-2x" title="Amazon Web ervices"></i>';
						} elseif ($instances[$x]['type'] == 'azure') {
							echo '<i class="fab fa-microsoft fa-2x" title="Microsoft Azure"></i>';
						} elseif ($instances[$x]['type'] == 'gcp') {
							echo '<i class="fab fa-google fa-2x" title="Google Cloud Platform"></i>';
						} else {
							echo '<i class="fa fa-cloud fa-2x" title="Other"></i>';
						}
						?>
					</td>
					<td class="text-center">
						<?php
						if ($instances[$x]['status'] == 0) {
							echo '<span class="label label-info">Scheduled for deployment</span>';
						} elseif ($instances[$x]['status'] == 1) {
							echo '<span class="label label-success">Deployed</span>';
						} elseif ($instances[$x]['status'] == 2) {
							echo '<span class="label label-warning">Deployment in progress</span>';
						} elseif ($instances[$x]['status'] == -1) {
							echo '<span class="label label-success">Unmanaged</span>';
						} elseif ($instances[$x]['status'] == -100) {
							echo '<span class="label label-danger">Marked for removal</span>';
						} else {
							echo 'N/A';
						}
						?>
					</td>
					<td class="text-center">
						<?php
						if ($instances[$x]['status'] == 2 || $instances[$x]['status'] == -100) {
							echo 'N/A';
						} else {
							if (!is_null($instances[$x]['hostname'])) {
							?>
							<button class="btn btn-default btn-connect" data-hostname="<?php echo $instances[$x]['hostname']; ?>" data-id="<?php echo $instances[$x]['id']; ?>" title="Connect">Connect</button>
							<?php
							}
							?>
							<button class="btn btn-default btn-edit" data-id="<?php echo $instances[$x]['id']; ?>" title="Edit">Edit</button>
							<button class="btn btn-default btn-delete" data-id="<?php echo $instances[$x]['id']; ?>" title="Delete">Delete</button>
						<?php
						}
						?>
					</td>
				</tr>
				<?php
				}
				?>
			</tbody>
		</table>
		<br />
		<?php
		}
	}
}
?>
</div>

<div class="modal" id="sessionModalCenter" role="dialog">
    <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header"><h1 class="modal-title">Update settings</h1></div>
      <div class="modal-body">
	    <div class="row">
			<label class="col-md-2 control-label" for="hostname">Hostname:</label>
			<div class="col-md-6">
			  <input type="text" id="hostname" name="hostname" class="form-control input-md">
			</div>
		</div>
		<br />
		<div class="row">
			<label class="col-md-2 control-label" for="port">Port:</label>
			<div class="col-md-6">
			  <input type="text" id="port" name="port" class="form-control input-md">
			</div>
		</div>
		<br />
		<div class="row">
			<label class="col-md-2 control-label" for="username">Username:</label>
			<div class="col-md-6">
			  <input type="text" id="username" name="username" class="form-control input-md">
			</div>
		</div>
		<br />
		<div class="row">
			<label class="col-md-2 control-label" for="password">Password:</label>  
			<div class="col-md-6">
			<input type="password" id="password" name="password" class="form-control input-md">
			</div>
		</div>
      </div>
	  <div class="modal-footer">
		<input type="hidden" id="instanceid" name="instanceid">
		<button type="button" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</button>
		<button type="button" class="btn btn-success btn-save">Save</button>
	  </div>
    </div>
  </div>
</div>

<script>
$('.btn-connect').click(function(e) {
	var id = $(this).data('id');
	var hostname = $(this).data('hostname');
	
	Swal.fire({
		type: 'info',
		title: 'Connecting',
		text: 'Connecting to ' + hostname + '...'
	})
	
	$.get('core/veeam.php', {'action' : 'connect', 'id' : id}).done(function(data) {
		if (data == 'true') {
			Swal.fire({
				type: 'success',
				title: 'Success!',
				text: 'Connection was successful.'
			}).then(function(e) {
				window.location.href = '/index.php';
			});
		} else {
			Swal.fire({
				type: 'error',
				title: 'Error!',
				text: data
			});
		}
	});
});
$('.btn-edit').click(function(e) {
	var id = $(this).data('id');
	
	$.get('core/veeam.php', {'action' : 'getinstance', 'id' : id}).done(function(data) {
		var instance = JSON.parse(data);
		
		$('#instanceid').val(instance.id);
		$('#hostname').val(instance.hostname);
		$('#port').val(instance.port);
		$('#username').val(instance.username);
	});
	
	$('#sessionModalCenter').modal('show');
});
$('.btn-delete').click(function(e) {
	var id = $(this).data('id');
	$(this).parents('tr').addClass('hide');
	
	$.get('core/veeam.php', {'action' : 'deleteinstance', 'id' : id}).done(function(data) {
		Swal.fire({
			type: 'success',
			title: 'Success!',
			text: 'Instance was marked for removal.'
		});
	});
});
$('.btn-save').click(function(e) {
	var id = $('#instanceid').val();
	var hostname = $('#hostname').val();
	var port = $('#port').val();
	var username = $('#username').val();
	var password = $('#password').val();
	var parameter = { id:id, hostname:hostname, port:port, username:username, password:password };
	var json = JSON.stringify(parameter);
	
	$.get('core/veeam.php', {'action' : 'updateinstance', 'json' : json}).done(function(data) {
		$('#sessionModalCenter').modal('hide');
		
		if (data == 'true') {
			Swal.fire({
				type: 'success',
				title: 'Instance updated',
				text: 'Instance has been updated.'
			}).then(function(e) {
				$('#main').load('includes/instances.php');
			});
		}
	});
});
</script>