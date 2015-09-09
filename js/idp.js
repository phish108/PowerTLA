/*jslint white: true */
/*jslint vars: true */
/*jslint sloppy: true */
/*jslint browser: true */
/*jslint todo: true */
/*jslint unparam: true */
/*jslint bitwise: true*/

/*global global*/

(function (glob) {
    var IDP = {};
    var jq,
        rsd,
        myServiceURL,
        myProfile;

    function getProfileURL() {
        if (myServiceURL &&
            myProfile &&
            myProfile.id) {
            return myServiceURL + "/user/" + myProfile.id;
        }
        return "";
    }

    function getAgentProfile() {
        var uri = getProfileURL();
        if (uri && uri.length) {
            return {
                objectType: "Agent",
                openid: uri
            };
        }
        return undefined;
    }



    function loadProfile(cbFunc, bind) {
        if (!bind) {
            bind = IDP;
        }

        function cbProfileOK(pData) {
            if (pData &&
                pData.id) {
                myProfile = pData;
                cbFunc.call(bind);
            }
        }

        function cbProfileFail(request, msg) {
            switch (request.status) {
                case 401:
                case 403:
                    break;
                default:
//                    if (request.status < 100) {
//                        // internal error
//                    }
//                    else {
//                        // server error
//                    }
                    break;
            }
        }

        if (myServiceURL && myServiceURL.length) {
            jq.ajax({
                type: "GET",
                url: myServiceURL,
                dataType: 'json',
                success: cbProfileOK,
                error: cbProfileFail
            });
        }
    }

    function setRSD(newRSD) {
        if (newRSD) {
            rsd = newRSD;
            myServiceURL = "";
            if (rsd.hasOwnProperty("engine") &&
                typeof newRSD.engine === "object" &&
                rsd.engine.hasOwnProperty("servicelink")) {
                myServiceURL = newRSD.engine.servicelink;
            }

            if (rsd.hasOwnProperty("apis") &&
                Array.isArray(rsd.apis)) {
                newRSD.apis.some(function (api) {
                    if (api.name === "org.ieee.papi") {
                        myServiceURL += api.link;
                        return true;
                    }
                });
            }
        }
    }

    /** ******************************************************************
     * Define external accessors
     */
    IDP.setRSD      = setRSD;
    IDP.getProfile  = getAgentProfile;
    IDP.load        = loadProfile;

    /** ******************************************************************
     * Expose the LRS API
     */

    if (glob.define && glob.define.amd) {
        // RequireJS  stuff
        glob.define(["jquery"], function ($) { jq = $; return IDP;});
    }
    else {
        // any other environment
        if (glob.jQuery) {
            jq = glob.jQuery;
        }
        glob.idp = IDP;
    }
} (
    function factory() {
        if (window) {
            return window;
        }
        if (global) {
            return global;
        }
        return {};
    }
));