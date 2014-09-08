# PowerTLA Service Layer

PowerTLA is built as a set of TLA related services. Each service implements
one part of the TLA architecture.

PowerTLA implements presently three levels of the TLA:

* XAPI LRS (lrs.php)
* Content Broker (content/*.php)
* Learner Profiles (profiles/*.php)

## Getting an overview

In order to simplify the automatic detection of the appropriate services for
callers, PowerTLA offers a simple API detection. This API detection provides
an overview on the existing services of the specific PowerTLA instance. At
each level of PowerTLA one can check the available services at that level in
order to access the correct URL to the service of interest.

calls to the following URLS will reveil the service detection

```
   http://URL.to/PowerTLA
   http://URL.to/PowerTLA/restservice
   http://URL.to/PowerTLA/restservice/content
   http://URL.to/PowerTLA/restservice/profiles
``

The service detection is specific to the levels. I.e., only content broker
services are visible at the content-broker level. However, all sub-levels
will be presented at the next higher level. The service detection is provided
in JSON format and contains an object of service descriptors. Each descriptor
covers one service and includes three descriptive elements.

 * The relative URL to the service
 * The provided service by protocol
 * The version of the implemented protoco

The service descriptors have the following format.

```JSON
    {
       "id": "unified.protocol.name.or.url"
       "version": "Protocol.Version.Number"
       "href": "relative/service/url.php"
    }
```

The overarching service detection object mapps to the service id.

## Using the service detection

The PowerTLA service detection only provides basic information on the service
and does not replace the information that is provided by the service itself.
If a caller intends to identify the exact capabilities of a service through
the service detection, the following algorithm should be used.

```
   var services   = get.service.data('http://URL.to/PowerTLA/')
   var service = services["gov.adlnet.xapi.lrs"]
   if (service !== null) {
       var serviceurl = 'http://URL.to/PowerTLA/' + service.href
       var servicedescription = get.service.data(serviceurl + "/about)
   }
```
