# Frontend support modules

This folder contains JavaScript files for interacting with the PoweTLA services.

The files in this directory are intended to operated within the calling page.

## Contents

* lrs.js
*
* lrsAnalytics.js

## LRS Functions

PowerTlA comes with a extended XAPI LRS on board.

The file lrs.js provides XAPI conform action reporting and stream loading.

The file lrsDocuments.js provides the interfaces to the XAPI document API.

The file lrsAnalytics.js provides extended interfaces to operate on the XAPI stream.

The file lrsFilters.js provides interfaces to PowerTLA's XAPI filters.


## Notes

Built in filters for XAPI, but not documented.

- lrs.php?actor=...         - loads everything for one actor allow actor shortcuts
- lrs.php?verb.id=...       - loads everything containing the verbid
- lrs.php?object.id =       - loads everything containing to the object
- lrs.pgp?context.parent.id - loads everything within a given context

