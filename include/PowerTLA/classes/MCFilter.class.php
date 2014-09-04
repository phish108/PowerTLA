<?php

class MCFilter
{
    protected $dbh;
    protected $param;

    public function __construct($dbh)
    {
        $this->dbh = $dbh;
        $this->param = array();
    }

    public function setParams($oParam)
    {
        $this->param = $oParam;
    }

    public function addSelector($selector)
    {}

    public function setParams($oParam)
    {}

    public function runSelector()
    {}

    public function matchStatement($statement)
    {
        return false;
    }

}
?>