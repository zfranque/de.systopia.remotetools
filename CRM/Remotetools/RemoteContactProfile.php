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
use Civi\RemoteToolsDispatcher;

/**
 * RemoteContactProfile:
 *   define and prepare the contact data going in and out of the interface
 */
abstract class CRM_Remotetools_RemoteContactProfile {

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
        return [];
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
        $profile_search = new GetRemoteContactProfiles($profile_name);
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
}
