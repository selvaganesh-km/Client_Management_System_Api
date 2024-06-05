<?php
error_reporting(0);
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class SERVICEPROVIDERMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    $result = $this->getService($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createService($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getServiceDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateService($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteService($data, $loginData);
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
    // Initiate db connection
    private function dbConnect()
    {
        $conn = new DBCONNECTION();
        $db = $conn->connect();
        return $db;
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function getServiceDetails($data, $loginData)
    {
        try {
            $responseArray = "";
            $res = array();
            $db = $this->dbConnect();
            $totalRecordCount = $this->getTotalCount($loginData);
            if (empty($data['pageIndex']) && $data['pageIndex'] != 0) {
                throw new Exception("page_index should not be empty");
            }
            if (empty($data['dataLength'])) {
                throw new Exception("data_length should not be empty");
            }
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];
            $sql = "SELECT id,service_name,service_url,password FROM tbl_service_provider WHERE status = 1 and created_by = " . $loginData['user_id'] . " ORDER BY id DESC LIMIT " . $start_index . "," . $end_index . "";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    // echo json_encode($data);

                    array_push($res, $data);
                }

                $responseArray = array(
                    "pageIndex" => $start_index,
                    "dataLength" => $end_index,
                    "totalRecordCount" => $totalRecordCount,
                    'serviceData' => $res,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus"=>array(
                    "code" => "200",
                    "message" => "Service details fetched successfully"),

                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus"=>array(
                    "code" => "404",
                    "message" => "No data found..."),

                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function getService($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT id,service_name,service_url, password FROM tbl_service_provider WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $responseArray = array(
                    'ServiceData' => $data,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus"=>array(
                    "code" => "200",
                    "message" => "Service details fetched successfully"),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus"=>array(
                    "code" => "404",
                    "message" => "No data found..."),

                );
            }
        } catch (Exception $e) {
            echo "ssdfd";
            throw new Exception($e->getMessage());
        }
    }
    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function createService($data, $loginData)
    {
        try {
            $db = $this->dbConnect();
            $validationData = array("service_name" => $data['service_name'], "service_url" => $data['service_url']);            
            $this->validateInputDetails($validationData);
            $password = $data['password'];

            $sql = "SELECT id FROM tbl_service_provider	WHERE status = 1 and created_by = " . $loginData['user_id'] . " and service_name ='" . $data['service_name'] . "'";

            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);

            if ($row_cnt != 0) {
                throw new Exception("Servicename " . $data['service_name'] . " already found");
            }
            $dateNow = date("Y-m-d H:i:s");
			$hashed_password = hash('sha256', hash('sha256', $password));

            $insertQuery = "INSERT INTO tbl_service_provider (service_name,service_url,password,status,created_by, created_date) VALUES ('" . $data['service_name'] . "','" . $data['service_url'] . "','" . $hashed_password . "','" . '1' . "','" . $loginData['user_id'] . "','$dateNow')";
            // print_r($insertQuery);
           
            if ($db->query($insertQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Service details created successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to create service details, please try again later";
            }
            $resultArray = array(
                "apiStatus"=> array(
                "code" => $statusCode,
                "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus"=>array(
                "code" => "401",
                "message" => $e->getMessage()),

            );
        }
    }

    /**
     * Put/Update a Sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function updateService($data, $loginData)
    {
// print_r($loginData);exit;
        try {
            $db = $this->dbConnect();
            $validationData = array("id" => $data['id'], "service_name" => $data['service_name'], "service_url" => $data['service_url']);
            // print_r($validationData);exit;
            
            $this->validateInputDetails($validationData);
            $dateNow = date("Y-m-d H:i:s");
            $updateQuery = "UPDATE tbl_service_provider SET service_name = '" . $data['service_name'] . "', service_url = '" . $data['service_url'] ."',password = '" . $data['password'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow' WHERE id = " . $data['id'] . "";
            if ($db->query($updateQuery) === true) {
            // print_r($updateQuery);

                $db->close();
                $statusCode = "200";
                $statusMessage = "Service details updated successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to update Service details, please try again later";
            }
            $resultArray = array(
                "apiStatus"=>array(
                "code" => $statusCode,
                "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus"=>array(
                "code" => "401",
                "message" => $e->getMessage()),

            );
        }
    }
    private function deleteService($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request id is required");
            }
            $sql = "SELECT id FROM tbl_service_provider	WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;
            if ($row_cnt == 0) {
                throw new Exception("id " . $id . " not found");
            }
            $deleteQuery = "UPDATE tbl_service_provider set status=0 WHERE id = " . $id . "";
            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Service details deleted successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to delete Service details, please try again later";
            }
            $resultArray = array(
                "apiStatus"=>array(
                "code" => $statusCode,
                "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        }
    }
    public function validateInputDetails($validationData)
    {
        foreach ($validationData as $key => $value) {
            if (empty($value) || trim($value) == "") {
                throw new Exception($key . " should not be empty!");
            }
        }
    }

    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_service_provider WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            return $row_cnt;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "result" => "401",
                "message" => $e->getMessage(),
            ));
        }
    }
    private function getTotalPages($dataCount)
    {
        try {
            $pages = null;
            if (MAX_LIMIT) {
                $pages = ceil((int) $dataCount / (int) MAX_LIMIT);
            } else {
                $pages = count($dataCount);
            }
            return $pages;
        } catch (Exception $e) {
            return array(
                "result" => "401",
                "message" => $e->getMessage(),
            );
        }
    }
    // Unautherized api request
    private function handle_error()
    {
    }
    /**
     * Function is to process the crud request
     *
     * @param array $request
     * @return array
     */
    public function processList($request, $token)
    {
        try {
            $responseData = $this->processMethod($request, $token);
            $result = $this->response($responseData);
            return $responseData;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                "code" => "401",
                "message" => $e->getMessage()

            ));
        }
    }
}
