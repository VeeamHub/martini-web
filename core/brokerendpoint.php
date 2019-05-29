<?php
require_once('database.php');

class BrokerDatabaseException extends Exception {
    public function __construct(Exception $previous = null, $code = 0) {
        parent::__construct("Broker Database exception {$previous->getMessage()}", $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
class BrokerEndpointException extends Exception {
    public function __construct(Exception $previous = null, $code = 0) {
        parent::__construct("Broker Endpoint Exception", $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class BrokerEndpoint {
	public $port;
	
	public function __construct($port) {
		$this->port = $port;
	}
}

function getBrokerendpoints() {
	$db = getDBConnection();
	$list = [];

	try {
		$stmt = $db->prepare("select id, port from martini_endpoint");
		$stmt->execute();
		$result = $stmt->fetchAll();

		foreach ($result as $row) {
			array_push($list,(new BrokerEndpoint($row["port"])));
		}

	} catch (PDOException $ex) {
	      throw new TenantDatabaseException($ex);
	}
	return $list;
}

function addbrokerendpoint($port) {
	$db = getDBConnection();
	$db->beginTransaction();
	
	try {
		$stmt = $db->prepare("select id from martini_endpoint where port = ?");
		$stmt->execute(array($port));

        if ($row = $stmt->fetch()) {
			throw new BrokerEndpointException();
		} else {
			$stmt = $db->prepare("insert into  martini_endpoint(port,validuntil,pid,tenantid) values(?,0,0,0) ");
			$result = $stmt->execute(array($port));
			error_log("adding port $result");
		}
	} catch (PDOException $ex) {
	      throw new TenantDatabaseException($ex);
	} finally {
		$db->commit();
	}
}

function deletebrokerendpoint($port) {
	$db = getDBConnection();
	$db->beginTransaction();

	try {
		$stmt = $db->prepare("select id from martini_endpoint where port = ?");
		$stmt->execute(array($port));
	
		if ($row = $stmt->fetch()) {
			$stmt = $db->prepare("delete from martini_endpoint where id = ?");
			$stmt->execute(array($row['id']));
			$db->commit();
		} else {
			$db->commit();

			throw new BrokerEndpointException();
		}
	} catch (PDOException $ex) {
		throw new TenantDatabaseException($ex);
	}

	error_log("deleting $port");
}
?>