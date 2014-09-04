<?php

class XAPIFilter
{
    protected $dbh;
    protected $param;
    public function __construct($dbh)
    {
        $this->dbh = $dbh;
        $this->param = array();
    }

    /**
     * addSelect initializes the filter for a given selector
     *
     * the selector format
     *
     * {
     *    "id": "filterURI",     // the official reference to the filter, should provice a description
     *    "query": [             // arrays refer to OR statements
     *      {                    // objects refer to AND statements
     *         "context.statement.id": { // dot notation for filter parameter
     *             "param": "keyname",   // param: keyname pair indicates required GET parameters; multiple for complex selects
     *             "map": {              // for param clauses "map" indicates how the param should be used.
     *                "query": {
     *                   "verb.id": { "value": "http://ilias.org/vocab/course/participation"},
     *                   "result.success": {"!value": true}, // leading ! means NOT
     *                   "object.id": {"map": "http://foo.bar.com/xyz/{param}"} // '{param}' indicates where the param should be mapped
     *                }
     *             }
     *         },
     *         "result.score.raw": {"value": 1},    // explicit value
     *         "agent.id": {"value": ["mailto:a@b.com", "mailto:b@b.com"]}, // several values possible
     *         "agent.id": {"param": "keyname", "map": "mailto:{param}"}    // simple parameter mapping (if no subqueries are needed)
     *      }
     *    ],
     *
     *   // trigger API
     *   "action": {
     *      "experience": {}, // record a new experience; use array notation for multiple experiences
     *      "service": {},    // trigger an service with specific parameters, use array notation for multiple service calls
     *   }
     * }
     *
     * mapped queries will always result in a list of statement ids
     * filter queries will always result in a list of statements
     *
     * action triggers are only executed on insert
     */
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
