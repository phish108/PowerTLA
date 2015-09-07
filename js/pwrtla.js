/*jslint white: true */
/*jslint vars: true */
/*jslint sloppy: true */
/*jslint browser: true */
/*jslint todo: true */
/*jslint unparam: true */
/*jslint bitwise: true*/

/*global lrs, rsd, console, $, jQuery*/

var MainActionVerb    = "http://mobinaut.org/xapi/verb/creative/brainstormassign";
var IdeaActionVerb    = "http://mobinaut.org/xapi/verb/creative/ideacontribute";
var AssignActionVerb  = "http://mobinaut.org/xapi/verb/reflective/ideaassign";

var mainUUID;

//function cbLoadDocuments(ok) {
//    // eventually we can work
//}
//
function cbAdminStream(ok) {
    if (ok === undefined) {
        console.log("you are no admin");
    }
    else {
        console.log("got " + ok.length + " responses ");
    }
}
//
//function cbMyStream(ok) {
//
//}

$("#contenthello").text("Hello World");

$("#contenthello").bind("click", function() {
    console.log("hello click");

    lrs.endAction(mainUUID, {score: 4});
    console.log(JSON.stringify(lrs.getStream()));
});

function cbLocalActor(actor) {
    if (actor && actor.id) {
        console.log("got user " + actor.id);
        // lrs.fetch({}, cbMyStream);
         lrs.fetchAdmin({verb: MainActionVerb,
                         object: document.location.href},
                        cbAdminStream);

        mainUUID = lrs.startAction(MainActionVerb,
                                   {"id": document.location.href});
        lrs.startContext({"statement": mainUUID});

        lrs.recordAction(IdeaActionVerb,
                         {id: document.location.href},
                         {extensions: {"foobar": "idea1"}});
        lrs.recordAction(IdeaActionVerb,
                         {id: document.location.href},
                         {extensions: {"foobar": "idea2"}});



//        lrs.recordAction(MainActionVerb,
//                         {"id": document.location.href});

        console.log(lrs.getStream());
    }
    else {
        console.log('no actor?');
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
