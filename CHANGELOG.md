# Changelog
All notable changes to this project will be documented in this file.

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
