<?php
session_start();

require_once(__DIR__ . '/../core/veeam.vbo.class.php');

if (isset($_SESSION['connected'])) {
	$host = $_SESSION['hostname'];
	$port = $_SESSION['port'];
    $veeam = new VBO($host, $port);
	$veeam->setToken($_SESSION['token']);
	$veeam->refreshToken($_SESSION['refreshtoken']);

	$proxies = $veeam->getProxies();
?>
<div class="main-container">
    <h1>Proxies</h1>
	<hr>
    <?php
    if (count($proxies) != '0') {
    ?>
    <table class="table table-hover table-bordered table-padding table-striped" id="table-proxies">
        <thead>
            <tr>
                <th>Name</th>
                <th>Port</th>
                <th>Description</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody> 
            <?php
            for ($i = 0; $i < count($proxies); $i++) {
            ?>
                <tr>
                    <td><?php echo $proxies[$i]['hostName']; ?></td>
                    <td><?php echo $proxies[$i]['port']; ?></td>
                    <td><?php echo $proxies[$i]['description']; ?></td>
                    <td>
                    <?php
                    if (strtolower($proxies[$i]['status']) == 'online') { 
                        echo '<span class="label label-success">'.$proxies[$i]['status'].'</span>'; 
                    } else { 
                        echo '<span class="label label-danger">'.$proxies[$i]['status'].'</span>'; 
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
    } else {
        echo 'No proxies available.';
    }
    ?>
</div>
<?php
} else {
    unset($_SESSION);
    session_destroy();
	?>
	<script>
	Swal.fire({
		type: 'info',
		title: 'Session terminated',
		text: 'Your remote session has timed out and requires you to connect again.'
	}).then(function(e) {
		window.location.href = '/index.php';
	});
	</script>
	<?php
}
?>