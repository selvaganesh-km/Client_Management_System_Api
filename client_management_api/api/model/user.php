<?php
error_reporting(0);
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
class USERMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {
        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    $result = $this->getUser($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createUser($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getUserDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateuser($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteUser($data, $loginData);
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
    public function getUserDetails($data, $loginData)
    {
        try {
            $responseArray = "";
            $res = array();
            $db = $this->dbConnect();
            $loggedId = $loginData['user_id'];
            $totalRecordCount = $this->getTotalCount($loginData);
            // print_r($totalRecordCount);exit;
            if (empty($data['pageIndex']) && $data['pageIndex'] != 0) {
                throw new Exception("pageIndex should not be empty");
            }
            if (empty($data['dataLength'])) {
                throw new Exception("dataLength should not be empty");
            }
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];
            // $sql = "SELECT id,user_name,email_id,phone,created_date, updated_date FROM tbl_contact_user_info WHERE status = 1  ORDER BY id ASC LIMIT " . $start_index . "," . $end_index . "";
            $sql = "SELECT u.id, u.user_name, u.email_id, u.phone,c.id AS client_id, c.client_name,u.created_by FROM tbl_contact_user_info u INNER JOIN tbl_client_user_map m ON u.id = m.user_id
            INNER JOIN tbl_client c ON m.client_id = c.id WHERE u.status = 1 AND u.created_by =$loggedId  ORDER BY u.id DESC LIMIT $start_index, $end_index";
            // print_r($sql);exit;
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
                    'userData' => $res,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "User details fetched successfully"),

                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(

                    "code" => "404",
                    "message" => "No data found...",

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
    public function getUser($data)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT rl.id,rl.user_name,rl.user_name,rl.email_id,rl.phone,cl.id AS client_id,cl.client_name FROM tbl_client_user_map AS csm JOIN tbl_contact_user_info AS rl JOIN tbl_client AS cl ON csm.client_id = cl.id AND csm.user_id=rl.id WHERE csm.status=1 AND rl.id=$id ";
// print_r($sql);exit;
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                $responseArray = array(
                    'userData' => $data,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "User details fetched successfully"),

                    "result" => $responseArray,
                );
                return $resultArray;
            } else {
                return array(

                    "code" => "404",
                    "message" => "No data found...",

                );
            }
        } catch (Exception $e) {
            // echo "ssdfd";
            throw new Exception($e->getMessage());
        }
    }
    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */

    //ClientUser Creations
    public function createUser($data, $loginData)
    {

        // $userData = $data['userData'];
        // print_r($userData);exit;
        try {
            $db = $this->dbConnect();
            $userData = $data['userData'];

            $user_name = isset($userData['user_name']) ? $userData['user_name'] : "";
            $emailId = isset($userData['emailId']) ? $userData['emailId'] : "";
            $phone = isset($userData['phone']) ? $userData['phone'] : "";

            if (empty($user_name)) {throw new Exception("user_name is required");}
            if (empty($emailId)) {throw new Exception("email_id is required");}
            if (empty($phone)) {throw new Exception("phone is required");}

            if ($userData['user_name'] != $userData['user_name']) {
                throw new Exception("Username not correct!");
            }
            $password = $userData['password'];
            $sql = "SELECT id,user_name,email_id FROM tbl_contact_user_info WHERE  status = 1 AND email_id = '" . $userData['emailId'] . "' OR user_name = '" . $userData['user_name'] . "'";
            // print_r($sql);exit;
            $result = mysqli_query($db, $sql);
            $row_cnt = mysqli_num_rows($result);

            if ($row_cnt > 0) {
                throw new Exception("User already exist");
            }
            if (!empty($userData['user_name'])) {
                $user_name = $userData['user_name'];
            } else {
                $user_name = "";
            }
            // if (!empty($userData['role_id'])) {
            //     $role_id = $userData['role_id'];
            // } else {
            //     $role_id = "";
            // }
            $hashed_password = hash('sha256', hash('sha256', $password));
            $dateNow = date("Y-m-d H:i:s");
            $insertQuery = "INSERT INTO tbl_contact_user_info (user_name, email_id, phone, created_by, created_date) VALUES ('" . $user_name . "','" . $userData['emailId'] . "','" . $userData['phone'] . "','" . $loginData['user_id'] . "','$dateNow')";
            // print_r($insertQuery);exit;
            // if (true) {
            if ($db->query($insertQuery) === true) {
                $lastInsertedId = mysqli_insert_id($db);
                // $this->updateUserRole($lastInsertedId, $role_id);
                $this->updateClientUser($lastInsertedId, $userData['client_id'], $loginData['user_id']);
                $db->close();
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => "200",
                    "status" => "Ok",
                    "message" => "User Created Successfully"),
                "result" => array("lastUserId" => $lastInsertedId),
            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()),
            );
        }
    }

    //Insert the client_id & user_id  to tbl_client_user_map
    private function updateClientUser($userId, $clientId, $createdBy)
    {
        // print_r($userId);print_r($clientId);print_r($createdBy);exit;
        try {

            $db = $this->dbConnect();
            $insertQuery = "INSERT INTO tbl_client_user_map (`client_id`, `user_id`, `created_by`) VALUES ('$clientId','$userId','$createdBy')";
            if ($db->query($insertQuery) === true) {
                $db->close();
                return true;
            }

        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()),
            );
        }
    }

    //UpdateClient function start
    private function updateUserRole($lastInsertedId, $role_id)
    {
        try {
            $db = $this->dbConnect();

            if ($lastInsertedId) {
                $insertQuery = "INSERT INTO tbl_user_role_map (`user_id`, `role_id`, `created_by`) VALUES ('$lastInsertedId', $role_id, '$lastInsertedId') ";
                if ($db->query($insertQuery) === true) {
                    $db->close();
                    return true;
                }
                return false;
            } else {
                throw new Exception("Not able to update role");
            }

        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
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
    private function updateUser($data, $loginData)
    {
// print_r($loginData);exit;
        try {
            $db = $this->dbConnect();
            $data = $data['userData'];
            $validationData = array("id" => $data['id'], "user_name" => $data['user_name'], "email_id" => $data['email_id'], "phone " => $data['phone']);
            $this->validateInputDetails($validationData);
            if (empty($data['id'])) {
                throw new Exception("User ID should not be empty");
            }
            $dateNow = date("Y-m-d H:i:s");
            // $updateQuery = "UPDATE tbl_contact_user_info SET user_name = '" . $data['user_name'] . "', email_id = '" . $data['email_id'] . "',phone = '" . $data['phone'] . "',updated_by = '" . $loginData['id'] . "',updated_date = '$dateNow' WHERE id = " . $data['id'] . "";
            $updateQuery = "UPDATE tbl_contact_user_info SET user_name = '" . $data['user_name'] . "',email_id = '" . $data['email_id'] . "',
        phone = '" . $data['phone'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow',status = 1
        WHERE id = " . $data['id'] . "";

            if ($db->query($updateQuery) === true) {
                $updateClientNameQuery = "UPDATE tbl_client_user_map  SET client_id = '" . $data['client_id'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow',status = 1 WHERE user_id = " . $data['id'];
                $db->query($updateClientNameQuery);

                $db->close();
                $statusCode = "200";
                $statusMessage = "User details updated successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to update User details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            return array(

                "code" => "401",
                "message" => $e->getMessage(),

            );
        }
    }

    private function deleteUser($data)
    {

        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request id is required");
            }
            $sql = "SELECT id FROM tbl_contact_user_info WHERE status = 1 and id =$id";

            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;
            if ($row_cnt == 0) {
                throw new Exception("id " . $id . " not found");
            }
            $deleteQuery = "UPDATE tbl_contact_user_info AS u, tbl_client_user_map AS cum SET u.status = 0,cum.status = 0
            WHERE u.id = cum.user_id AND u.id =" . $id . "";
            // print_r($deleteQuery);exit;

            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "User details deleted successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to delete User details, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
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
    /**
     * Validate function for sale create
     *
     * @param array $data
     * @throws Exception
     * @return multitype:string NULL
     */

    private function getTotalCount($loginData)
    {
        // print_r($loginData);exit;
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_contact_user_info WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
            // print_r($sql);exit;
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

                "code" => "401",
                "message" => $e->getMessage(),

            );
        }
    }
}
