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
}
