<?php
require_once('translateapi.php');

class LicenseQueryException extends Exception {}

function getLicensedUsers($instanceid) {
	$licenses = [];

	$veeam = instanceAuth($instanceid);	
	$lic = $veeam->getLicensedUsers();
	if (isset($lic["results"])) {
		$licenses = $lic["results"];
	} else {
		throw new LicenseQueryException("Queried but no results");
	}

	return $licenses;
}

class LicenseOrgUsage {
	public $orgid;
	public $orgname;
	public $licensedUsers;
	public $newUsers;

	function  __construct($orgid, $orgname, $licensedUsers, $newUsers) {
		$this->orgid = $orgid;
		$this->orgname = $orgname;
		$this->licensedUsers = $licensedUsers;
		$this->newUsers = $newUsers ;
	}
}
function getLicenseInformation($instanceid) {
	$licenses = [];
	$veeam = instanceAuth($instanceid);
	$orgs = $veeam->getOrganizations();
	
	foreach($orgs as $org) {
		$licinfo = $veeam->getLicenseInfo($org['id']);
		
		array_push($licenses,(new LicenseOrgUsage($org['id'], $org['name'], $licinfo['licensedUsers'], $licinfo['newUsers'])));
	}
	return $licenses;
}
?>