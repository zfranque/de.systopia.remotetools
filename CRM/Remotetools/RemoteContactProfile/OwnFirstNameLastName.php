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
use Civi\RemoteContact\GetFieldsEvent;
use Civi\RemoteContact\RemoteContactGetRequest;

/**
 * EXAMPLE: a very simple contact profile:
 *   - only containing first and last name fields/
 *   - only returns the contact identified by the remote_contact_id
 *
 * to try, uncomment in CRM_Remotetools_RemoteContactProfile::registerKnownProfiles
 */
class CRM_Remotetools_RemoteContactProfile_OwnFirstNameLastName extends CRM_Remotetools_RemoteContactProfile
{

    /**
     * Is this profile suitable for the RemoteContat.get_self method?
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     *
     * @return boolean
     *   does this profile only return the data of the caller?
     */
    public function isOwnDataProfile($request)
    {
        return true;
    }

    /**
     * Initialise the profile. This is a good place to do some sanity checks
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     *
     */
    public function initProfile($request)
    {
        // implement this to format the results before delivery
    }

    /**
     * Get the list of fields to be returned.
     *  This is meant to be overwritten by the profile
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     *
     * @return array
     */
    public function getReturnFields($request)
    {
        // get the list of fields this profile wants/needs
        return ['first_name', 'last_name'];
    }

    /**
     * If the profile wants to restrict any fields
     *  This is meant to be overwritten by the profile
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute

     * @param array $request_data
     *    the request parameters, to be edited in place
     *
     */
    public function applyRestrictions($request, &$request_data)
    {
        $request_data['contact_type'] = 'Individual';
        $request_data['sequential'] = 0;
    }

    /**
     * This is a point where the profile can re-format the results
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     *
     * @param array $reply_records
     *    the current reply records to edit in-place
     */
    public function filterResult($request, &$reply_records)
    {
        foreach (array_keys($reply_records) as $index) {
            $reply_records[$index] = [
                'civicrm_id' => CRM_Utils_Array::value('id', $reply_records[$index], ''),
                'first_name' => CRM_Utils_Array::value('first_name', $reply_records[$index], ''),
                'last_name'  => CRM_Utils_Array::value('last_name', $reply_records[$index], ''),
            ];
        }
    }

    /**
     * Add the profile's fields to the fields collection
     *
     * @param $fields_collection GetFieldsEvent
     */
    public function addFields($fields_collection)
    {
        $fields_collection->setFieldSpec('civicrm_id',
             [
                 'name' => 'civicrm_id',
                 'type' => CRM_Utils_Type::T_STRING,
                 'title' => E::ts("CiviCRM ID"),
                 'localizable' => 0,
                 'is_core_field' => true,
             ]
        );
        $fields_collection->setFieldSpec('first_name',
            [
                'name' => 'first_name',
                'type' => CRM_Utils_Type::T_STRING,
                'title' => E::ts("First Name"),
                'localizable' => 0,
                'is_core_field' => true,
            ]
        );
        $fields_collection->setFieldSpec('last_name',
            [
                'name' => 'last_name',
                'type' => CRM_Utils_Type::T_STRING,
                'title' => E::ts("Last Name"),
                'localizable' => 0,
                'is_core_field' => true,
            ]
        );
    }

}
