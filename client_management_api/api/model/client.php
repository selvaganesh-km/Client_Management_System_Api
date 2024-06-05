<?php
error_reporting(1);

require_once "include/apiResponseGenerator.php";
require_once "model/user.php";
require_once "include/dbConnection.php";

class CLIENTMODEL extends APIRESPONSE
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
                        return $result;
                    }if ($urlParam[2] == "serviceofferedlist") {
                        $result = $this->getServiceDrop($data);
                        return $result;
                    } else {
                        $result = $this->getClient($data, $loginData);
                        return $result;
                    }

                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createClient($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getClientDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateClient($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteClient($data, $loginData);
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
    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */

    public function getClientDrop($data)
    {

        try {
            $responseArray = "";
            $clientDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, client_name FROM tbl_client WHERE status=1";
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
                // print_r($resultArray);exit;
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
            $responseArray = "";
            $serviceDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, service_name,path FROM tbl_service_offered WHERE status=1";
            $result = $db->query($queryService);
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

    //ListClientDetails function start
    public function getClientDetails($data, $loginData)
    {
        try {
            $db = $this->dbConnect();
            $totalRecordcount = $this->getTotalCount($loginData);
            if ($data['pageIndex'] == "" || $data['dataLength'] == "") {
                throw new Exception("pageIndex and dataLength should not be empty!");
            }

            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];

            // Fetch tenant details along with image data
            $queryServiceTenant = "SELECT tn.id, tn.client_name, tn.email, tn.address, tn.phone, tn.img_id,
                                ti.original_file_name, ti.altered_file_name, ti.path
                                FROM tbl_client AS tn
                                LEFT JOIN tbl_image AS ti ON tn.img_id = ti.id AND ti.status=1
                                WHERE tn.status = 1 AND tn.created_by = " . $loginData['user_id'] . "
                                ORDER BY tn.id DESC
                                LIMIT " . $start_index . ", " . $end_index;
// print_r($queryServiceTenant);exit;
            $resultTenant = $db->query($queryServiceTenant);
            $row_cnt_tenant = mysqli_num_rows($resultTenant);

            // Fetch user and service details for each tenant
            $clientData = array();
            while ($client = mysqli_fetch_array($resultTenant, MYSQLI_ASSOC)) {
                $userData = null;
                $imageData = null;
                $serviceData = array();

                // Fetch user details
                $queryServiceUsers = "SELECT u.id, u.user_name, u.email_id AS user_email, u.phone AS user_phone
                                    FROM tbl_client_user_map AS tum
                                    JOIN tbl_contact_user_info AS u ON tum.user_id = u.id
                                    WHERE tum.status = 1 AND u.status = 1 AND tum.client_id = " . $client['id'] . " AND u.created_by = " . $loginData['user_id'];
// print_r($queryServiceUsers);exit;

                $resultUsers = $db->query($queryServiceUsers);
                if ($resultUsers) {
                    $userData = mysqli_fetch_array($resultUsers, MYSQLI_ASSOC);
                }

                // Fetch service details
                $queryService = "SELECT s.id AS service_id, s.service_name,s.path
                                FROM tbl_client_service_map AS csm
                                JOIN tbl_service_offered AS s ON csm.service_id = s.id
                                WHERE csm.client_id = " . $client['id'] . " AND csm.status = 1";

                $resultService = $db->query($queryService);
                while ($service = mysqli_fetch_array($resultService, MYSQLI_ASSOC)) {
                    $serviceData[] = $service;
                }
                if ($client['img_id'] !== null || $client['original_file_name'] !== null || $client['altered_file_name'] !== null || $client['path'] !== null) {
                    $imageData = array(
                        'id' => $client['img_id'],
                        'original_file_name' => $client['original_file_name'],
                        'altered_file_name' => $client['altered_file_name'],
                        'path' => $client['path'],
                    );
                }

                $clientDetails = array(
                    'id' => $client['id'],
                    'client_name' => $client['client_name'],
                    'email' => $client['email'],
                    'phone' => $client['phone'],
                    'address' => $client['address'],
                    'imgData' => $imageData,
                    'userData' => $userData,
                    'serviceData' => $serviceData,
                );

                array_push($clientData, $clientDetails);
            }

            $responseArray = array(
                "pageIndex" => $data['pageIndex'],
                "dataLength" => $data['dataLength'],
                "totalRecordCount" => $totalRecordcount,
                'clientData' => $clientData,
            );

            $resultArray = array(
                "apiStatus" => array(
                    "code" => ($row_cnt_tenant > 0) ? "200" : "404",
                    "message" => ($row_cnt_tenant > 0) ? "Client with user and service details fetched successfully" : "No data found...",
                ),
                "result" => $responseArray,
            );

            return $resultArray;

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    //ListClientDetails function end

    /**
     * Log create For Login
     *
     * @param string $message
     * @param string $userName
     * @throws Exception
     */
    public function loginLogCreate($message, $userName, $dir)
    {
        try {
            $fp = fopen(LOG_LOGIN, "a");
            $file = $dir;
            fwrite($fp, "" . "\t" . Date("r") . "\t$file\t$userName\t$message\r\n");
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }

    //This function used for tbl_client_service_map and get the service for included ID
    public function getClientService($userId)
    {
        // print_r($userId);exit;
        try {
            $db = $this->dbConnect();
            $queryService = "SELECT rl.id,rl.service_name,rl.description,rl.path FROM tbl_client_service_map as csm
            JOIN tbl_service_offered as rl ON csm.service_id=rl.id
            WHERE csm.client_id = '$userId' AND rl.status=1 and csm.status=1";
            // echo $queryService;exit;
            $result = $db->query($queryService);

            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;exit;
            if ($row_cnt > 0) {
            }
            $service = array();
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($service, $data);
            }
            // $service = array('id' => $data['id'],'serviceName' => $data['service_name'], 'description' =>$data['description']);
            return $service;
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */

    //GetByIdClient function start
    public function getClient($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($id)) {
                throw new Exception("Bad request");
            }

            // Fetch client details
            // $query = "SELECT cl.id, cl.client_name, cl.email, cl.phone, cl.address, cl.img_id
            //     FROM tbl_client AS cl
            //     WHERE cl.status = 1 AND cl.id = ?";

            // $stmt = $db->prepare($query);
            // $stmt->bind_param("i", $id);
            // $stmt->execute();
            // $clientResult = $stmt->get_result();
            $query = "SELECT cl.id, cl.client_name, cl.email, cl.phone, cl.address, cl.img_id
        FROM tbl_client AS cl
        WHERE cl.status = 1 AND cl.id = $id";

            $clientResult = $db->query($query);
            if ($clientResult->num_rows > 0) {
                $clientData = $clientResult->fetch_assoc();

                // Fetch associated user details
                // $userQuery = "SELECT u.id, u.user_name, u.email_id, u.phone
                // FROM tbl_client_user_map AS csm
                // JOIN tbl_contact_user_info AS u ON csm.user_id = u.id
                // WHERE csm.status = 1 AND csm.client_id = ?";

                // $stmt = $db->prepare($userQuery);
                // $stmt->bind_param("i", $id);
                // $stmt->execute();
                // $userResult = $stmt->get_result();
                $userQuery = "SELECT u.id, u.user_name, u.email_id, u.phone
                FROM tbl_client_user_map AS csm
                JOIN tbl_contact_user_info AS u ON csm.user_id = u.id
                WHERE csm.status = 1 AND csm.client_id = $id";

                $userResult = $db->query($userQuery);
                $userData = ($userResult->num_rows > 0) ? $userResult->fetch_assoc() : null;

                // Fetch associated service details
                // $serviceQuery = "SELECT s.id AS service_id, s.service_name,s.path
                // FROM tbl_client_service_map AS csm
                // JOIN tbl_service_offered AS s ON csm.service_id = s.id
                // WHERE csm.client_id = ?";
                // // print_r($serviceQuery);exit;
                // $stmt = $db->prepare($serviceQuery);
                // $stmt->bind_param("i", $id);
                // $stmt->execute();
                // $serviceResult = $stmt->get_result();
                $serviceQuery = "SELECT s.id AS service_id, s.service_name, s.path
                FROM tbl_client_service_map AS csm
                JOIN tbl_service_offered AS s ON csm.service_id = s.id
                WHERE csm.status = 1 AND csm.client_id = $id";

                $serviceResult = $db->query($serviceQuery);
                $serviceData = [];
                while ($row = $serviceResult->fetch_assoc()) {
                    $serviceData[] = $row;
                }

                $imageData = null;
                if ($clientData['img_id'] !== null || $clientData['original_file_name'] !== null || $clientData['altered_file_name'] !== null || $clientData['path'] !== null) {
                    $imageData = array(
                        'id' => $clientData['img_id'],
                        'original_file_name' => $clientData['original_file_name'],
                        'altered_file_name' => $clientData['altered_file_name'],
                        'path' => $clientData['path'],
                    );
                }

                // Prepare response data
                $responseData = array(
                    'id' => $clientData['id'],
                    'client_name' => $clientData['client_name'],
                    'email' => $clientData['email'],
                    'phone' => $clientData['phone'],
                    'address' => $clientData['address'],
                    'imgData' => $imageData,
                    'userData' => $userData,
                    'serviceData' => $serviceData,
                );

                // Check if img_id exists, if yes, fetch imgData
                if ($clientData['img_id']) {
                    // $imageQuery = "SELECT id, original_file_name, altered_file_name, path FROM tbl_image WHERE id = ?";
                    // $stmt = $db->prepare($imageQuery);
                    // $stmt->bind_param("i", $clientData['img_id']);
                    // $stmt->execute();
                    // $imageResult = $stmt->get_result();
                    $imageQuery = "SELECT id, original_file_name, altered_file_name, path FROM tbl_image WHERE id = {$clientData['img_id']} AND status=1";
                    $imageResult = $db->query($imageQuery);
                    if ($imageResult->num_rows > 0) {
                        $responseData['imgData'] = $imageResult->fetch_assoc();
                    }
                }

                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "status" => "Ok",
                        "message" => "Client details fetched successfully",
                    ),
                    "result" => array(
                        'clientData' => $responseData,
                    ),
                );
            } else {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "No data found...",
                    ),
                );
            }

            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } finally {
            // Close database connection
            $db->close();
        }
    }

    //GetByIdClient function end

    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */

    /**
     * Post/Add sale
     *
     * @param array $data
     * @return multitype:string
     */

    //CreateClient function start
    private function createClient($data, $loginData)
    {
        // print_r($data);
        try {
            $db = $this->dbConnect();
            $validationData = array("client_name" => $data['client_name'], "email" => $data['email'], "phone" => $data['phone']);
            $this->validateInputDetails($validationData);

            $sql1 = "SELECT id FROM tbl_client WHERE client_name = '" . $data['client_name'] . "' AND email = '" . $data['email'] . "' AND phone = '" . $data['phone'] . "' AND status = 1";
            // print_r($sql1);exit;
            $result = mysqli_query($db, $sql1);
            $row_cnt = mysqli_num_rows($result);

            if ($row_cnt > 0) {
                throw new Exception("Client name & email_id is already exist");
            }
            // exit;
            if (!empty($data['client_name'])) {
                $client_name = $data['client_name'];
            } else {
                $client_name = "";
            }
            if (!empty($data['email'])) {
                $email = $data['email'];
            } else {
                $email = "";
            }
            if (!empty($data['phone'])) {
                $phone = $data['phone'];
            } else {
                $phone = "";
            }
            if (isset($data['service_offer']) && !empty($data['service_offer'])) {
                $service_offer = $data['service_offer'];
            } else {
                // throw new Exception("Please validate the service offer");
                // $service_offer = $data['service_offer'];
                $service_offer = [];
                // echo 'kkk';exit;
            }

            $dateNow = date("Y-m-d H:i:s");
            $insertQuery = "INSERT INTO tbl_client (client_name,email, phone,address,img_id,status, created_by, created_date) VALUES ('" . $client_name . "','" . $email . "','" . $phone . "','" . $data['address'] . "','" . $data['img_id'] . "','" . '1' . "','" . $loginData['user_id'] . "','$dateNow')";
            $serviceIds = implode(',', $data['service_offer']);
            if (!empty($serviceIds)) {
                $serviceCheckQuery = "SELECT id FROM tbl_service_offered WHERE id IN ($serviceIds)";
                $serviceCheckResult = $db->query($serviceCheckQuery);
                // print_r($serviceCheckQuery);exit;

                // if ($serviceCheckResult->num_rows !== count($data['service_offer'])) {
                //     throw new Exception("Service ID provided are invalid.");
                // }
            }
            if ($db->query($insertQuery) === true) {
                $lastInsertedId = mysqli_insert_id($db);
                $this->updateClientService($lastInsertedId, $service_offer);
                if (!empty($data['userData'])) {
                    $userConn = new USERMODEL();
                    $data['userData']['client_name'] = $data['client_name'];
                    $connect = $userConn->createUser($data, $loginData);

                    if ($connect['apiStatus']['code'] == 200) {
                        $userLastId = $connect['result']['lastUserId'];
                        $this->updateClientUser($lastInsertedId, $loginData, $userLastId);
                        // echo $userLastId;
                        $db->close();
                        $statusCode = "200";
                        $status = "Ok";
                        $statusMessage = "Client with User details created successfully";
                    } else {
                        $statusCode = $connect['apiStatus']['code'];
                        $status = "Ok";
                        $statusMessage = $connect['apiStatus']['message'];
                    }
                } else {
                    $statusCode = "200";
                    $status = "Ok";
                    $statusMessage = "Client details created successfully";
                }
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to create Client details, please try again later";
            }

            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "status" => $status,
                    "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),

                ));
        }
    }

    //CreateClient function end

    //Insert the client_id & service_id  to tbl_client_service_map
    private function updateClientService($lastInsertedId, $service_offer)
    {
        try {
            $db = $this->dbConnect();
            if ($lastInsertedId) {
                if (empty($service_offer)) {
                    // If service_offer is empty, create a new client service with NULL service_id
                    $insertQuery = "INSERT INTO tbl_client_service_map (`client_id`, `service_id`, `created_by`) VALUES ('$lastInsertedId', NULL, '$lastInsertedId')";
                    $db->query($insertQuery);
                } else {
                    // If service_offer is not empty, update the client service
                    foreach ($service_offer as $x) {
                        $insertQuery = "INSERT INTO tbl_client_service_map (`client_id`, `service_id`, `created_by`) VALUES ('$lastInsertedId', $x, '$lastInsertedId')";
                        $db->query($insertQuery);
                    }
                }
                $db->close();
                return true;
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

    //Insert the client_id & user_id  to tbl_client_user_map
    private function updateClientUser($lastInsertedId, $loginData, $userLastId)
    {
        try {

            $db = $this->dbConnect();
            if ($lastInsertedId) {
                $insertQuery = "INSERT INTO tbl_client_user_map (`client_id`, `user_id`, `created_by`) VALUES ('$lastInsertedId','$userLastId','" . $loginData['user_id'] . "') ";
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

    //UpdateClient function start
    /**
     * Put/Update a Sale
     *
     * @param array $data
     * @return multitype:string
     */
    private function updateClient($data, $loginData)
    {
    try {
        // $take="SELECT id,img_id FROM tbl_client WHERE id={$data['id']} AND STATUS=1";
        // print_r($take);exit;
        $db = $this->dbConnect();
        $validationData = array(
            "Id" => $data['id'],
            "client_name" => $data['client_name'],
            "email" => $data['email'],
            "phone" => $data['phone'],
            // "address" => $data['address'],
            // "service_id" => $data['service_id'],
        );
        $this->validateInputDetails($validationData);

        $checkIdQuery = "SELECT COUNT(*) AS count FROM tbl_client WHERE id = {$data['id']}  AND status = 1";
        // print_r($checkIdQuery);exit;
        $result = $db->query($checkIdQuery)->fetch_assoc();
        $rowCount = $result['count'];

        if ($rowCount == 0) {
            return array(
                "apiStatus" => array(
                    "code" => "400",
                    "message" => "No data found...",
                ),
            );
        }

        $dateNow = date("Y-m-d H:i:s");

        if (isset($data['img_id'])) {
            // Get the current img_id of the client
            $getCurrentImgIdQuery = "SELECT img_id FROM tbl_client WHERE id = '{$data['id']}' AND status = 1";
            $currentImgIdResult = $db->query($getCurrentImgIdQuery);
            if ($currentImgIdResult->num_rows > 0) {
                $currentImgId = $currentImgIdResult->fetch_assoc()['img_id'];
        
                // Zero the status of the old image ID in tbl_images
                $zeroOldImageStatusQuery = "UPDATE tbl_image SET status = 0 WHERE id = '{$currentImgId}'";
                $db->query($zeroOldImageStatusQuery);
            }
        
            // Validate the provided image ID
            $checkImageQuery = "SELECT COUNT(*) AS count FROM tbl_image WHERE id = {$data['img_id']} AND status = 1";
            $resultImage = $db->query($checkImageQuery)->fetch_assoc();
            $rowCountImage = $resultImage['count'];
        
            if ($rowCountImage == 0) {
                return array(
                    "apiStatus" => array(
                        "code" => "400",
                        "message" => "Invalid image id...",
                    ),
                );
            }
        
            // Update client with img_id
            $updateClientQuery = "UPDATE tbl_client SET client_name = '{$data['client_name']}', email = '{$data['email']}', phone = '{$data['phone']}', address = '{$data['address']}', img_id='{$data['img_id']}', updated_by = '{$loginData['user_id']}', updated_date = '$dateNow' WHERE id = {$data['id']}";
        } else {
            // Update client details only
            $updateClientQuery = "UPDATE tbl_client SET client_name = '{$data['client_name']}', email = '{$data['email']}', phone = '{$data['phone']}', address = '{$data['address']}', updated_by = '{$loginData['user_id']}', updated_date = '$dateNow' WHERE id = {$data['id']}";
        }

        if ($db->query($updateClientQuery) === false) {
            return array(
                "apiStatus" => array(
                    "code" => "500",
                    "message" => "Unable to update client details, please try again later",
                ),
            );
        }

        // Update client services
        $db->query("UPDATE tbl_client_service_map SET status = 0 WHERE client_id = {$data['id']}");
        if (!empty($data['service_id'])) {
            foreach ($data['service_id'] as $serviceId) {
                $insertQuery = "INSERT INTO tbl_client_service_map (client_id, service_id, status,created_by, updated_by, updated_date) VALUES ({$data['id']}, '{$serviceId}', 1,{$data['id']}, '{$loginData['user_id']}', '$dateNow')";
                if ($db->query($insertQuery) === false) {
                    return array(
                        "apiStatus" => array(
                            "code" => "500",
                            "message" => "Unable to update client services, please try again later",
                        ),
                    );
                }
            }
        }

        return array(
            "apiStatus" => array(
                "code" => "200",
                "message" => "Client details updated successfully",
            ),
        );
    } catch (Exception $e) {
        return array(
            "apiStatus" => array(
                "code" => "401",
                "message" => $e->getMessage(),
            ),
        );
    }
}

    //UpdateClient function end

    //DeleteClient function start

    private function deleteClient($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request id is required");
            }
            $sql = "SELECT id FROM tbl_client WHERE status = 1 and created_by = " . $loginData['user_id'] . " and id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;
            if ($row_cnt == 0) {
                throw new Exception("No data found...");
            }
            $deleteQuery = "UPDATE tbl_client set status=0 WHERE id = " . $id . "";
            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $status = "Ok";
                $statusMessage = "Client details deleted successfully";
            } else {
                $deleteQuery = "UPDATE tbl_client AS cl, tbl_client_user_map AS cu, tbl_contact_user_info AS u SET cl.status = 0,
            cu.status = 0,u.status = 0 WHERE cl.id = cu.client_id AND cu.user_id = u.id AND cl.id =" . $id . "";
                // echo $deleteQuery;exit;
                if ($db->query($deleteQuery) === true) {
                    $db->close();
                    $statusCode = "200";
                    $status = "Ok";
                    $statusMessage = "Client details deleted successfully";

                } else {
                    $statusCode = "500";
                    $statusMessage = "Unable to delete client details, please try again later";
                }
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "status" => $status,
                    "message" => $statusMessage),

            );
            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());

        }
    }
    //DeleteClient function end

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
            $sql = "SELECT * FROM tbl_client WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
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
                "apiStatus" => array(
                    "result" => "401",
                    "message" => $e->getMessage(),
                ));
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
                    "message" => $e->getMessage(),

                ));
        }
    }
}
