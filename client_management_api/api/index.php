<?php
//postman header file
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: *");
// ini_set ( "display_errors", 1 );
include "include/config.php";

// Turn off error reporting
error_reporting(1);

class APIREQUESTPROCESSING
{
	/*
	 * Process Client Request
	 * Public method for access api.
	 * This method dynmically call the method based on the query string
	 *
	 */
	public function apiRequestBuild()
	{

		$urlParam = $_GET['url'];
		$param = explode('/', $urlParam);
		$apiRequest = $param[0];
		$request = isset ($apiRequest) ? (trim(@$apiRequest)) : "";
		$headers = apache_request_headers();
		if (REQUESTMETHOD != "GET" && REQUESTMETHOD != "DELETE") {
			$data = "";
			$request_body = file_get_contents('php://input');
			$data = json_decode($request_body, true);
		} else {
			if ($headers['fromDate'] && $headers['toDate']) {
				$data = ["fromDate" => $headers['fromDate'], "toDate" => $headers['toDate']];
			} else {
				// Get request payload data from API
				$data = $param;
				// print_r($data);exit;?
			}

		}
		$token = isset ($headers['Authorization']) ? (trim(@$headers['Authorization'])) : "";
		$token = explode(' ', $token)[1];
		// echo $token;exit;
		if ($request != "") {
			// Include request controller
			require_once "controller/" . $request . ".php";
		}
	}
}
$apiRequstProcess = new APIREQUESTPROCESSING();
// print_r($param); exit;
$apiRequstProcess->apiRequestBuild();
?>