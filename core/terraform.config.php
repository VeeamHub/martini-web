<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../core/configuration.php');

use Terraform\Blocks\Resource;
use Terraform\Blocks\Output;
use Terraform\Helpers\Aws as AwsHelpers;
use Terraform\Macros\Aws\Aws as AwsMacros;

/*
 * @param $instancename
 * @param $region
 */
function genAWSConfig($instancename, $region = null) {
	$awssettings = getAWSGeneralSettings();
	$configurationpath = '/home/deployment';

	if (!isset($region)) {
		$region = $awssettings->region;
	}
	
	$terraform = new \Terraform\Terraform();
	$combinedname = 'aws_' . $instancename;
	
	/* Define provider block */
	$provider = new \Terraform\Blocks\Provider('aws');
	
	$provider->region = $region;
	$provider->access_key = $awssettings->accesskey;
	$provider->secret_key = $awssettings->secretkey;
	$terraform->provider = $provider;

	/* Define data block */
	$data = new \Terraform\Blocks\Data('aws_ami', 'windows-2016-latest');
	$data->most_recent = true;
	$data->owners = [ 'amazon' ];
	$data->filter = [
		'name' => 'name', 
		'values' => ["Windows_Server-2016-English-Full-Base*"]
	];
	$terraform->data = $data;

	/* Define resource block */
	/* Create AWS instance resource */
	$awsinstance = new Resource('aws_instance', $combinedname);
	$awsinstance->ami = '${data.aws_ami.windows-2016-latest.id}';
	$awsinstance->get_password_data = true;
	$awsinstance->instance_type = 't2.medium';
	$awsinstance->key_name = 'terraform';
	$awsinstance->user_data = '${file("setup/bootstrap.ps1")}';
	$awsinstance->tags = [ 'Name' => $instancename ];
	$awsinstance->vpc_security_group_ids = [ '${aws_security_group.security_group_' . $instancename . '.id}' ];
	$awsinstance->connection = [
		'type' => 'winrm', 
		'port' => '5985', 
		'https' => false, 
		'insecure' => true, 
		'timeout' => '5m', 
		'user' => 'Administrator',
		'password' => '${rsadecrypt(self.password_data, file("setup/terraform.pem"))}'
	];
	$awsprovisioner = [ 
		'file' => [
			'source' => 'setup/prep-vbo365.ps1',
			'destination' => 'C:\\VBO365Install\\prep-vbo365.ps1'
		],
		'remote-exec' => [
			'inline' => [
				'powershell.exe -File C:\\VBO365Install\\prep-vbo365.ps1'
			]
		]
	];	
	$awsinstance->provisioner = [ $awsprovisioner ];
	
	$terraform->{"awsinstance"} = $awsinstance;
	
	/* Create null resource for the installation */
	$install = new Resource('null_resource', 'install_vbo_server');
	$install->depends_on = [ 'aws_instance.' . $combinedname ];
	$install->connection = [
		'host' => '${aws_instance.' . $combinedname . '.public_ip}',
		'type' => 'winrm', 
		'port' => '5985', 
		'https' => false, 
		'insecure' => true, 
		'timeout' => '5m', 
		'user' => 'Administrator',
		'password' => '${rsadecrypt("${aws_instance.' . $combinedname . '.password_data}", file("setup/terraform.pem"))}'
	];
	$nullprovisioner = [
		'local-exec' => [
			'command' => 'sleep 60'
		],
		'file' => [
			'source' => 'setup/install-vbo365.ps1',
			'destination' => 'C:\\VBO365Install\\install-vbo365.ps1'
		],
		'remote-exec' => [
			'inline' => [
				'powershell.exe -File C:\\VBO365Install\\install-vbo365.ps1'
			]
		]
	];
	$install->provisioner = [ $nullprovisioner ];

	$terraform->{"install"} = $install;
	
	
	/* Define security group block */
	/*
	 * Defaults:
	 * 3389: RDP
	 * 4443: Veeam Backup for Microsoft Office 365 RESTful API service
	 * 5985: WinRM (HTTP)
	 * 9191: Veeam Backup for Microsoft Office 365 service
	 */
	$rules = [
		'0.0.0.0/0' => [3389, 4443, 5985, 9191],
	];
	$sg = AwsMacros::securityGroup('security_group_' . $instancename, 'vpc-a3f7d8c8', $rules);
	$sg->description = 'Security group for tenant instance ' . $instancename;
	$terraform->sg = $sg;

	
	/* Output the IP's */
	foreach (['public_ip', 'password_data'] as $result) {
		$output = new Output($result);
		$output->value = '${aws_instance.' . $combinedname . '.'.$result.'}';
		$terraform->{"output_$result"} = $output;
	}
	
	
	/* Create the config file */
	$config = $terraform->toJson();
	
	return $config;
}
?>