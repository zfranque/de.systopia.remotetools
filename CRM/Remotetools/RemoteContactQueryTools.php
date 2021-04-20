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
            $values = (array) $request->getRequestParameter($multivalue_search_or_field);
            if (empty($values) || isset($values['NOT IN'])) {
                // both of these cases we can safely ignore
                continue;
            }
            if (!empty($values['IN'])) {
                $values = (array) $values['IN'];
            }
            if (count($values) < 2) {
                $request->addWarning("RemoteContact: field '{$multivalue_search_or_field}' has requested *OR* search, but has less than two values. Will use std API.");
            } else {
                $multivalue_search_or_queries[$multivalue_search_or_field] = $values;
            }
        }

        // if no relevant OR queries could be extracted, we're done...
        if (empty($multivalue_search_or_queries)) {
            return;
        }

        // from those: extract the valid SQL queries, i.e remove the from the list.
        //  valid means, that there it actually refers to a multi-value custom field
        $valid_sql_queries = self::extractMutltivalueSQLQueries($multivalue_search_or_queries, $request);

        // now finally: we're set to run the queries (if there is any)
        if ($valid_sql_queries) {
            self::applySqlQueries($valid_sql_queries, $request);
        }
    }

    /**
     * Will extract the queries that can be executed as an SQL from the list of candidates.
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
    protected static function extractMutltivalueSQLQueries($multivalue_search_or_queries, $request)
    {
        // here we will store the extracted queries
        $extracted_queries = [];

        // map list of fields with profile
        $field_mapping = [];
        $profile = $request->getProfile();
        if ($profile) {
            $field_mapping = $profile->getExternalToInternalFieldMapping();
        }

        // check which ones are _actually_ multivalue data and remove from (API) query
        //  see also: CRM_Remoteevent_EventCustomFields::processCustomFieldFilters
        foreach ($multivalue_search_or_queries as $external_field => $value) {
            // first: map
            if (isset($field_mapping[$external_field])) {
                $internal_field = $field_mapping[$external_field];
            } else {
                $internal_field = $external_field;
            }

            if (substr($internal_field, 0, 7) == 'custom_' && !empty($value)) {
                // this is a custom field, check if it's multi-value
                $custom_field_id = substr($internal_field, 7);
                $custom_field = CRM_Remotetools_CustomData::getFieldSpecs($custom_field_id);
                if (!empty($custom_field['serialize'])) {
                    // this is a multi-value custom field:
                    //  add the query to the special ones
                    $extracted_queries[] = [
                        'original_query_parameter' => $external_field,
                        'values'                   => $value,
                        'custom_field'             => $custom_field,
                        'custom_group'             => CRM_Remotetools_CustomData::getGroupSpecs($custom_field['custom_group_id']),
                    ];

                    // also: remove the original query parameter
                    $request->removeRequestParameter($external_field);

                } else {
                    $request->addStatus("Custom field [{$external_field}] is not a multi-value custom field.");
                }
            }
        }

        return $extracted_queries;
    }

    /**
     * Will use the given requests to perform the given OR queries on the contact
     *
     * @param array $sql_queries
     *   list of queries with the following attributes:
     *     'original_query_parameter' => the original parameter in the query
     *     'custom_field'             => the custom field data
     *     'custom_group'             => the custom group data
     *     'values'                   => the query
     *
     * @param $request RemoteContactGetRequest
     *   the request
     */
    protected static function applySqlQueries($sql_queries, $request)
    {
        // todo: is there already a id restriction?
        $SQL_WHERE_PART_JOIN = ' AND '; // todo: maybe later we'll also have an 'OR' mode
        $SQL_ENTITY = 'civicrm_contact';

        // build queries
        $SQL_WHERES = [];
        $SQL_QUERY_JOINS = '';
        $SQL_PARAMS = [0 => ''];

        foreach ($sql_queries as $index => $sql_query) {
            $custom_field = $sql_query['custom_field'];
            $custom_group = $sql_query['custom_group'];
            $values       = (array) $sql_query['values'];

            if (empty($values)) continue;

            // build JOIN
            $SQL_QUERY_JOINS .= " LEFT JOIN {$custom_group['table_name']} AS multi{$index}
                                         ON multi{$index}.entity_id = entity.id ";

            // build where clause
            $or_clauses = [];
            foreach ($values as $value) {
                $param_number = count($SQL_PARAMS);
                $or_clauses[] = "multi{$index}.{$custom_field['column_name']} LIKE %{$param_number}";
                $SQL_PARAMS[$param_number] = ['%' . CRM_Utils_Array::implodePadded($value) . '%', 'String'];
            }
            $SQL_WHERES[] = '(' . implode(' OR ', $or_clauses) . ')';
        }

        // compile the main query
        $SQL_WHERE = implode($SQL_WHERE_PART_JOIN, $SQL_WHERES);
        $QUERY = "
            SELECT entity.id AS entity_id
            FROM {$SQL_ENTITY} entity
            {$SQL_QUERY_JOINS}
            WHERE {$SQL_WHERE}";

        // run the main query
        $entity_ids = [];
        unset($SQL_PARAMS[0]);
        $entity_id_query = CRM_Core_DAO::executeQuery($QUERY, $SQL_PARAMS);
        while ($entity_id_query->fetch()) {
            $entity_ids[] = (int) $entity_id_query->entity_id;
        }
        $entity_id_query->free();

        // finally: restrict the query to the given IDs
        $request->restrictToEntityIds($entity_ids);
     }
}
