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
}
