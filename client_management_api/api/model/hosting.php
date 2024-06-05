<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";

error_reporting(0);

class HOSTINGMODEL extends APIRESPONSE
{

    private function processMethod($data, $loginData)
    {
        // echo"fir";exit;
        switch (REQUESTMETHOD) {
            case 'GET':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "get") {
                    if ($urlParam[2] == "clientlist") {
                        $result = $this->gethostClientDrop($data);
                    } elseif ($urlParam[2] == "servicelist") {
                        $result = $this->gethostServiceDrop($data);
                    } else {
                        $result = $this->getHost($data, $loginData);
                    }
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'POST':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] === 'create') {
                    $result = $this->createHost($data, $loginData);
                    return $result;
                } elseif ($urlParam[1] === 'list') {
                    $result = $this->getHostDetails($data, $loginData);
                    return $result;
                } else {
                    throw new Exception("Method not allowed!");
                }
                break;
            case 'PUT':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "update") {
                    $result = $this->updateHost($data, $loginData);
                } else {
                    throw new Exception("Method not allowed!");
                }
                return $result;
                break;
            case 'DELETE':
                $urlPath = $_GET['url'];
                $urlParam = explode('/', $urlPath);
                if ($urlParam[1] == "delete") {
                    $result = $this->deleteHost($data, $loginData);
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

    public function getHostDetails($data, $loginData)
    {
        try {
            $responseArray = "";
            // $res = array();
            $db = $this->dbConnect();
            $loggedId = $loginData['user_id'];
            $totalRecordCount = $this->getTotalCount($loginData);
            if (empty($data['pageIndex']) && $data['pageIndex'] != 0) {
                throw new Exception("pageIndex should not be empty");
            }
            if (empty($data['dataLength'])) {
                throw new Exception("dataLength should not be empty");
            }
            $start_index = $data['pageIndex'] * $data['dataLength'];
            $end_index = $data['dataLength'];

            // $sql="SELECT d.id AS host_id ,d.host_name,c.client_name,d.server_name,d.product,d.plan,d.server_ip,d.purchase_date,d.expiry_date,dcs.service_id,
            // sr.type,sc.user_name,sc.password,sc.key,n.id,n.notification_type,n.notification_data,n.notification_prior,n.notification_interval,
            // sp.id,sp.service_name,sp.service_url FROM tbl_host_client_server_notify_map dcs JOIN tbl_hosting d ON dcs.host_id = d.id
            // INNER JOIN tbl_client c ON dcs.client_id = c.id INNER JOIN tbl_service_credential sc ON dcs.service_id = sc.id INNER JOIN
            // tbl_notification n ON dcs.notification_id = n.id INNER JOIN  tbl_service_provider sp ON dcs.service_provider_id = sp.id
            // INNER JOIN tbl_server sr ON dcs.type_id = sr.id
            // WHERE d.created_by =$loggedId AND dcs.status=1 ORDER BY host_id ASC";

            $sql = "SELECT
                d.id AS host_id,
                d.host_name,
                dcs.client_id,
                c.client_name,
                d.server_name,
                d.product,
                d.plan,
                d.server_ip,
                d.purchase_date,
                d.expiry_date,
                dcs.service_id,
                sr.id AS type_id,
                sr.type,
                sc.user_name,
                sc.password,
                sc.key,
                n.id AS notify_id,
                n.notification_type,
                n.notification_data,
                n.notification_prior,
                n.notification_interval,
                sp.id,
                sp.service_name,
                sp.password,
                sp.service_url
            FROM
                tbl_host_client_server_notify_map dcs
            JOIN
                tbl_hosting d ON dcs.host_id = d.id
            INNER JOIN
                tbl_client c ON dcs.client_id = c.id
            INNER JOIN
                tbl_service_credential sc ON dcs.service_id = sc.id
            INNER JOIN
                tbl_notification n ON dcs.notification_id = n.id
            INNER JOIN
                tbl_service_provider sp ON dcs.service_provider_id = sp.id
            INNER JOIN
                tbl_server sr ON dcs.type_id = sr.id
            WHERE
                d.created_by = $loggedId  AND dcs.status=1 AND d.status=1
            ORDER BY
                host_id DESC LIMIT " . $start_index . ", " . $end_index . "";

            if (!$db->query($sql)) {
                throw new Exception("Error updating related tables" . $db->error);
            }
            // print_r($sql);exit;

            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                $hostData1 = array();
                while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                    //    $serviceProviderData =array("service_provider_id"=>$data['id'],"service_name"=>$data['service_name'],"service_url"=>$data['service_url']);

                    $serviceData = array("type_id" => $data['type_id'], "type_name" => $data['type'], "user_name" => $data['user_name'], "password" => $data['password'], "key" => $data['key']);

                    $notification = array("notification_type" => $data['notification_type'], "notification_data" => $data['notification_data'],
                        "notification_prior" => $data['notification_prior'], "notification_interval" => $data['notification_interval']);

                    $hostData = array("id" => $data['host_id'], "host_name" => $data['host_name'], "client_id" => $data['client_id'], "client_name" => $data['client_name'], "server_name" => $data['server_name'], "product" => $data['product'],
                        "plan" => $data['plan'], "server_ip" => $data['server_ip'], "purchase_date" => $data['purchase_date'], "service_id" => $data['id'], "service_name" => $data['service_name'], "expiry_date" => $data['expiry_date'], "serviceData" => $serviceData, "notification" => $notification);
                    // print_r($hostData);exit;
                    array_push($hostData1, $hostData);
                }

                $responseArray = array(
                    "pageIndex" => $start_index,
                    "dataLength" => $end_index,
                    "totalRecordCount" => $totalRecordCount,
                    'hostData' => $hostData1,

                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Host details fetched successfully"),

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

    public function gethostClientDrop($data)
    {
        try {
            $responseArray = "";
            $clientDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, client_name FROM tbl_client  WHERE status=1";
            $result = $db->query($queryService);
            $row_cnt = mysqli_num_rows($result);
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($clientDrop, $data);
            }
            // print_r($clientDrop);
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

    public function gethostServiceDrop($data)
    {
        try {
            $responseArray = "";
            $clientDrop = array();
            $db = $this->dbConnect();

            $queryService = "SELECT id, service_name FROM tbl_service_provider  WHERE status=1";
            $result = $db->query($queryService);
            $row_cnt = mysqli_num_rows($result);
            while ($data = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                array_push($clientDrop, $data);
            }
            // print_r($clientDrop);
            $responseArray = array(
                "totalRecordCount" => $row_cnt,
                "serviceData" => $clientDrop,
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

    public function getHost($data)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request");
            }

            $responseArray = "";
            $db = $this->dbConnect();
            $sql = "SELECT
            d.id AS host_id,
            d.host_name,
            dcs.client_id,
            c.client_name,
            d.server_name,
            d.product,
            d.plan,
            d.server_ip,
            d.purchase_date,
            d.expiry_date,
            dcs.service_id,
            dcs.service_provider_id,
            spc.id,
            spc.service_url,
            spc.service_name,
            spc.password,
            sr.id AS type_id,
            sr.type,
            sp.id AS service_credentials_id,
            sp.user_name,
            sp.key,
            sp.password,
            n.id,
            n.notification_type,
            n.notification_data,
            n.notification_prior,
            n.notification_interval
        FROM
            tbl_host_client_server_notify_map dcs
            inner JOIN tbl_hosting d ON dcs.host_id = d.id
            inner JOIN tbl_client c ON dcs.client_id = c.id
            inner JOIN tbl_service_credential sp ON dcs.service_id = sp.id
            inner JOIN tbl_service_provider spc ON dcs.service_provider_id = spc.id
            inner JOIN tbl_server sr ON dcs.type_id = sr.id
            inner JOIN tbl_notification n ON dcs.notification_id = n.id
        WHERE
            d.id = $id AND dcs.status =1";
            // print_r($sql);exit;
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            if ($row_cnt > 0) {
                // while ($data = $result->fetch_assoc()) {
                $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                // print_r($data);exit;

                $hostData = array(
                    "id" => $data['host_id'],
                    "host_name" => $data['host_name'],
                    "client_id" => $data['client_id'],
                    "client_name" => $data['client_name'],
                    "server_name" => $data['server_name'],
                    "product" => $data['product'],
                    "plan" => $data['plan'],
                    "server_ip" => $data['server_ip'],
                    "purchase_date" => $data['purchase_date'],
                    "expiry_date" => $data['expiry_date']);
                $serviceProviderData = array(
                    "serviceProvider_id" => $data['service_provider_id'],
                    "serviceProvider_name" => $data['service_name'],
                    "serviceProvider_url" => $data['service_url'],
                    // "serviceProvider_"=>$data['password']
                );
                $serviceData = array(
                    "service_id" => $data['service_id'],
                    "type_id" => $data['type_id'],
                    "type_name" => $data['type'],
                    "user_name" => $data['user_name'],
                    "password" => $data['password'],
                    "key" => $data['key']);
                $notification = array(
                    "id" => $data['id'],
                    "notification_type" => $data['notification_type'],
                    "notification_data" => $data['notification_data'],
                    "notification_prior" => $data['notification_prior'],
                    "notification_interval" => $data['notification_interval'],
                );
                $responseArray = array(
                    'hostData' => $hostData,
                    'serviceProvider' => $serviceProviderData,
                    'service_credentials' => $serviceData,
                    'notification' => $notification,
                );
            }
            if ($responseArray) {
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Host details fetched successfully"),

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

    private function createHost($data, $loginData)
    {
        // print_r($data);exit;
        try {
            $hostData = array(
                "host_name" => $data['hostData']['host_name'],
                "client_id" => $data['hostData']['client_id'],
                "plan" => $data['hostData']['plan'],
                "server_ip" => $data['hostData']['server_ip'],
                "service_provider_id" => $data['hostData']['service_provider_id'],
                "purchase_date" => $data['hostData']['purchase_date'],
                "expiry_date" => $data['hostData']['expiry_date'],
                "type_id" => $data['hostData']['service_credentials']['type_id'],
                "user_name" => $data['hostData']['service_credentials']['user_name'],
                "password" => $data['hostData']['service_credentials']['password'],
                "notification_type" => $data['hostData']['notification']['notification_type'],
                "notification_data" => $data['hostData']['notification']['notification_data'],
                "notification_prior" => $data['hostData']['notification']['notification_prior'],
                "notification_interval" => $data['hostData']['notification']['notification_interval'],
            );
// print_r($hostData);exit;
            $this->validateInputDetails($hostData);
            $db = $this->dbConnect();
            // echo"fir";exit;
            $dateNow = date("Y-m-d H:i:s");
            $checkDomainQuery = "SELECT id FROM tbl_hosting WHERE host_name = '{$hostData['host_name']}'";
            // print_r($checkDomainQuery);exit;
            $result = $db->query($checkDomainQuery);
            if ($result && $result->num_rows > 0) {
                throw new Exception("Host name already exists");
            } else {

                $notificationData = $data['hostData']['notification'];
                $this->validateInputDetails($notificationData);
                $notificationInsertQuery = "INSERT INTO tbl_notification (notification_type, notification_data, notification_prior, notification_interval, created_by, created_date)
            VALUES ('" . $notificationData['notification_type'] . "', '" . $notificationData['notification_data'] . "', '" . $notificationData['notification_prior'] . "', '" . $notificationData['notification_interval'] . "','" . $loginData['user_id'] . "', '$dateNow')";
                // print_r($notificationInsertQuery); exit;
                $db->query($notificationInsertQuery);
                $notificationDataId = mysqli_insert_id($db);

                $serviceCredentials = $data['hostData']['service_credentials'];
                // print_r($serviceCredentials);
                $this->validateInputDetails($serviceCredentials);
                $typeId = $data['hostData']['service_credentials']['type_id'];
                $getTypeQuery = "SELECT id FROM tbl_server WHERE id = '$typeId'";
                $typeResult = $db->query($getTypeQuery);

                if ($typeResult && $typeResult->num_rows > 0) {
                    $typeData = $typeResult->fetch_assoc();
                    $typeId = $typeData['id'];
                } else {
                    throw new Exception("ID not found");
                }
                $serviceCredentialsInsertQuery = "INSERT INTO tbl_service_credential (type_id, user_name, password, `key`, created_by, created_date) VALUES ('$typeId', '" . $serviceCredentials['user_name'] . "', '" . $serviceCredentials['password'] . "', '" . $serviceCredentials['key'] . "', '" . $loginData['user_id'] . "', '$dateNow')";
                // echo $serviceCredentialsInsertQuery;
                $db->query($serviceCredentialsInsertQuery);
                $serviceCredentialsInsertQuery = mysqli_insert_id($db);

                // print_r($serviceCredentialsId);

                $checkDomainQuery = "SELECT id FROM tbl_hosting WHERE host_name = '" . $hostData['host_name'] . "'";
                // print_r($checkDomainQuery);
                $result = $db->query($checkDomainQuery);
                if ($result && $result->num_rows > 0) {
                    throw new Exception("Host '{$hostData['host_name']}' already exists");
                } else {
                    $domainInsertQuery = "INSERT INTO tbl_hosting (host_name, server_name, product, plan,server_ip, purchase_date, expiry_date, status, created_by, created_date)
            VALUES ('" . $hostData['host_name'] . "', '" . $data['hostData']['server_name'] . "', '" . $data['hostData']['product'] . "', '" . $hostData['plan'] . "', '" . $hostData['server_ip'] . "', '" . $hostData['purchase_date'] . "', '" . $hostData['expiry_date'] . "', '1', '" . $loginData['user_id'] . "', '$dateNow')";
                    // print_r($domainInsertQuery); exit;
                    $db->query($domainInsertQuery);
                    $lastInsertedId = mysqli_insert_id($db);

                    $domainClientMapInsertQuery = "INSERT INTO tbl_host_client_server_notify_map (type_id,host_id, client_id, service_id,service_provider_id,notification_id, created_by, created_date)
                VALUES ($typeId,'$lastInsertedId', '" . $hostData['client_id'] . "', '" . $serviceCredentialsInsertQuery . "', '" . $hostData['service_provider_id'] . "','" . $notificationDataId . "', '" . $loginData['user_id'] . "', '$dateNow')";
                    // print_r($domainClientMapInsertQuery); exit;

                    $db->query($domainClientMapInsertQuery);

                    $db->close();
                    return array(
                        "apiStatus" => array(
                            "code" => 200,
                            "status" => "Ok",
                            "message" => "Host Created Successfully",
                        ),
                        "result" => array(
                            "lastUserId" => $lastInsertedId));
                }
            }
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => 401,
                    "status" => "Error",
                    "message" => $e->getMessage()));
        }
    }

    private function updateHost($data, $loginData)
    {
        try {
            $hostData = array(
                "id" => $data['hostData']['id'],
                "host_name" => $data['hostData']['host_name'],
                "client_id" => $data['hostData']['client_id'],
                "service_provider_id" => $data['hostData']['service_provider_id'],
                "plan" => $data['hostData']['plan'],
                "server_ip" => $data['hostData']['server_ip'],
                "purchase_date" => $data['hostData']['purchase_date'],
                "expiry_date" => $data['hostData']['expiry_date'],
                "type_id" => $data['hostData']['service_credentials']['type_id'],
                "user_name" => $data['hostData']['service_credentials']['user_name'],
                "password" => $data['hostData']['service_credentials']['password'],
                "notification_type" => $data['hostData']['notification']['notification_type'],
                "notification_data" => $data['hostData']['notification']['notification_data'],
                "notification_prior" => $data['hostData']['notification']['notification_prior'],
                "notification_interval" => $data['hostData']['notification']['notification_interval'],
            );

            $requiredFields = ['host_name', 'client_id', 'service_provider_id', 'plan', 'server_ip', 'purchase_date', 'expiry_date', 'type_id', 'user_name', 'password', 'notification_type', 'notification_data', 'notification_prior', 'notification_interval'];
            foreach ($requiredFields as $field) {
                if (empty($hostData[$field])) {
                    return array(
                        "apiStatus" => array(
                            "code" => "400",
                            "message" => "'$field' cannot be empty",
                        ),
                    );
                }
            }

            $data = $data['hostData'];
            $service_data = $data['service_credentials'];
            $notify_data = $data['notification'];

            $db = $this->dbConnect();
            $checkIdQuery = "SELECT COUNT(*) AS count FROM tbl_hosting WHERE id = '" . $data['id'] . "' AND status = 1";

            $result = $db->query($checkIdQuery);
            $rowCount = $result->fetch_assoc()['count'];

            if ($rowCount == 0) {
                $db->close();
                return array(
                    "apiStatus" => array(
                        "code" => "400",
                        "message" => "Hosting ID does not exist",
                    ),
                );
            }

            $queryService = "SELECT id, host_id, client_id, service_id, notification_id FROM tbl_host_client_server_notify_map WHERE host_id = '" . $data['id'] . "' AND status = 1";

            if (!$db->query($queryService)) {
                throw new Exception("Error updating related tables" . $db->error);
            }
            $result = $db->query($queryService);

            if ($result) {
                $hostData = mysqli_fetch_array($result, MYSQLI_ASSOC);

                $dateNow = date("Y-m-d H:i:s");

                $updateQuery = "UPDATE tbl_hosting SET host_name = '" . $data['host_name'] . "', server_name = '" . $data['server_name'] . "', product = '" . $data['product'] . "', plan = '" . $data['plan'] . "',
                server_ip = '" . $data['server_ip'] . "', purchase_date = '" . $data['purchase_date'] . "', expiry_date = '" . $data['expiry_date'] . "',updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow'
                WHERE id = '" . $hostData['host_id'] . "'AND status =1";

                if ($db->query($updateQuery)) {

                    $updateQuery1 = "UPDATE tbl_service_credential SET type_id = '" . $service_data['type_id'] . "', user_name = '" . $service_data['user_name'] . "',password = '" . $service_data['password'] . "', `key` = '" . $service_data['key'] . "'
                ,updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow'
                WHERE id = '" . $hostData['service_id'] . "'AND status =1";
                    if ($db->query($updateQuery1)) {
                        $updateQuery2 = "UPDATE tbl_notification SET notification_type = '" . $notify_data['notification_type'] . "', notification_data = '" . $notify_data['notification_data'] . "',notification_prior = '" . $notify_data['notification_prior'] . "', notification_interval = '" . $notify_data['notification_interval'] . "'
                ,updated_by = '" . $loginData['user_id'] . "',updated_date = '$dateNow'
                WHERE id = '" . $hostData['notification_id'] . "'AND status =1";
                        if ($db->query($updateQuery2)) {
                            $hostClientMapInsertQuery = "UPDATE tbl_host_client_server_notify_map
            SET type_id = '" . $service_data['type_id'] . "',
            client_id = '" . $data['client_id'] . "',
            service_provider_id = '" . $data['service_provider_id'] . "',
            updated_by = '" . $loginData['user_id'] . "',
            updated_date = '$dateNow'
            WHERE id = '" . $hostData['id'] . "'AND status =1";
                            if ($db->query($hostClientMapInsertQuery)) {
                                $statusCode = "200";
                                $statusMessage = "Hosting details updated successfully";
                            } else {
                                throw new Exception("Error updating tbl_host_client_server_notify_map: " . $db->error);
                            }
                        } else {
                            throw new Exception("Error updating tbl_notification: " . $db->error);
                        }
                    } else {
                        throw new Exception("Error updating tbl_service_credential: " . $db->error);
                    }
                } else {
                    throw new Exception("Error updating tbl_hosting: " . $db->error);
                }
            } else {
                $statusCode = "500";
                $statusMessage = "Error executing the query";
            }

            $db->close();

            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage,
                ),
            );

            return $resultArray;
        } catch (Exception $e) {
            return array(
                "code" => "401",
                "message" => $e->getMessage(),
            );
        }
    }

    //DeleteClient function start

    private function deleteHost($data, $loginData)
    {
        try {
            $id = $data[2];
            $db = $this->dbConnect();
            if (empty($data[2])) {
                throw new Exception("Bad request id is required");
            }
            $sql = "SELECT id FROM tbl_hosting WHERE status = 1 and created_by = " . $loginData['user_id'] . " AND id =$id";
            $result = $db->query($sql);
            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;
            if ($row_cnt == 0) {
                throw new Exception("id " . $id . " not found");
            }
            // $deleteQuery = "UPDATE tbl_client set status=0 WHERE id = " . $id . "";
            $deleteQuery = "UPDATE tbl_client AS cl
            INNER JOIN tbl_host_client_server_notify_map AS cu ON cl.id = cu.client_id
            INNER JOIN tbl_hosting AS u ON cu.host_id = u.id
            INNER JOIN tbl_service_credential AS sc ON cu.service_id = sc.id
            INNER JOIN tbl_service_provider AS sp ON cu.service_provider_id = sp.id
            INNER JOIN tbl_notification AS n ON cu.notification_id = n.id
            SET  cu.status = 0, u.status = 0, sp.status=0, sc.status = 0, n.status = 0
            WHERE u.id =" . $id;
            // echo($deleteQuery);ext;

            if ($db->query($deleteQuery) === true) {
                $db->close();
                $statusCode = "200";
                $status = "Ok";
                $statusMessage = "Host details deleted successfully";

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to delete client details, please try again later";
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

    private function getTotalCount($loginData)
    {
        try {
            $db = $this->dbConnect();
            $sql = "SELECT * FROM tbl_hosting WHERE status = 1 and created_by = " . $loginData['user_id'] . "";
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

    public function validateInputDetails($hostData)
    {
        foreach ($hostData as $key => $value) {
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

    public function validateDate($date, $format = "Y-m-d H:i:s")
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
