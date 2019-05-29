<?php
require_once(__DIR__ . '/../core/configuration.php');

$awssettings = getAWSGeneralSettings();
?>
<div class="main-container">
	<h1>Global configuration</h1>
	<hr>
	<!-- AWS code -->
	<fieldset class="title aws">
		<legend>Configuration for AWS:</legend>
	</fieldset>
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="awsdefaultregion">Default AWS Region</label>  
	  <div class="col-md-4">
	  <select id="awsdefaultregion" name="awsdefaultregion" class="form-control input-md">
		<option disabled selected>-- Select a region --</option>
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
	  </div>
	  <span class="help-block">Default AWS region used for deploying a new instance.</span>
	</div>
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="awsaccesskey">Access key:</label>  
	  <div class="col-md-4">
	  <input type="text" id="awsaccesskey" name="awsaccesskey" class="form-control input-md" value="<?php if (!empty($awssettings->accesskey)) echo $awssettings->accesskey; ?>">
	  </div>
	  <span class="help-block">Your AWS access key.</span>
	</div>
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="awssecretkey">Secret key:</label>  
	  <div class="col-md-4">
	  <input type="password" id="awssecretkey" name="awssecretkey" class="form-control input-md">
	  </div>
	  <span class="help-block">Your AWS secret key is hidden. Changing the value will <strong>overwrite</strong> the existing one.</span>
	</div>
	<div class="form-group row buttons">
	  <label class="col-md-2 control-label" for="awsgensave"></label>
	  <div class="col-md-4 text-center">
		<button class="btn btn-primary save" id="awsgensave" name="awsgensave">Save</button>
	  </div>
	</div>
	<div class="alert alert-info aws">Select an AWS region to change settings per region.</div>
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="awsregion">AWS Region</label>  
	  <div class="col-md-4">
	  <select id="awsregion" name="awsregion" class="form-control input-md">
		<option disabled selected>-- Select a region --</option>
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
		<!-- <option value="cn-north-1 ">China (Beijing)</option>
		<option value="cn-northwest-1">China (Ningxia)</option> -->
		<option value="eu-central-1">EU (Frankfurt)</option>
		<option value="eu-west-1">EU (Ireland)</option>
		<option value="eu-west-2">EU (London)</option>
		<option value="eu-west-3">EU (Paris)</option>
		<option value="eu-north-1">EU (Stockholm)</option>
		<option value="sa-east-1">South America (S&atilde;o Paulo)</option>
		<!-- <option value="us-gov-east-1">AWS GovCloud (US-East)</option>
		<option value="us-gov-west-1">AWS GovCloud (US)</option> -->
	  </select>
	  </div>
	</div>
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="awsvpc">Default VPC:</label>  
	  <div class="col-md-4">
	  <select id="awsvpc" name="awsvpc" class="form-control input-md">
		<option disabled selected>-- Select a region to list the VPCs --</option>
	  </select>
	  </div>
	  <span class="help-block">Default VPC for the region used for deploying a new instance.</span>
	</div>
	<div class="form-group row aws">
	  <label class="col-md-2 control-label" for="privatekey">Private key:</label>  
	  <div class="col-md-4">
	  <textarea id="awsprivatekey" name="awsprivatekey" rows="7" cols="57"></textarea>
	  </div>
	  <span class="help-block">Your private key is hidden. Changing the value will <strong>overwrite</strong> the existing one.<br/><br/>The private key used for password retrieval via AWS console as well as automatic deployment.<br />You can generate new keys either via the <a href="https://eu-central-1.console.aws.amazon.com/ec2/v2/home#KeyPairs" target="_blank">AWS console</a>.</span>
	</div>
	<div class="form-group row buttons">
	  <label class="col-md-2 control-label" for="awsregionsave"></label>
	  <div class="col-md-4 text-center">
		<button class="btn btn-primary save" id="awsregionsave" name="awsregionsave">Save</button>
	  </div>
	</div>
	<!-- Azure code -->
	<fieldset class="title azure hide">
		<legend>Configuration for Azure:</legend>
	</fieldset>
	<div class="alert alert-info azure hide">Select a location to create or update your Azure settings.</div>
	<div class="form-group row azure hide">
	  <label class="col-md-2 control-label" for="azureclientid">Client ID</label>  
	  <div class="col-md-4">
	  <input type="text" id="azureclientid" name="azureclientid" class="form-control input-md">
	  <span class="help-block">Azure Client ID</span>
	  </div>
	</div>
</div>
<script>
<?php
if (!empty($awssettings->region)) {
?>
$("#awsdefaultregion option[value='<?php echo $awssettings->region; ?>']").prop('selected', 'selected');
<?php
}
?>
$('#awsregion').change(function(e) {
	var region = $('#awsregion option:selected').val();

	$.get('core/veeam.php', {'action' : 'getvpcs', 'region' : region}).done(function(data) {
		var vpcs = JSON.parse(data);
		
		if (vpcs.length != 0) {
			$('#awsvpc').empty();
			
			for (var i = 0; i < vpcs.length; i++) {
				$('#awsvpc').append('<option>' + vpcs[i] + '</option>');
			}
		} else {
			$('#awsvpc').empty();
			$('#awsvpc').append('<option disabled selected>-- Select a region to list the VPCs --</option>');
		}
	});
});
$('#generateawskey').click(function(e) {
	Swal.fire('Not yet implemented');
});
$('#awsgensave').click(function(e) {
	/* Update default AWS settings */
	var awsdefaultregion = $('#awsdefaultregion option:selected').val();
	var awsaccesskey = $('#awsaccesskey').val();
	var awssecretkey = $('#awssecretkey').val();
	var awsdefparameter = { region:awsdefaultregion, accesskey:awsaccesskey, secretkey:awssecretkey };
	var awsdefjson = JSON.stringify(awsdefparameter);
	
	$.get('core/veeam.php', {'action' : 'saveawsgeneralsettings', 'json' : awsdefjson}).done(function(data) {
		if (data == 'true') {
			Swal.fire({
				type: 'success',
				title: 'Success!',
				text: 'Settings saved.'
			});
		} else {
			Swal.fire({
				type: 'error',
				title: 'Error!',
				text: 'Settings could not be saved. Please try again.'
			});
		}
	});
});
$('#awsregionsave').click(function(e) {
	/* Update AWS region specific settings */	
	var awsregion = $('#awsregion option:selected').val();
	var awsvpc = $('#awsvpc').val();
	var awsprivatekey = $('#awsprivatekey').val();
	var awsregionparameter = { region:awsregion, vpc:awsvpc, privatekey:awsprivatekey };
	var awsregionjson = JSON.stringify(awsregionparameter);
	
	$.get('core/veeam.php', {'action' : 'saveawsregionsettings', 'json' : awsregionjson}).done(function(data) {
		if (data == 'true') {
			Swal.fire({
				type: 'success',
				title: 'Success!',
				text: 'Settings saved.'
			});
		} else {
			Swal.fire({
				type: 'error',
				title: 'Error!',
				text: 'Settings could not be saved. Please try again.'
			});
		}
	});
});
</script>