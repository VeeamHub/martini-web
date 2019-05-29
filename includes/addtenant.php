<div class="main-container">
    <h1>Create a new tenant</h1>
	<hr>
	<div class="form-group row tenantname">
	  <label class="col-md-2 control-label" for="tenantname">Tenant name</label>  
	  <div class="col-md-4">
	  <input type="text" id="tenantname" name="tenantname" placeholder="Tenant-XYZ" class="form-control input-md">
	  <span class="help-block">Tenant name to be used within the overview.</span>
	  </div>
	</div>
	<div class="form-group row tenantemail">
	  <label class="col-md-2 control-label" for="tenantemail">Tenant e-mail</label>  
	  <div class="col-md-4">
	  <input type="text" id="tenantemail" name="tenantemail" class="form-control input-md">
	  <span class="help-block">Tenant e-mail.</span>
	  </div>
	</div>
	<div class="form-group row buttons">
	  <label class="col-md-2 control-label" for="submit"></label>
	  <div class="col-md-4 text-center">
		<button class="btn btn-primary save" id="save" name="save">Save</button>
	  </div>
	</div>
</div>
<script>
$('#save').click(function(e) {
	var tenantname = $("#tenantname").val();
	var tenantemail = $("#tenantemail").val();
	
	var parameter = { name:tenantname, email:tenantemail };
	var json = JSON.stringify(parameter);
		
	$.get('core/veeam.php', {'action' : 'addtenant', 'json' : json}).done(function(data) {
		var result = JSON.parse(data);
		
		Swal.fire({
			type: 'success',
			title: 'Tenant added',
			text: 'Tenant has been created. The password has been set to: ' + result.password
		}).then(function(e) {
			$('#main').load('includes/tenants.php');
		});
	});
});
</script>