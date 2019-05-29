<?php
require_once('database.php');

/* AWS related functions */
function getAWSGeneralSettings() {
	$db = getDBConnection();
	
	try {
		$result = $db->query(sprintf("select * from martini_general_aws_config"));	
		
		if ($result->rowCount() != '0') {
			foreach ($result as $row) {
				$provider = new stdClass();
				$provider->region = $row['region'];
				$provider->accesskey = $row['accesskey'];
				$provider->secretkey = $row['secretkey'];
			}
			
			return $provider;
		}
	} catch (PDOException $ex) {
		print "Got error";
		print var_dump($ex);
	}
}

function getAWSRegionSettings($region) {
	$db = getDBConnection();
	
	try {
		$result = $db->query(sprintf("select * from martini_provider_aws_region WHERE region = '%s'", $region));	
		
		if ($result->rowCount() != '0') {
			foreach ($result as $row) {
				$provider = new stdClass();
				$provider->vpc = $row['vpc'];
				$provider->privatekey = $row['privatekey'];
			}
			
			return $provider;
		}
	} catch (PDOException $ex) {
		print "Got error";
		print var_dump($ex);
	}
}

function saveAWSGeneralSettings($json) {
	$db = getDBConnection();
	
	try {
		$result = $db->query(sprintf("select * from martini_general_aws_config"));	
		
		if ($result->rowCount() != '0') {
			foreach ($result as $row) {
				$settings = new stdClass();
				$settings->accesskey = $row['accesskey'];
				$settings->secretkey = $row['secretkey'];
			}
		}
		
		$decoded = json_decode($json);
		$region = $decoded->region;
		$accesskey = $decoded->accesskey;
		$secretkey = $decoded->secretkey;
		
		if (!empty($accesskey)) {	
			if (!empty($secretkey)) {
				$sql = sprintf("replace into martini_general_aws_config values ('aws', '%s', '%s', '%s')", $region, $accesskey, $secretkey);
			} else { 
				$sql = sprintf("replace into martini_general_aws_config values ('aws', '%s', '%s', '%s')", $region, $accesskey, $settings->secretkey);
			}
		} else {
			$sql = sprintf("replace into martini_general_aws_config values ('aws', '%s', '%s', '%s')", $region, $settings->accesskey, $settings->secretkey);
		}
		
		$stmt = $db->prepare($sql);
		
		echo json_encode($stmt->execute());
	} catch (PDOException $ex) {
		print "Got error";
		print var_dump($ex);
	}
}

function saveAWSRegionSettings($json) {
	$db = getDBConnection();
	
	try {
		$result = $db->query(sprintf("select * from martini_provider_aws_region"));	
		
		if ($result->rowCount() != '0') {
			foreach ($result as $row) {
				$provider = new stdClass();
				$provider->privatekey = $row['privatekey'];
			}
		}
		
		$decoded = json_decode($json);
		$region = $decoded->region;
		$vpc = $decoded->vpc;
		$privatekey = $decoded->privatekey;
		
		if ((isset($privatekey) && !empty($privatekey))) {
			$sql = sprintf("replace into martini_provider_aws_region values ('%s', '%s', '%s')", $region, $vpc, $privatekey);	
		} else {
			$sql = sprintf("replace into martini_provider_aws_region values ('%s', '%s', '%s')", $region, $vpc, $provider->privatekey);
		}
				
		$stmt = $db->prepare($sql);
		
		echo json_encode($stmt->execute());
	} catch (PDOException $ex) {
		print "Got error";
		print var_dump($ex);
	}
}
?>