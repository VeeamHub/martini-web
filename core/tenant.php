<?php
require_once('database.php');
require_once('securestore.php');
require_once('terraform.config.php');
require_once('cloudprovideroptionlist.php');

class MartiniTenant {
	var $id = -1;
	var $name = '';
	var $email = '';
	var $registered = -1;
	var $password = '';

	function __construct($id, $name, $email, $registered) {
		$this->id = $id;
		$this->name = $name;
		$this->email = $email;
		$this->registered = $registered;
	}
}

class TenantNotFoundException extends Exception {
	public $tenantid = 0;

	public function __construct($id, $code = 0, Exception $previous = null) {
		$this->tenantid = $id;
		parent::__construct("Tenant not found with id {$this->tenantid}", $code, $previous);
	}
}

class TenantDatabaseException extends Exception {
	public function __construct($code = 0, Exception $previous = null) {
		parent::__construct("Tenant Database exception", $code, $previous);
	}
}

class InstanceNotFoundException extends Exception {
	public $instanceid = 0;

	public function __construct($id, $code = 0, Exception $previous = null) {
		$this->instanceid = $id;
		parent::__construct("Instance not found with id {$this->instanceid}", $code, $previous);
	}
}

class InstanceNoFreeBrokerSlotException extends Exception {
	public function __construct($code = 0, Exception $previous = null) {
		parent::__construct("No free port slot", $code, $previous);
	}
}

class DeployMethodUnknownCloudException extends Exception {
	public function __construct($cloudmessage="",$code = 0, Exception $previous = null) {
		parent::__construct("Method for deployment is not known, can be unknown cloud or region $cloudmessage", $code, $previous);
	}
}

function guidv4() {
    $data = openssl_random_pseudo_bytes(16);

    assert(strlen($data) == 16);

    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function brokerTenantInstance($id, $clientip, $autokill = 300) {
	$port = -1;
	$db = getDBConnection();
	$db->beginTransaction();

	try {
		$stmt = $db->prepare("select id, hostname from martini_tenant_instances where id = ?");
		$stmt->execute(array($id));
		
		if ($row = $stmt->fetch()) {
			$port = 0;
			$now = time();

			$stmtslot = $db->prepare("select id,port from martini_endpoint where validuntil < ? for update;");
			$stmtslot->execute(array($now));

			if($rowslot = $stmtslot->fetch()) {
				$stmtupd = $db->prepare("update martini_endpoint set validuntil = ?, pid = ?, tenantid = ? where id = ? ");
				$tenantfqdn = $row["hostname"];
				$port = $rowslot['port'];
				$pid = exec("/usr/bin/martini-pfwd -clientaddr $clientip -local :$port -name pfwd-$port -remote $tenantfqdn:3389 -autokill $autokill;cat /tmp/pfwd-$port.pid");
				$now = time();
				$stmtupd->execute(array(($now+$autokill+5),$pid,$id,$rowslot['id']));

				error_log($pid);
			} else {
				throw new InstanceNoFreeBrokerSlotException();
			}
		    
			$db->commit();	
		} else {
			 throw new InstanceNotFoundException($id);
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
	
	return $port;
}

/*
 * Delete a tenant
 */
function deleteTenant($id) {
	$deleted = -1;
	$db = getDBConnection();

	try {
		$stmt = $db->prepare("select id from martini_tenant where id = ?");
		$stmt->execute(array($id));

		if ($row = $stmt->fetch()) {
			$stmt = $db->prepare("delete from martini_tenant where id = ?");
			$stmt->execute(array($id));
			
			$deleted = $id;
		} else {
			 throw new TenantNotFoundException($id);
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}

	return $deleted;
}

/*
 * Deploy a tenant
 */
function deployTenant($tenantid, $type, $config) {
	$rid = -1;

	$t = getTenant($tenantid);
	$type = strtolower($type);

	switch($type) {
		case 'aws':
			if (isAllowedAWSRegion($config->region)) {
				$name = $t->name. '-' . guidv4();
				$region = $config->region;
				$configjson = genAWSConfig($name, $region);
				$rid = saveInstance($tenantid, $name, $configjson, $type, 0, $region);
			} else {
				throw new DeployMethodUnknownCloudException("cloud AWS known but not defined region {$config->region}");
			}

			break;
		default:
			throw new DeployMethodUnknownCloudException("$type is unknown, allowed : AWS");
	}

	return $rid;
}

/*
 * Get tenant details
 */
function getTenant($id) {
	$db = getDBConnection();
	
	try {
		$stmt = $db->prepare("select * from martini_tenant where id = ?");
		$stmt->execute(array($id));
	
		if ($row = $stmt->fetch()) {
			$tenant = new stdClass();
			$tenant->id = $row['id'];
			$tenant->name = $row['name'];
			$tenant->email = $row['email'];
			$tenant->registered = $row['registered'];
		} else {
			 throw new TenantNotFoundException($id);
		}	
	} catch (PDOException $ex) {
		 throw new TenantDatabaseException(0, $ex);
	}
	
	return $tenant;
}

/*
 * Get orphaned instances
 */
function getOrphanedInstances() {
	$db = getDBConnection();
	
	try {
		$stmt = $db->prepare("select id, name, hostname, type, status, location from martini_tenant_instances where tenant_id = ?");
		$stmt->execute(array("-1"));
		$instances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	} catch (PDOException $ex) {
		 throw new TenantDatabaseException(0, $ex);
	}
	
	return $instances;
}

function getTenantAllInstancesRef($id = -1) {
	return getTenantAllInstances($id, "id, name, tenant_id, type, status, location, hostname, port, username");
}

/*
 * Get all instances for a tenant
 */
function getTenantAllInstances($id = -1, $fields = '*') {
	$db = getDBConnection();
	
	try {
		if ($id == -1) {
			$stmt = $db->prepare("select $fields from martini_tenant_instances");
			$stmt->execute();
			$instances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$stmt = $db->prepare("select $fields from martini_tenant_instances where tenant_id = ?");
			$stmt->execute(array($id));
			$instances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
	} catch (PDOException $ex) {
		 throw new TenantDatabaseException(0, $ex);
	}
	
	return $instances;
}

/*
 * Get a specific instance for a tenant
 */
function getTenantInstance($id, $resolvepw = false) {
	$db = getDBConnection();
	
	try {
		$stmt = $db->prepare("select * from martini_tenant_instances where id = ?");
		$stmt->execute(array($id));
	
		if ($row = $stmt->fetch()) {
			$instance = new stdClass();
			$instance->id = $row['id'];
			$instance->name = $row['name'];
			$instance->tenantid = $row['tenant_id'];
			$instance->type = $row['type'];
			$instance->status = $row['status'];
			$instance->location = $row['location'];
			$instance->hostname = $row['hostname'];
			$instance->port = $row['port'];
			$instance->username = $row['username'];
			
			if ($resolvepw) {
				$pw = getPassword($row['password']);
				$instance->password = $pw;
			} else {
				$pw = "*******";
				$instance->password = $pw;
			}			
		} else {
			 throw new InstanceNotFoundException($id);
		}	
	} catch (PDOException $ex) {
		 throw new TenantDatabaseException(0, $ex);
	}
	
	return $instance;
}

/*
 * Get total instances for a tenant
 */
function getTenantInstanceCounter($id) {
	$db = getDBConnection();
	
	try {
		$stmt = $db->prepare("select * from martini_tenant_instances where tenant_id = ?");
		$stmt->execute(array($id));	
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
	
	return $stmt->rowCount();
}

/*
 * Get all instances for all tenants
 */
function getTenantInstances() {
	$db = getDBConnection();
	
	try {
		$stmt = $db->prepare("select * from martini_tenant_instances");
		$stmt->execute();	
	} catch (PDOException $ex) {
		 throw new TenantDatabaseException(0, $ex);
	}
	
	return $stmt->rowCount();
}

/*
 * Get all tenants
 */
function getTenants() {
	$db = getDBConnection();
	$tenants = array();

	try {	
		$result = $db->query("select * from martini_tenant");
		
		foreach ($result as $row) {
			array_push($tenants, new MartiniTenant($row['id'], $row['name'], $row['email'], $row['registered']));
		}

	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}

	return $tenants;
}

/*
 * Random pass generator
 */
function randPass() {
	 $s = '';
	 
	 for ($i = 0, $z = strlen($a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')-1; $i != 12; $x = rand(0, $z), $s .= $a{$x}, $i++);
	 
	 return $s;
}

/*
 * Password hasher
 */
function hashPass($password) {
	return  hash('sha512', sprintf("martini!%s", $password));
}

/*
 * Save a tenant
 */
function saveTenant($tenant, $password = false) {
	$db = getDBConnection();

	if ($tenant->registered == -1) {
		$date = new DateTime();
		$tenant->registered = $date->getTimestamp();
	}
	try {
		if ($tenant->id == -1) { /* New tenant */
			$pass = randPass();
			$hash = hashPass($pass);
			
			$sql = "insert into martini_tenant (name, email, registered, password) values (?,?,?,?)";
			$stmt = $db->prepare($sql);
			$stmt->execute(array($tenant->name, $tenant->email, $tenant->registered, $hash));
			
			$id = $db->lastInsertId();
			
			$tenant->id = (int)$id;
			$tenant->password = $pass;
		} else { /* Update tenant */
			if ($password) {
				$pass = randPass();
				$hash = hashPass($pass);
				
				$sql = "update martini_tenant set name = ?, email = ?, password = ? where id = ?";
				$stmt = $db->prepare($sql);
				$stmt->execute(array($tenant->name, $tenant->email, $hash, $tenant->id));
				
				$tenant->password = $pass;
			} else {
				$sql = "update martini_tenant set name = ?, email = ? where id = ?";
				$stmt = $db->prepare($sql);
				$stmt->execute(array($tenant->name, $tenant->email, $tenant->id));
			}
		} 
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}

	return $tenant;
}

/*
 * Clean up an instance (this will actually delete it)
 */
function cleanupInstance($id) {
	$deleted = -1;
	$db = getDBConnection();

	try {
		$stmt = $db->prepare("select id from martini_tenant_instances where id = ?");
		$stmt->execute(array($id));

		if ($row = $stmt->fetch()) {
			deletePassword($row['password']);
			
			$stmt = $db->prepare("delete from martini_tenant_instances where id = ?");
			$stmt->execute(array($id));	
			$deleted = $id;
		} else {
			 throw new InstanceNotFoundException($id);
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}

	return $deleted;
}

/*
 * Mark an instance for removal
 */
function deleteInstance($id) {
	$deleted = -1;
	$db = getDBConnection();

	try {
		$stmt = $db->prepare("select id, password from martini_tenant_instances where id = ?");
		$stmt->execute(array($id));

		if ($row = $stmt->fetch()) {
			$stmt = $db->prepare("update martini_tenant_instances set status = ? where id = ?");
			$stmt->execute(array("-100", $id));
			deletePassword($row['password']);
			
			$deleted = $id;
		} else {
			 throw new InstanceNotFoundException($id);
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}

	return $deleted;
}

/*
 * Mark all instances for removal for a specific tenant
 */
function deleteInstances($id) {
	$deleted = -1;
	$db = getDBConnection();

	try {
		$stmt = $db->prepare("select * from martini_tenant_instances where tenant_id = ?");
		$stmt->execute(array($id));

		if ($row = $stmt->fetch()) {
			$stmt = $db->prepare("update martini_tenant_instances set status = ? where tenant_id = ?");
			$stmt->execute(array("-100", $id));
			deletePassword($row['password']);
			
			$deleted = $id;
		} else {
			 throw new InstanceNotFoundException($id);
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}

	return $deleted;
}

/*
 * Save an instance
 */
function saveInstance($id, $name, $json, $type, $status, $location) {
	$db = getDBConnection();
	$rid = -1;

	try {
		$sql = "insert into martini_tenant_instances (tenant_id, name, json, type, status, location) values (?,?,?,?,?,?)";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($id, $name, $json, $type, $status, $location));
		$rid = (int)$db->lastInsertId();
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
	
	return $rid;
}

/* 
 * Used to (re)assign an instance to another tenant
 */
function assignInstance($tenantid, $instanceid) {
	$db = getDBConnection();
	$t = getTenant($tenantid);

	try {
		$sql = "update martini_tenant_instances set tenant_id = ? where id = ?";
		$stmt = $db->prepare($sql);
		
		if ($stmt->execute(array($tenantid, $instanceid))) {
			return true;
		} else {
			return false;
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
	return false;	
}

/*
 * Update instance settings
 */
function updateInstance($id, $hostname, $port = 4443, $username, $password) {
	$db = getDBConnection();

	try {
		$uuidkey = guidv4();
		savePassword($uuidkey, $password);
		
		$sql = "update martini_tenant_instances set hostname = ?, port = ?, username = ?, password = ? where id = ?";
		$stmt = $db->prepare($sql);
		if ($stmt->execute(array($hostname, $port, $username, $uuidkey, $id))) {
			return true;
		} else {
			return false;
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
	
	return $sql;
}

/*
 * Update settings for all instances for a tenant
 */
function updateInstances($id) {
	$db = getDBConnection();

	try {	
		$sql = "update martini_tenant_instances set tenant_id = ? where tenant_id = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array("-1", $id));
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
}

/*
 * Update specific instance state
 * 0 = scheduled for deployment
 * 1 = deployed
 * 2 = deployment in progress
 * -1 = unmanaged
 * -100 = marked for removal
 */
function updateInstanceState($status = 1, $id) {
	$db = getDBConnection();

	try {	
		$sql = "update martini_tenant_instances set status = ? where id = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($status, $id));
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
}
?>