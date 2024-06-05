<?php
require_once "include/apiResponseGenerator.php";
require_once "include/dbConnection.php";

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

class LOGINMODEL extends APIRESPONSE
{
    private function processMethod($data, $token)
    {
        // print_r($token);exit;
        $request = explode("/", substr(@$_SERVER['PATH_INFO'], 1));

        $urlPath = $_GET['url'];
        $urlParam = explode('/', $urlPath);
        switch (REQUESTMETHOD) {
            case 'GET':
                // if ($urlParam[1] === "validateToken") {
                //     $result = $this->validateToken($request);
                //     return $result;
                // }
                break;
            case 'POST':

                if ($urlParam[1] === "forgotPassword") {
                    $result = $this->forgotPassword($data, $token);
                    return $result;
                } else if ($urlParam[1] === "resetPassword") {
                    // echo "validate";
                    $result = $this->resetPassword($data);

                    return $result;
                } else if ($urlParam[1] === "resendMail") {
                    // echo "rsend Mail";
                    $result = $this->resendMail($data);

                    return $result;

                } else if ($urlParam[1] === 'changePassword') {
                    $result = $this->changePassword($data, $token);
                    return $result;
                } else if (($urlParam[1] === "validateToken")) {
                    $result = $this->validateToken($data);
                    return $result;
                } else {
                    $result = $this->loginCheck($data);
                    return $result;
                }
                break;
            case 'PUT':
                // $result = $this->    ($data, $token);
                // return $result;
                break;
            case 'DELETE':
                echo REQUESTMETHOD;
                exit;
                // $result = $this->logout($token);
                // return $result;
                break;
            default:
                $result = $this->handle_error($request);
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
     * Get Login Authendication
     *
     * @return multitype:
     */
    private function loginCheck($request)
    {
        // print_r($request['loginType']);exit;
        try {
            if (empty($request['loginType'])) {
                throw new Exception("Please select login Type");
            } else if (empty($request['user_name'])) {
                throw new Exception("Please give the User Name");
            } else if (empty($request['password'])) {
                throw new Exception("Please give the Password");
            }

            $validationData = array("login type" => $request['loginType'], "user_name" => $request['user_name'], "password" => $request['password']);
            $this->validateInputDetails($validationData);
            $db = $this->dbConnect();

            if ($request['loginType'] === "super admin") {
                $query = "SELECT u.user_name, u.password, u.id,urm.role_id,rl.role_name FROM tbl_users u JOIN tbl_user_role_map urm ON urm.user_id = u.id JOIN tbl_role rl ON urm.role_id = rl.id ";
                $query .= "  WHERE u.email_id = '" . $request['user_name'] . "' AND rl.role_name='" . $request['loginType'] . "'
                AND u.status = 1";
                // print_r($query);exit;
            } elseif ($request['loginType'] === "admin") {
                $query = "SELECT u.user_name, u.password, u.id,urm.role_id,rl.role_name FROM tbl_users u JOIN tbl_user_role_map urm ON urm.user_id = u.id JOIN tbl_role rl ON urm.role_id = rl.id ";
                $query .= "  WHERE u.email_id = '" . $request['user_name'] . "' AND rl.role_name='" . $request['loginType'] . "'
                AND u.status = 1";
                // print_r($query);exit;
            } else {
                throw new Exception("Your Login has not activated.");
            }
            $result = $db->query($query);
            // print_r($data);exit;
            if ($result) {
                $row_cnt = mysqli_num_rows($result);
                if ($row_cnt > 0) {
                    $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    // print_r($data);exit;
                    $hash = hash('sha256', hash('sha256', $request['password']));
                    if ($hash != $data['password']) {
                        throw new Exception("Invalid password");
                    }
                } else {
                    throw new Exception("Invalid Username Or password");
                }
            } else {
                // echo ("Asdsd");exit;
                throw new Exception("Invalid Username Or password");
            }

            // Create Token collection for authendication
            $token = md5(uniqid(mt_rand(), true));

            $roles = $this->getUserRoles($data['id']);
            // print_r ($data);exit;
            $userDetails = array(
                'loginid' => $data['id'],
                'userName' => $data['user_name'],
                'roles' => $roles,
            );
            $result = array(
                "token" => $token,
                "userDetail" => $userDetails,
            );
            if (empty($newuser)) {
                $new = array(
                    "firstTime" => "true",
                );
                $result = array_merge($result, $new);
            }
            $userId = $data['id'];
            // print_r($data['id']);exit;
            $timeNow = date("Y-m-d H:i:s");
            $sqlInsert = "INSERT INTO tbl_user_login_log (user_id, token, login_time, last_active_time) VALUES ('$userId', '$token', '$timeNow', '$timeNow')";

            //print_r($sqlInsert);exit();
            if ($db->query($sqlInsert) === true) {
                $db->close();
                // $logger = $this->loginLogCreate("logged into the application", $request['userName'], getcwd());
            }

            $resultArray = array(
                "apiStatus" => array(
                    "code" => "200",
                    "message" => "Login Successfully"),
                "result" => $result,
            );
            return $resultArray;
        } catch (Exception $e) {

            $this->loginLogCreate($e->getMessage(), "", getcwd());
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage()),
            );
        }
    }

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

    public function getUserRoles($userId)
    {
        // print_r($userId);exit;
        try {
            $db = $this->dbConnect();
            $querySdfd = "SELECT rl.role_name, rl.id FROM tbl_user_role_map as urm JOIN tbl_role as rl ON urm.role_id=rl.id WHERE urm.user_id = '$userId'";
            // print_r($querySdfd);exit;
            $result = $db->query($querySdfd);

            $row_cnt = mysqli_num_rows($result);

            $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
            $role = array('role_id' => $data['id'], 'roleName' => $data['role_name']);
            return $role;
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }

    public function forgotPassword($request)
    {
        // echo "hello";exit;
        // print_r($request);exit;
        // echo $validate_token;
        // echo "br  ";
        // echo "forgot password";
        // echo ($data)["emailId"];exit;
        // $email = $request['emailId'];
        try {
            if (empty($request['emailId'])) {
                throw new Exception("email required");
            } else {
                $email = $request['emailId'];
            }

            // $this->validateEmail($email);
            // $this->email_id = $email;
            // echo $email_id;exit;

            $db = $this->dbConnect();
            $query = "SELECT id,email_id FROM tbl_users ";
            $query .= " WHERE email_id = '" . $email . "'";
            $result = $db->query($query);
            if ($result) {
                $row_cnt = mysqli_num_rows($result);
                // echo "$row_cnt";
                if ($row_cnt > 0) {
                    $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $userId = ($data['id']);
                    // return $userId;
                } else {
                    throw new Exception("Invalid Username");
                }
            } else {
                throw new Exception("Invalid Username");
            }

            //calling this function for Genearate Token For user Validation
            $validate_token = $this->generateValidateToken();
            //    echo $validate_token , "  br  ";

            //Calling this function for Deactivate Existing Token
            $this->deactivateExistingToken($data);

            //Calling this Function For Insert Validate Token
            $insertValidateToken = $this->insertValidateToken($userId);
            // echo $sendMailStatus;

            if ($insertValidateToken == true) {
                //Calling This Function For Send Forgot Password Mail for User
                $forgotPasswordMail = $this->sendForgotPasswordMail($email);
                // $resetPassword = $this->resetPassword($data, $validate_token);
                $resultArray = array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Forgot password mail sent successfully",
                    ),
                );
                return $resultArray;
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "401",
                        "message" => "Unable to process the request. Not able to send mail. Please contact support team!.",
                    ),
                );
            }
        } catch (Exception $e) {
            //throw $th;
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ),
            );
        }
    }

    //Function for get user id using email id
    public function getUserId()
    {

    }

    //function for generate Validate token
    public function generateValidateToken()
    {
        static $validate_token;
        if (!$validate_token) {
            $validate_token = md5(uniqid(mt_rand(), true));
        }
        return $validate_token;
    }

    //function for insert validate token
    public function insertValidateToken($userId)
    {
        // echo $userId;exit;
        $db = $this->dbConnect();
        // print_r($data). "   br    ";exit;
        $validate_token = $this->generateValidateToken();
        // echo $validate_token, "   brrrr   ";
        $timeNow = date("Y-m-d H:i:s");
        // echo $timeNow;exit;
        // $userId = $data['id'];
        // echo $userId;exit;

        //query for insert validate token into tbl_forgot_password for validation
        $insertToken = "INSERT into tbl_forgot_password (`user_id`,`validate_token`,`created_date`) values ('$userId','$validate_token','$timeNow')";
        $result_token = mysqli_query($db, $insertToken);
        return $result_token;
    }

    public function sendForgotPasswordMail($email)
    {
        try {
            // Get the protocol (HTTP or HTTPS)
            $validate_token = $this->generateValidateToken();
            // echo "  validate in mail '$validate_token'";
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";

            // Get the host
            $host = $_SERVER['HTTP_HOST'];

            // Get the current script's directory path
            $currentPath = dirname($_SERVER['REQUEST_URI']);

            // Combine all parts to form the full URL
            $fullUrl = $protocol . $host . $currentPath;
            // echo $fullUrl;exit;
            $url = $_GET['url'];
            // echo $url ." brwak ";exit;
            $link = "http://localhost:3000" . "/reset/$validate_token";
            // $resendLink = $fullUrl . "/resendMail/$email/$validate_token";
            // echo $link;exit;
            // echo $resendLink;exit;

            // echo "hii";
            $db = $this->dbConnect();
            // $userData = $data['userData'];
            // $name = $request['name'];
            // $email = $request['emailId'];

            $mail = new PHPMailer(true);
            $fromEmail = "vel88275@gmail.com";
            $fromName = "Sakthivel";

            // $mail->SMTPDebug = 4;                               // Enable verbose debug output

            // fftc jtfk vsxb ibty
            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host = 'smtp.gmail.com'; // Specify main and backup SMTP servers
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = $fromEmail; // SMTP username
            $mail->Password = 'fftcjtfkvsxbibty'; // SMTP password
            $mail->SMTPSecure = 'ssl'; // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 465; // TCP port to connect to

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($email); // Add a recipient
            $mail->isHTML(true); // Set email format to HTML

            $mail->Subject = 'Forgot Password';
            $mail->Body = "<H2> Hello User! <H2/> \n\n <H2>We Got Your Request for Forgot Password.<H2/> \n\n <a href = $link >Click Here<a/> to Reset Password";
            $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

            if (!$mail->send()) {
                $mailStatus = "Cannot send the mail to User";
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            } else {

                $mailStatus = "Forgot Password Email Sent Successfully";
            }

            return $mailStatus;

            // header("Location: $resendLink");
            // exit(0);
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ),
            );
        }
    }

    public function resetPassword($data)
    {
        try {

            // if($mailExpairyTime)

            $db = $this->dbConnect();

            // // Display result
            // $token = $params['token'];
            $url = $_GET['url'];
            $urlParam = explode('/', $url);
            // print_r($urlParam);exit;
            $token = $urlParam[2];
            // echo $token;exit;

            //Calling Mail Expiry Function
            $mailExpairyTime = $this->mailExpairy($token);

            //query for get user id for insert token

            $tokenCheck = "SELECT fp.user_id, fp.validate_token, fp.created_date, u.id
            FROM tbl_forgot_password fp
            INNER JOIN tbl_users u ON fp.user_id = u.id
            WHERE fp.validate_token = '" . $token . "' AND fp.status = 1 ";
            $tokenResult = mysqli_query($db, $tokenCheck);
            $tokenData = mysqli_fetch_assoc($tokenResult);

            $userId = $tokenData["id"];

            //Calling Validation Function For Validate Fields
            $validationData = array("New Password" => $data['newPassword'], "Confirm Password" => $data['confirmPassword']);
            $this->validateInputDetails($validationData);

            $newPassword = $data['newPassword'];
            $confirmPassword = $data['confirmPassword'];

            if ($newPassword != $confirmPassword) {
                throw new Exception("Password & Confirm Password Must Be Same");
            }
            //Calling function for validate Password
            // $this->validatePassword($newPassword);

            $hashPassword = hash('sha256', hash('sha256', $newPassword));
            $dateNow = date("Y-m-d H:i:s");
            $UpdatePassword = "UPDATE tbl_users set password = '" . $hashPassword . "', updated_date = '" . $dateNow . "' where id = '" . $userId . "' ";
            $result = mysqli_query($db, $UpdatePassword);
            if ($db->query($UpdatePassword) === true) {
                $db->close();
                $statusCode = "200";
                $statusMessage = "Password Changed Successfully";

                $mailExpiryByPasswordChange = $this->mailExpirybyPasswordChange($token);

            } else {
                $statusCode = "500";
                $statusMessage = "Unable to Change Password, please try again later";
            }
            $resultArray = array(
                "apiStatus" => array(
                    "code" => $statusCode,
                    "message" => $statusMessage,
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

    //Function for Mail Expairy timing
    public function mailExpairy($token)
    {
        // $url =  $_GET['url'];
        // $urlParam = explode('/', $url);
        // print_r($urlParam);exit;
        // $token =  $urlParam[2];
        // print_r($token);exit;
        // $token =
        if (empty($token)) {
            throw new Exception("Invalid Token");
        }
        $db = $this->dbConnect();
        // echo $token;exit;
        //query for get created date
        $query = "SELECT user_id, validate_token, created_date, status from tbl_forgot_password where validate_token = '" . $token . "'";
        $result = $db->query($query);
        $row_cnt = mysqli_num_rows($result);
        // echo $row_cnt;
        $tokenData = mysqli_fetch_array($result, MYSQLI_ASSOC);
        $status = $tokenData['status'];
        // print_r($tokenData);exit;
        // echo $status;exit;

        if ($row_cnt > 0) {
            //get current date time for check expiry by time
            $currentDateTime = date("Y-m-d H:i:s");
            $creted_date = $tokenData['created_date'];
            $creted_date_to_srting = strtotime($creted_date);
            $expiry_time = $creted_date_to_srting + (60 * 60);
            $expiry_date = date("Y-m-d H:i:s", $expiry_time);

            //get time difference
            if ($currentDateTime > $expiry_date || $status == 0) {
                throw new Exception("Your Mail Link Has Expired");
            }
        } else if ($tokenData['validate_token'] != $token) {
            throw new Exception("Invalid Token");
        } else {
            throw new Exception("Your Mail Link Has Expired");
        }

    }

    public function validateToken($request)
    {
        try {
            // echo "validarw";
            // print_r($request);exit;
            $validate_token = $request['token'];
            $db = $this->dbConnect();
            // echo $token;exit;
            //query for check the token is valid
            $query = "SELECT validate_token, status from tbl_forgot_password where validate_token = '" . $validate_token . "'";
            $result = $db->query($query);
            $row_cnt = mysqli_num_rows($result);
            // echo $row_cnt;exit;
            if ($row_cnt > 0) {
                return array(
                    "apiStatus" => array(
                        "code" => "200",
                        "message" => "Token is Valid",
                    ));
            } else {
                return array(
                    "apiStatus" => array(
                        "code" => "400",
                        "message" => "Invalid Token!",
                    ));
            }
            // $tokenExpiry = $this->mailExpairy($validate_token);
            // if($tokenExpiry){
            //     throw new Exception("Mail expired");
            // }else{
            //     throw new Exception("mail valid");
            // }
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",

                    "message" => $e->getMessage(),
                ));
        }

    }

    //Function For Mail Expiry After Password Changed
    public function mailExpirybyPasswordChange($token)
    {
        $db = $this->dbConnect();
        //Set Status = 0 Once Password Changed
        $query = "UPDATE tbl_forgot_password set status = 0 where validate_token = '" . $token . "' ";
        $result = $db->query($query);

    }

    //Function for getExisting Token and Set status = 0
    public function deactivateExistingToken($data)
    {
        // echo "exist";exit;
        // print_r($data);exit;
        $email = $data['email_id'];
        // echo $email;exit;
        $db = $this->dbConnect();

        //Get active validate token using email id
        $query = "SELECT u.id, fp.user_id, fp.validate_token
        FROM tbl_users u
        INNER JOIN tbl_forgot_password fp ON u.id = fp.user_id
        WHERE u.email_id = '" . $email . "' AND fp.status = 1";
        $result = $db->query($query);
        $resultArray = mysqli_fetch_assoc($result);
        $row_cnt = mysqli_num_rows($result);
        // echo $row_cnt;exit;

        $userId = $resultArray['user_id'];
        // echo $userId;
        // echo "  .....   ";
        // print_r($resultArray['id']);exit;
        // print_r($resultArray);exit;

        if ($result) {
            //Deactive unused validate token
            $update = "UPDATE tbl_forgot_password set status = 0 where user_id = '" . $userId . "'";
            $result = $db->query($update);

        }
    }

    public function resendMail($data)
    {
        try {
            //    $this->deactivateExistingToken($data);
            $db = $this->dbConnect();
            // print_r($data);exit;
            //    print_r($userId) ;exit;
            // $url =  $_GET['url'];
            // $urlParam = explode('/', $url);
            // $email =  $urlParam[2];
            // $token = $urlParam[3];
            // echo $email . " br " . $token;exit;
            // echo "hello";exit;
            $email = $data['emailId'];
            // $this->validateEmail($email);
            // $email = trim($email);
            // echo $email;exit;

            //query for get user id using emailid
            $query = "SELECT id,email_id FROM tbl_users ";
            $query .= " WHERE email_id = '" . $email . "'";
            $result = $db->query($query);
            if ($result) {
                $row_cnt = mysqli_num_rows($result);
                // echo "$row_cnt";
                if ($row_cnt > 0) {
                    $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
                    $userId = ($data['id']);
                    // echo $userId;exit;
                    // return $userId;
                } else {
                    throw new Exception("Invalid Username");
                }
            } else {
                throw new Exception("Invalid Username");
            }

            // $existToken = $this->deactivateExistingToken($token);
            $this->deactivateExistingToken($data);
            $resendMail = $this->sendForgotPasswordMail($email);
            // exit;
            // if ($resendMail) {
            //     // echo 'email';exit;
            //     // $email;exit;
            // }
            if ($resendMail) {

                $insertToken = $this->insertValidateToken($userId);
                if ($insertToken) {
                    return array(
                        "apiStatus" => array(
                            "code" => "200",
                            "message" => "Mail Sent Sucessfully",
                        ));
                } else {
                    throw new Exception("Mail Not Sent");
                }
            }
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }

    /**
     * Function is to check the Login Authendication By token
     *
     * @param array $request
     * @throws Exception
     * @return multitype:
     */
    public function tokenCheck($token = "")
    {
        try {

            if (empty($token)) {

                throw new Exception("Please give the Token");
            }

            $db = $this->dbConnect();
            // $token = explode(" ", $request);
            $query = "SELECT a.id, u.email_id, a.user_id,c.role_id,b.role_name FROM tbl_user_login_log a
            LEFT JOIN tbl_user_role_map c ON a.user_id = c.user_id
            LEFT JOIN tbl_role b ON c.role_id = b.id
            LEFT JOIN tbl_users u ON a.user_id = u.id
            WHERE a.token = '$token'";
            // print_r($query);exit;
            $result = $db->query($query);
            $row_cnt = mysqli_num_rows($result);
            $data = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if ($row_cnt < 1) {
                throw new Exception("Unauthorized Login");
            }
            // echo $data;exit;
            return $data;
        } catch (Exception $e) {
            $this->loginLogCreate($e->getMessage(), "", getcwd());
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

    public function changePassword($data, $token)
    {
        try {
            // echo " this is change pass";exit;
            // print_r($token);exit;
            $db = $this->dbConnect();
            if (empty($token)) {
                print_r($token);exit;
                throw new Exception("Please give token");
            }
            if (empty($data['currentPassword'])) {
                throw new Exception("Current Password is required");
            } else {
                $currentPassword = $data['currentPassword'];
            }
            if (empty($data['newPassword'])) {
                throw new Exception("New Password is required");
            } else {
                $newPassword = $data['newPassword'];
            }
            if ($currentPassword === $newPassword) {
                throw new Exception("New Password should not be same as current password");
            }
            // $this->validatePassword($newPassword);
            //query for get user id from token
            $tokenQuery = "SELECT user_id from tbl_user_login_log where token = '" . $token . "' and status = 1";
            $tokenResult = $db->query($tokenQuery);
            $tokenData = mysqli_fetch_assoc($tokenResult);
            $tokenUserId = $tokenData['user_id'];
            // echo $tokenUserId;exit;

            //query for get user id from user tables
            $userIdQuery = "SELECT id from tbl_users where id = '" . $tokenUserId . "' and status = 1";
            $userIdResult = mysqli_query($db, $userIdQuery);
            $userIdData = mysqli_fetch_assoc($userIdResult);
            $userId = $userIdData['id'];
            $row_cnt = mysqli_num_rows($userIdResult);
            // echo $userId;exit;

            //query for get user password from user tables
            $query = "SELECT id, password FROM tbl_users WHERE id = $userId AND status = 1";
            $result = mysqli_query($db, $query);
            $resultArr = mysqli_fetch_assoc($result);
            $regcurrentPassword = $resultArr['password'];

            $hashcurrentPassword = hash('sha256', hash('sha256', $currentPassword));
            $hashNewPassword = hash('sha256', hash('sha256', $newPassword));

            // echo $regcurrentPassword,"  ", $hashcurrentPassword;exit;

            if ($row_cnt == 0) {
                throw new Exception("User not found");
            }

            if ($regcurrentPassword === $hashcurrentPassword) {
                $updateQuery = "UPDATE tbl_users SET password = '$hashNewPassword' WHERE id = '" . $userId . "'";
                $updateResult = mysqli_query($db, $updateQuery);
                if ($updateResult) {
                    $resultArray = array(
                        "apiStatus" => array(
                            "code" => "200",
                            "message" => "Password changed successfully",
                        ),
                    );
                    return $resultArray;
                } else {
                    throw new Exception("Unable to Change Password!");
                }
            } else {
                throw new Exception("Current password is incorrect");
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

    // Unautherized api request
    private function handle_error($request)
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
            return $result;
        } catch (Exception $e) {
            return array(
                "apiStatus" => array(
                    "code" => "401",
                    "message" => $e->getMessage(),
                ));
        }
    }
}
