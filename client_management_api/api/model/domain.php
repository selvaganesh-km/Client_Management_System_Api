<?php
// echo"ggg";exit;
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
//  echo"ggg";exit;
error_reporting(0);

class DOMAINMODEL extends APIRESPONSE
{

    private function processMethod($data, $loginData)
    {
        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    if ($urlParam[2] == "clientdroplist") {
                        $result = $this->getClientDrop($data);
                    } elseif ($urlParam[2] == "servicedroplist") {
                        $result = $this->getServiceDrop($data);
                    } else {
                        $result = $this->getDomainbyId($data, $loginData);
                    }
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createDomain($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getDomainDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateDomain($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteDomain($data);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            default:
                $result = $this->handle_error();
                return $result;
                break;
        }
    }

    private function dbConnect()
    {
        $conn = new DBCONNECTION();
        $db = $conn->connect();
        return $db;
    }

    public function getClientDrop($data)
    {
        try {
            // echo"hg"; exit;
            $responseArray = "";
            $clientDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, client_name FROM tbl_client  WHERE status=1";
            $result = $db->query($queryService);
            $row_cnt = mysqli_num_rows($result);
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($clientDrop, $data);
            }

            $responseArray = array(
                "totalRecordCount" => $row_cnt,
                "clientData" => $clientDrop,
            );
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Client details fetched successfully",
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getServiceDrop($data)
    {
        try {
            // echo"hg"; exit;
            $responseArray = "";
            $serviceDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, service_name FROM tbl_service_provider  WHERE status=1";
            $result = $db->query($queryService);
            // print_r($result); exit;
            $row_cnt = mysqli_num_rows($result);
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($serviceDrop, $data);
            }

            $responseArray = array(
                "totalRecordCount" => $row_cnt,
                "serviceData" => $serviceDrop,
            );
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Service details fetched successfully",
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getDomainDetails($data, $loginData)
    {

        try {
            $responseArray = "";
            $res = array();
            $db = $this->dbConnect();
            $loginUserId = $loginData['user_id'];
            $totalRecordCount = $this->getTotalCount($loginData);
            if (empty($data['page_index']) && $data['page_index'] != 0) {
                throw new Exception("page_index should not be empty");
            }
            if (empty($data['data_length'])) {
                throw new Exception("data_length should not be empty");
            }
            $start_index = $data['page_index'] * $data['data_length'];
            $end_index = $data['data_length'];


            // $responseArray = "";
            // // $res = array();
            // $db = $this->dbConnect();
            // $loggedId = $loginData['user_id'];
            // $totalRecordCount = $this->getTotalCount($loginData);
            // if (empty($data['pageIndex']) && $data['pageIndex'] != 0) {
            //     throw new Exception("pageIndex should not be empty");
            // }
            // if (empty($data['dataLength'])) {
            //     throw new Exception("dataLength should not be empty");
            // }
            // $start_index = $data['pageIndex'] * $data['dataLength'];
            // $end_index = $data['dataLength'];



            $sql = "SELECT d.id, d.domain_name, dcs.client_id, c.client_name, d.a_record, d.purchase_date, d.expiry_date, sp.service_name, sp.service_url, dcs.service_provider_id, n.notification_type, n.notification_data, n.notification_prior, n.notification_interval
                        FROM tbl_domain_client_service_map dcs LEFT JOIN tbl_domain d ON dcs.domain_id = d.id LEFT JOIN tbl_client c ON dcs.client_id = c.id LEFT JOIN tbl_service_provider sp ON dcs.service_provider_id = sp.id LEFT JOIN tbl_notification n ON dcs.notification_id = n.id WHERE
                        dcs.created_by = '{$loginUserId}' AND dcs.status = 1 ORDER BY id DESC LIMIT $start_index, $end_index";
            // print_r($sql);exit;
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if (!$result) {
                throw new Exception("Error executing query: " . $db->error);
            }
            if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    $res[] = array(
                        "id" => $data['id'],
                        "domain_name" => $data['domain_name'],
                        "client_id" => $data['client_id'],
                        "client_name" => $data['client_name'],
                        "a_record" => $data['a_record'],
                        "purchase_date" => $data['purchase_date'],
                        "expiry_date" => $data['expiry_date'],
                        "service_provider_id" => $data['service_provider_id'],
                        "service_name" => $data['service_name'],
                        "service_url" => $data['service_url'],
                        "notification" => array(
                            "notification_type" => $data['notification_type'],
                            "notification_data" => $data['notification_data'],
                            "notification_prior" => $data['notification_prior'],
                            "notification_interval" => $data['notification_interval']));
                }
                $responseArray = array(
                    "pageIndex" => $start_index,
                    "dataLength" => $end_index,
                    "totalRecordCount" => $totalRecordCount,
                    "domainData" => $res);
            }
            if (!empty($responseArray)) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Domain details fetched successfully",
                    ),
                    "result" => $responseArray);
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getDomainbyId($data)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();

            $sql = "SELECT d.id, d.domain_name,dcs.client_id, c.client_name, d.a_record, d.purchase_date, d.expiry_date, sp.service_name, sp.service_url, dcs.service_provider_id, n.notification_type, n.notification_data, n.notification_prior, n.notification_interval
            FROM tbl_domain_client_service_map dcs LEFT JOIN tbl_domain d ON dcs.domain_id = d.id LEFT JOIN tbl_client c ON dcs.client_id = c.id LEFT JOIN tbl_service_provider sp ON dcs.service_provider_id = sp.id LEFT JOIN tbl_notification n ON dcs.notification_id = n.id WHERE dcs.id = $id";

            $result = $db->query($sql);
            if (!$result) {
                throw new Exception("Error executing query: " . $db->error);
            }
            if ($result->num_rows > 0) {
                while ($data = $result->fetch_assoc()) {
                    $res = array(
                        "id" => $data['id'],
                        "domain_name" => $data['domain_name'],
                        "client_id" => $data['client_id'],
                        "client_name" => $data['client_name'],
                        "a_record" => $data['a_record'],
                        "purchase_date" => $data['purchase_date'],
                        "expiry_date" => $data['expiry_date'],
                        "service_provider_id" => $data['service_provider_id'],
                        "service_name" => $data['service_name'],
                        "service_url" => $data['service_url'],
                        "notification" => array(
                            "notification_type" => $data['notification_type'],
                            "notification_data" => $data['notification_data'],
                            "notification_prior" => $data['notification_prior'],
                            "notification_interval" => $data['notification_interval']));
                }
                $responseArray = $res;
            }
            if (!empty($responseArray)) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Domain details fetched successfully",
                    ),
                    "result" => $responseArray);
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function createDomain($data, $loginData)
    {
        try {
            // echo"ggg";exit;
            // print_r($data);exit;
            $domainData = array(
                "domain_name" => $data['domainData']['domain_name'],
                "client_id" => $data['domainData']['client_id'],
                "purchase_date" => $data['domainData']['purchase_date'],
                "expiry_date" => $data['domainData']['expiry_date'],
                "service_provider_id" => $data['domainData']['service_provider_id'],
            );
            $this->validateInputDetails($domainData);
            $db = $this->dbConnect();
            // echo"fir";exit;
            $dateNow = date("Y-m-d");

            $checkDomainQuery = "SELECT id FROM tbl_domain WHERE domain_name = '{$domainData['domain_name']}' AND status=1";
            $result = $db->query($checkDomainQuery);
            if ($result && $result->num_rows > 0) {
                throw new Exception("Domain '{$domainData['domain_name']}' already exists");
            } else {

                $notificationData = $data['domainData']['notification'];
                $this->validateInputDetails($notificationData);
                $notificationInsertQuery = "INSERT INTO tbl_notification (notification_type, notification_data, notification_prior, notification_interval, created_by, created_date)
            VALUES ('{$notificationData['notification_type']}', '{$notificationData['notification_data']}', '{$notificationData['notification_prior']}', '{$notificationData['notification_interval']}','{$loginData['user_id']}', '$dateNow')";
                // print_r($notificationInsertQuery); exit;
                $db->query($notificationInsertQuery);
                $notificationDataId = mysqli_insert_id($db);

                $domainInsertQuery = "INSERT INTO tbl_domain (domain_name, a_record, purchase_date, expiry_date, status, created_by, created_date)
            VALUES ('{$domainData['domain_name']}','{$data['domainData']['a_record']}', '{$domainData['purchase_date']}', '{$domainData['expiry_date']}', '1', '{$loginData['user_id']}', '$dateNow')";
                // print_r($domainInsertQuery); exit;
                $db->query($domainInsertQuery);
                $lastInsertedId = mysqli_insert_id($db);
                // print_r($lastInsertedId); exit;

                $domainClientServiceMapInsertQuery = "INSERT INTO tbl_domain_client_service_map (domain_id, client_id, service_provider_id, notification_id, created_by, created_date)
            VALUES ('$lastInsertedId', '{$domainData['client_id']}', '{$domainData['service_provider_id']}', '$notificationDataId', '{$loginData['user_id']}', '$dateNow')";
                $db->query($domainClientServiceMapInsertQuery);
                // print_r($domainClientServiceMapInsertQuery); exit;

                $db->close();
                return array(
                    "apiStatus" => array(
                        "code" => 200,
                        "status" => "Ok",
                        "message" => "Domain Created Successfully",
                    ),
                    "result" => array(
                        "lastUserId" => $lastInsertedId));
            }
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => 401,
                    "status" => "Error",
                    "message" => $e->getMessage()));
        }
    }

    private function updateDomain($data, $loginData)
    {
        try {
            $domainData = array(
                "id" => $data['domainData']['id'],
                "domain_name" => $data['domainData']['domain_name'],
                "client_id" => $data['domainData']['client_id'],
                "purchase_date" => $data['domainData']['purchase_date'],
                "expiry_date" => $data['domainData']['expiry_date'],
                "service_provider_id" => $data['domainData']['service_provider_id'],
            );

            $requiredFields = ['domain_name', 'client_id', 'purchase_date', 'expiry_date', 'service_provider_id'];
            foreach ($requiredFields as $field) {
                if (empty($domainData[$field])) {
                    return array(
                        "apiStatus" => array(
                            "code" => "400",
                            "message" => "'$field' cannot be empty",
                        ),
                    );
                }
            }

            // Establish database connection
            $db = $this->dbConnect();

            // Check if domain ID exists
            $checkIdQuery = "SELECT COUNT(*) AS count FROM tbl_domain WHERE id = '" . $data['domainData']['id'] . "' AND status = 1";
            $result = $db->query($checkIdQuery);
            $rowCount = $result->fetch_assoc()['count'];

            // If ID doesn't exist, return error
            if ($rowCount == 0) {
                $db->close();
                return array(
                    "apiStatus" => array(
                        "code" => "400",
                        "message" => "Domain ID does not exist",
                    ),
                );
            }

            // Proceed with updating domain details
            $dateNow = date("Y-m-d");

            $notificationData = $data['domainData']['notification'];
            $this->validateInputDetails($notificationData);
            $getNotificationIdQuery = "SELECT notification_id FROM tbl_domain_client_service_map WHERE domain_id = '{$domainData['id']}'";
            $notificationResult = $db->query($getNotificationIdQuery);
            if ($notificationResult->num_rows > 0) {
                $notificationRow = $notificationResult->fetch_assoc();
                $notificationId = $notificationRow['notification_id'];

                $updateNotificationQuery = "UPDATE tbl_notification SET " . "notification_type = '{$data['domainData']['notification']['notification_type']}', " . "notification_data = '{$data['domainData']['notification']['notification_data']}', " . "notification_prior = '{$data['domainData']['notification']['notification_prior']}', " . "notification_interval = '{$data['domainData']['notification']['notification_interval']}' " . "WHERE id = '{$notificationId}'";
                $db->query($updateNotificationQuery);

                $updateDomainQuery = "UPDATE tbl_domain SET " . "domain_name = '{$domainData['domain_name']}', " . "purchase_date = '{$domainData['purchase_date']}', " . "expiry_date = '{$domainData['expiry_date']}', " . "updated_by = '{$loginData['user_id']}', " . "updated_date = '{$dateNow}' " . "WHERE id = '{$domainData['id']}'";
                $db->query($updateDomainQuery);

                $updateDomainClientServiceQuery = "UPDATE tbl_domain_client_service_map SET " . "client_id =  '{$domainData['client_id']}'," . "service_provider_id = '{$domainData['service_provider_id']}', " . "updated_by = '{$loginData['user_id']}', " . "updated_date = '{$dateNow}' " . "WHERE domain_id = '{$domainData['id']}'";
                $db->query($updateDomainClientServiceQuery);

                $db->close();
                return array(
                    "apiStatus" => array(
                        "code" => 200,
                        "message" => "Domain details updated successfully",
                    ),
                );
            } else {
                $db->close();
                return array(
                    "apiStatus" => array(
                        "code" => 400,
                        "message" => "No notification found for the domain",
                    ),
                );
            }
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => 500,
                    "message" => $e->getMessage(),
                ),
            );
        }
    }

    private function deleteDomain($data)
    {
        try {
            $id = $data[2];
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }
            $db = $this->dbConnect();
            $checkDomainQuery = "SELECT domain_id FROM tbl_domain_client_service_map WHERE domain_id = '{$id}' AND status = 1";
            $result = $db->query($checkDomainQuery);
            if (!$result || $result->num_rows === 0) {
                throw new Exception("no data found");
            } else {
                $deleteDomainQuery = "UPDATE tbl_domain_client_service_map AS cu
         INNER JOIN tbl_domain AS u ON cu.domain_id = u.id
         INNER JOIN tbl_notification AS n ON cu.notification_id = n.id
         SET cu.status = 0, u.status = 0, n.status = 0
         WHERE u.id =" . $id;

                $db->query($deleteDomainQuery);
                $db->close();

                $statusCode = "200";
                $statusMessage = "Domain details deleted successfully";

                $resultArray = array(
                    "apiStatus" => array(
                        "code" => $statusCode,
                        "message" => $statusMessage,
                    ),
                );
                return $resultArray;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_domain WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            return $row_cnt;
        } catch (Exception $e) {
            return array(
                "result" => "401",
                "message" => $e->getMessage(),
            );
        }
    }

    public function validateInputDetails($domainData)
    {
        foreach ($domainData as $key => $value) {
            if (empty($value) || trim($value) == "") {
                throw new Exception($key . " should not be empty!");
            }
        }
    }

    public function validateInputDateFormate($validationDateData)
    {

        foreach ($validationDateData as $key => $value) {
            if ($this->validateDate($value)) {

            } else {
                throw new Exception($key . " invalid date format!");
            }
        }
    }

    public function validateDate($date, $format = "Y-m-d")
    {
        $dateTimeObj = DateTime::createFromFormat($format, $date);
        return $dateTimeObj && $dateTimeObj->format($format) === $date;
    }

    private function handle_error()
    {

    }

    public function processList($request, $token)
    {
        try {
            $responseData = $this->processMethod($request, $token);
            $result = $this->response($responseData);
            return $responseData;
        } catch (Exception $e) {
            return array(

                "code" => "401",
                "message" => $e->getMessage());
        }
    }
}
