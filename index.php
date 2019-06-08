<?php
session_start();

require_once(__DIR__ . '/core/auth.php');

if (isset($_POST['username']) && $_POST['username'] != "") {
	$username = $_POST['username'];
}

if (isset($_POST['password']) && $_POST['password'] != "") {
	$password = $_POST['password'];
}

if (isset($username) && isset($password)) {
	$check = filter_var($username, FILTER_VALIDATE_EMAIL);
	
	if ($check === false) {
		$login = authenticateWithToken($username, $password);
		$auth = $login->auth;
		
		if ($auth == 1) {
			$_SESSION['name'] = $login->name;
			$_SESSION['lifetime'] = $login->token->lifetime;
			$_SESSION['portaltoken'] = $login->token->token;
			$_SESSION['portalrenewtoken'] = $login->token->renew;
		}
	} else {
		$login = authenticate($username, $password);
		$auth = $login->auth;
		
		if ($auth == 1) {
			$_SESSION['name'] = $login->name;
			$_SESSION['tenantid'] = $login->tenantid;
			$_SESSION['portaltoken'] = 'tenant';
		}
	}
}

if (isset($_POST['logout']) && $_POST['logout'] != "") {
    unset($_SESSION);
	session_destroy();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Project Martini</title>
    <base href="/" />
    <link rel="shortcut icon" href="images/favicon.ico" />
    <link rel="stylesheet" type="text/css" href="vendor/twbs/bootstrap/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="css/fontawesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/style.css" />
	<link rel="stylesheet" type="text/css" href="css/sweetalert2.min.css" />	
    <script src="vendor/components/jquery/jquery.min.js"></script>
    <script src="vendor/twbs/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="js/filesize.min.js"></script>
    <script src="js/fontawesome.min.js"></script>
	<script src="js/moment.min.js"></script>
	<script src="js/sweetalert2.all.min.js"></script>	
</head>
<body>
<?php
if (empty($_SESSION['portaltoken'])) {
?>
<section class="login-block">
	<div class="container login-container">
		<div class="row">
			<div class="col-md-4 login-sec">
				<h2 class="text-center">Login</h2>
				<form class="login-form" method="post">
					<div class="form-group">
						<label for="username" class="text-uppercase">Username:</label>
						<input type="text" class="input-loginform form-control" name="username" autofocus /><span class="fa fa-user fa-2x icon"></span>
					</div>
					<div class="form-group">
						<label for="password" class="text-uppercase">Password:</label>
						<input type="password" class="input-loginform form-control" name="password" /><span class="fa fa-lock fa-2x icon"></span>
					</div>
					<div class="form-check text-center">
						<button type="submit" class="btn btn-login">Login</button>
					</div>
					<div class="text-center">
						<?php
						if (isset($auth) && $auth == 0) {
							echo '<br /><p class="text-warning">Incorrect username or password.</p>';
						}
						?>
					</div>
				</form>
			</div>
			<div class="col-md-8 banner-sec"></div>				
		</div>
	</div>
</section>
<?php
} else {
?>
<nav class="navbar navbar-default navbar-static-top">
	<ul class="nav navbar-header">
	  <li><a class="navbar-brand navbar-logo" href="/"><img src="images/logo.png" class="logo" /></a></li>
	</ul>
	<?php
	if (isset($_SESSION['connected'])) {
	?>
	<ul class="nav navbar-nav" id="nav">
	  <li><a href="exchange">Exchange</a></li>
	  <li><a href="onedrive">OneDrive</a></li>
	  <li><a href="sharepoint">SharePoint</a></li>
	</ul>
	<?php
	}
	?>
	<ul class="nav navbar-nav navbar-right">
	  <?php
	  if (!isset($_SESSION['connected']) && $_SESSION['portaltoken'] != 'tenant') {
      ?>
	  <li id="configuration"><a href="#"><span class="fa fa-cog"></span> Configuration</a></li>
	  <?php
	  }
	  ?>
	  <li><a href="#" onClick="return false;"><span class="fa fa-user"></span> Welcome <?php if (isset($_SESSION['name'])) echo $_SESSION['name']; ?> !</a></li>
	  <li id="logout"><a href="#" onClick="return false;"><span class="fa fa-sign-out-alt"></span> Logout</a></li>
	</ul>
</nav>
<div class="container-fluid">
	<aside id="sidebar">
		<div class="logo-container"><i class="logo fa fa-magic"></i></div>
			<div class="separator"></div>
			<menu class="menu-segment">
				<?php
				if (isset($_SESSION['connected'])) { /* Connected to an instance */
					echo '<div class="text-center">';
					echo '<button class="btn btn-default btn-danger btn-disconnect" title="Disconnect">Disconnect</button>';
					echo '<div class="separator"></div>';
					echo '</div>';
				}
				?>
				<ul class="menu">
					<li id="dashboard"><i class="fa fa-tachometer-alt"></i> Dashboard</li>
					<?php
					if (isset($_SESSION['connected'])) { /* Connected to an instance */
					?>
					<li id="organizations"><i class="fa fa-building"></i> Organizations</li>
					<li id="jobs"><i class="fa fa-calendar"></i> Jobs</li>
					<li id="proxies"><i class="fa fa-server"></i> Proxies</li>
					<li id="repositories"><i class="fa fa-database"></i> Repositories</li>
					<li id="licensing"><i class="fa fa-file-alt"></i> License</li>
					<li id="activity"><i class="fa fa-tasks"></i> Activity</li>
					<?php
					} else { /* Not connected to an instance */
						if (isset($_SESSION['portaltoken']) && $_SESSION['portaltoken'] == 'tenant') { /* Tenant view */
							echo '<li id="instances"><i class="fa fa-server"></i> List instances</a></li>';
						} else {
					?>
					<li id="addtenant"><i class="fa fa-user-plus"></i> Create tenant</a></li>
					<li id="tenants"><i class="fa fa-users"></i> List tenants</a></li>
					<li id="addinstance"><i class="fa fa-plus-circle"></i> Create instance</a></li>
					<li id="instances"><i class="fa fa-server"></i> List instances</a></li>		
					<?php
						}
					}
					?>
				</ul>
			</menu>
			<div class="separator"></div>
		</div>
	</aside>
	<main id="main">
		<?php
		include('includes/dashboard.php');
		?>
	</main>
</div>
<script>
$('ul.menu li').click(function(e) {
	$('#main').load('includes/' + this.id + '.php');
});

$('ul.nav li').click(function(e) {
	if (this.id == 'logout') {
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
	} else {
		$('#main').load('includes/' + this.id + '.php');
	}
});

$('.btn-disconnect').click(function(e) {
	var id = $(this).data('id');
	
	$.get('core/veeam.php', {'action' : 'disconnect', 'id' : id}).done(function(data) {
		if (data == 'true') {
			Swal.fire({
				type: 'success',
				title: 'Success!',
				text: 'Remote connection has been terminated.'
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
</script>
<?php
}
?>	
</body>
</html>