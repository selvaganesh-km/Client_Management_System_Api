<?php
// error_reporting(E_ALL);

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
// require_once "model/user.php";
require_once "model/register.php";

class TENANTMODEL extends APIRESPONSE
{
    private function processMethod($data, $loginData)
    {

        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    $result = $this->gettenant($data, $loginData);
                } else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createtenant($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->gettenantDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Unable to proceed your request!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updatetenant($data, $loginData);
                } else {
                    throw new Exception("Unable to proceed your request!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deletetenant($data);
                } else {
                    throw new Exception("Unable to proceed your request!");
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

     public function gettenantDetails($data, $loginData)
     {
         try {
             $db = $this->dbConnect();
             $totalcount = $this->getTotalCount($data, $loginData);
             if ($data['pageIndex'] == "" && $data['pageIndex'] == "") {
                throw new Exception("pageIndex should not be empty!");
            }
            if ($data['dataLength'] == "" && $data['dataLength'] == "") {
                throw new Exception("dataLength should not be empty!");
            }
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];
             
             // Fetch tenant details separately
             if ($loginData['role_name'] === 'super admin') {
                 $queryServiceTenant = "SELECT tn.id, tn.tenant_name, tn.email, tn.address, tn.phone,
                     tit.id AS imgs_id, tit.original_file_name, tit.altered_file_name, tit.path
                     FROM tbl_tenant AS tn
                     LEFT JOIN tbl_image AS tit ON tn.img_id = tit.id
                     WHERE tn.status = 1 ORDER BY tn.id DESC
                     LIMIT $start_index, $end_index";
             } else {
                 return array(
                     "apiStatus" => array(
                         "code" => "404",
                         "message" => "No data found...",
                     ),
                 );
             }
             
             $resultTenant = $db->query($queryServiceTenant);
             $tenantData = array();
             
             // Fetch user details for each tenant
             while ($dataTenant = mysqli_fetch_array($resultTenant, MYSQLI_ASSOC)) {
                 // Check if the user is a super admin
                 if ($loginData['role_name'] === 'super admin') {
                     // If user is a super admin, fetch all data
                     $queryServiceUsers = "SELECT u.id AS user_id, u.user_name, u.email_id AS user_email, u.phone AS user_phone, u.address, u.password, 
                         ti.id AS img_id, ti.original_file_name, ti.altered_file_name, ti.path, r.id, r.role_name
                         FROM tbl_tenant_user_mapping AS tum
                         JOIN tbl_users AS u ON tum.user_id = u.id
                         LEFT JOIN tbl_image AS ti ON u.img_id = ti.id
                         JOIN tbl_user_role_map AS urm ON u.id = urm.user_id
                         JOIN tbl_role AS r ON urm.role_id = r.id
                         WHERE tum.status = 1 AND u.status = 1 AND tum.tenant_id = " . $dataTenant['id'];
     
                     $resultUsers = $db->query($queryServiceUsers);
                    //  $userData = array(); // Initialize userData array for this tenant
     
                     while ($dataUser = mysqli_fetch_array($resultUsers, MYSQLI_ASSOC)) {
                         $userData = array(
                             'id' => $dataUser['user_id'],
                             'user_name' => $dataUser['user_name'],
                             'email' => $dataUser['user_email'],
                             'phone' => $dataUser['user_phone'],
                             'address' => $dataUser['address'],
                             'password' => $dataUser['password'],
                             'role_id' => $dataUser['id'],
                             'role_name' => $dataUser['role_name'],
                             'imageData1' => ($dataUser['img_id'] !== null) ? array(
                                 'img_id' => $dataUser['img_id'],
                                 'original_file_name' => $dataUser['original_file_name'],
                                 'altered_file_name' => $dataUser['altered_file_name'],
                                 'path' => $dataUser['path'],
                             ) : array(
                                 'img_id' => null,
                                 'original_file_name' => null,
                                 'altered_file_name' => null,
                                 'path' => null,
                             ),
                         );
                     }
                 }
                 
                 // Create tenant details array
                 $tenantDetails = array(
                     'id' => $dataTenant['id'],
                     'tenant_name' => $dataTenant['tenant_name'],
                     'email' => $dataTenant['email'],
                     'address' => $dataTenant['address'],
                     'phone' => $dataTenant['phone'],
                     'imageData' => ($dataTenant['imgs_id'] !== null) ? array(
                         'img_id' => $dataTenant['imgs_id'],
                         'original_file_name' => $dataTenant['original_file_name'],
                         'altered_file_name' => $dataTenant['altered_file_name'],
                         'path' => $dataTenant['path'],
                     ) : array(
                         'img_id' => null,
                         'original_file_name' => null,
                         'altered_file_name' => null,
                         'path' => null,
                     ),
                     'userData' => $userData,
                 );
     
                 array_push($tenantData, $tenantDetails);
             }
     
             $responseArray = array(
                 "pageIndex" => $start_index,
                 "dataLength" => $end_index,
                 "totalRecordCount" => $totalcount,
                 'tenantData' => $tenantData,
             );
     
             if ($tenantData) {
                 $resultArray = array(
                     "apiStatus" => array(
                         "code" => "200",
                         "message" => "Tenant with user details fetched successfully",
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
     

    /**
     * Function is to get the for particular record
     *
     * @param array $data
     * @return multitype:
     */
    public function gettenant($data, $loginData)
    {
        // print_r($data);exit;
        try {
            $id = $data[2];
            // print_r($id);exit;
            $db = $this->dbConnect();
            if (empty($id)) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            if ($loginData['role_name'] === 'super admin') {
            $queryServiceTenant = "SELECT tn.id, tn.tenant_name, tn.email, tn.address, tn.phone,
            tit.id AS imgs_id,tit.original_file_name,tit.altered_file_name,tit.path
        FROM tbl_tenant AS tn
        LEFT JOIN tbl_image AS tit ON tn.img_id = tit.id
        WHERE tn.status = 1 AND tn.id = " . $id;
        }
        else{
            return array(
                "apiStatus" => array(
                    "code" => "404",
                    "message" => "No data found...",
                ),
            );
        }
            $resultTenant = $db->query($queryServiceTenant);
            $loggedId = $loginData['user_id'];

// print_r($queryServiceTenant);exit;
            // Fetch user details for the specified tenant ID
            // $tenantData = array();
            while ($dataTenant = mysqli_fetch_array($resultTenant, MYSQLI_ASSOC)) {
                $userData = array(); // Reset $userData array for each tenant
                if ($loginData['role_name'] === 'super admin') {
                $queryServiceUsers = "SELECT u.id AS use_id, u.user_name, u.email_id AS user_email, u.phone AS user_phone,u.address,u.password,ti.id AS img_id,ti.original_file_name,ti.altered_file_name,ti.path, r.id, r.role_name
            FROM tbl_tenant_user_mapping AS tum
            JOIN tbl_users AS u ON tum.user_id = u.id
            LEFT JOIN tbl_image AS ti ON u.img_id = ti.id
            JOIN tbl_user_role_map AS urm ON u.id = urm.user_id
            JOIN tbl_role AS r ON urm.role_id = r.id
            WHERE tum.status = 1 AND u.status = 1 AND tum.tenant_id = " . $id;
                }
                else{
                    return array(
                        "apiStatus" => array(
                            "code" => "404",
                            "message" => "No data found...",
                        ),
                    );
                }
                $resultUsers = $db->query($queryServiceUsers);
                if (mysqli_num_rows($resultUsers) > 0) {
                    while ($dataUser = mysqli_fetch_array($resultUsers, MYSQLI_ASSOC)) {
                        $userData = array(
                            'id' => $dataUser['use_id'],
                            'user_name' => $dataUser['user_name'],
                            'email' => $dataUser['user_email'],
                            'phone' => $dataUser['user_phone'],
                            'address' => $dataUser['address'],
                            'password' => $dataUser['password'],
                            'role_id' => $dataUser['id'],
                            'role_name' => $dataUser['role_name'],
                            'imageData1' => ($dataUser['img_id'] !== null) ? array(
                                'img_id' => $dataUser['img_id'],
                                'original_file_name' => $dataUser['original_file_name'],
                                'altered_file_name' => $dataUser['altered_file_name'],
                                'path' => $dataUser['path'],
                            ) : array(
                                'img_id' => null,
                                'original_file_name' => null,
                                'altered_file_name' => null,
                                'path' => null,
                            ),
                        );
                    }
                }

                $tenantDetails = array(
                    'id' => $dataTenant['id'],
                    'tenant_name' => $dataTenant['tenant_name'],
                    'email' => $dataTenant['email'],
                    'address' => $dataTenant['address'],
                    'phone' => $dataTenant['phone'],
                    'imageData' => ($dataTenant['imgs_id'] !== null) ? array(
                        'img_id' => $dataTenant['imgs_id'],
                        'original_file_name' => $dataTenant['original_file_name'],
                        'altered_file_name' => $dataTenant['altered_file_name'],
                        'path' => $dataTenant['path'],
                    ) : array(
                        'img_id' => null,
                        'original_file_name' => null,
                        'altered_file_name' => null,
                        'path' => null,
                    ),
                    'userData' => (!empty($userData)) ? $userData : null,
                );
                // array_push($tenantData, $tenantDetails);
            }

            if ($tenantDetails) {
                $message = (!empty($userData)) ? "Tenant with user details fetched successfully" : "Tenant details fetched successfully";
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => $message,
                    ),
                    "result" => array("tenantData" => $tenantDetails),
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "404",
                        "message" => "Id not found...",
                    ),
                );
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Post/Add tenant
     *
     * @param array $data
     * @return multitype:string
     */
    private function createtenant($data, $loginData)
    {
// print_r($loginData);exit;
        try {
            $db = $this->dbConnect();
            // print_r($data);exit;
            $validationData = array("tenant_name" => $data['tenantname'], "email" => $data['email'], "phone" => $data['phone'], "address" => $data['address']);
            $this->validateInputDetails($validationData);
            $sql = "SELECT id FROM tbl_tenant WHERE email = '" . $data['email'] . "' AND status = 1";
            $result = mysqli_query($db, $sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                throw new Exception("Tenant email is already exist");
            }

            $dateNow = date("Y-m-d H:i:s");
            $insertQuery = "INSERT INTO tbl_tenant (tenant_name,email, phone, address,img_id, created_by, created_date) VALUES ('" . $data['tenantname'] . "','" . $data['email'] . "','" . $data['phone'] . "','" . $data['address'] . "','" . $data['img_id'] . "','" . $loginData['user_id'] . "','$dateNow')";

            // if (true) {
            if ($db->query($insertQuery) === true) {
                $lastInsertedId = mysqli_insert_id($db);
                // print_r($lastInsertedId);
                if (!empty($data['userData'])) {
                    $usercon = new REGISTERMODEL();
                    $data['userData']['tenantname'] = $data['tenantname'];
                    $User1 = $usercon->userRegistration($data, $loginData);

                    if ($User1['apiStatus']['code'] == 200) {
                        $userlastid = $User1['result']['lastUserId'];
                        // print_r($userlastid);exit;
                        $this->updateTenantUsermapping($lastInsertedId, $loginData, $userlastid);
                        $db->close();
                        $statusCode = "200";
                        $status = "Ok";
                        $statusMessage = "Tenant with user details created successfully";
                    } else {
                        $statusCode = $User1['apiStatus']['code'];
                        $status = "Ok";
                        $statusMessage = $User1['apiStatus']['message'];
                    }

                } else {
                    $statusCode = "200";
                    $status = "Ok";
                    $statusMessage = "Tenant details created successfully";
                }

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to create tenant details, please try again later";
            }

            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "status" => $status,
                    "message" => $statusMessage,
                    "result" => array("lastTenantId" => $lastInsertedId),
                ),
            );
            return $resultArray;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ),
            );
        }
    }
            
    /**
     * Put/Update a tenant
     *
     * @param array $data
     * @return multitype:string
     */
    private function updateTenant($data, $loginData)
    {
    try {
        $db = $this->dbConnect();
        $checkIdQuery = "SELECT COUNT(*) AS count FROM tbl_tenant WHERE id = {$data['id']} AND status = 1";
        $checkImageQuery = "SELECT COUNT(*) AS count FROM tbl_image WHERE id = {$data['img_id']} AND status = 1";
        $result = $db->query($checkIdQuery);
        $rowCount = $result->fetch_assoc()['count'];
        $userData = $data['userData'];
        $password = $data['userData']['password'];
        
        // If ID doesn't exist, return error
        if ($rowCount == 0) {
            $db->close();
            return array(
                "apiStatus" => array(
                    "code" => "400",
                    "message" => "Tenant ID does not exist",
                ),
            );
        }

        $validationData = array("Id" => $data['id'], "tenant_name" => $data['tenantname'], " email" => $data['email'], "address" => $data['address']);
        $this->validateInputDetails($validationData);
        $hashed_password = hash('sha256', hash('sha256', $password));

        $dateNow = date("Y-m-d H:i:s");

        if (!isset($data['img_id'])) {
            // Update tenant details without img_id
            $updateTenantQuery = "UPDATE tbl_tenant SET tenant_name = '{$data['tenantname']}', email = '{$data['email']}', phone = '{$data['phone']}', address = '{$data['address']}', updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE id ='{$data['id']}' AND status = 1;";
        } else {
            $getCurrentImgIdQuery = "SELECT img_id FROM tbl_tenant WHERE id = '{$data['id']}' AND status = 1";
            $currentImgIdResult = $db->query($getCurrentImgIdQuery);
            if ($currentImgIdResult->num_rows > 0) {
                $currentImgId = $currentImgIdResult->fetch_assoc()['img_id'];
        
                // Zero the status of the old image ID in tbl_images
                $zeroOldImageStatusQuery = "UPDATE tbl_image SET status = 0 WHERE id = '{$currentImgId}'";
                $db->query($zeroOldImageStatusQuery);
            }

            // Update tenant details with img_id
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

            $updateTenantQuery = "UPDATE tbl_tenant SET tenant_name = '{$data['tenantname']}', email = '{$data['email']}', phone = '{$data['phone']}', address = '{$data['address']}', img_id = '{$data['img_id']}', updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE id ='{$data['id']}' AND status = 1;";
        }

        if ($db->query($updateTenantQuery) === true) {
            $statusCode = "200";
            $statusMessage = "Tenant details updated successfully";

            // Check if user data is provided
            if (!empty($userData)) {
                // Fetch user ID from mapping table using provided tenant ID
                $fetchUserQuery = "SELECT user_id FROM tbl_tenant_user_mapping WHERE tenant_id = '{$data['id']}'";
                $userResult = $db->query($fetchUserQuery);
            
                if ($userResult->num_rows > 0) {
                    $userRow = $userResult->fetch_assoc();
                    $userId = $userRow['user_id'];
            
                    // Update user details
                    if (!isset($userData['img_id'])) {
                        // Update user details without img_id
                        $updateUserQuery = "UPDATE tbl_users SET user_name = '{$userData['user_name']}', email_id = '{$userData['emailId']}', password ='" . $hashed_password . "', phone = '{$userData['phone']}', address = '{$userData['address']}', updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE id ='{$userId}'";
                    } else {
                        // Update user details with img_id
                        $updateUserQuery = "UPDATE tbl_users SET user_name = '{$userData['user_name']}', email_id = '{$userData['emailId']}', password ='" . $hashed_password . "', phone = '{$userData['phone']}', address = '{$userData['address']}', img_id = '{$userData['img_id']}', updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE id ='{$userId}'";
                    }
            
                    if ($db->query($updateUserQuery) === true) {
                        // Update mapping table
                        $updateMappingQuery = "UPDATE tbl_tenant_user_mapping SET updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE tenant_id ='{$data['id']}' AND user_id = '{$userId}'";
            
                        if ($db->query($updateMappingQuery) === true) {
                            $statusCode = "200";
                            $statusMessage = "Tenant and user details updated successfully";
                        } else {
                            $statusCode = "500";
                            $statusMessage = "Unable to update Mapping details, please try again later";
                        }
                    } else {
                        $statusCode = "500";
                        $statusMessage = "Unable to update User details, please try again later";
                    }
                } else {
                    // Handle case where no user is found for the given tenant ID
                    $statusCode = "404";
                    $statusMessage = "No user found for the given tenant ID";
                }
            }
            
        } else {
            $statusCode = "500";
            $statusMessage = "Unable to update Tenant details, please try again later";
        }

        return array(
            "apiStatus" => array(
                "code" => $statusCode,
                "message" => $statusMessage,
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



            // $updateQuery = "UPDATE tbl_tenant SET tenant_name = '{$data['tenantname']}', email = '{$data['email']}', phone = '{$data['phone']}', address = '{$data['address']}', updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE id ='{$data['id']}' AND status = 1;";

            // if ($db->query($updateQuery) === true) {
            //     $updateUserQuery = "UPDATE tbl_users SET user_name = '{$userData['user_name']}', email_id = '{$userData['emailId']}', password ='".$hashed_password."', phone = '{$userData['phone']}', address = '{$userData['address']}',updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE id ='{$data['id']}';";

            //     if ($db->query($updateUserQuery) === true) {
            //         $updateMappingQuery = "UPDATE tbl_tenant_user_mapping SET user_id = '{$loginData['user_id']}',updated_by = '{$loginData['user_id']}', updated_date = '{$dateNow}' WHERE tenant_id ='{$data['id']}';";

            //         if ($db->query($updateMappingQuery) === true) {
            //             $statusCode = "200";
            //             $statusMessage = "Tenant details updated successfully";
            //         } else {
            //             $statusCode = "500";
            //             $statusMessage = "Unable to update Mapping details, please try again later";
            //         }
            //     } else {
            //         $statusCode = "500";
            //         $statusMessage = "Unable to update User details, please try again later";
            //     }
            // } else {
            //     $statusCode = "500";
            //     $statusMessage = "Unable to update Tenant details, please try again later";
            // }

    //         $db->close();

    //         $resultArray = array(
    //             "apiStatus" => array(
    //                 "code" => $statusCode,
    //                 "message" => $statusMessage,
    //             ),
    //         );
    //         return $resultArray;

    //     } catch (Exception $e) {
    //         return array(
    //             "apiStatus" => array(
    //                 "code" => "401",
    //                 "message" => $e->getMessage(),
    //             ),
    //         );
    //     }
    // }

//tanent user role mapping

    private function updateTenantUsermapping($lastInsertedId, $loginData, $userlastid)
    {
        // print_r($userlastid);exit;
        try {
            $db = $this->dbConnect();

            if ($lastInsertedId) {

                $insertQuery = "INSERT INTO tbl_tenant_user_mapping (`user_id`, `tenant_id`, `created_by`) VALUES ('$userlastid', ' $lastInsertedId', '" . $loginData['user_id'] . "') ";
                // print_r($insertQuery);exit;
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

    private function deletetenant($data)
    {
// print_r($data);exit;
        try {

            $id = $data[2];
            $db = $this->dbConnect();
            // Check if the ID is provided and valid
            if (empty($data[2])) {
                throw new Exception("Invalid. Please enter your ID.");
            }
            $checkIdQuery = "SELECT COUNT(*) AS count FROM tbl_tenant WHERE id = $id AND status=1";
            // print_r($checkIdQuery);exit;
            $result = $db->query($checkIdQuery);
            $rowCount = $result->fetch_assoc()['count'];

            // If ID doesn't exist, return error
            if ($rowCount == 0) {
                $db->close();
                return array(
                    "apiStatus" => array(
                        "code" => "400",
                        "message" => "Tenant ID does not exist",
                    ),
                );
            }

            $deleteQuery = "UPDATE tbl_tenant AS tm
            LEFT JOIN tbl_tenant_user_mapping AS tum ON tm.id = tum.tenant_id
            LEFT JOIN tbl_users AS u ON tum.user_id = u.id
            LEFT JOIN tbl_user_role_map AS tur on tur.user_id =u.id
            SET tm.status = 0,
                tum.status = 0,
                u.status = 0,
                tur.status=0
            WHERE tm.id =$id";
            // print_r($deleteQuery);exit;
            if ($db->query($deleteQuery) === true) {

                $db->close();
                $statusCode = "200";
                $statusMessage = "Tenant details deleted successfully";
            } else {
                $statusCode = "500";
                $statusMessage = "Unable to delete Tenant details, please try again later";
            }

            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage,
                ),
            );
            return $resultArray;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    /**
     * Validate function for tenant create
     *
     * @param array $data
     * @throws Exception
     * @return multitype:string NULL
     */
    public function validateInputDetails($validationData)
    {
        foreach ($validationData as $key => $value) {
            if (empty($value) || trim($value) == "") {
                throw new Exception($key . " should not be empty!");
            }
        }
    }

    private function getTotalCount($data, $loginData)
    {
        // print_r($loginData);
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_tenant WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
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
                ),
            );
        }
    }
}
