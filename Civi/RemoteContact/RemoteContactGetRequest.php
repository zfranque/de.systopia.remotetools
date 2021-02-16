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

use Civi\RemoteToolsRequest;
use Civi\Setup\PackageUtil;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event to collect all CRM_Remotetools_RemoteContactProfile implementations
 * (matching the filter)
 */
class RemoteContactGetRequest extends RemoteToolsRequest
{
    const BEFORE_ADD_PROFILE_DATA   = RemoteToolsRequest::EXECUTE_REQUEST + 1250;
    const ADD_PROFILE_DATA          = RemoteToolsRequest::EXECUTE_REQUEST + 1000;

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
                $profile->applyRestrictions($request_data);

                // update the 'return' fields
                $current_return_fields = $request->getReturnFields();
                $profile_return_fields = $profile->getReturnFields();
                if (empty($current_return_fields)) {
                    $request_data['return'] = $profile_return_fields;
                } else {
                    // todo: use array_intersect? what logic to we want here?
                    $request_data['return'] = array_unique(array_merge($return_fields, $profile_return_fields));
                }
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
                    $request->addError(E::ts("Date profile not found"));

                } else {
                    // finally execute
                    $request->result = false; // mark es being executed
                    $request_data = $request->getRequest();
                    $request->result = \civicrm_api3('Contact', 'get', $request_data);
                    $request->reply = $request->result; // set default reply to result
                }

            } catch (Exception $ex) {
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
