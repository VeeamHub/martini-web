<?php
session_start();

require_once(__DIR__ . '/../core/configuration.php');
require_once(__DIR__ . '/../core/tenant.php');

$awssettings = getAWSGeneralSettings();
$tenants = getTenants();
?>
<div class="main-container">
    <h1>Create a new instance</h1>
	<hr>
	<div class="form-group row tenantname">
	  <label class="col-md-2 control-label" for="tenantname">Select tenant</label>  
	  <div class="col-md-4">
	  <select class="form-control input-md" id="tenantname" name="tenantname">
		<?php
		for ($i = 0; $i < count($tenants); $i++) {
			echo '<option data-id="' . $tenants[$i]->id . '">' . $tenants[$i]->name . '</option>';
		}
		?>
	  </select>
	  <span class="help-block tenantnamehelp">Tenant to deploy instance for.</span>
	  </div>
	</div>
	<div class="form-group row instancetype">
	  <label class="col-md-2 control-label" for="instancetype">Instance type</label>  
	  <div class="col-md-4">
	  <select class="form-control input-md" id="instancetype" name="instancetype">
		<option value="new" selected>New instance</option>
		<option value="existing">Existing instance</option>
	  </select>
	  <span class="help-block">New or existing instance.</span>
	  </div>
	</div>
	<div class="form-group row provider">
	  <label class="col-md-2 control-label" for="provider">Location</label>  
	  <div class="col-md-4">
	  <select class="form-control input-md" id="provider" name="provider">
		<option selected>AWS</option>
		<option>Azure</option>
		<option>VMware</option>
	  </select>
	  <span class="help-block providerhelp">Where to deploy the Veeam Backup for Microsoft Office 365 server for the tenant.</span>
	  </div>
	</div>		
	<!-- VBO code -->
	<div class="form-group row vbo hide">
	  <label class="col-md-2 control-label" for="vboname">Name</label>  
	  <div class="col-md-4">
	  <input type="text" id="vboname" name="vboname" class="form-control input-md">
	  <span class="help-block">Instance name used for the Veeam Backup for Office 365 server.</span> 
	  </div>
	</div>
	<div class="form-group row vbo hide">
	  <label class="col-md-2 control-label" for="vbohostname">Hostname</label>  
	  <div class="col-md-4">
	  <input type="text" id="vbohostname" name="vbohostname" class="form-control input-md">
	  <span class="help-block">Hostname or IP of the Veeam Backup for Office 365 server.</span>  
	  </div>
	</div>
	<div class="form-group row vbo hide">
	  <label class="col-md-2 control-label" for="vboport">Port</label>  
	  <div class="col-md-4">
	  <input type="text" id="vboport" name="vboport" placeholder="4443 (default)" class="form-control input-md" value="4443">
	  <span class="help-block">Port of the Veeam Backup for Office 365 RESTful API service.</span>
	  </div>
	</div>
	<div class="form-group row vbo hide">
	  <label class="col-md-2 control-label" for="vbousername">Username</label>  
	  <div class="col-md-4">
	  <input type="text" id="vbousername" name="vbousername" class="form-control input-md">
	  <span class="help-block">Username used for the Veeam Backup for Office 365 server.</span>
	  </div>
	</div>
	<div class="form-group row vbo hide">
	  <label class="col-md-2 control-label" for="vbopassword">Password</label>  
	  <div class="col-md-4">
	  <input type="password" id="vbopassword" name="vbopassword" class="form-control input-md">
	  <span class="help-block">Password used for the Veeam Backup for Office 365 server.</span>
	  </div>
	</div>
	<!-- AWS code -->
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="awsregion">AWS Region</label>  
	  <div class="col-md-4">
	  <select id="awsregion" name="awsregion" class="form-control input-md">
		<option value="us-east-2">US East (Ohio)</option>
		<option value="us-east-1">US East (N. Virginia)</option>
		<option value="us-west-1">US West (N. California) 	</option>
		<option value="us-west-2">US West (Oregon)</option>
		<option value="ap-south-1">Asia Pacific (Mumbai)</option>
		<option value="ap-northeast-3">Asia Pacific (Osaka-Local)</option>
		<option value="ap-northeast-2">Asia Pacific (Seoul)</option>
		<option value="ap-southeast-1">Asia Pacific (Singapore)</option>
		<option value="ap-southeast-2">Asia Pacific (Sydney)</option>
		<option value="ap-northeast-1">Asia Pacific (Tokyo)</option>
		<option value="ca-central-1">Canada (Central)</option>
		<option value="cn-north-1 ">China (Beijing)</option>
		<option value="cn-northwest-1">China (Ningxia)</option>
		<option value="eu-central-1">EU (Frankfurt)</option>
		<option value="eu-west-1">EU (Ireland)</option>
		<option value="eu-west-2">EU (London)</option>
		<option value="eu-west-3">EU (Paris)</option>
		<option value="eu-north-1">EU (Stockholm)</option>
		<option value="sa-east-1">South America (S&atilde;o Paulo)</option>
		<option value="us-gov-east-1">AWS GovCloud (US-East)</option>
		<option value="us-gov-west-1">AWS GovCloud (US)</option>
	  </select>
	  <span class="help-block">AWS region to deploy a new Veeam Backup for Microsoft Office 365 instance.</span>
	  </div>
	</div>
	<!-- Azure code -->
	<div class="form-group row azure hide">
	  <label class="col-md-2 control-label" for="azuresubscriptionid">Subscription ID</label>  
	  <div class="col-md-4">
	  <input type="text" id="azuresubscriptionid" name="azuresubscriptionid" class="form-control input-md">
	  <span class="help-block">Azure Subscription ID.</span>
	  </div>
	</div>
	<div class="form-group row azure hide">
	  <label class="col-md-2 control-label" for="azureclientid">Client ID</label>  
	  <div class="col-md-4">
	  <input type="text" id="azureclientid" name="azureclientid" class="form-control input-md">
	  <span class="help-block">Azure Client ID.</span>
	  </div>
	</div>
	<div class="form-group row azure hide">
	  <label class="col-md-2 control-label" for="azureclientsecret">Client Secret</label>  
	  <div class="col-md-4">
	  <input type="text" id="azureclientsecret" name="azureclientsecret" class="form-control input-md">
	  <span class="help-block">Azure Client Secret.</span>
	  </div>
	</div>
	<div class="form-group row azure hide">
	  <label class="col-md-2 control-label" for="azuretenantid">Tenant ID</label>  
	  <div class="col-md-4">
	  <input type="text" id="azuretenantid" name="azuretenantid" class="form-control input-md">
	  <span class="help-block">Azure Tenant ID.</span>
	  </div>
	</div>
	<!-- VMware code -->
	<div class="form-group row buttons">
	  <label class="col-md-2 control-label" for="submit"></label>
	  <div class="col-md-4 text-center">
		<button class="btn btn-primary save" id="save" name="save">Save</button>
	  </div>
	</div>
</div>
<script>
/* https://gist.github.com/jed/982883 */
function uuidv4() {
  return ([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16));
}

<?php
if (!empty($awssettings->region)) {
?>
$("#awsregion option[value='<?php echo $awssettings->region; ?>']").prop('selected', 'selected');
<?php
}
?>
$('#instancetype').click(function(e) {
	if (this.value.toLowerCase() == 'new') {
		$('.provider').removeClass('hide');
		$('.aws').removeClass('hide');
		$('.azure').addClass('hide');
		$('.vbo').addClass('hide');
		$('.tenantnamehelp').text('Tenant to deploy instance for.');
	} else {
		$('.provider').addClass('hide');
		$('.vbo').removeClass('hide');
		$('.aws').addClass('hide');
		$('.azure').addClass('hide');
		$('.tenantnamehelp').text('Tenant to configure instance for.');
	}
});
$('#provider').click(function(e) {
	if ($('#instancetype').val().toLowerCase() == 'new') {
		if (this.value.toLowerCase() == 'aws') {
			$('.aws').removeClass('hide');
			$('.azure').addClass('hide');
		} else if (this.value.toLowerCase() == 'azure') {
			$('.aws').addClass('hide');
			$('.azure').removeClass('hide');
		} else {
			Swal.fire('Not implemented yet.');
			return;
		}
	}
});
$('#save').click(function(e) {
	var tenantname = $("#tenantname").val();
	var id = $("#tenantname").find(':selected').attr('data-id');
	var provider = $('#provider').val().toLowerCase();
	var uuid = uuidv4();
	
	if ($('#instancetype').val().toLowerCase() == 'new') {
		if (typeof tenantname === undefined || !tenantname) {
			Swal.fire('Missing parameter: tenant label.')
			return;
		}
		
		if (provider == 'aws') {
			var region = $("#awsregion").val();
			var name = tenantname.split(' ').join('_') + '-' + uuid;
			var awsparameter = { name:name, region:region };
			var providerjson = JSON.stringify(awsparameter);
			var type = 'aws';
		} else {
			Swal.fire('Not implemented yet.');
			return;
		}
			
		$.get('core/veeam.php', {'action' : 'addinstance', 'provider' : provider, 'id' : id, 'json' : providerjson, 'type' : type, 'status' : '0'}).done(function(data) {
			Swal.fire({
				type: 'success',
				title: 'Instance added',
				text: 'Instance configuration has been created. The instance will be deployed in the background. This may take up to 30 minutes depending on the provider and settings. You can close this window while the deployment is ongoing.'
			}).then(function(e) {
				$('#main').load('includes/instances.php');
			});
		});
	} else {
		var vboname = $("#vboname").val();
		var vbohostname = $("#vbohostname").val();
		var vboport = $("#vboport").val();
		var vbousername = $("#vbousername").val();
		var vbopassword = $("#vbopassword").val();
		var type = 'other';
		
		var vboparameter = { name:vboname, hostname:vbohostname, port:vboport, username:vbousername, password:vbopassword };
		var vbojson = JSON.stringify(vboparameter);
			
		$.get('core/veeam.php', {'action' : 'addinstance', 'provider' : 'vbo', 'id' : id, 'json' : vbojson, 'type' : type, 'status' : '-1'}).done(function(data) {
			Swal.fire({
				type: 'success',
				title: 'Instance added',
				text: vbohostname + ' has been added.'
			}).then(function(e) {
				$('#main').load('includes/instances.php');
			});
		});
	}
});
</script>