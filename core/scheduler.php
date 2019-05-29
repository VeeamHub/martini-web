<?php
error_reporting(E_ALL || E_STRICT);

require_once('../vendor/autoload.php');
require_once('configuration.php');
require_once('database.php');
require_once('tenant.php');

$getopts = new Fostam\GetOpts\Handler();

$getopts->addOption('action')
	->long('action')
	->short('a')
    ->description('Action to perform: clean, create, delete, deploy, destroy or update)')
	->argument('ACTION')
    ->required();
	
$getopts->parse();

$options = $getopts->getOptions();
$action = $options['action'];

switch ($action) {
	case 'clean':
		deleteTenantInstance();
		break;
	case 'create':
		deployTenantInstances();
		break;
	case 'delete':
		deleteTenantInstance();
		break;
	case 'deploy':
		deployTenantInstances();
		break;
	case 'destroy':
		deleteTenantInstance();
		break;
	case 'update':
		updateTenantInstance();
		break;
	default:
		exit('Invalid action.');
}

/*
 * Generate tenant instance configuration for Terraform
 * Files are saved under /home/deployment
 */
function createTenantInstanceConfig($id, $name, $json, $key) {
	$folder = '/home/deployment/tenant_' . $id . '_' . $name . '/';
	$privatekey = $folder . '/setup/terraform.pem';

    /* Create the Terraform JSON file */
	$jsonpath = $folder . 'terraform-'.$name.'.json.tf';

	if (!is_dir($folder)) {
		mkdir($folder , 0755, true);
		recurse_copy('setup', $folder . '/setup');
	}

	file_put_contents($jsonpath, $json); /* Terraform JSON */
	file_put_contents($privatekey, $key); /* Terraform private key */
	
	return $folder;
}

/*
 * Delete a tenant instance via Terraform
 */
function deleteTenantInstance() {
	$tenants = getTenants();
	$orphaned = getOrphanedInstances();
	
	for ($i = 0; $i < count($tenants); $i++) {
		try {
			$tenantid = $tenants[$i]->id;
			$instances = getTenantAllInstances($tenantid);

			for ($x = 0; $x < count($instances); $x++) {
				if ($instances[$x]['status'] == -100) { /* Only delete ones with the correct status */
					$id = $instances[$x]['id'];
					$name = $instances[$x]['name'];
					$folder = '/home/deployment/tenant_' . $id . '_' . $name . '/';
					
					echo "Removing '".$instances[$x]['type']."' instance: $name (tenant ID: $tenantid)\r\n";
					cleanupInstance($id);
					
					/* Check the instance(s) */
					if (is_dir($folder)) {
						system('cd ' . $folder . ' && terraform destroy -auto-approve');
						system('rm -rf ' . $folder);
					}
				}
			}
		} catch (Exception $e) {
		}
	}
	
	for ($x = 0; $x < count($orphaned); $x++) {
		try {
			$id = $orphaned[$x]['id'];
			$name = $orphaned[$x]['name'];
			
			echo "Removing '".$orphaned[$x]['type']."' instance: $name (instance ID: $orphaned[$x]['id'])\r\n";
			cleanupInstance($id);
		} catch (Exception $e) {
		}
	}
}


/*
 * Deploy a tenant instance via Terraform
 */
function deployTenantInstances() {
	$tenants = getTenants();

	for ($i = 0; $i < count($tenants); $i++) {
		try {
			$tenantid = $tenants[$i]->id;
			$instances = getTenantAllInstances($tenantid);
			
			for ($x = 0; $x < count($instances); $x++) {
				if ($instances[$x]['status'] == 0) { /* Only scheduled ones need to be deployed */
					if ($instances[$x]['type'] == 'aws') {
						$region = getAWSRegionSettings($instances[$x]['location']);
						$json = $instances[$x]['json'];
						$privatekey = $region->privatekey;
						$id = $instances[$x]['id'];
						$name = $instances[$x]['name'];
						$folder = createTenantInstanceConfig($id, $name, $json, $privatekey);

						echo "Creating 'AWS' instance: $name (tenant ID: $tenantid).\r\n";
					} else {
						echo "Not implemented yet.\r\n";

						echo "Creating 'Other' instance: $name (tenant ID: $tenantid)\r\n";
					}

					/* Deploy the instance */
					updateInstanceState('2', $id);
					system('cd '. $folder . ' && terraform init');
					system('cd '. $folder . ' && terraform apply -auto-approve');
				}
			}
		} catch (Exception $e) {
		}
	}
}

/*
 * Update a tenant instance state
 */
function updateTenantInstance() {
	$tenants = getTenants();

	for ($i = 0; $i < count($tenants); $i++) {
		try {
			$tenantid = $tenants[$i]->id;
			$instances = getTenantAllInstances($tenantid);

			for ($x = 0; $x < count($instances); $x++) {
				if ($instances[$x]['status'] == 2) { /* Only scheduled ones need to be updated */
					$id = $instances[$x]['id'];
					$name = $instances[$x]['name'];
					$folder = '/home/deployment/tenant_' . $id . '_' . $name . '/';

					/* Check the instance(s) */
					if (is_dir($folder)) {
						if ($instances[$x]['type'] == 'aws') {
							$region = getAWSRegionSettings($instances[$x]['location']);
							$privatekey = $region->privatekey;
							$output = json_decode(shell_exec('cd ' . $folder . ' && terraform output -json'));
							$ip = $output->public_ip->value;
							$password = $output->password_data->value;
							openssl_private_decrypt(base64_decode($password), $decrypted, $privatekey);

							echo "Updating settings for 'AWS' instance: $name (tenant ID: $tenantid)\r\n";
							updateInstance($id, $ip, '4443', 'Administrator', $decrypted);
							updateInstanceState('1', $id);
						}
					}
				}
			}
		} catch (Exception $e) {
		}
	}
}

/* 
 * Recurse copy method for setup files 
 */
function recurse_copy($src, $dst) {
    $dir = opendir($src);
    mkdir($dst, 0755, true);

    while (false !== ($file = readdir($dir))) {
        if (($file != '.' ) && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            } else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }

    closedir($dir);
}
?>