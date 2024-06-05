<?php
require_once "model/domain.php";
require_once "model/login.php";
class DOMAIN extends DOMAINMODEL{
	public function domainCtrl($request, $tokenParms)
	{
		try {
			$loginAuthendicate = new LOGINMODEL();
			$token = $loginAuthendicate->tokenCheck($tokenParms);
			// print_r($token);exit;
			if (!empty ($token)) {
				$response = $this->processList($request, $token);
				echo $this->json($response);
				exit();
			} else {
				throw new Exception("Unauthorized Login");
			}
		} catch (Exception $e) {
			echo $this->json(array(
					"result" => "401",
					"message" => $e->getMessage()));
			exit();
		}
	}
}
$classActivate = new DOMAIN();
// echo"fir";exit;
$classActivate->domainCtrl($data, $token);
// echo"fir";exit; 

?>