<?php
/*-------------------------------------------------------+
| SYSTOPIA Remote Tools                                  |
| Copyright (C) 2020 SYSTOPIA                            |
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

use CRM_Remotetools_ExtensionUtil as E;

/**
 * Tools to remotely authorize/identify contacts or users
 */
class CRM_Remotetools_DataTools {

    /**
     * Get a localised list of option group values for the field keys
     *
     * @param string|integer $option_group_id
     *   identifier for the option group
     *
     * @return array list of key => (localised) label
     */
    public static function getOptions($option_group_id, $localise = false, $params = [], $use_name = false, $sort = 'weight asc')
    {
        // todo: caching!

        $option_list = [];
        $query       = [
            'option.limit'    => 0,
            'option_group_id' => $option_group_id,
            'return'          => 'value,label,name',
            'is_active'       => 1,
            'sort'            => $sort,
        ];

        // extend/override query
        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        // run query + compile result
        $result = civicrm_api3('OptionValue', 'get', $query);
        foreach ($result['values'] as $entry) {
            if ($use_name) {
                $option_list[$entry['name']] = $localise ? E::ts($entry['label']) : $entry['label'];
            } else {
                $option_list[$entry['value']] = $localise ? E::ts($entry['label']) : $entry['label'];
            }
        }

        return $option_list;
    }

    /**
     * Get the current sorting instructions as an array
     *
     * @param array $request_data
     *   the API request
     *
     * @return array
     *   list of [field_name, 'ASC'|'DESC']  tuples
     */
    public static function getSortingTuples($request_data)
    {
        // extract current sorting string
        $current_sorting_string = '';
        if (!empty($request_data['option.sort']) && is_string($request_data['option.sort'])) {
            $current_sorting_string = $request_data['option.sort'];
        }
        if (!empty($request_data['options']['sort']) && is_string($request_data['options']['sort'])) {
            $current_sorting_string .= ',' . $request_data['options']['sort'];
        }

        // parse string
        $sorting_tuples = [];
        $sorting_string_chunks = explode(',', $current_sorting_string);
        foreach ($sorting_string_chunks as $sorting_spec) {
            $sorting_spec = trim($sorting_spec);
            if (preg_match('/^([\w.]+) +(asc|desc)$/i', $sorting_spec, $match)) {
                $sorting_tuples[] = [$match[1], $match[2]];
            } elseif (preg_match('/^([\w.]+)$/i', $sorting_spec, $match)) {
                $sorting_tuples[] = [$match[1], 'asc'];
            }
        }

        return $sorting_tuples;
    }

    /**
     * Convert the sorting tuples into a API option.sort string
     *
     * @param $sorting_tuples array
     *   list of [field_name, 'ASC'|'DESC']  tuples
     *
     * @param array $request_data
     *   the API request
     *
     * @return string
     */
    public static function setSortingString($sorting_tuples, &$request_data)
    {
        $sorting_chunks = [];
        foreach ($sorting_tuples as $sorting_tuple) {
            if (!empty($sorting_tuple[0])) {
                if (!empty($sorting_tuple[1]) && strtolower($sorting_tuple[1]) == 'desc') {
                    $sorting_chunks[] = "{$sorting_tuple[0]} desc";
                } else {
                    $sorting_chunks[] = "{$sorting_tuple[0]} asc";
                }
            }
        }

        // set in request data
        $sorting_string = implode(', ', $sorting_chunks);
        unset($request_data['option.sort'], $request_data['option']['sort']); // remove for compatibility
        if ($sorting_string) {
            $request_data['options']['sort'] = $sorting_string;
        }
    }

    /**
     * Get the current restriction of IDs
     *
     * Remark: this returns
     *   null  if not set
     *   fail  if couldn't be parsed
     *   array with a list of IDs, if everything's fine
     *
     * @param $request array
     *   the request

     * @param $field_name string
     *   name of the field, default is 'id'
     *
     * @return array|null|string
     *   list of requested IDs
     */
    public static function getIDs($request, $field_name = 'id')
    {
        if (isset($request[$field_name])) {
            $id_param = $request[$field_name];
            if (is_string($id_param)) {
                // this is a single integer, or a list of integers
                $id_list = explode(',', $id_param);
                return array_map('intval', $id_list);

            } else if (is_array($id_param)) {
                // this is an array. we can deal with the 'IN' => [] notation
                if (count($id_param) == 2) {
                    if (strtolower($id_param[0]) == 'in' && is_array($id_param[1])) {
                        // this should be a list of IDs
                        return array_map('intval', $id_param[1]);
                    }
                }
            }

            // if we get here, we couldn't parse it
            \Civi::log()->debug("DataTools.getIDs: couldn't parse '{$field_name}' parameter: " . json_encode($id_param));
            return 'fail';

        } else {
            // 'id' field not set
            return null;
        }
    }

    /**
     * Restrict the query parameter to the given IDs.
     *  Existing restrictions will be taken into account (intersection)
     *
     * @param $request array
     *   the request
     *
     * @param array $ids
     *   list of event IDs
     *
     * @param $field_name string
     *   name of the field, default is 'id'
     */
    public static function restrictToIds(&$request, $ids, $field_name = 'id')
    {
        if (empty($ids)) {
            // this basically means: restrict to empty set:
            $request[$field_name] = 0;
        } else {
            $current_restriction = self::getIDs($request, $field_name);
            if ($current_restriction === null) {
                // no restriction set so far
                $request[$field_name] = ['IN' => $ids];

            } else if (is_array($current_restriction)) {
                // there is a restriction -> intersect
                $intersection = array_intersect($current_restriction, $ids);
                $request[$field_name] = ['IN' => $intersection];

            } else {
                // something's wrong here
                \Civi::log()->debug("DataTools.restrictToIds: couldn't restrict '{$field_name}' parameter: " . json_encode($current_restriction));
            }
        }
    }
}
