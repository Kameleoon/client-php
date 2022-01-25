<?php
namespace Kameleoon\Network;

class KameleoonQuery
{
    static function experimentQuery(string $siteCode): string {
        return '
        {
            "operationName": "getExperiments",
            "query": "query getExperiments($first: Int, $after: String, $filter: FilteringExpression, $sort: [SortingParameter!]) { 
                experiments(first: $first, after: $after, filter: $filter, sort: $sort) { 
                    edges { 
                        node { 
                            id
                            name
                            type
                            site { 
                                id 
                                code 
                                isKameleoonEnabled
                            } 
                            status 
                            variations { 
                                id 
                                customJson
                            }
                            deviations { 
                                variationId 
                                value
                            } 
                            respoolTime { 
                                variationId
                                value
                            } 
                            segment { 
                                id
                                name
                                conditionsData {
                                    firstLevelOrOperators
                                    firstLevel {
                                        orOperators
                                        conditions {
                                            targetingType
                                            isInclude
                                            ... on CustomDataTargetingCondition {
                                                customDataIndex
                                                value
                                                valueMatchType
                                            }
                                        }
                                    }
                                }
                            } 
                            __typename
                        }
                        __typename
                    }
                    pageInfo {
                        endCursor
                        hasNextPage
                        __typename
                    } 
                    totalCount
                    __typename
                }
            }",
            "variables": {
                "filter": {
                    "and": [{
                        "condition": {
                            "field": "status",
                            "operator": "IN",
                            "parameters": ["ACTIVE", "DEVIATED", "USED_AS_PERSONALIZATION"]
                        }
                    }, {
                        "condition": {
                            "field": "type",
                            "operator": "IN",
                            "parameters": ["SERVER_SIDE", "HYBRID"]
                        }
                    }, {
                        "condition": {
                            "field": "siteCode",
                            "operator": "IN",
                            "parameters": ["' . $siteCode . '"]
                        }
                    }]
                },
                "sort": [{
                    "field": "id",
                    "direction": "ASC"
                }]
            }
        }';
    }

    static function featureFlagQuery(string $siteCode): string {
        return '
        {
            "operationName": "getFeatureFlags",
            "query": "query getFeatureFlags($first: Int, $after: String, $filter: FilteringExpression, $sort: [SortingParameter!]) {
                featureFlags(first: $first, after: $after, filter: $filter, sort: $sort) {
                    edges {
                        node {
                            id
                            name
                            site {
                                id
                                code
                                isKameleoonEnabled
                            } 
                            bypassDeviation
                            status
                            variations {
                                id
                                customJson
                            } 
                            respoolTime {
                                variationId
                                value
                            }
                            expositionRate
                            identificationKey
                            featureFlagSdkLanguageType
                            featureStatus
                            schedules {
                                dateStart
                                dateEnd
                            } 
                            segment {
                                id
                                name
                                conditionsData {
                                    firstLevelOrOperators
                                    firstLevel {
                                        orOperators
                                        conditions { 
                                            targetingType
                                            isInclude
                                            ... on CustomDataTargetingCondition {
                                                customDataIndex
                                                value
                                                valueMatchType
                                            }
                                        }
                                    }
                                }
                            } 
                            __typename
                        }
                        __typename
                    } 
                    pageInfo {
                        endCursor
                        hasNextPage
                        __typename
                    }
                    totalCount
                    __typename
                }
            }",
            "variables": {
                "filter": {
                    "and": [{
                        "condition": {
                            "field": "featureStatus",
                            "operator": "IN",
                            "parameters": ["ACTIVATED", "SCHEDULED", "DEACTIVATED"]
                        }
                    }, {
                        "condition": {
                            "field": "siteCode",
                            "operator": "IN",
                            "parameters": ["' . $siteCode . '"]
                        }
                    }]
                },
                "sort": [{
                    "field": "id",
                    "direction": "ASC"
                }]
            }
        }';
    }
}

?>