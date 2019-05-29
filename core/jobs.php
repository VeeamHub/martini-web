<?php
require_once('translateapi.php');

function getJobs($instanceid) {
	$jobs = [];

	$veeam = instanceAuth($instanceid);	
	$jobs = $veeam->getJobs();
	
	return $jobs;
}

function startJob($instanceid, $jobid) {
	$veeam = instanceAuth($instanceid);	
	$veeam->startJob($jobid);
}
?>