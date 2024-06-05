<?php

// use LDAP\Result;

require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";
// echo "from imageInsert model";exit;
class FILEUPLOADMODEL extends APIRESPONSE
{
    private function processMethod($data, $token)
    {

        $request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

        $urlPath = $_GET['url'];
        $urlParam = explode('/', $urlPath);
        // echo $urlParam[0];exit;
        // print_r($urlParam);exit;
        switch (REQUESTMETHOD) {
            case 'POST':
                if ($urlParam[1] === "imageInsert") {
                    $result = $this->imageInsert($data);
                    return $result;
                    break;
                } else if ($urlParam[1] === "imageUpdate") {
                    $result = $this->imageUpdate();
                    return $result;
                    break;
                }

            default:
                $result = throw new Exception("Error Occured in Request Method");
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

    public function imageInsert($data)
    {
        try {
        
            $file = $_REQUEST['file'];

            $db = $this->dbConnect();

            // print_r($_FILES);exit;
            $fileName = $_FILES['file']['name'];
            $tempPath = $_FILES['file']['tmp_name'];
            $fileSize = $_FILES['file']['size'];
            $fileType = $_FILES['file']['type'];
            // echo $tempPath;exit;

            $upload_path = 'uploads/images/';

            //to get name and file extension
            $fileExt = explode('.', $fileName);
            $fileActExt = strtolower(end($fileExt));
            // echo $fileActExt;exit;

            //get time stamp
            $time = date('ymdhms');
            $fullName = preg_replace('/\s+/', '', $fileExt[0]);
            $altFileName = $fullName . "_" . $time . "." . $fileActExt;
            // echo $fullFileName;

            $filePath = $upload_path;
            // echo $filePath;exit;

            //Validate file type
            $fileType1 = explode('/', $fileType)[1];
            // echo $fileType;exit;
  
            //validate file size 2MB
            if ($fileSize > 2000000) {
                throw new Exception("File is Too Large, Please Uplaod Below 2 MB!");
            }

            if (move_uploaded_file($tempPath, $upload_path . $altFileName)) {

                // else{
                //query to insert into database
                $query = "INSERT into tbl_image (`original_file_name`,`altered_file_name`,`path`) values ('$fileName','$altFileName','$filePath')";
                if ($db->query($query)) {
                    $id = mysqli_insert_id($db);
                    $responseData = array(
                        "apiStatus" => array(
                            "code" => "200",
                            "message" => "File Uploaded Successfully",
                        ),
                        "result" => array(
                            "image_id" => $id,
                            "File Original Name" => $fileName,
                            "File Altered Name" => $altFileName,
                            "Path" => $filePath,
                        ),
                    );
                    // }
                    return $responseData;
                }
                // else {
                //     throw new Exception("Unable to Insert Record into Database");
                // }
            } else {
                throw new Exception("Unable to Upload file");
            }

        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ),
            );
        }
    }

    public function imageUpdate()
    {
        try {
            $db = $this->dbConnect();
            $client_id = $_REQUEST['client_id'];

            if (empty($client_id)) {
                throw new Exception("Client id Required!");
            }

            $fileName = $_FILES['file']['name'];
            $tempPath = $_FILES['file']['tmp_name'];
            $fileSize = $_FILES['file']['size'];
            $fileType = $_FILES['file']['type'];
            // print_r($_FILES['file']);exit;

            $upload_path = 'uploads/images/';

            //to get name and file extension
            $fileExt = explode('.', $fileName);
            $fileActExt = strtolower(end($fileExt));
            // echo $fileActExt;exit;

            //get time stamp
            $time = date('ymdhms');
            $fullName = preg_replace('/\s+/', '', $fileExt[0]);
            $altFileName = $fullName . "_" . $time . "." . $fileActExt;
            // echo $fullFileName;

            //get path
            // echo __DIR__;exit;
            // echo __FILE__;exit;
            $filePath = $upload_path;
            // echo $filePath;exit;

            //Validate file type
            $fileType1 = explode('/', $fileType)[1];
            // echo $fileType;exit;
            if ($fileType1 != 'png' && $fileType1 != 'jpg' && $fileType1 != 'jpeg') {
                throw new Exception("Unsupported File Type!");
            }

            //validate file size 2MB
            if ($fileSize > 2000000) {
                throw new Exception("File is Too Large, Please Uplaod Below 2 MB!");
            }

            if (move_uploaded_file($tempPath, $upload_path . $altFileName)) {
                if (isset($client_id)) {
                    //query for get image id from tbl_client using client_id
                    $clientQuery = "SELECT img_id from tbl_client where id = '$client_id' and status = 1";
                    $resultQuery = mysqli_query($db, $clientQuery);
                    $clientData = mysqli_fetch_assoc($resultQuery);
                    $client_image_id = $clientData['img_id'];

                    if ($client_image_id !== null) {
                        // Set the existing image ID of the client to 0 in tbl_image
                        $updateImageQuery = "UPDATE tbl_image SET status = 0 WHERE id = '$client_image_id'";
                        mysqli_query($db, $updateImageQuery);

                        // // Set the existing image ID of the client to 0 in tbl_client
                        // $updateClientQuery = "UPDATE tbl_client SET image_id = 0 WHERE id = '$client_id'";
                        // mysqli_query($db, $updateClientQuery);
                    }

                    // Insert new record into tbl_image
                    $insertquery = "INSERT into tbl_image (`original_file_name`,`altered_file_name`,`path`) values ('$fileName','$altFileName','$filePath')";
                    if ($db->query($insertquery)) {
                        $last_image_id = mysqli_insert_id($db);

                        // Update the client's image ID to the newly inserted image ID in tbl_client
                        $client_update = "UPDATE tbl_client set img_id = '$last_image_id' where id = '$client_id'";
                        $db->query($client_update);

                        $responseData = array(
                            "apiStatus" => array(
                                "code" => "200",
                                "message" => "File Uploaded Successfully",
                            ),
                            "result" => array(
                                "image Id" => $last_image_id,
                                "File Original Name" => $fileName,
                                "File Altered Name" => $altFileName,
                                "Path" => $filePath,
                            ),
                        );
                    }
                    return $responseData;
                }
            } else {
                throw new Exception("Unable to Upload file!");
            }

        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ),
            );
        }
    }
    // $server_url  =  "http://localhost/project_management/api/imageInsert/uploads/images";
    public function processList($data, $token)
    {
        try {
            $responseData = $this->processMethod($data, $token);
            $result = $this->response($responseData);
            return $result;
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
