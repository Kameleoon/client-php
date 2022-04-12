# Changelog
All notable changes to this project will be documented in this file.

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
