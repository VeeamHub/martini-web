<?php
namespace Terraform;

use Terraform\Blocks\Block;

class Terraform {
    protected $terraform = [];

    public function __get($name) {
        return $this->terraform[$name];
    }

    public function __set($name, $value){
        if (!($value instanceof Block)) {
            throw new \Exception('Value must be a type of block.');
        }
		
        $this->terraform[$name] = $value;
    }

    public function deepMerge() {
        $a = [];
		
        foreach ($this->terraform as $key => $value) {
            $a = array_merge_recursive($a, $value->toArray());
        }

        return $a;
    }

    public function toJson() {
        $a = $this->deepMerge();

        return self::jsonEncode($a);
    }

    public static function jsonEncode($input, $pretty = true) {
        $flag = $pretty ? JSON_PRETTY_PRINT : 0;
		
        return json_encode($input, $flag | JSON_UNESCAPED_SLASHES);
    }
}
?>