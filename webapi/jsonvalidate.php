<?php
class InvalidDataException extends Exception{}

class JSONValidationRule {
	public $name;
	public function __construct($name) {
		$this->name = $name;
	}
	public function Validate($json) {
	}
}
class JSONValidationRuleInteger extends JSONValidationRule {
	public function __construct($name) {
		parent::__construct($name);
	}
	
	public function Validate($json) {
		if (isset($json->{$this->name})) {
			$val = $json->{$this->name};
			
			if ($val != "") {
				if (is_numeric($val)) {
					$ival = (int)$val;
					if("$ival" != "$val") {
						throw new InvalidDataException("{$this->name} is set but not an integer $ival == $val");
					}
				} else {
					throw new InvalidDataException("{$this->name} is set but not numeric");
				}
			} else {
                throw new InvalidDataException("{$this->name} is set but is empty so not usable {$this->typename}");
			}
		} else {
			throw new InvalidDataException("{$this->name} is not set (expecting integer)");
		}
	}
}

class JSONValidationRuleFloat extends JSONValidationRule {
        public function __construct($name) {
            parent::__construct($name);
        }
		
        public function Validate($json) {
            if (isset($json->{$this->name})) {
				$val = $json->{$this->name};
		
				if ($val != "") {
					if (!is_numeric($val)) {
							throw new InvalidDataException("{$this->name} is set but not numeric");
					}
				} else {
						 throw new InvalidDataException("{$this->name} is set but is empty so not usable {$this->typename}");
				}
			} else {
					throw new InvalidDataException("{$this->name} is not set (expecting float)");
			}
        }
}

class JSONValidationRuleRegex extends JSONValidationRule {
	public $regexp;
	public $typename;

	public function __construct($name,$typename,$regexp) {
		parent::__construct($name);
		$this->typename = $typename;
		$this->regexp = $regexp;
	}
	
	public function Validate($json) {
		if (isset($json->{$this->name})) {
			
			$val = $json->{$this->name};
			if ($val != "") {
				if (preg_match($this->regexp,$val) != 1) {
					 throw new InvalidDataException("{$this->name} is set but not a valid {$this->typename}");
				} 
			} else {
				throw new InvalidDataException("{$this->name} is set but is empty so not usable {$this->typename}");
			}
		} else {
				throw new InvalidDataException("{$this->name} is not set (expecting {$this->typename})");
		}
    }
}

class JSONValidationRuleEmail extends JSONValidationRuleRegex {
	public function __construct($name) {
		parent::__construct($name,"email address","/^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/");
	}
}

class JSONValidationRuleTenantName extends JSONValidationRuleRegex {
	public function __construct($name) {
		parent::__construct($name,"tenant name (only alpha,num,_,- and . allowed)","/^([a-zA-Z0-9_\-\.]+)$/");
	}
}
class JSONValidationRuleGenericLabel extends JSONValidationRuleRegex {
	public function __construct($name) {
		parent::__construct($name,"label (only alpha,num,_,- and . allowed)","/^([a-zA-Z0-9_\-\.]+)$/");
	}
}

class JSONValidationRulePassword extends JSONValidationRuleRegex {
	public function __construct($name) {
		parent::__construct($name,"password (quotes, backslashes and dollars not allowed)","/^([^\"\'\\\\$]+)$/");
	}
}
class JSONValidationRuleFQDN extends JSONValidationRuleRegex {
	public function __construct($name) {
		parent::__construct($name,"fqdn (only alpha,num,_,- and . allowed)","/^([a-zA-Z0-9_\-\.]+)$/");
	}
}
class JSONValidationRuleHash extends JSONValidationRuleRegex {
	public function __construct($name) {
		parent::__construct($name,"fqdn (only alpha,num and - allowed)","/^([a-zA-Z0-9\-]+)$/");
	}
}
?>