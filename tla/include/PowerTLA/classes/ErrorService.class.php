<?php


// TODO: Move this class to RESTling

class ErrorService exntends Logger{
    private $message;
    private $type;

    public function __construct($type, $message) {
        $this->log("ErrorService::__construct - FATAL ERROR: Error Service launched");
        $this->type = $type;
        $this->message = $message;
    }

    public function run() {
        if (function_exists('http_response_code'))
        {
            http_response_code(400);
        }
        else {
            header('HTTP/1.1 400 Bad Request');
        }

        $msg = "Fatal Error during ". $this->type . " phase";

        $this->log($this->type . " " . $this->message);
        if (!in_array($this->type, array("loading", "instantiation"))) {
            $msg = $this->message;
        }

        $res = array("error"   => $this->type,
                     "message" => $msg);

        header('content-type: ' . "application/json");
        print json_encode($res);
    }
}
?>
