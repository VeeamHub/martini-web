<?php 
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');

require_once('../core/auth.php');
require_once('../core/tenant.php');
require_once('../core/jobs.php');
require_once('../core/license.php');
require_once('../core/brokerendpoint.php');
require_once('./jsonvalidate.php');

$getclass = 'login';
$getaction = 'create';

if(isset($_GET['class'])) {
	$getclass = $_GET['class'];
}
if(isset($_GET['action'])) {
	$getaction = $_GET['action'];
}

$tokenheader = '';
$tokentype = '';
$tokenextract = '';

if (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
	$tokenheader = $_SERVER['HTTP_X_AUTHORIZATION'];
	
	if ($tokenheader != '') {
		if(preg_match("/^[\s]*(bearer)[\s]*([a-zA-Z0-9-]+)[\s]*$/",$tokenheader,$tokenarr)) {
			$tokentype = $tokenarr[1];
			$tokenextract = $tokenarr[2];
		}
	}	
}
if ($getclass == 'login') {
	$exec404 = 1;

	switch ($getaction) {
		case "create":
			$readpost = file_get_contents('php://input');
			$json = json_decode($readpost);
			$returnobject = new stdClass();
			
			if (isset($json->username) && isset($json->password)) {
				$res = authenticateWithToken($json->username, $json->password);
				if ($res->auth == 1 && $res->token->token != '') {
					$t = $res->token->token;
					
					header("X-Authentication: $t");

					$returnobject->token = $t;
					$returnobject->renew = $res->token->renew;
					$returnobject->lifetime = $res->token->lifetime;
					$date = new DateTime();
					$returnobject->now = $date->getTimestamp();
					$exec404 = 0;

					print json_encode($returnobject);
				} else {
			        http_response_code(401);
				}
			}
			
			break;
			
		case "heartbeat":
			$result = 0;
			$returnobject = new stdClass();
			$returnobject->status = "unauthenticated";

			if ($tokentype == "bearer" && $tokenextract != '') {
					$check = verifyToken($tokenextract);
					$result = $check->getStatus();
				   
					if ($result == 1) {
						$returnobject->status = "ok";
						$exec404 = 0;
					} else if ($result == 2) {
						$returnobject->status = "expired";
						$exec404 = 0;
					}
			}
			
			if ($exec404 == 0) {
				print json_encode($returnobject);
			}
			
			break;
			
		case "renew":
			if ($tokentype == "bearer" && $tokenextract != '') {
	            $readpost = file_get_contents('php://input');
				$json = json_decode($readpost);
				$returnobject = new stdClass();

				if (isset($json->renew)) {
					$res = renewToken($tokenextract, $json->renew);
					$status = $res->getStatus();
					
					if($status == 1) {
						$t = $res->token;
						
						header('X-Authentication: ' . $t);

						$returnobject->status = 'ok';
						$returnobject->token = $t;
						$returnobject->renew = $res->renew;
						$returnobject->lifetime = $res->lifetime;
						$date = new DateTime();
						$returnobject->now = $date->getTimestamp();

						print json_encode($returnobject);
						
						$exec404 = 0;
					} elseif ($status == 2) {
						$returnobject->status = 'expired';
						$returnobject->token = 0;
						$returnobject->renew = 0;
						$returnobject->lifetime = 0;
						
						print json_encode($returnobject);
						
						$exec404 = 0;
					} else {
			            http_response_code(401);
					}
				} else {
					http_response_code(401);
				}
			} else {
				http_response_code(401);
			}
			
			break;
		default:
			http_response_code(401);
			break;
	}
	
	if ($exec404) {
		http_response_code(401);
	}
} else {
	$result = 0;
	if ($tokentype == 'bearer' && $tokenextract != '') {
		$check = verifyToken($tokenextract);
		$result = $check->getStatus();
	}
	
	if ($result == 1) {
			switch ($getclass) {
				case "tenant":
					switch ($getaction) {
						case "create":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }

								(new JSONValidationRuleTenantName('name'))->Validate($json);
								(new JSONValidationRuleEmail('email'))->Validate($json);


								$mt = new MartiniTenant(-1, $json->name, $json->email, -1);
								saveTenant($mt);
							
								if ($mt->id != -1) {
									$returnobject = new stdClass();
									$returnobject->id = strval($mt->id);
									$returnobject->name = strval($mt->name);
									$returnobject->email = strval($mt->email);
									$returnobject->password = strval($mt->password);
									$returnobject->registered = strval($mt->registered);
								} else {
									throw new Exception('tenant id creation resulted in -1');
								}
							} catch(InvalidDataException $ex) {
								http_response_code(500);
								$returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (Throwable $ex) {
									http_response_code(500);
									$returnobject->status = 'Unexpected error, please check at server side logs.';
									error_log($ex->getMessage());
							}
							print json_encode($returnobject);
							break;
							
						case "list":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$returnobject = getTenants();
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}

							print json_encode($returnobject);
							break;
							
						case "delete":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger('id'))->Validate($json);

								$id = deleteTenant($json->id);
								$returnobject->status = 'deleted';
								$returnobject->id = $id;
							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (TenantNotFoundException $ex) {
								http_response_code(500);
								$returnobject->status = "Tenant id {$ex->tenantid} unknown, please list and try an existing tenantid";
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}
							
							print json_encode($returnobject);
							break;
						default:
							http_response_code(401);
							break;
					}
						
					break;	

				case "instance":
					switch ($getaction) {
						case "list":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);
								
								$returnobject = getTenantAllInstancesRef($json->id);
							} catch(InvalidDataException $ex) {
								http_response_code(500);
								$returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (TenantNotFoundException $ex) {
									http_response_code(500);
									$returnobject->status = "Tenant id {$ex->tenantid} unknown, please list and try an existing tenantid";
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}

							print json_encode($returnobject);
							break;
							
						case "listorphans":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';
							
							try {
								$returnobject = getOrphanedInstances();
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}

							print json_encode($returnobject);
							break;
							
						case "delete":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);

								$id = deleteInstance($json->id);
								$returnobject->status = "deleted";
								$returnobject->id = $id;
							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (TenantNotFoundException $ex) {
								http_response_code(500);
								$returnobject->status = "Instnace id {$ex->tenantid} unknown, please list and try an existing tenantid";
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}
							
							print json_encode($returnobject);
							break;
							
						case "assign":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("newtenantid"))->Validate($json);
								(new JSONValidationRuleInteger("instanceid"))->Validate($json);

								assignInstance($json->newtenantid, $json->instanceid);
								$returnobject->status = "reassigned";
								$returnobject->id = $tenantid;
								$returnobject->subId = $instanceid;

							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (TenantNotFoundException $ex) {
								http_response_code(500);
								$returnobject->status = "Instnace id {$ex->tenantid} unknown, please list and try an existing tenantid";
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}
							
							print json_encode($returnobject);
							break;

						case "broker":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';
							$returnobject->id = "-1";

							try {
                                $readpost = file_get_contents('php://input');
								
								$json = json_decode($readpost);
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);

								$clientip = $_SERVER['REMOTE_ADDR'];
								if (isset($json->clientip) && $json->clientip != "") {
									(new JSONValidationRuleFQDN("clientip"))->Validate($json);
									$clientip = $json->clientip;
								}
								if (!isset($clientip) || $clientip == "") {
									throw new Exception('Don\'t know who to broker for.');
								}
                                $port = brokerTenantInstance($json->id,$clientip);
								$returnobject->port = $port;
								$returnobject->expectedclient = $clientip;
								$returnobject->status = "mapped";
								$returnobject->id = $json->id;

							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}.";
                            } catch (InstanceNotFoundException $ex) {
                                http_response_code(500);
                                $returnobject->status = "Instance id {$ex->instanceid} unknown, please list and try an existing instanceid.";
							} catch (InstanceNoFreeBrokerSlotException $ex) {
								http_response_code(500);
								$returnobject->status = 'No free port slots to broker, please add more slots or wait until some expire.';
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
                            }
							print json_encode($returnobject);
							break;
							
						case "deploy":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);

								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);
								(new JSONValidationRuleGenericLabel("type"))->Validate($json);

								$id = deployTenant($json->id,$json->type, $json->config);
								$returnobject->status = "deployed";
								$returnobject->id = $id;

							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}.";
							} catch(DeployMethodUnknownCloudException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Cloud Deployment Parameter: {$ex->getMessage()}.";
							} catch (TenantNotFoundException $ex) {
								http_response_code(500);
								$returnobject->status = "Tenantid not found id {$ex->tenantid} unknown, please list and try an existing tenantid.";
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}

							print json_encode($returnobject);
							break;
						case "create":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }

								(new JSONValidationRuleTenantName("name"))->Validate($json);
								(new JSONValidationRuleFQDN("hostname"))->Validate($json);
								(new JSONValidationRuleInteger("port"))->Validate($json);
								(new JSONValidationRuleGenericLabel("username"))->Validate($json);
								(new JSONValidationRulePassword("password"))->Validate($json);

								(new JSONValidationRuleInteger("tenant_id"))->Validate($json);
								(new JSONValidationRuleGenericLabel("type"))->Validate($json);
								(new JSONValidationRuleInteger("status"))->Validate($json);
								(new JSONValidationRuleGenericLabel("location"))->Validate($json);

								$miid = saveInstance($json->tenant_id, $json->name, "", $json->type, $json->status, $json->location);
								updateInstance($miid, $json->hostname,$json->port, $json->username, $json->password);

								if ($miid != -1) {
									$returnobject->status = "created";
									$returnobject->id = strval($miid);
								} else {
									throw new Exception("tenant id creation resulted in -1");
								}
							} catch(InvalidDataException $ex) {
								http_response_code(500);
								$returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (Throwable $ex) {
								http_response_code(500);
								$returnobject->status = 'Unexpected error, please check at server side logs.';
								error_log($ex->getMessage());
							}
							print json_encode($returnobject);
							break;

						default:
							http_response_code(401);	
							break;
					}
				break;

				case "job":
					switch ($getaction) {
						case "list":
							$returnobject = new stdClass();	
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
						        	$json = json_decode($readpost);	
								
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);

								$returnobject = getJobs($json->id);
							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}";
							}  catch (Exception $ex) {
								http_response_code(500);
								$returnobject->data = "error listing job, internal credentials error might be broken";
								error_log($ex->getMessage());
							}
							print json_encode($returnobject);

						break;
						case "start":
							$returnobject = new stdClass();
							$returnobject->status = 'internal error';

							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);	

								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);
								(new JSONValidationRuleHash("jobid"))->Validate($json);

								startJob($json->id, $json->jobid);

								$returnobject->id = $json->id;
								$returnobject->jobid = $json->jobid;
								$returnobject->status = "started";

							} catch(InvalidDataException $ex) {
								http_response_code(500);
                                $returnobject->status = "Invalid Data: {$ex->getMessage()}";
                            } catch (TenantNotFoundException $ex) {
								http_response_code(500);
                                $returnobject->status = "Tenant id {$ex->tenantid} unknown, please list and try an existing tenantid";
							} catch (Exception $ex) {
								http_response_code(500);
								$returnobject->data = "error starting job, internal credentials error might be broken";
								error_log($ex->getMessage());
							}

							print json_encode($returnobject);
							break;
							
						default:
							$returnobject = new stdClass();
							$returnobject->status = "unknown tenant action";
							http_response_code(401);
							print json_encode($returnobject);
							break;
					}
					break;

				case "brokerendpoint":
					switch($getaction) {
						case "add":
							$returnobject = new stdClass();
                            
							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);
								
								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("port"))->Validate($json);

								$p = addbrokerendpoint($json->port);
								$returnobject->status = "added";
							} catch(InvalidDataException $ex) {
								http_response_code(500);
															$returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (BrokerEndpointException $ex) {
								http_response_code(500);
								$returnobject->status = "Port already exists";
							} catch (Exception $ex) {
								http_response_code(500);
								$returnobject->status = "error adding port";
								error_log($ex->getMessage());
							}
							
							print json_encode($returnobject);
							break;
							
					case "delete":
						$returnobject = new stdClass();
                        
						try {
							$readpost = file_get_contents('php://input');
							$json = json_decode($readpost);

							if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
							(new JSONValidationRuleInteger("port"))->Validate($json);

							$p = deletebrokerendpoint($json->port);
							$returnobject->status = "deleted";
						} catch(InvalidDataException $ex) {
							http_response_code(500);
                            $returnobject->status = "Invalid Data: {$ex->getMessage()}";
						} catch (BrokerEndpointException $ex) {
							http_response_code(500);
							$returnobject->status = "Port does not exists";
						} catch (Exception $ex) {
							http_response_code(500);
							$returnobject->status = "error deleting port";
							error_log($ex->getMessage());
						}
						print json_encode($returnobject);
						break;
						
					case "list":
						$returnobject = new stdClass();
                        
						try {
							$ps = getBrokerendpoints();
							$returnobject->portlist = $ps;
						} catch (Exception $ex) {
							http_response_code(500);
							$returnobject->status = "error deleting port";
							error_log($ex->getMessage());
						}
						
						print json_encode($returnobject);
						break;
						
					default:
						$returnobject = new stdClass();
						$returnobject->status = "unknown brokerendpoint action";
						http_response_code(401);
						print json_encode($returnobject);
						break;
					}
					
					break;

				case "license":
					switch($getaction) {
						case "listusers":
							$returnobject = new stdClass();
							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);

								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
									(new JSONValidationRuleInteger("id"))->Validate($json);

								$returnobject = getLicensedUsers($json->id);
							} catch(InvalidDataException $ex) {
								http_response_code(500);
								$returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (Exception $ex) {
								http_response_code(500);
								$returnobject->status = "Unexpected internal error, please check error log on the server";
								error_log($ex->getMessage());
							}
							
							print json_encode($returnobject);
							break;
							
						case "listinformation":
							$returnobject = new stdClass();
							try {
								$readpost = file_get_contents('php://input');
								$json = json_decode($readpost);

								if (!isset($json)) { throw new InvalidDataException('Could not parse JSON, make sure it is valid.'); }
								(new JSONValidationRuleInteger("id"))->Validate($json);

								$returnobject =  getLicenseInformation($json->id);
							} catch(InvalidDataException $ex) {
								http_response_code(500);
								$returnobject->status = "Invalid Data: {$ex->getMessage()}";
							} catch (Exception $ex) {
								http_response_code(500);
								$returnobject->status = "Unexpected internal error, please check error log on the server";
								error_log($ex->getMessage());
							}
							
							print json_encode($returnobject);
							break;
						
						default:
							$returnobject = new stdClass();
							$returnobject->status = "unknown license action";
							http_response_code(401);
							print json_encode($returnobject);
						break;
					}
					
					break;
						
					default:
						http_response_code(401);
						break;
			}
	} else {
		http_response_code(401);
	}
}
?>