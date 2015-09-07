/*jslint white: true */
/*jslint vars: true */
/*jslint sloppy: true */
/*jslint browser: true */
/*jslint todo: true */
/*jslint unparam: true */
/*jslint bitwise: true*/
/*jslint devel: true*/

/*global global*/

(function (glob) {
    var RSD = {};
    var jq,
        rsd;

    function dirname(path) {
        if (path !== "/") {
            var aDir = path.split("/");
            if (aDir && aDir.length) {
                aDir.pop();
            }

            var dir = aDir.join("/");
            return dir.length ? dir : "/";
        }
        return path;
    }

    function getRSD() {
        return rsd;
    }

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
                            glob.console.log("cannot load rsd " + msg);
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

    /** ******************************************************************
     * Define external accessors
     */
    RSD.get   = getRSD;
    RSD.load  = loadRSD;

    /** ******************************************************************
     * Expose the LRS API
     */

    if (glob.define && glob.define.amd) {
        // RequireJS  stuff
        glob.define(["jquery"], function ($) { jq = $; return RSD;});
    }
    else {
        // any other environment
        if (glob.jQuery) {
            jq = glob.jQuery;
        }
        glob.rsd = RSD;
    }
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