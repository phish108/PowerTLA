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

## LICENSE

PowerTLA is licensed under the GNU Affero License.

## Contributors

* Christian Glahn (ISN, ETH Zurich)

* Evangelia Mitsopoulou (ISN, ETH Zurich)

