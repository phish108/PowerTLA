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

1. Clone the PowerTLA repository into a safe place, but not into your learning management system.
 * run ```git clone https://github.com/phish108/PowerTLA.git```
 * run ```git submodule update --init --recoursivce``` 
2. copy or link the plugin into your LMS .
3. create a powertla.ini from the powertla.ini.example.

### ILIAS LMS

3. connect to the LMS data base and install the PowerTLA schema.

```
> cat doc/dbschema.sql | mysql -u iliasDBUser -p iliasDBInstance
```

4. Done.

### Moodle

3. copy the moodle's powertla folder into the /your/moodle/local directory.

```
> cp -r VLE-plugins/moodle/powertla /your/moodle/local/
```

4. Log into your moodle as an administrator

5. visit System Administration/ Notifications

6. Update the database.

6. Done.

## Documentation

Find some bits of documentation in the [docs folder](docs/).

## Contribute

Committing is easy.

1. Create a new branch for your fixes, changes and additions using ```git branch __yourBranchName__```

2. Implement and test you changes.

3. Check if you code contains only valid PHP. Pull-requests that do not pass PHP lint will not get accepted.

4. Commit your changes to your branch.

5. Create a pull request.

## LICENSE

PowerTLA is licensed under the GNU Affero License.

## Contributors

* Christian Glahn (HTW Chur and Mobinaut.IO)

* Evangelia Mitsopoulou (ISN, ETH Zurich, until 2014)

