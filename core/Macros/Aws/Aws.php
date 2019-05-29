<?php
namespace Terraform\Macros\Aws;

use Terraform\Blocks\Resource;
use Terraform\Terraform;

class Aws {
    public static function securityGroup($name, $vpcId, array $rules) {
        $defaults = [
            'cidr_blocks' => ['0.0.0.0/0'],
            'from_port' => 0,
            'to_port' => 0,
            'protocol' => "-1",
        ];
        
		if (!count($rules)) {
            $ingress = $defaults;
        }
		
        foreach ($rules as $rule => $value) {	
			for ($i = 0; $i < count($rules); $i++) {
				for ($j = 0; $j < count($value); $j++) {
					$ingress[] = [ 'cidr_blocks' => [ $rule ], 'from_port' => $value[$j], 'to_port' => $value[$j], 'protocol' => 'TCP'];
				}
			}
		}
		
        $sg = new Resource('aws_security_group', $name);
        $sg->ingress = $ingress;
        $sg->egress = $defaults;
        $sg->vpc_id = $vpcId;
        $sg->name = $name;
        $sg->description = "$name security group";
        $sg->tags = [ 'Name' => $name ];

        return $sg;
    }
}