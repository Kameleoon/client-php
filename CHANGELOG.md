# Changelog
All notable changes to this project will be documented in this file.

## 3.1.0 - 2023-04-17
* The option **default_timeout** has been added to allow overriding the default timeout parameter specified in the configuration file. Additionally, the default timeout has been reduced to **5 seconds**.
* An optional timeout parameter has been added for methods. If no timeout is specified, the SDK will use the default timeout value:
    - [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerExperiment)
    - [`isFeatureActive`](https://developers.kameleoon.com/php-sdk.html#isFeatureActive)
    - [`getVariationAssociatedData`](https://developers.kameleoon.com/php-sdk.html#getVariationAssociatedData)
    - [`getRemoteData`](https://developers.kameleoon.com/php-sdk.html#getRemoteData)
    - [`getFeatureVariationKey`](https://developers.kameleoon.com/php-sdk.html#getFeatureVariationKey)
    - [`getFeatureVariable`](https://developers.kameleoon.com/php-sdk.html#getFeatureVariable)
    - [`getFeatureAllVariables`](https://developers.kameleoon.com/php-sdk.html#getFeatureAllVariables)
    - [`getExperimentList`](https://developers.kameleoon.com/php-sdk.html#getExperimentList)
    - [`getExperimentListForVisitor`](https://developers.kameleoon.com/php-sdk.html#getExperimentListForVisitor)
    - [`getFeatureList`](https://developers.kameleoon.com/php-sdk.html#getFeatureList)
    - [`getActiveFeatureListForVisitor`](https://developers.kameleoon.com/php-sdk.html#getActiveFeatureListForVisitor)
* Added a new method:
    - [`getEngineTrackingCode`](https://developers.kameleoon.com/php-sdk.html#getenginetrackingcode) which can be used to simplify utilization of hybrid mode
* Renaming of methods:
    - `retrieveDataFromRemoteSource` -> [`getRemoteData`](https://developers.kameleoon.com/php-sdk.html#getRemoteData)

## 3.0.0 - 2023-02-02
* Added support of new feature flag rules:
    - [`getFeatureVariationKey`](https://developers.kameleoon.com/php-sdk.html#getFeatureVariationKey)
    - `obtainFeatureVariable` -> [`getFeatureVariable`](https://developers.kameleoon.com/php-sdk.html#getFeatureVariable)
    - [`getFeatureAllVariables`](https://developers.kameleoon.com/php-sdk.html#getFeatureAllVariables)
    - `activateFeature` -> [`isFeatureActive`](https://developers.kameleoon.com/php-sdk.html#isFeatureActive)
* Methods added for obtaining experiment and feature flag lists:
    - [`getExperimentList`](https://developers.kameleoon.com/php-sdk.html#getExperimentList)
    - [`getExperimentListForVisitor`](https://developers.kameleoon.com/php-sdk.html#getExperimentListForVisitor)
    - [`getFeatureList`](https://developers.kameleoon.com/php-sdk.html#getFeatureList)
    - [`getActiveFeatureListForVisitor`](https://developers.kameleoon.com/php-sdk.html#getActiveFeatureListForVisitor)
* Renaming:
    - `obtainVisitorCode` -> [`getVisitorCode`](https://developers.kameleoon.com/php-sdk.html#getVisitorCode)
    - `obtainVariationAssociatedData` -> [`getVariationAssociatedData`](https://developers.kameleoon.com/php-sdk.html#getVariationAssociatedData)
    - `NotActivated` -> `NotAllocated`
* Changes in Kameleoon Data:
    - Added possibility to set [`UserAgent`](https://developers.kameleoon.com/php-sdk.html#useragent).
* Removed blocking mode of SDK.
* Added support of `is among the values` operator for Custom Data

## 2.1.6 - 2022-11-03
* Fixed issue when SDK fetches configuration on every API request

## 2.1.5 - 2022-08-16
* Added support of PHP 7.x versions

## 2.1.4 - 2022-08-11
* Fixed crash on [`KameleoonClientFactory.create`](https://developers.kameleoon.com/php-sdk.html#create) method.

## 2.1.3 - 2022-06-27
* Significantly improved configuration load time
* Added update campaigns and feature flag configurations instantaneously with Real-Time Streaming Architecture: [`documentation`](https://developers.kameleoon.com/php-sdk.html#streaming) or [`product updates`](https://www.kameleoon.com/en/blog/real-time-streaming)
* Fixed issue when already associated visitor could get a new variation from [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerexperiment)
* Added support for **Experiment** & **Exclusive Campaign** conditions. Related to [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerexperiment)

## 2.1.2 - 2022-06-14
* Fixed an issue when tracking data could be sent twice. Related to: [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature), [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerexperiment) methods.

## 2.1.1 - 2022-05-16
* Added KameleoonData [`Device`](https://developers.kameleoon.com/php-sdk.html#device) data. Possible values are: **PHONE**, **TABLET**, **DESKTOP**.
* Removed KameleoonData `Interest`
* Fixed security issue

## 2.1.0 - 2022-04-12
* Added method for retrieving data from remote source: [`retrieveDataFromRemoteSource`](https://developers.kameleoon.com/php-sdk.html#retrievedatafromremotesource)

## 2.0.9 - 2022-02-15
* Added support of multi-environment for feature flags, Related to [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature), [`obtainFeatureVariable`](https://developers.kameleoon.com/php-sdk.html#obtainfeaturevariable)
* Fixed issue when [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature) returns wrong result


## 2.0.8 - 2022-01-25
* Added scheduling functionality for [`activateFeature`](https://developers.kameleoon.com/swift-sdk.html#activatefeature)
* Adding URI encoding for [`CustomData`](https://developers.kameleoon.com/php-sdk.html#customdata) & [`PageView`](https://developers.kameleoon.com/php-sdk.html#pageview)
* Added VisitorCodeNotValid exception when exceeding the limit of 255 chars for [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature) ,  [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerexperiment) , [`trackConversion`](https://developers.kameleoon.com/php-sdk.html#trackConversion) ,
    [`addData`](https://developers.kameleoon.com/php-sdk.html#addData) , [`flush`](https://developers.kameleoon.com/php-sdk.html#flush)
* Fixed searching by featureKey for [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature)
* Added boolean, number and JSON objects to [`obtainFeatureVariable`](https://developers.kameleoon.com/php-sdk.html#obtainfeaturevariable) as a returned values (before it returns only strings)
* Fixed issue with variationId == `origin` for [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature) and [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerexperiment)
* Added checking for status of site (Enable / Disable). Related to [`activateFeature`](https://developers.kameleoon.com/php-sdk.html#activatefeature) and [`triggerExperiment`](https://developers.kameleoon.com/php-sdk.html#triggerexperiment)

# Deprecated versions
All of the versions listed below are no longer supported and we strongly advise to upgrade to the latest version.

## 2.0.7
* Performance improvements

## 2.0.6
* Retrieve experiments with status used_as_personalization
* Update job to handle json

## 2.0.5
* Add DEVIATED status for experiment / feature flags
* Add Kameleoon-client custom header

## 2.0.4
* Update priority for creating cookie, domain parameter is taken if set in configuration

## 2.0.3
* Change configuration file (.conf) to JSON file
* Add options about cookies (samesite, secure, httponly, domain)
* Set domain as optional for obtainVisitorCode as can be declared on configuration
* Update job to log errors

## 2.0.2
* Add security when parsing json

## 2.0.1
* Add security when fetching empty response.

## 2.0.0
* Fetch configurations from automation api.
* Add Feature flags.
* Add targeting for experiments / feature flags.
* Add obtainVariationAssociatedData.
* Change Kameleoon\Exceptions to Kameleoon\Exception
* Rename Custom into CustomData
* Update default configuration path
* Update job name

## 1.1.9
* Update configuration file naming to be able to load multiple site code.
