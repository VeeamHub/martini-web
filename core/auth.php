<?php
require_once('database.php');

class MartiniTokenObject {
	var $token = 0;
	var $renew = 0;
	var $lifetime = 0;
	var $status = 0;

	function __construct($token, $renew, $lifetime) {
		$this->token = $token;
		$this->renew = $renew;
		$this->lifetime = $lifetime;
		$this->status = 0;
	}
	
	function getToken() { return $this->token; }
	function getRenew() { return $this->renew; }
	function getLifetime() { return $this->lifetime; }
	function getStatus() { return $this->status; }
}

class MartiniAuthObject {
	var $auth = 0;
	var $id = 0;
	var $name = '';
	var $token = 0;
	var $tenantid = -1;
	var $isadmin = False;

	function __construct($auth, $id) {
		$this->auth = $auth;
		$this->id = $id;
		$this->name = "";
		$this->token = new MartiniTokenObject(0,0,0);
	}
	
	function getId() { return $this->id; }
	function getName() { return $this->name; }
	function isAuthed() { return ($this->auth > 0); }
}

/* 
 * Authenticate class for an admin or tenant
 * 0: noaccess
 * >0: authenticated
 */
function authenticate($username, $password, $type = 'tenant') {
	$db = getDBConnection();
	$ao = new MartiniAuthObject(0, 0);

	try {
		$hashpwd = hash("sha512", sprintf("martini!%s", $password));
			
		switch ($type) {
			case "admin":
				$stmt = $db->prepare("select * from martini_user where name = ?");
				$stmt->execute(array($username));

				if ($row = $stmt->fetch()) {
					if ($hashpwd == strtolower($row["hashpassword"])) {
						$ao->auth = 1;
						$ao->id = $row["id"];
						$ao->name = $row["name"];
						$ao->isadmin = true;
					}
				}
			break;
			case "tenant":
				$stmt = $db->prepare("select * from martini_tenant where email = ?");
                $stmt->execute(array($username));

				if ($row = $stmt->fetch()) {
					if ($hashpwd == strtolower($row["password"])) {
						$ao->auth = 1;
						$ao->id = $row["id"];
						$ao->tenantid = $row["id"];
						$ao->name = $row["email"];
						$ao->isadmin = false;
					}
				}
	
			break;
		}
	} catch (PDOException $ex) {
	}
	
	return $ao;
}

/* 
 * Returns a token if authenticated
 */
function authenticateWithToken($username, $password) {
	$result = authenticate($username, $password, 'admin');
	
	if ($result->auth > 0 && $result->isadmin) {
		$validlifetime = 3600;
		$date = new DateTime();
		$unixtime = $date->getTimestamp() + $validlifetime;
		$token = bin2hex(random_bytes(24)); 
		$renew = bin2hex(random_bytes(24)); 

		$hashedtoken = hash("sha512", "mixeduptoken!$token");
		$hashedrenew = hash("sha512", "mixeduptoken!$renew");
		
		$db = getDBConnection();

		$stmt = $db->prepare("INSERT INTO martini_token (token, renew, validuntil, userid) VALUES (?,?,?,?)");
		$stmt->execute(array($hashedtoken, $hashedrenew, $unixtime, $result->id));

		$result->token->token = $token;
		$result->token->renew = $renew;
		$result->token->lifetime = $unixtime;
	}
	
	return $result;
}

/*
 * Password validation for tenant authenticate
 */
function validateTenantPass($mail, $pass) {
	$db = getDBConnection();
	$hash = hashPass($pass);
	$authenticated = 0;

	try {	
		$stmt = $db->prepare("select * from martini_tenant where email = ? and password = ?");
		$stmt->execute(array($mail, $hash));
		
		if ($row = $stmt->fetch()) {
			if ($row['email'] == $mail) {
				foreach ($result as $row) {
					if ($hashpwd == strtolower($row["hashpassword"])) {
						$ao->auth = 1;
						$ao->id = $row["id"];
						$ao->name = $row["name"];
					}
				}
			}	
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException(0, $ex);
	}
	
	return $authenticated;
}

/*
 * Renew admin token
 */
function renewToken($token, $renew) {
	$db = getDBConnection();
	$tokeno = new MartiniTokenObject(0, 0, 0);

	try {
		$hashedtoken = hash("sha512", "mixeduptoken!$token");
		$hashedrenew = hash("sha512", "mixeduptoken!$renew");
		
		$stmt = $db->prepare("select * from martini_token where token = ? and renew = ?");
		$stmt->execute(array($hashedtoken,$hashedrenew));
		$result = $stmt->fetchAll();

		foreach ($result as $row) {
			$date = new DateTime();
			if ($row['validuntil'] >  $date->getTimestamp()) {
				$token = bin2hex(random_bytes(24)); 
				$hashedtoken = hash("sha512", "mixeduptoken!$token");
				$validlifetime = 3600 ;
				$unixtime =  $date->getTimestamp() + $validlifetime;
				$stmt = $db->prepare("UPDATE martini_token SET token = ?, validuntil = ?  WHERE renew = ? and id = ?");
				$stmt->execute(array($hashedtoken, $unixtime,$hashedrenew, $row['id']));
				$tokeno->status = 1;
				$tokeno->token = $token;
				$tokeno->renew = $renew;
				$tokeno->lifetime = $unixtime;
			} else {
				$tokeno->status = 2;
			}
		}

	} catch(PDOException $ex) {
		error_log($ex);
	}
	
	return $tokeno;
}

/*
 * Verify admin token
 * returns:
 * 0: invalid
 * 1: valid
 * 2: expired
 */
function verifyToken($token) {
	$db = getDBConnection();
	$tokeno = new MartiniTokenObject($token,0,0);

	try {
		$date = new DateTime();
		$hashedtoken = hash("sha512", "mixeduptoken!$token");
		$stmt = $db->prepare("select * from martini_token where token = ?");
		$stmt->execute(array($hashedtoken));
		$result = $stmt->fetchAll();
		
		foreach ($result as $row) {
			$tokeno->lifetime = $row['validuntil'];

			if ($row['validuntil'] > $date->getTimestamp()) {
				$tokeno->status = 1;
			} else {	
				$tokeno->status = 2;
			}
		}
	} catch(PDOException $ex) {
		error_log("danger $ex");	
	}
	
	return $tokeno;
}
?>