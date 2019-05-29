<?php
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

require_once(__DIR__ . '/../core/tenant.php'); 
require_once(__DIR__ . '/../core/veeam.vbo.class.php');
?>
<div class="main-container">
	<h1>Dashboard</h1>
	<hr>
	<?php
	if (isset($_SESSION['connected'])) {
		$host = $_SESSION['hostname'];
		$port = $_SESSION['port'];
		$veeam = new VBO($host, $port);
		$veeam->setToken($_SESSION['token']);
		$veeam->refreshToken($_SESSION['refreshtoken']);
	
		/* Create dashboard stats */
		$org = $veeam->getOrganizations();
		$jobs = $veeam->getJobs();
		$proxies = $veeam->getProxies();
		$repos = $veeam->getBackupRepositories();
		$licensetotal = 0;
		$newlicensetotal = 0;

		for ($i = 0; $i < count($org); $i++) {
			$license = $veeam->getLicenseInfo($org[$i]['id']);
			$licensetotal += $license['licensedUsers'];
			$newlicensetotal += $license['newUsers'];
		}
	?>
	<div class="row">
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-primary">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-building fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($org); ?> organizations</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="organizations" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-green">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-calendar fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($jobs); ?> backup jobs</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="jobs" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-yellow">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-server fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($proxies); ?> proxies</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="proxies" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-lightgreen">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-database fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo count($repos); ?> repositories</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="repositories" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
		<div class="col-lg-3 col-md-6">
		  <div class="panel panel-gray">
			<div class="panel-heading">
			  <div class="row">
				<div class="col-xs-4">
				  <i class="fa fa-file-alt fa-4x"></i>
				</div>
				<div class="col-xs-8 text-left">
				  <div class="medium">&nbsp;<?php echo $licensetotal; ?> licenses used<br />&nbsp;<?php echo $newlicensetotal; ?> extra licenses</div>
				</div>
			  </div>
			</div>
			<a href="#" class="dash" data-call="licensing" onClick="return false;">
			<div class="panel-footer">
			  <span class="pull-left">Overview</span>
			  <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
			  <div class="clearfix"></div>
			</div>
			</a>
		  </div>
		</div>
	</div>
	<?php
	} else {
		if ($_SESSION['portaltoken'] != 'tenant') { /* Admin view */
			$tenants = getTenants();
			$instances = getTenantInstances();
		?>
		<div class="row">
			<div class="col-lg-2 col-sm-6">
			  <div class="circle-tile">
				<a href="#"><div class="circle-tile-heading blue"><i class="fa fa-users fa-fw fa-3x"></i></div></a>
				<div class="circle-tile-content blue">
				  <div class="circle-tile-description text-faded"> Tenants</div>
				  <div class="circle-tile-number text-faded "><?php echo count($tenants); ?></div>
				  <a class="circle-tile-footer dash" data-call="tenants" onClick="return false;" href="#">Overview <i class="fa fa-chevron-circle-right"></i></a>
				</div>
			  </div>
			</div>
			<div class="col-lg-2 col-sm-6">
			  <div class="circle-tile">
				<a href="#"><div class="circle-tile-heading green"><i class="fa fa-user-plus fa-fw fa-3x"></i></div></a>
				<div class="circle-tile-content green">
				  <div class="circle-tile-description text-faded"> New tenant</div>
				  <div class="circle-tile-number text-faded ">&nbsp;</div>
				  <a class="circle-tile-footer dash" data-call="addtenant" onClick="return false;" href="#">Go <i class="fa fa-chevron-circle-right"></i></a>
				</div>
			  </div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-2 col-sm-6">
			  <div class="circle-tile">
				<a href="#"><div class="circle-tile-heading orange"><i class="fa fa-server fa-fw fa-3x"></i></div></a>
				<div class="circle-tile-content orange">
				  <div class="circle-tile-description text-faded"> Instances</div>
				  <div class="circle-tile-number text-faded "><?php echo $instances; ?></div>
				  <a class="circle-tile-footer dash" data-call="instances" onClick="return false;" href="#">Overview <i class="fa fa-chevron-circle-right"></i></a>
				</div>
			  </div>
			</div>
			<div class="col-lg-2 col-sm-6">
			  <div class="circle-tile">
				<a href="#"><div class="circle-tile-heading purple"><i class="fa fa-plus-circle fa-fw fa-3x"></i></div></a>
				<div class="circle-tile-content purple">
				  <div class="circle-tile-description text-faded"> New instance</div>
				  <div class="circle-tile-number text-faded ">&nbsp;</div>
				  <a class="circle-tile-footer dash" data-call="addinstance" onClick="return false;" href="#">Go <i class="fa fa-chevron-circle-right"></i></a>
				</div>
			  </div>
		</div>
		<?php
		} else {
		?>
			<div class="col-lg-2 col-sm-6">
			  <div class="circle-tile">
				<a href="#"><div class="circle-tile-heading orange"><i class="fa fa-server fa-fw fa-3x"></i></div></a>
				<div class="circle-tile-content orange">
				  <div class="circle-tile-description text-faded"> Instances</div>
				  <div class="circle-tile-number text-faded "><?php echo getTenantInstanceCounter($_SESSION['tenantid']); ?></div>
				  <a class="circle-tile-footer dash" data-call="instances" onClick="return false;" href="#">Overview <i class="fa fa-chevron-circle-right"></i></a>
				</div>
			  </div>
			</div>
		<?php
		
		}
	}
?>
</div>
<script>
$('a.dash').click(function(e) {
	$('#main').load('includes/' + $(this).data('call') + '.php');
});
</script>