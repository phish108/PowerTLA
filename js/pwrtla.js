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

var bAdmin  = false,
    bUser   = false,
    i       = 0;

var mainUUID;

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

function cbAdminStream(ok) {
    if (ok !== undefined) {
        bAdmin = true;
    }
}

function cbMyStream(ok) {
    if (ok !== undefined) {
        bUser = true;
    }
}

$(document).bind("xapiready", function() {
    lrs.fetch({verb: MainActionVerb,
               object: document.location.href},
              cbMyStream);

    lrs.fetchAdmin({verb: MainActionVerb,
                    object: document.location.href},
                   cbAdminStream);
});
