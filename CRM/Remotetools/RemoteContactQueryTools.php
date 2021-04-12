<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Tools                                  |
| Copyright (C) 2021 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

use Civi\RemoteContact\RemoteContactGetRequest;
use CRM_Remotetools_ExtensionUtil as E;

/**
 * RemoteContact: exchange contact data with a remote system
 */
class CRM_Remotetools_RemoteContactQueryTools {

    /**
     * Process the (custom) multivalue_search_mode_or option.
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function processMultivalueOrSearch($request)
    {
        // if there is already an error, we do nothing
        if ($request->hasErrors()) {
            return;
        }

        // get the list of fields that should be treated as multivalue OR search
        $multivalue_search_or_fields = $request->getRequestOption('multivalue_search_mode_or');
        if (empty($multivalue_search_or_fields)) {
            return;
        }

        // make sure it's an array (will wrap single value in array)
        $multivalue_search_or_fields = (array)$multivalue_search_or_fields;

        // find out, which of these are actually used in a significant way,
        //  i.e. it's an [IN => values] query, and it has more than one value
        $multivalue_search_or_queries = [];
        foreach ($multivalue_search_or_fields as $multivalue_search_or_field) {
            $query = $request->getRequestParameter($multivalue_search_or_field);
            if (is_array($query) && strtoupper($query[0]) == 'IN' && is_array($query[1]) && count($query[1]) > 1) {
                $multivalue_search_or_queries[$multivalue_search_or_field] = $query[1];
            } else {
                Civi::log()->debug(
                    "RemoteContact: field '{$multivalue_search_or_field}' has requested *OR* search, but has only one value. Will use std API."
                );
            }
        }

        // if there's none left we're done...
        if (empty($multivalue_search_or_queries)) {
            return;
        }

        // from those: extract the valid SQL queries, i.e remove the from the list.
        //  valid means, that there it actually refers to a multi-valy custom field
        $valid_sql_queries = self::extractMutltivalueSQLQueries($multivalue_search_or_queries, $request);

        // then: remove the parameters from the query that we'll be processing externally
        foreach ($valid_sql_queries as $valid_sql_query) {
            $get_parameters->removeParameter($valid_sql_query['original_query_parameter']);
        }

        // and: log a remark about the wrongly requested ones
        foreach ($multivalue_search_or_queries as $remaining_field => $value) {
            Civi::log()->debug(
                "RemoteContact: field '{$remaining_field}' does not refer to a multivalue custom field. Will use std API."
            );
        }

        // now finally: we're set to run the queries (if there is any)
        if ($valid_sql_queries) {
            self::applySqlQueries($valid_sql_queries, $request);
        }
    }

    // TODO:

    /**
     * Will extract the queries that can be excecuted as an SQL from the list of candidates.
     *  Those will be removed from the list and instead returned with some additional data
     *
     * @param $multivalue_search_or_queries array
     *   list of candidates [field name => value spec]
     *
     * @param $request RemoteContactGetRequest
     *   the request
     *
     * @return array
     *   list of queries with the following attributes:
     *     'original_query_parameter' => the original parameter in the query
     *     'custom_field'             => the custom field data
     *     'custom_group'             => the custom group data
     *     'values'                   => the query
     *
     */
    protected static function extractMutltivalueSQLQueries(&$multivalue_search_or_queries, $request)
    {
        // map list of fields with profile
        $field_mapping = [];
        $profile = $request->getProfile();
        if ($profile) {
            $field_mapping = $profile->getExternalToInternalFieldMapping();
        }

        // resolve the custom fields
        $resolved_queries = [];
        foreach ($multivalue_search_or_queries as $external_field => $value) {
            if (isset($field_mapping[$external_field])) {
                $resolved_queries = [$field_mapping[$external_field] => $value];
            } else {
                $resolved_queries = [$internal_field => $value];
            }
        }
        CRM_Remotetools_CustomData::resolveCustomFields($resolved_queries);

        // check which ones are _actually_ multivalue data and remove from (API) query
        //  see also: CRM_Remoteevent_EventCustomFields::processCustomFieldFilters
        $multi_value_custom_field_filters = [];
        foreach ($resolved_queries as $field_name => $value) {
            if (substr($field_name, 0, 7) == 'custom_' && !empty($value)) {
                // this is a custom field, check if it's multi-value
                $custom_field_id = substr($field_name, 7);
                $custom_field = CRM_Remotetools_CustomData::getFieldSpecs($custom_field_id);
                if (!empty($custom_field['serialize'])) {
                    // this is a multi-value custom field
                    //  first: add it to the list
                    $multi_value_custom_field_filters[$custom_field_id] = $value;

                    // then: remove the original query parameter
                    $custom_group = CRM_Remotetools_CustomData::getGroupSpecs($custom_field['custom_group_id']);
                    $get_parameters->removeParameter("{$custom_group['name']}.{$custom_field['name']}");
                    continue;
                }
            }
            // if we get here, this is not a mutlivalue custom field
            unset($resolved_queries['field_name']);
        }
    }

    // TODO:
    protected static function applySqlQueries($sql_queries, $request)
    {
    }
}
