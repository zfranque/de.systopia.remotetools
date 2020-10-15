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
 * API Wrapper to the internal '.' separators between
 *  custom_group_name and custom_field_name with another separator
 *  if the call is external
 */
class CRM_Remotetools_SeparatorApiWrapper implements API_Wrapper
{

    /** @var string the internally used separator */
    protected $internal_separator;

    /** @var string the externally used separator */
    protected $external_separator;

    /**
     * Create a new API wrapper to replace the separator for external communication (REST-API)
     *
     * @param string $external_separator
     *   the separator to be used externally
     * @param string $internal_separator
     *   the separator being used internally, default is '.'
     */
    public function __construct($external_separator, $internal_separator = '.')
    {
        $this->external_separator = $external_separator;
        $this->internal_separator = $internal_separator;
    }

    /**
     * map values with the external separator to the internal one,
     *  if this is an external API call
     */
    public function fromApiInput($apiRequest)
    {
        if ($this->internal_separator != $this->external_separator) {
            if ($this->isExternal()) {
                $data = $apiRequest['params'];
                $this->mapSeparators($data, $this->external_separator, $this->internal_separator);
                $apiRequest['params'] = $data;
            }
        }
        return $apiRequest;
    }

    /**
     * map values with the internal separator to the external one,
     *  if this is an external API call
     */
    public function toApiOutput($apiRequest, $result) {
        if ($this->internal_separator != $this->external_separator) {
            if ($this->isExternal()) {
                $this->mapSeparators($result, $this->internal_separator, $this->external_separator);
            }
        }
        return $result;
    }

    /**
     * Change the separators between two (regex) words
     *
     * @param array $data
     *   the array in which the keys should be changed
     * @param $from
     *   the old separator
     * @param $to
     *   the new separator
     */
    protected function mapSeparators(&$data, $from, $to)
    {
        // build pattern
        $pattern = '/^(?P<group_name>\w+)';
        foreach (str_split($from) as $separator_character) {
            $pattern .= '\\' . $separator_character;
        }
        $pattern .= '(?P<field_name>\w+)$/';

        // replace all matches
        foreach (array_keys($data) as $key) {
            if (preg_match($pattern, $key, $match)) {
                // exclude API options
                if ($match['group_name'] == 'option' || $match['group_name'] == 'options') {
                    continue;
                }

                // replace key
                $data["{$match['group_name']}{$to}{$match['field_name']}"] = $data[$key];
                unset($data[$key]);
            }
        }
    }

    /**
     * Check whether this is an external call from the REST API
     */
    protected function isExternal()
    {
        // TODO: can be traced to REST?
        return true;
    }
}
