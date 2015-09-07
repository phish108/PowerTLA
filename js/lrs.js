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
    /** ******************************************************************
     * Private Members
     */

    var jq,
        bSync         = false,
        myServiceURL  = null,
        idurl         = null,
        rsd           = {},

        /**
         * Local activity stream.
         *
         * Must not be exposed externally.
         */
        stream      = [],
        adminStream = [],
        upstream    = [], // upload cache

        /**
         * internal action map of not terminated actions.
         *
         * This adheres that actions if the official stream must not get
         * updated.
         */
        ongoing = {},

        /**
         * maps uuids to the local activity stream.
         *
         * This is used for quick access to actions.
         */
        uuidMap = {},

        /**
         * active Contexts
         */
        context = {},

        /**
         * The ACTIVE actor context.
         */
        actor = {},

        /**
         * State Documents
         */
        stateDocs = {},

        /**
         * The local Actor context.
         *
         * The local actor is the active user interacting with the system.
         *
         * This is not necessarily the same actor than the actions that are
         * recorded.
         */
        localActor = {},

        /**
         * The main accessor object.
         */
        LRS = {};

    /**
     * Helper function to create random UUIDs
     */
    function makeUUID() {
        var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx',
            retval;

        function mapChar(c) {
            var r = Math.random()*16|0,
                v = c === 'x' ? r : (r&0x3|0x8);
            return v.toString(16);
        }

        while (retval === undefined ||
               uuidMap.hasOwnProperty(retval) ||
               ongoing.hasOwnProperty(retval)) {
            retval = uuid.replace(/[xy]/g, mapChar);
        }
        return retval;
    }

    /**
     * Deep clone of an object
     *
     * Helper function to avoid accidental cross references
     */
    function cloneObject(obj) {
        var object;
        if (typeof obj === 'string') {
            return obj;
        }

        if (typeof obj === "object") {
            if (Array.isArray(obj)) {
                object = [];
                obj.forEach(function (e) {
                    object.push(cloneObject(e));
                });
            }
            else {
                object = {};
                Object.getOwnPropertyNames(obj).forEach(function (k) {
                    object[k] = cloneObject(obj[k]);
                });
            }
        }
        return object;
    }

    /**
     * returns a unmutable copy of the stream
     */
    function getStream() {
        return cloneObject(stream);
    }

    /**
     * Start an activity context.
     *
     * Provides a safe way for setting and unsetting action contexts.
     *
     * This function accepts an action context object.
     *
     * It will *extend* any existing activty context that is already active.
     */
    function startContext(nContext) {
        var bad = false,
            aOKNames = ["registration", "contextActivities",
                        "statement", "language"],
            bOKNames = ["parent", "grouping", "category", "other"],
            mapObj = {};

        if (typeof nContext === 'object') {
            Object.getOwnPropertyNames(nContext).forEach(function (nm) {
                if (aOKNames.indexOf(nm) >= 0) {
                    if (nm === "contextActivities" &&
                        typeof nContext[nm] === "object") {
                        mapObj[nm] = {};
                        Object.getOwnPropertyNames(nContext[nm]).forEach(function (at) {
                            if (bOKNames.indexOf(at) >= 0) {
                                mapObj[nm][at] = nContext[nm][at];
                            }
                            else {
                                bad = true;
                            }
                        });
                    }
                    else if (typeof nContext[nm] === "string") {
                        if (nm === "language") {
                            if (nContext[nm].length === 2 ||
                                (nContext[nm].length === 5 &&
                                 nContext[nm].indexOf("-") === 2)) {
                                mapObj[nm] = nContext[nm];
                            }
                        }
                        else {
                            mapObj[nm] = nContext[nm];
                        }
                    }
                }
                else {
                    bad = true;
                }
            });

            if (!bad) {
                Object.getOwnPropertyNames(mapObj).forEach(function (nm) {
                    context[nm] = mapObj[nm];
                });
            }
        }
    }

    /**
     * Removes a partial context from the activty context
     */
    function endContext(nContext) {
        if (typeof nContext === 'object') {
            Object.getOwnPropertyNames(nContext).forEach(function (nm) {
                if (context.hasOwnProperty(nm)) {
                    delete context[nm];
                }
            });
        }
    }

    /**
     * Exposes the context object.
     */
    function getContext() {
        return cloneObject(context);
    }

    /**
     * resets the context
     */
    function resetContext() {
        context = {};
    }

    /**
     * sets an actor by PowerTLA token.
     */
    function setActor(nActor) {
        var tActor = {"objectType": "Agent"};
        var bOK = false;
        if (typeof nActor === "object") {
            if (nActor.openid &&
                typeof nActor.openid === "string") {
                tActor.openid = nActor.openid;
                bOK = true;
            }

            // handle the other flags
            if (nActor.name &&
                typeof nActor.name === "string") {
                tActor.name = nActor.name;
            }

            if (nActor.mbox &&
                typeof nActor.mbox === "string" &&
                nActor.mbox.indexOf("mailto:") === 0) {
                tActor.mbox = nActor.mbox;
                bOK = true;
            }

            if (nActor.mbox_sha1sum &&
                typeof nActor.mbox_sha1sum === "string") {
                tActor.mobx_sha1shum = nActor.mbox_sha1sum;
                bOK = true;
            }

            if (nActor.account &&
                typeof nActor.account === "object") {
                tActor.account = {};
                if (nActor.account.name &&
                    typeof nActor.account.name === "string") {
                    tActor.account.name = nActor.account.name;
                    bOK = true;
                }
                if (nActor.account.homePage &&
                    typeof actor.account.homePage === 'string') {
                    tActor.account.homePage = nActor.account.homePage;
                    bOK = true;
                }
            }
        }
        if (bOK) {
            actor = tActor;
        }
    }

    /**
     * removes the active actor, unless it is the same as the
     * local actor.
     */
    function unsetActor() {
        actor = localActor;
    }

    /**
     * starts an action and returns its uuid.
     *
     * this function instantiates a new XAPI action, without entering it
     * to the activity stream. With any other respect, this function creates
     * a valid XAPI statement, that can get used for contextualising other
     * actions. However, a remote LRS may reject the other statements, if
     * the contextualising action is not in the stream.
     *
     * The action is only send to the remote LRS if it is terminated with
     * endAction().
     *
     * The combination of startAction() and endAction is useful for the
     * automatic timing of durations.
     */
    function startAction(verbid, object) {
        var uuid;

        if (verbid &&
            verbid.length &&
            typeof object === "object" &&
            object.hasOwnProperty("id") &&
            object.id.length) {

            uuid = makeUUID();


            var now = new Date();
            var action = {
                "id": uuid,
                "timestamp": now.toISOString(),
                "actor": cloneObject(actor),
                "verb": {
                    id: verbid
                },
                "object": cloneObject(object)
            };

            if (Object.getOwnPropertyNames(context).length) {
                action.context = cloneObject(context);
            }

            ongoing[uuid] = {"action": action, "start": now};
        }

        return uuid;
    }

    /**
     * ends an action and marks it for delivery
     */
    function endAction(uuid, result) {
        if (uuid &&
            ongoing.hasOwnProperty(uuid)) {
            var now = new Date();
            var duration = "P";

            if (result &&
                typeof result === "object") {
                var bad = false,
                    aOKResults = ["score", "completion", "success",
                                  "response", "duration", "extensions"];
                // NOTE - Duration is accepted as a result, but it will be
                // overwritten with the real duration from start to finish.

                // check for valid fields
                var arv = Object.getOwnPropertyNames(result);
                arv.some(function (n) {
                    if (aOKResults.indexOf(n) < 0) {
                        bad = true;
                        return bad;
                    }
                });
                if (!bad) {
                    ongoing[uuid].action.result = cloneObject(result);

                    // add duration
                    var dt, s, m, h;

                    dt = now - ongoing[uuid].start;

                    s = Math.floor(dt/1000);
                    m = Math.floor(s/60);
                    h = Math.floor(m/60);

                    s = s - m*60;
                    m = m - h*60;

                    duration = "PT" + h + "H" + m + "M" + s + "S";
                }
            }
            else {
                ongoing[uuid].action.result = {};
            }

            ongoing[uuid].action.result.duration = duration;

            uuidMap[uuid] = stream.length;
            stream.push(ongoing[uuid].action);

            delete ongoing[uuid];
        }
    }

    /**
     * writes an action directly to the activity stream
     *
     * returns the new UUID
     */
    function recordAction(verbid, object, result) {
        var uuid, arv;

        if (verbid &&
            verbid.length &&
            typeof object === "object" &&
            object.hasOwnProperty("id") &&
            object.id.length) {

            uuid = makeUUID();

            var action = {
                "id": uuid,
                "timestamp": new Date().toISOString(),
                "actor": cloneObject(actor),
                "verb": {
                    id: verbid
                },
                "object": cloneObject(object)
            };

            if (Object.getOwnPropertyNames(context).length) {
                action.context = cloneObject(context);
            }

            if (result && typeof result === "object") {
                var bad = false,
                    aOKResults = ["score", "completion", "success",
                                  "response", "duration", "extensions"];

                // check for valid fields
                arv = Object.getOwnPropertyNames(result);
                arv.some(function (n) {
                    if (aOKResults.indexOf(n) < 0) {
                        bad = true;
                        return bad;
                    }
                });

                if (!bad) {
                    action.result = cloneObject(result);
                }
            }

            uuidMap[uuid] = stream.length;
            stream.push(action);
        }

        return uuid;
    }

    /**
     * returns the last *completed* statement on the activity stream
     */
    function lastAction() {
        return stream[stream.length - 1];
    }

    function setStateDoc(uuid, stateid, doc) {
        if (typeof doc === "object") {
            if (!stateDocs.hasOwnProperty(uuid)) {
                stateDocs[uuid] = {};
            }
            if (!stateDocs[uuid].hasOwnProperty(stateid)) {
                stateDocs[uuid][stateid] = {};
            }
            Object.getOwnPropertyNames(doc).forEach(function (nm) {
                stateDocs[uuid][stateid][nm] = doc[nm];
            });
        }
    }

    function getStateDoc(uuid, stateid) {
        if (stateDocs.hasOwnProperty(uuid) &&
            stateDocs[uuid].hasOwnProperty(stateid)) {
            return stateDocs[uuid][stateid];
        }
    }

    /**
     * callback functions for network requrests
     */

    function pushSuccess() {
        bSync = false;
        upstream = [];
    }

    function cbError(req) {
        bSync = false;
    }

    /**
     * loads the actor data from the local PowerTLA service.
     * The local actor is the default actor, if no other actor is set
     * via setActor();
     */
    function initLocalActor(cbFunc, bind) {
        if (!bind) {
            bind = LRS;
        }

        function cbLoadActorSuccess(data) {
            if (data &&
                data.id) {
                localActor = {
                    "objectType": "Agent",
                    "openid": idurl + "/user/" + data.id
                };

                if (!actor ||
                    !actor.objectType) {
                    actor = localActor;
                }

                cbFunc.call(bind, data);
            }
        }

        idurl = "";
        if (rsd.engine &&
            rsd.engine.servicelink) {
            idurl = rsd.engine.servicelink;
        }
        rsd.apis.some(function (api) {
            if (api.name === "org.ieee.papi") {
                idurl += api.link;
                return true;
            }
        });

        // now we have the link, fetch the data
        if (idurl &&
            jq) {
            jq.ajax({
                type: "GET",
                url: idurl,
                dataType: 'json',
                success: cbLoadActorSuccess,
                error: cbError
            });
        }
    }

    /**
     * download the activity stream for the active user from the server.
     */
    function fetchStream(options, cbFunc, bind) {
        if (!bind) {
            bind = LRS;
        }

        if (!localActor || !localActor.objectType) {
            throw "no authorized actor";
        }

        function fetchSuccess(data) {
            stream = data;
            uuidMap = {};
            stream.forEach(function (a, i) {
                uuidMap[a.id] = i;
            });

            cbFunc.call(bind, stream);
        }

         if (jq && myServiceURL) {
            var aopts = [];
            if (options.hasOwnProperty("verb")) {
                aopts.push("verb=" + encodeURIComponent(options.verb));
            }
            if (options.hasOwnProperty("object")) {
                aopts.push("activity=" + encodeURIComponent(options.object));
            }
            if (options.hasOwnProperty("activity")) {
                aopts.push("activity=" + encodeURIComponent(options.activty));
            }

            var uri = myServiceURL + "/statements";

            aopts.push("agent=" + encodeURIComponent(JSON.stringify(localActor)));

            if (aopts.length) {
                uri += "?" + aopts.join("&");
            }


            jq.ajax({
                type: "GET",
                url: uri,
                dataType: 'json',
                success: fetchSuccess,
                error: cbError
            });
        }
    }

    function fetchAdminStream(options, cbFunc, bind) {
        if (!bind) {
            bind = LRS;
        }

        if (!localActor || !localActor.objectType) {
            throw "no authorized actor";
        }

        function fetchSuccess(data) {
            adminStream = data;
            cbFunc.call(bind, stream);
        }

        if (jq && myServiceURL) {
            var aopts = [];
            if (options.hasOwnProperty("verb")) {
                aopts.push("verb=" + encodeURIComponent(options.verb));
            }
            if (options.hasOwnProperty("object")) {
                aopts.push("activity=" + encodeURIComponent(options.object));
            }
            if (options.hasOwnProperty("activity")) {
                aopts.push("activity=" + encodeURIComponent(options.activty));
            }

            var uri = myServiceURL + "/statements";
            if (aopts.length) {
                uri += "?" + aopts.join("&");
            }

            jq.ajax({
                type: "GET",
                url: uri,
                dataType: 'json',
                success: fetchSuccess,
                error: function () {
                    cbFunc.call(bind);
                }
            });
        }
    }



    /**
     * uploads the activity stream to the server
     */
    function pushStream() {
        // don't push if another push is in progress
        // also don't push if no local actor is present
        if (!localActor || !localActor.objectType) {
            throw "no authorized actor";
        }

        if (jq &&
            myServiceURL &&
            !bSync) {
            bSync = true;

            if (upstream) {
                upstream.concat(stream);
            }
            else {
                upstream = stream;
            }

            // flush the stream
            stream  = [];
            uuidMap = {};

            jq.ajax({
                type: "POST",
                url: myServiceURL + "/statements",
                dataType: 'json',
                contentType: 'application/json',
                success: pushSuccess,
                error: cbError,
                data: JSON.stringify(upstream)
            });
        }
    }

    /**
     * push the state documents to the server
     */
    function pushState() {
        return;
    }

    /**
     * fetch state document from the server
     */
    function fetchState() {
        return;
    }

    /**
     * Local Storage Persistency Layer
     */
    function storeStream() {
        if (glob.localStorage) {
            glob.localStorage.setItem("prwtlaXAPIStream", JSON.stringify(stream));
            glob.localStorage.setItem("pwrtlaXAPIState", JSON.stringify(stateDocs));
        }
    }

    function loadStream() {
        if (glob.localStorage) {
            var sStream = glob.localStorage.setItem("prwtlaXAPIStream"),
                sState  = glob.localStorage.getItem("pwrtlaXAPIState");

            if (sStream &&
                sStream.length) {
                stream.concat(JSON.parse(sStream));
                stream.forEach(function (a, i) {
                    uuidMap[a.id] = i;
                });
            }

            if (sState &&
                sState.length) {
                var tObj = JSON.parse(sState);
                Object.getOwnPropertyNames(tObj).forEach(function (nm) {
                    stateDocs[nm] = tObj[nm];
                });
            }
        }
    }

    function flushStream() {
        if (glob.localStorage) {
            glob.localStorage.setItem("prwtlaXAPIStream", "[]");
            glob.localStorage.setItem("pwrtlaXAPIState", "{}");
        }

        stateDocs = {};
        stream = [];
    }

    function setRSD(newRSD) {
        if (newRSD) {
            rsd = newRSD;
            myServiceURL = "";
            if (newRSD.hasOwnProperty("engine") &&
                typeof newRSD.engine === "object" &&
                newRSD.engine.hasOwnProperty("servicelink")) {
                myServiceURL = newRSD.engine.servicelink;
            }

            if (newRSD.hasOwnProperty("apis") &&
                Array.isArray(newRSD.apis)) {
                newRSD.apis.some(function (api) {
                    if (api.name === "gov.adlnet.xapi") {
                        myServiceURL += api.link;
                        return true;
                    }
                });
            }
        }
    }

    function ownActions() {
        return stream.length;
    }

    function otherActions() {
        return adminStream.length;
    }

    /** ******************************************************************
     * Define external accessors
     */

    LRS.setActor      = setActor;
    LRS.unsetActor    = unsetActor;

    LRS.startContext  = startContext;
    LRS.endContext    = endContext;
    LRS.clearContext  = resetContext;
    LRS.getContext    = getContext;

    LRS.startAction   = startAction;
    LRS.endAction     = endAction;
    LRS.recordAction  = recordAction;
    LRS.getStream     = getStream;
    LRS.lastAction    = lastAction;

    // TODO add State Document API
    LRS.setStateDoc   = setStateDoc;
    LRS.getStateDoc   = getStateDoc;

    LRS.fetchAgent    = initLocalActor;

    LRS.fetch         = fetchStream;
    LRS.fetchAdmin    = fetchAdminStream;
    LRS.push          = pushStream;

    LRS.store         = storeStream;
    LRS.load          = loadStream;
    LRS.flush         = flushStream;

    LRS.setRSD        = setRSD;
    LRS.myActions     = ownActions;
    LRS.adminActions  = otherActions;

    /** ******************************************************************
     * Expose the LRS API
     */

    if (glob.define &&
        glob.define.amd) {
        // RequireJS  stuff
        glob.define(["jquery"], function ($) { jq = $; return LRS;});
    }
    else {
        // any other environment
        if (glob.jQuery) {
            jq = glob.jQuery;
        }
        glob.lrs = LRS;
    }
} ((function () {
    if (window) {
            return window;
        }
        if (global) {
            return global;
        }
        return {};
}())));
