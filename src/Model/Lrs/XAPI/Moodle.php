<?php

namespace PowerTLA\Model\LRS\XAPI;

use \PowerTLA\Model\Identity\Webfinger\Moodle as Webfinger;

class Moodle extends \PowerTLA\Model\LRS\XAPI
{
    private $webfingerModel;

    protected function getWebfingerModel() {
        if (!$this->webfingerModel) {
            $this->webfingerModel = new Webfinger();
        }
        return $this->webfingerModel;
    }

    protected function findDataByAttr($tableName, $attrMap, $dataField = ["*"]) {
        global $DB;
        if (empty($tableName)) {
            throw new \PowerTLA\Exception\MissingDatabaseTableName();
        }
        if (empty($attrMap)) {
            throw new \PowerTLA\Exception\MissingDatabaseFilterMap();
        }

        $attrMap = $this->fixFilter($attrMap);

        if (empty($dataField)) {
            $dataField = ['*'];
        }

        try {
            $rec = $DB->get_record($tableName, $attrMap, join(',', $dataField));
        }
        catch (Exception $err) {
            throw new \PowerTLA\Exception\InternalDatabaseException();
        }

        if (!$rec) {
            throw new \RESTling\Exception\NotFound();
        }
        return $rec;
    }

    protected function filterDataByAttr($tableName, $attrMap, $dataField = null) {
        global $DB;
        if (empty($tableName)) {
            throw new \PowerTLA\Exception\MissingDatabaseTableName();
        }
        if (empty($attrMap)) {
            throw new \PowerTLA\Exception\MissingDatabaseFilterMap();
        }

        if (empty($dataField)) {
            $dataField = ['*'];
        }

        try {
            $rec = $DB->get_records($tableName, $attrMap, join('*',$dataField));
        }
        catch (Exception $err) {
            throw new \PowerTLA\Exception\InternalDatabaseException();
        }

        if (!$rec) {
            throw new \RESTling\Exception\NotFound();
        }

        return (array)$rec;
    }

    protected function addData($tableName, $attrMap) {
        global $DB;
        if (empty($tableName)) {
            throw new \PowerTLA\Exception\MissingDatabaseTableName();
        }
        if (empty($attrMap)) {
            throw new \PowerTLA\Exception\MissingDatabaseFilterMap();
        }

        try {
            $DB->insert_record($tableName, $attrMap);
        }
        catch (Exception $err) {
            throw new \PowerTLA\Exception\InternalDatabaseException();
        }
    }

    protected function deleteData($tableName, $attrMap) {
        global $DB;
        if (empty($tableName)) {
            throw new \PowerTLA\Exception\MissingDatabaseTableName();
        }
        if (empty($attrMap)) {
            throw new \PowerTLA\Exception\MissingDatabaseFilterMap();
        }

        try {
            $DB->delete_records($tableName, $attrMap);
        }
        catch (Exception $err) {
            throw new \PowerTLA\Exception\InternalDatabaseException();
        }
    }

    protected function updateData($tableName, $attrMap) {
        global $DB;
        if (empty($tableName)) {
            throw new \PowerTLA\Exception\MissingDatabaseTableName();
        }
        if (empty($attrMap)) {
            throw new \PowerTLA\Exception\MissingDatabaseFilterMap();
        }

        try {
            $DB->update_record($tableName, $attrMap);
        }
        catch (Exception $err) {
            throw new \PowerTLA\Exception\InternalDatabaseException();
        }
    }

    protected function fetchStream($output) {
        global $DB;
        $sort   = "";
        $fields = $this->fields;
        $table  = $this->table;
        $tSort  =[];
        $select = null;
        $param  = null;

        if ($fields == "document") {
            $fields .= ", id, updated";
        }

        foreach ($this->order as $k => $v) {
            $tSort[] = "$k $v";
        }
        $sort = join(", ", $tSort);

        if (!empty($this->filter)) {
            $where = [];
            $whereExtra = [];
            $param = [];
            $paramExtra = [];
            if (array_key_exists("issued", $this->filter)) {
                $issued = $this->filter["issued"];
                unset($this->filter["issued"]);

                if (isset($issued[0])) {
                    $whereExtra[] = "issued > ?";
                    $paramExtra[] = $issued[0];
                }

                if (isset($issued[1])) {
                    $whereExtra[] = "issued <= ?";
                    $paramExtra[] = $issued[1];
                }
            }

            foreach ($this->filter as $key => $value) {
                $where[] = "$key = ?";
                $param[] = $value;
            }

            $where = array_merge($where, $whereExtra);
            $param = array_merge($param, $paramExtra);

            $select = join(' AND ', $where);
        }

        try {
            $records = $DB->get_recordset_select($table, $select, $param, $sort, $fields, 0, $this->limit);
        }
        catch (Exception $err) {
            $records = null;
        }

        if ($records) {
            $rf = $this->fields;
            foreach ($records as $record) {
                $output->data($record->$rf);
            }
        }
    }
}

?>
