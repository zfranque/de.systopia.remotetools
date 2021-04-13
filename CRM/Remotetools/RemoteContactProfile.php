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
use Civi\RemoteContact\RemoteContactGetRequest;
use Civi\RemoteContact\GetRemoteContactProfiles;
use Civi\RemoteContact\GetFieldsEvent;


/**
 * RemoteContactProfile:
 *   define and prepare the contact data going in and out of the interface
 */
abstract class CRM_Remotetools_RemoteContactProfile {

    /**
     * Get the profile's ID
     *
     * @return string
     *   profile ID
     */
    public abstract function getProfileID();

    /**
     * Get the profile's (human readable) name
     *
     * @return string
     *   profile ID
     */
    public function getProfileName()
    {
        // please override
        return $this->getProfileID();
    }

    /**
     * Add the profile's fields to the fields collection
     *
     * @param $fields_collection GetFieldsEvent
     */
    public function addFields($fields_collection)
    {
        // implement this to add your fields
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
        // overwrite to make available to get_self
        return false;
    }

    /**
     * Make sure that the sorting works,
     *  e.g. by translating custom fields
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public function adjustSorting($request) {
        $field_mapping = $this->getExternalToInternalFieldMapping();
        $old_sorting_tuples = $request->getSorting();
        $new_sorting_tuples = [];
        foreach ($old_sorting_tuples as list($field_name, $order)) {
            if (isset($field_mapping[$field_name])) {
                $new_sorting_tuples[] = [$field_mapping[$field_name], $order];
            } else {
                $new_sorting_tuples[] = [$field_name, $order];
            }
        }

        $request->setSorting($new_sorting_tuples);
    }

    /**
     * Get the list of (internal) fields to be returned.
     *  This can be overwritten by the profile
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     *
     * @return array
     */
    public function getReturnFields($request)
    {
        // get the list of fields this profile wants/needs
        return array_keys($this->getInternalToExternalFieldMapping());
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
        // implement this to apply any restrictions (e.g. contact attributes/IDs) to the request
    }

    /**
     * Get a mapping of external field names to
     *  the internal ones,
     *   e.g. ['my_super_field' => 'custom_23']
     *
     * @return array
     *   [external field name => internal field name]
     */
    public function getExternalToInternalFieldMapping()
    {
        return [];
    }

    /**
     * Get a mapping of internal field names to
     *  the internal ones,
     *   e.g. ['custom_23' => 'my_super_field']
     *
     * @return array
     *   [external field name => internal field name]
     */
    public function getInternalToExternalFieldMapping()
    {
        return array_flip($this->getExternalToInternalFieldMapping());
    }

    /**
     * Translate the list of external field names to internal ones
     *
     * @param  array $field_names
     *   list of external field names
     *
     * @return array
     *   list of internal field names
     */
    public function mapExternalFields($field_names)
    {
        $internal_field_names = [];
        $mapping = $this->getExternalToInternalFieldMapping();
        foreach ($field_names as $field_name) {
            if (isset($mapping[$field_name])) {
                $internal_field_names[] = $mapping[$field_name];
            } else {
                $internal_field_names[] = $field_name;
            }
        }
        return $internal_field_names;
    }

    /**
     * Translate the list of internal field names to internal ones
     *
     * @param  array $field_names
     *   list of internal field names
     *
     * @return array
     *   list of external field names
     */
    public function mapInternalFields($field_names)
    {
        $external_field_names = [];
        $mapping = $this->getInternalToExternalFieldMapping();
        foreach ($field_names as $field_name) {
            if (isset($mapping[$field_name])) {
                $external_field_names[] = $mapping[$field_name];
            } else {
                $external_field_names[] = $field_name;
            }
        }
        return $external_field_names;
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
        // implement this to format the results before delivery
    }

    /*************************************************************************
     ***                 PROFILE ADMIN FUNCTIONS                           ***
     *************************************************************************/

    /**
     * Get all registered profiles
     *
     * @param array list of
     *   'name' => CRM_Remotetools_RemoteContactProfile instance
     */
    public static function getAvailableProfiles()
    {
        // trigger event
        $profile_search = new GetRemoteContactProfiles();
        Civi::dispatcher()->dispatch('civi.remotecontact.getprofiles', $profile_search);

        // return the first match (if any)
        return $profile_search->getInstances();
    }


    /**
     * Get a registered profile instance by name
     *
     * @param string $profile_name
     *
     * @return \CRM_Remotetools_RemoteContactProfile|null
     *   the profile
     */
    public static function getProfileByName($profile_name)
    {
        // trigger event
        $profile_search = new GetRemoteContactProfiles($profile_name);
        Civi::dispatcher()->dispatch('civi.remotecontact.getprofiles', $profile_search);

        // return the first match (if any)
        return $profile_search->getFirstInstance();

        // todo: warn, if multiple instances?
    }


    /**
     * Register the profiles provided by this module itself.
     *
     * @param GetRemoteContactProfiles $profiles
     */
    public static function registerKnownProfiles($profiles)
    {
        $known_profiles = [
//            'simple_first_name_last_name' => 'CRM_Remotetools_RemoteContactProfile_OwnFirstNameLastName'
        ];

        foreach ($known_profiles as $name => $class) {
            if ($profiles->matchesName($name)) {
                $profiles->addInstance(new $class());
            }
        }
    }

    /**
     * Get a list of all registered profiles
     *
     * @return array
     *   list of profile ID => name
     */
    public static function getProfileList()
    {
        $list = [];

        $profiles = self::getAvailableProfiles();
        foreach ($profiles as $profile) {
            /** @var $profile CRM_Remotetools_RemoteContactProfile */
            $list[$profile->getProfileID()] = $profile->getProfileName();
        }

        return $list;
    }
}
