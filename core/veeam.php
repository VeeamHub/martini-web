<?php
/* Action handler page for jQuery Calls */
require_once(__DIR__ . '/../vendor/autoload.php');
require_once('database.php');
require_once('configuration.php');
require_once('tenant.php');
require_once('terraform.config.php');
require_once('veeam.vbo.class.php');

use Terraform\Helpers\Aws as AwsHelpers;

session_start();

if (isset($_GET['action'])) { $action = $_GET['action']; }
if (isset($_GET['id'])) { $id = $_GET['id']; }
if (isset($_GET['json'])) { $json = $_GET['json']; }
if (isset($_GET['offset'])) { $offset = $_GET['offset']; }
if (isset($_GET['provider'])) { $provider = $_GET['provider']; }
if (isset($_GET['region'])) { $region = $_GET['region']; }
if (isset($_GET['type'])) { $type = $_GET['type']; }
if (isset($_GET['status'])) { $status = $_GET['status']; }

/* Restore related */
if (isset($_GET['folderid'])) { $folderid = $_GET['folderid']; }
if (isset($_GET['itemid'])) { $itemid = $_GET['itemid']; }
if (isset($_GET['mailboxid'])) { $mailboxid = $_GET['mailboxid']; }
if (isset($_GET['rid'])) { $rid = $_GET['rid']; }
if (isset($_GET['siteid'])) { $siteid = $_GET['siteid']; }
if (isset($_GET['userid'])) { $userid = $_GET['userid']; }

if (isset($_SESSION['connected'])) {
	$host = $_SESSION['hostname'];
	$port = $_SESSION['port'];
	
    $veeam = new VBO($host, $port);
	$veeam->setToken($_SESSION['token']);
	$veeam->refreshToken($_SESSION['refreshtoken']);
}

/* Configuration Calls */
if ($action == 'getvpcs') {
	$settings = getAWSGeneralSettings();
	$accesskey = $settings->accesskey;
	$secretkey = $settings->secretkey;
	
	$aws = new AwsHelpers\Aws($region, $accesskey, $secretkey);
	
	echo json_encode($aws->listVpcs());
}
if ($action == 'saveawsgeneralsettings') {
	saveAWSGeneralSettings($json);
}
if ($action == 'saveawsregionsettings') {
	saveAWSRegionSettings($json);
}

/* Instance Calls */
if ($action == 'addinstance') { /* Create a new instance */
	$decoded = json_decode($json);

	if (strtolower($provider) == 'aws') {
		$name = $decoded->name;
		$region = $decoded->region;
		$config = genAWSConfig($name, $region);
		
		saveInstance($id, $name, $config, $type, $status, $region);
	} elseif (strtolower($provider) == 'vbo') {
		$name = $decoded->name;
		$hostname = $decoded->hostname;
		$port = $decoded->port;
		$username = $decoded->username;
		$password = $decoded->password;
		$region = $decoded->region;
		
		$instanceid = saveInstance($id, $name, '0', $type, $status, '0');
		updateInstance($instanceid, $hostname, $port, $username, $password);
	} else {
		echo 'Not implemented yet.';
		exit;
	}
}
if ($action == 'getinstance') { /* Get specific instance details */
	echo json_encode(getTenantInstance($id));
}
if ($action == 'deleteinstance') { /* Delete an instance */
	echo json_encode(deleteInstance($id));
}
if ($action == 'deleteinstances') { /* Delete all instances for a tenant */
	echo json_encode(deleteInstances($id));
}
if ($action == 'updateinstance') { /* Update an instance */
	$decoded = json_decode($json);
	$instance = getTenantInstance($decoded->id, true);

	if (empty($decoded->password)) {
		$password = $instance->password;
	} else {
		$password = $decoded->password;
	}
	
	echo json_encode(updateInstance($decoded->id, $decoded->hostname, $decoded->port, $decoded->username, $password));
}
if ($action == 'updateinstances') { /* Update all instance for a tenant */
	echo json_encode(updateInstances($id, $status));
}

if ($action == 'connect') {
	$tenant = getTenantInstance($id, true);
	
	$veeam = new VBO($tenant->hostname, $tenant->port);
	$login = $veeam->login($tenant->username, $tenant->password);
	
	if ($login == '200') {
		$_SESSION['refreshtoken'] = $veeam->getRefreshToken();
		$_SESSION['token'] = $veeam->getToken();
		$_SESSION['username'] = $tenant->username;
		$_SESSION['hostname'] = $tenant->hostname;
		$_SESSION['port'] = $tenant->port;
		$_SESSION['connected'] = true;
		
		echo json_encode(true);
	} elseif ($login == '400') { 
		echo 'Authorization error: Invalid credential';
	}
}
if ($action == 'disconnect') {
	unset($_SESSION['refreshtoken']);
	unset($_SESSION['token']);
	unset($_SESSION['username']);
	unset($_SESSION['hostname']);
	unset($_SESSION['port']);
	unset($_SESSION['connected']);
	
    header("Refresh:0");
	
	echo json_encode(true);
}

/* Tenant Calls */
if ($action == 'addtenant') { /* Create a new tenant */
	$decoded = json_decode($json);
	$tenant = New MartiniTenant(-1, $decoded->name, $decoded->email, -1);

	echo json_encode(saveTenant($tenant));
}
if ($action == 'gettenant') { /* Get specific tenant details */
	echo json_encode(getTenant($id));
}
if ($action == 'deletetenant') { /* Delete a tenant */
	echo json_encode(deleteTenant($id));
}
if ($action == 'updatetenant') { /* Update a tenant */
	$decoded = json_decode($json);
	$tenant = getTenant($decoded->id);
	$update = New MartiniTenant($decoded->id, $decoded->name, $decoded->email, $tenant->registered);
	
	if (isset($decoded->password) && $decoded->password == 'true') {
		echo json_encode(saveTenant($update, true));
	} else {
		echo json_encode(saveTenant($update, false));
	}
}

/* Veeam Backup for Microsoft Office 365 RESTful API calls */
/* Jobs Calls */
if ($action == 'changejobstate') {
    $veeam->changeJobState($id, $json);
}
if ($action == 'getjobs') {
    $jobs = $veeam->getJobs($id);
    echo json_encode($jobs);
}
if ($action == 'getjobsession') {
    $getjobsession = $veeam->getJobSession($id);
    echo json_encode($getjobsession);
}
if ($action == 'startjob') {
    $veeam->startJob($id);
}

/* Organizations Calls */
if ($action == 'getorganizations') {
    $org = $veeam->getOrganizations();
    echo json_encode($org);
}

/* Repositories Calls */
if ($action == 'getrepo') {
    $repo = $veeam->getBackupRepository($id);
    echo json_encode($repo);
}

/* Sessions Calls */
if ($action == 'getbackupsessionlog') {
	$log = $veeam->getBackupSessionLog($id);
	echo json_encode($log);
}
if ($action == 'getbackupsessions') {
	$log = $veeam->getBackupSessions();
	echo json_encode($log);
}
if ($action == 'getrestoresessionevents') {
	$log = $veeam->getRestoreSessionEvents($id);
	echo json_encode($log);
}
if ($action == 'getrestoresessions') {
	$log = $veeam->getRestoreSessions();
	echo json_encode($log);
}

/* Restore Session Calls */
if ($action == 'startrestore') {
    if (isset($id) && ($id != "tenant")) {
        $session = $veeam->startRestoreSession($json, $id);
    } else {
        $session = $veeam->startRestoreSession($json);
    }
    
    $_SESSION['rid'] = $session['id'];
    $_SESSION['rtype'] = strtolower($session['type']);
    echo $session['id']; /* Return the Restore Session ID */
}
if ($action == 'stoprestore') {
    $session = $veeam->stopRestoreSession($id);
    unset($_SESSION['rid']);
    unset($_SESSION['rtype']);
}

/* Exchange Calls */
if ($action == 'getmailitems') {
    $items = $veeam->getMailboxItems($mailboxid, $rid, $folderid, $offset);
    echo json_encode($items);
}

/* Exchange Restore Calls */
if ($action == 'exportmailbox') {
    $veeam->exportMailbox($mailboxid, $rid, $json);
}
if ($action == 'exportmailitem') {
    $veeam->exportMailItem($itemid, $mailboxid, $rid, $json);
}
if ($action == 'exportmultiplemailitems') {
    $veeam->exportMultipleMailItems($itemid, $mailboxid, $rid, $json);
}
if ($action == 'restoremailbox') {
    $veeam->restoreMailbox($mailboxid, $rid, $json);
}
if ($action == 'restoremailitem') {
    $veeam->restoreMailItem($itemid, $mailboxid, $rid, $json);
}
if ($action == 'restoremultiplemailitems') {
    $veeam->restoreMultipleMailItems($mailboxid, $rid, $json);
}

/* OneDrive Calls */
if ($action == 'getonedriveitems') {
    $items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid, $offset);
    echo json_encode($items);
}
if ($action == 'getonedriveitemsbyfolder') {
    $items = $veeam->getOneDriveTree($rid, $userid, $type, $folderid);
    echo json_encode($items);
}
if ($action == 'getonedriveparentfolder') {
    $items = $veeam->getOneDriveParentFolder($rid, $userid, $type, $folderid);
    echo json_encode($items);
}

/* OneDrive Restore Calls */
if ($action == 'exportonedrive') {
    $veeam->exportOneDrive($userid, $rid, $json);
}
if ($action == 'exportonedriveitem') {
    $veeam->exportOneDriveItem($itemid, $userid, $rid, $json, $type);
}
if ($action == 'exportmultipleonedriveitems') {
    $veeam->exportMultipleOneDriveItems($itemid, $userid, $rid, $json, $type);
}
if ($action == 'restoreonedrive') {
    $veeam->restoreOneDrive($userid, $rid, $json);
}
if ($action == 'restoreonedriveitem') {
    $veeam->restoreOneDriveItem($itemid, $userid, $rid, $json, $type);
}
if ($action == 'restoremultipleonedriveitems') {
    $veeam->restoreMultipleOneDriveItems($userid, $rid, $json);
}

/* SharePoint Calls */
if ($action == 'getsharepointcontent') {
    $users = $veeam->getSharePointContent($rid, $siteid, $type);
    echo json_encode($users);
}
if ($action == 'getsharepointitems') {
    $items = $veeam->getSharePointTree($rid, $siteid, $folderid, $type, $offset);
    echo json_encode($items);
}
if ($action == 'getsharepointitemsbyfolder') {
    $items = $veeam->getSharePointTree($rid, $siteid, $folderid, $type);
    echo json_encode($items);
}
if ($action == 'getsharepointparentfolder') {
    $items = $veeam->getSharePointParentFolder($rid, $siteid, $type, $folderid);
    echo json_encode($items);
}

/* SharePoint Restore Calls */
if ($action == 'exportsharepoint') {
    $veeam->exportSharePoint($siteid, $rid, $json);
}
if ($action == 'exportsharepointitem') {
    $veeam->exportSharePointItem($itemid, $siteid, $rid, $json, $type);
}
if ($action == 'exportmultiplesharepointitem') {
    $veeam->exportMultipleSharePointItem($siteid, $rid, $json);
}
if ($action == 'restoresharepoint') {
    $veeam->restoreSharePoint($siteid, $rid, $json);
}
if ($action == 'restoresharepointitem') {
    $veeam->restoreSharePointItem($itemid, $siteid, $rid, $json, $type);
}
if ($action == 'restoremultiplesharepointitems') {
    $veeam->restoreMultipleSharePointItems($siteid, $rid, $json);
}
?>