<?php
date_default_timezone_set("Europe/Zurich");
/**
 *
 */
class QTIService extends RESTling
{
    protected $mode;
    protected $feature;

    protected function validateURI()
    {
        parent::validateURI();

        if($this->status === RESTling::OK)
        {
            $aPI = explode("/", $this->path_info);
            $this->mode    = array_shift($aPI);
            $this->feature = array_shift($aPI);

            // reset the mode and feature for Our filter API
            if (!empty($this->mode)
                && $this->mode === "statements"
                && !empty($this->feature)
                && $this->feature === "filter")
            {
                $this->mode = $this->feature;
                $this->feature = "result";
                $this->filter_id= array_shift($aPI);
            }
        }

    }
    protected function prepareOperation() {
        // This service is quite complex regarding the permitted methods.
        // the prepareOperation() method generates service functions directly from the
        // request.

        // The class provides
        $action_name = strtolower($this->method);

        // translate put and post to insert and update
        switch ($action_name) {
            case "put":
                $action_name = "insert";
                break;
            case "post":
                $action_name = "update";
                break;
            default:
                break;
        }

        if (empty($this->mode)) {
            $action_name .= "_about";
        }
        else {
            $action_name .= "_" . strtolower($this->mode);

            if (!empty($this->feature))
            {
                $action_name .= "_" . strtolower($this->feature);
            }
        }

        $this->operation = $action_name;
    }


}
