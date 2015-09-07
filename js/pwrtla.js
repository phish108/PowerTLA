/*jslint white: true */
/*jslint vars: true */
/*jslint sloppy: true */
/*jslint browser: true */
/*jslint todo: true */
/*jslint unparam: true */
/*jslint bitwise: true*/
/*jslint devel: true*/
/*jslint plusplus: true*/

/*global lrs, rsd, console, $, jQuery*/

/**
 * PART 1 BUSINESS LOGIC
 */
var MainActionVerb    = "http://mobinaut.org/xapi/verb/creative/brainstormassign";
var IdeaActionVerb    = "http://mobinaut.org/xapi/verb/creative/ideacontribute";
var AssignActionVerb  = "http://mobinaut.org/xapi/verb/reflective/ideaassign";

var blReady = 0;
var bAdmin = false;
var bUser  = false;
var mainUUID;
var i = 0;

function cbAdminStream(ok) {
    blReady++;
    if (ok !== undefined) {
        bAdmin = true;
    }
    if (blReady > 1) {
        $(document).trigger("tlaready");
    }
}

function cbMyStream(ok) {
    blReady++;
    if (ok !== undefined) {
        bUser = true;
    }
    if (blReady > 1) {
        $(document).trigger("tlaready");
    }
}

$(document).bind("xapiready", function() {
    lrs.fetch({verb: MainActionVerb,
               object: document.location.href}, cbMyStream);
    lrs.fetchAdmin({verb: MainActionVerb,
                    object: document.location.href},
                   cbAdminStream);
});

$("#content-stop").bind("click", function() {
    $("#content-next").addClass("hidden");
    $("#content-stop").addClass("hidden");

    var s = lrs.getStream();
    $("#content-stats")
        .text(s.length + " next clicks")
        .removeClass("hidden");

    lrs.finishAction(mainUUID, {score: s.length});
    lrs.setStateDoc(mainUUID, {"count": s.length, terms: [1,2,3]});

    lrs.push();

    // now we can store the document

    lrs.pushState();

});

$("#content-start").bind("click", function() {
    mainUUID = lrs.startAction(MainActionVerb,
                               document.location.href);
    lrs.startContext({"statement": mainUUID});
    $("#content-next").removeClass("hidden");
    $("#content-start").addClass("hidden");
});

$("#content-next").bind("click", function() {
    $("#content-stop").removeClass("hidden");
    lrs.recordAction(IdeaActionVerb,
                     document.location.href,
                     {extensions: {"foobar": "idea" + i++}});
});

/**
 * Part 2 PowerTLA Logic
 */

function cbLocalActor(actor) {
    if (actor && actor.id) {
        $(document).trigger("xapiready");
    }
}

function cbRSD(rsdDef) {
    if (rsdDef && rsdDef.engine) {
        lrs.setRSD(rsdDef);
        lrs.fetchAgent(cbLocalActor);
    }
    else {
        console.log("fatal error: cannot load RSD");
    }
}

function init() {
    var rsdDef = rsd.get();
    if (rsdDef) {
        cbRSD(rsdDef);
    }
    else {
        rsd.load(cbRSD);
    }
}

init();
