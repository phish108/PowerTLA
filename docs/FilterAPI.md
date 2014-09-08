# XAPI Filters

PowerTLA's LRS interfaces implements XAPI version 1.0.

## Status of development (WARNING)

The current version of PowerTLA is under development and does not (yet) implement all features.

If you are missing a feature, please head over to github and set an issue.

## Introduction

## Underlying Concepts

## XAPI selectors

```
   {
      "id": "filterURI",     // the official reference to the filter, should provice a description
      "scope": ["selector"]  // additional path info data will be assigned to the provided call scope variables
      "query": [             // arrays refer to OR statements
        {                    // objects refer to AND statements
           "context.statement.id": { // dot notation for filter parameter
               "param": "keyname",   // param: keyname pair indicates required GET parameters; multiple for complex selects
               "map": {              // for param clauses "map" indicates how the param should be used.
                  "query": {
                     "verb.id": { "value": "http://ilias.org/vocab/course/participation"},
                     "result.success": {"!value": true}, // leading ! means NOT
                     "object.id": {"map": "http://foo.bar.com/xyz/{param}"} // '{param}' indicates where the param should be mapped
                  }
               }
           },
           "result.score.raw": {"value": 1},    // explicit value
           "agent.id": {"value": ["mailto:a@b.com", "mailto:b@b.com"]}, // several values possible
           "agent.id": {"param": "keyname", "map": "mailto:{param}"}    // simple parameter mapping (if no subqueries are needed)
        }
      ],
     }
```

## Defining a filter

### Scope vs. Query Parameters

A scope defines a selectors that limits a filter. A scope can have exact one
value that is passed as part of the request URL. A scope is always OPTIONAL!

```
   http://url.to/PowerTLA/lrs.php/statements/filter/myfilter/scope
```

Typical examples for scopes are courses, group work, locations etc.

Query parameters offer more flexible ways for limiting a filter, because a
parameter can have multiple values. Query paramters are passed via the URL's
query string. A query parameter is ALWYAS required!

```
   http://url.to/PowerTLA/lrs.php/statements/filter/otherfilter?param1=value1&param2=value2&param1=value3
```

The example shows a filter that requires two parameters (param1 and param2),
while param2 has one value, param1 receives 2 values.

