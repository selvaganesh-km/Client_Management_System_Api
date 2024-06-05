<?php
error_reporting(0);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class SERVICEOFFERMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    $result = $this->getServiceDetails($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
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
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];
            if ($data['service_name'] != "") {
                $service_name = " and service_name = '" . $data['service_name'] . "'";
            } else {
                $service_name = "";
            }
            if ($data["description"] != "") {
                $description = " and description = '" . $data['description'] . "'";
                ;
            } else {
                $description = "";
            }
            $sql = "SELECT id,service_name,description, created_by,created_date, updated_by, updated_date, status FROM tbl_service_offered   WHERE status = 1 ". $service_name . $description ." ORDER BY id ASC LIMIT " . $start_index . "," . $end_index . "";
            // echo $sql;
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    array_push($res, $data);
                }
                $responseArray = array(
                    "pageIndex" => $start_index,
                    "dataLength" => $end_index,
                    "totalRecordCount" => $totalRecordCount,
                    'clientData' => $res,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Service details fetched successfully"
                    ),
                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found..."
                    ),
                );
            }
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
    private function getTotalCount()
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_service_offered WHERE status = 1";
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
