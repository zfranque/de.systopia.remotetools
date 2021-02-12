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

use CRM_Remotetools_ExtensionUtil as E;

/**
 * This is a very simple contact profile:
 *   - only containing first and last name fields/
 *   - only returns the contact identified by the remote_contact_id
 */
class CRM_Remotetools_RemoteContactProfile_OwnFirstNameLastName extends CRM_Remotetools_RemoteContactProfile {

    /**
     * Get the list of fields to be returned.
     *  This is meant to be overwritten by the profile
     *
     * @param $return_field_list
     *
     * @return mixed
     */
    public function getReturnFields($return_field_list)
    {
        return ['first_name', 'last_name'];
    }

    /**
     * If the profile wants to restrict any fields
     *  This is meant to be overwritten by the profile
     *
     * @param array $request_data
     *
     * @return mixed
     */
    public function applyRestrictions(&$request_data)
    {
        $request_data['contact_type'] = 'Individual';
        $request_data['sequential'] = 0;
    }

    /**
     * This is a point where the profile can re-format the results
     *
     * @param array $result_data
     */
    public function formatResult(&$result_data)
    {
        foreach (array_keys($result_data) as $index)
        {
            $result_data[$index] = [
                'id'         => CRM_Utils_Array::value('id', $result_data[$index], ''),
                'first_name' => CRM_Utils_Array::value('first_name', $result_data[$index], ''),
                'last_name'  => CRM_Utils_Array::value('last_name', $result_data[$index], ''),
            ];
        }
    }
}
