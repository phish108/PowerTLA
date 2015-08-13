# PowerTLA


PowerTLA aims to be a reference implementation for the ADL Training and Learning Architecture (TLA) for mobile and web-based
learning. It offers a light weight integration layer for Learning Management Systems (or other HR management frameworks)
that allows the use of the new functions in online courses on existing platforms.

Currently the ILIAS LMS (www.ilias.de) is supported.

PowerTLA offers a REST service layer for

* Session and Token based Authentication (OAuth2 Bearer and MAC tokens)
* Experience API (in progress)
* IMS QTI JSON binding (in progress)
* Learner profile service

PowerTLA builds on top of the existing LMS code and allows easy integration of mobile tools and services.

## Install

### ILIAS LMS

On Ilias create a folder in the root directory called "tla".

Then copy all powertla files into this directory. The rest will work (hopefully) like magic.

### Moodle

The moodle integration is presently in progress and will become available in Autumn 2015.

## Documentation

Find the documentation in the [docs folder](docs/).

## Contribute

Committing is easy.

1. Create a new branch for your fixes, changes and additions using ```git branch __yourBranchName__```

2. Implement and test you changes.

3. Check if you code contains only valid PHP. Pull-requests that do not pass PHP lint will not get accepted.

4. Commt your changes to your branch.

5. Create a pull request.

## LICENSE

PowerTLA is licensed under the GNU Affero License.

## Contributors

* Christian Glahn (HTW Chur and Mobinaut.IO)

* Evangelia Mitsopoulou (ISN, ETH Zurich, until 2014)

