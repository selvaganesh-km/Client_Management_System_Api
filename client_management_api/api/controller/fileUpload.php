<?php
// echo "from fileUpload";exit;
require_once "model/fileUpload.php";
class FILEUPLOAD extends FILEUPLOADMODEL{

    public function fileUploadCtrl($data,$token){
        try {
            $response = $this->processList($data,$token);
            echo $this->json($response);
            exit();
        } catch (Exception $e) {
           $e->getMessage();
        }
    }
}

$classActive = new FILEUPLOAD();
$classActive->fileUploadCtrl($data, $token);

?>