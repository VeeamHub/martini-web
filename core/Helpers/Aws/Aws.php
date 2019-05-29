<?php
namespace Terraform\Helpers\Aws;

use Aws\Sdk;

class Aws {
    protected $aws;

    public function __construct($region = null, $accesskey, $secretkey) {
        $this->aws = new Sdk([
            'region' => $region,
            'version' => 'latest',
			'credentials' => [
				'key'    => $accesskey,
				'secret' => $secretkey,
			],
        ]);
    }

    public function listVpcs($options = [], $fullResponse = false) {
        $ec2 = $this->aws->createEc2();
        $result = $ec2->describeVpcs($options);

        return $fullResponse ? $result->toArray() : array_column($result->toArray()['Vpcs'], 'VpcId');
    }

    public function getSdk() {
        return $this->aws;
    }
}