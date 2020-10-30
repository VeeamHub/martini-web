<?php
require_once('database.php');

function deletePassword($keyref) {
	$db = getDBConnection();
	
	try {
		$sql = "delete from martini_securestore where keyval = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($keyref));
	} catch(PDOException $ex) {
		error_log($ex);
	}						              
}

function getPassword($keyref) {
	$db = getDBConnection();
	$pwd = '';
	$hn = gethostname();
	$mysqlsalt = "cocktailsarefun$keyref$hn";
	
	try {
		$stmt = $db->prepare("select AES_ENCRYPT(encryptedpassword,UNHEX(SHA2(?,512))) as pw from martini_securestore where keyval = ?");
		$stmt->execute(array($mysqlsalt, $keyref));
		$result = $stmt->fetchAll();

		foreach ($result as $row) {
			$pwd = $row['pw'];
		}
	} catch(PDOException $ex) {
		error_log($ex);
	}
	
	return $pwd;
}

function savePassword($keyref, $pw) {
	$hashpw = $pw;
	$db = getDBConnection();
	$hn = gethostname();
	$mysqlsalt = "cocktailsarefun$keyref$hn";
	
	try {
		$sql = "insert into martini_securestore(keyval, encryptedpassword) values (?,AES_ENCRYPT(?,UNHEX(SHA2(?,512))))";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($keyref, $hashpw, $mysqlsalt));
	} catch(PDOException $ex) {
		error_log($ex);
	}
}

function updatePassword($id, $keyref, $pw) {
	$hashpw = $pw;
	$db = getDBConnection();
	$hn = gethostname();
	$mysqlsalt = "cocktailsarefun$keyref$hn";
	
	try {
		$sql = "update martini_securestore set keyval = ?, encryptedpassword = ENCODE(?,?) where id = ?";
		$stmt = $db->prepare($sql);
		$stmt->execute(array($keyref, $hashpw, $mysqlsalt, $id));
	} catch(PDOException $ex) {
		error_log($ex);
	}
}
?>
