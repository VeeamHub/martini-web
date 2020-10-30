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
		//encode/decode legacy fix
		//please patch db first
		//ALTER TABLE martini.martini_securestore ADD encryptedpassword_aes BLOB;
		//$stmt = $db->prepare("select DECODE(encryptedpassword, ?) as pw from martini_securestore where keyval = ?");
		$stmt = $db->prepare("select AES_DECRYPT(encryptedpassword_aes, SHA2(?,512)) as pw from martini_securestore where keyval = ?");
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
		//encode/decode legacy fix
		//please patch db first
		//ALTER TABLE martini.martini_securestore ADD encryptedpassword_aes BLOB;
		//$sql = "insert into martini_securestore(keyval, encryptedpassword) values (?,ENCODE(?,?))";
		$sql = "INSERT INTO martini_securestore(keyval, encryptedpassword_aes) VALUES (?,AES_ENCRYPT(?,SHA2(?,512)));";
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
