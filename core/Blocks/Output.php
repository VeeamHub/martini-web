<?php
namespace Terraform\Blocks;

class Output extends Block {
    public function __construct($resourceType) {
        parent::__construct('output', $resourceType);
    }
}