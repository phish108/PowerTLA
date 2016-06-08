<?php

class File extends BaseHandler
{
    private $file;

    public function setFile($options) {
        $optionList = array("owner", "filename", "path");
        $opt = array();

        if (isset($options) && !empty($options)) {
            foreach ($options as $k => $v) {
                $opt[$k] = $v;
            }
        }
        $this->file = $opt;
    }

    public function checkPermission($user) {
        return true; // return false if the user must not access the object.
    }

    public function streamFileContent() {
        // TODO implement
    }
}

?>