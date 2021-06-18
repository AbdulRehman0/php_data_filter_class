<?php
class UploadFile{
    private $file;
    private $allowedFileFormate;
    private $targetDir;
    private $fileSize;
    private $error;
    private $resp;
    function __construct($file){
        $this->file = $file;
        $this->error = ["status"=>0,"errorMsg"=>[]];
        $this->resp = [];
    }
    public function setFileFormats(Array $formats){
        $this->allowedFileFormate = $formats;
    }
    public function setFileSize($size){
        $this->fileSize = $size;
    }
    public function setDir($dir){
        $this->targetDir = $dir;
    }
    private function setError($errorMsg){
        $this->error["status"]=1;
        array_push($this->error["errorMsg"],$errorMsg);
    }
    public function getError(){
        return $this->error;
    }

    public function setMultipleFiles(){
        $_FILE =[];
            foreach($this->file as $property => $keys) {
                foreach($keys as $key => $value) {
                    $_FILE[$key][$property] = $value;
                }
            }
        $this->file = $_FILE;
    }
    private function checkFileSize(){
        
        foreach ($this->file as $value) {
            // print_r($value);die;
            if(empty($value["size"])){
                $this->setError("file [".$value['name']."] is too large max size is ".(($this->fileSize/1024)/1024)."MB");
            }else if($value["size"] > $this->fileSize){
                $this->setError("file [".$value['name']."] is too large max size is ".(($this->fileSize/1024)/1024)."MB");
            }
        }
    }
    private function checkFileFormat(){
        foreach ($this->file as $value) {
            $ext = $value["type"];
            if(!in_array($ext,$this->allowedFileFormate) || empty($ext)){
                $this->setError("file [".$value['name']."] format is not allowed");
            }    
        }
    }
    public function uploadFile(){
        $this->checkFileSize(); 
        $this->checkFileFormat();
        if($this->error["status"]){
            return ["status"=>0,"file_name"=>[],"error"=>$this->getError()];
        }

        $this->createNewFileName();
        $uploadedFilnames = [];
        foreach ($this->file as $row) {
            
            if(move_uploaded_file($row["tmp_name"],$this->targetDir.$row["name"])){
                array_push($uploadedFilnames,$row["name"]);
            }else{
                $this->setError(["Sorry, there is an error uploading your file[".$row["name"]."]"]);
            }    
        }
        if($this->error["status"]==0){
            return ["status"=>1,"file_name"=>$uploadedFilnames];
        }
        else{
            return ["status"=>0,"file_name"=>$uploadedFilnames,"error"=>$this->getError()];
        }
    }
    private function createNewFileName(){
        for ($i = 0; $i < count($this->file); $i++ ) {
            $this->file[$i]["name"]= explode(".",$this->file[$i]["name"])[0]."_".date("Y-m-d-H-i-s").".".explode(".",$this->file[$i]["name"])[1];
        }
    }
}

?>