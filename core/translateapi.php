<?php
require_once('veeam.vbo.class.php');
require_once('tenant.php');

class InstanceAuthException extends Exception {
    public function __construct($instanceid = 0, $post = '', $code = 0, Exception $previous = null) {
        parent::__construct('Unauthenticated error for tenant id ' . $instanceid . ', password of the tenant might be expired or changed for tenant; ' . $post);
	}
}

function instanceAuth($instanceid) {
	$instance = getTenantInstance($instanceid, true);
	$veeam = new VBO($instance->hostname, $instance->port);
	$login = $veeam->login($instance->username, $instance->password);
	
	if ($login == '200') {
		return $veeam;
	} elseif ($login == '400') {
		throw new InstanceAuthException($instanceid);
	} else {
		throw new InstanceAuthException($instanceid, 'unexpected return code from server');
	}
}
?>