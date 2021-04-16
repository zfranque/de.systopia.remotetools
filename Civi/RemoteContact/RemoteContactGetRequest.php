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


namespace Civi\RemoteContact;

use CRM_Remotetools_ExtensionUtil as E;
use Civi\RemoteToolsRequest;

/**
 * Event to collect all CRM_Remotetools_RemoteContactProfile implementations
 * (matching the filter)
 */
class RemoteContactGetRequest extends RemoteToolsRequest
{
    const BEFORE_ADD_PROFILE_DATA   = RemoteToolsRequest::EXECUTE_REQUEST + 300;
    const ADD_PROFILE_DATA          = RemoteToolsRequest::EXECUTE_REQUEST + 250;

    /**
     * @var \CRM_Remotetools_RemoteContactProfile the profile to be used
     */
    protected $profile = null;

    /** @var bool is this a RemoteContact.get_self request to view your own data? */
    protected $is_self_request = false;

    /**
     * Is this a request about one's own data?
     */
    public function isSelfRequest()
    {
        return $this->is_self_request;
    }

    /**
     * Set the 'self-request' flag
     *
     * @param $is_self_request boolean
     *
     */
    public function setSelfRequest($is_self_request)
    {
        $this->is_self_request = $is_self_request;
    }

    /**
     * Get the profile to be used in this request
     *
     * @return \CRM_Remotetools_RemoteContactProfile
     */
    public function getProfile()
    {
        if ($this->profile === null) {
            $profile_name = $this->getRequestParameter('profile');
            if (empty($profile_name)) {
                // remark: if you want to add a default profile, you need to hook in before this method is called
                $this->addError("A profile needs to be provided by caller or by code.");
            } else {
                $this->profile = \CRM_Remotetools_RemoteContactProfile::getProfileByName($profile_name);
                if (!$this->profile) {
                    $this->addError("Profile {$profile_name} not valid");
                }
            }
        }
        return $this->profile;
    }


    /******************************************************************************
     *                            EXECUTION                                       *
     ******************************************************************************/

    /**
     * Make sure the profile is present
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function initProfile($request)
    {
        if (!$request->hasErrors()) {
            $profile = $request->getProfile();
            if ($profile) {
                $profile->initProfile($request);
            }
        }
    }

    /**
     * Add the requirements the profile needs
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function addProfileRequirements($request)
    {
        if (!$request->hasErrors()) {
            $profile = $request->getProfile();
            if ($profile) {
                $request_data = &$request->getRequest();

                // impose the profile ID restriction
                $profile->applyRestrictions($request, $request_data);

                // make sure sorting works
                $profile->adjustSorting($request);

                // update the 'return' fields
                $requested_return_fields = $profile->mapExternalFields($request->getOriginalReturnFields());
                $profile_return_fields   = $profile->getReturnFields($request);
                if (empty($requested_return_fields)) {
                    $request_data['return'] = $profile_return_fields;
                } else {
                    $request_data['return'] = array_intersect($requested_return_fields, $profile_return_fields);
                }

                // make sure the sort fields are there as well
                $sorting = $request->getSorting($request_data);
                foreach ($sorting as $sorting_tuple) {
                    $sorting_field = $sorting_tuple[0];
                    if (!in_array($sorting_field, $request_data['return'])) {
                        $request_data['return'][] = $sorting_field;
                    }
                }

                // finally: map the search parameters themselves:
                $request->mapParameters($profile->getExternalToInternalFieldMapping());
            }
        }
    }


    /**
     * Take the compiled request and execute it
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function executeRequest($request)
    {
        if (!$request->hasErrors()) {
            try {
                // only execute if there is a profile
                $profile = $request->getProfile();
                if (!$profile) {
                    $request->addError(E::ts("Data profile not found"));

                } else {
                    // finally execute
                    $request->result = false; // mark es being executed
                    $request_data = $request->getRequest();
                    $log_debug = $request->getRequestParameter('log_debug');
                    if (!empty($log_debug)) {
                        \Civi::log()->debug("RemoteContact.get call: " . json_encode($request_data));
                    }
                    $request->result = \civicrm_api3('Contact', 'get', $request_data);
                    $request->reply = $request->result; // set default reply to result
                    if (!empty($log_debug)) {
                        \Civi::log()->debug("RemoteContact.get result: " . json_encode($request->result));
                    }
                }

            } catch (\Exception $ex) {
                $request->addError($ex->getMessage());
            }
        }
    }

    /**
     * Apply profile filters
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function filterResult($request)
    {
        if (!$request->hasErrors()) {
            $profile = $request->getProfile();
            if ($profile) {
                $profile->filterResult($request, $request->getReply()['values']);
            }
        }
    }

}
