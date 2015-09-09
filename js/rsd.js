/*jslint white: true */
/*jslint vars: true */
/*jslint sloppy: true */
/*jslint browser: true */
/*jslint todo: true */
/*jslint unparam: true */
/*jslint bitwise: true*/

/*global global*/

(function (glob) {
    var RSD = {};
    var jq,
        rsd;

    /**
     * moves one directory up on a given path.
     */
    function dirname(path) {
        if (path !== "/") {
            var aDir = path.split("/");
            if (aDir && aDir.length > 1) {
                aDir.pop();
            }

            var dir = aDir.join("/");
            return dir.length ? dir : "/";
        }
        return path;
    }

    /**
     * Remote Service Definition accessor.
     *
     * returns the RSD object or undefined (if not yet loaded)
     */
    function getRSD() {
        return rsd;
    }

    /**
     * tries to load the remote service definition from the active href.
     *
     * if no RSD meta-tag is provided in the document, this function will
     * try to detect the RSD by traversing the href path and look for
     * PowerTLA's rsd.php
     *
     * The function accepts a callback function that will be called when
     * the detection process terminates. If no RSD can be found, the
     * callback will receive and empty rsd object (without any properties).
     * Otherwise the identified rsd will be provided.
     */
    function loadRSD(cbFunc, bind) {
        if (!bind) {
            bind = RSD;
        }

        var host, dir;
        if (glob.document &&
            glob.document.location) {
            host  = glob.document.location.origin;
            dir   = glob.document.location.pathname;
        }

        function tryRSDPath() {
            if (jq && jq.ajax) {
                jq.ajax({
                    type: "GET",
                    dataType: "json",
                    url: host + (dir === "/"? "": dir) + "/tla/rsd.php",
                    success: function (data) {
                        rsd = data;
                        cbFunc.call(bind, rsd);
                    },
                    error: function (xhr, msg) {
                        if (xhr.status === 404 && dirname !== "/") {
                            dir = dirname(dir);
                            tryRSDPath();
                        }
                        else {
                            // call with an empty document
                            cbFunc.call(bind, {});
                        }
                    }
                });
            }
        }

        dir = dirname(dir);
        tryRSDPath();
    }

    /**
     * builds a request URL for the given protocol.
     *
     * if the protocol is not provided by the service this function
     * will return undefined.
     *
     * The function accepts an optional array containing url
     * encoded path parameters.
     */
    function getServiceURL(protocol, pathParam) {
        var retval;
        if (rsd &&
            rsd.hasOwnProperty("apis")) {

            var url = rsd.engine.servicelink;
            rsd.apis.some(function (spr) {

                if (spr.name === protocol) {

                    retval = url + spr.link;
                    return true;
                }
            });
            if (retval &&
                Array.isArray(pathParam)) {

                retval += "?" + pathParam.join("/");
            }
        }
        return retval;
    }

    /**
     * initialises the RSD process.
     *
     * This method emits the rsdready event when the initialisation has completed.
     */
    function init() {
        RSD.load(function() {
            jq(glob.document).trigger("rsdready");
        });
    }

    /** ******************************************************************
     * Define external accessors
     */

    RSD.get        = getRSD;
    RSD.load       = loadRSD;
    RSD.serviceURL = getServiceURL;

    /** ******************************************************************
     * Expose the LRS API
     */

    if (glob.define && glob.define.amd) {
        // RequireJS  stuff
        glob.define(["jquery"], function (jQuery) {
            jq = jQuery;
            init();
            return RSD;
        });
    }
    else {
        // any other environment
        if (glob.jQuery) {
            jq = glob.jQuery;
            init();
        }
        glob.rsd = RSD;
    }

    // load the RSD information

} (
    (function factory() {
        if (window) {
            return window;
        }
        if (global) {
            return global;
        }

        return {};
    }())
));