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
use Dompdf\Exception;
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

    /**
     * Get the profile to be used in this request
     *
     * @return \CRM_Remotetools_RemoteContactProfile
     */
    public function getProfile()
    {
        if ($this->profile === null) {
            $profile_name = $this->getRequestParameter('profile');
            $this->profile = \CRM_Remotetools_RemoteContactProfile::getProfileByName($profile_name);
            if (!$this->profile) {
                $this->addError("Profile {$profile_name} not valid");
            }
        }
        return $this->profile;
    }

    /**
     * Add the requirements the profile needs
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function addProfileRequirements($request)
    {
        $profile = $request->getProfile();
        $request_data = $request->getRequest();

        // impose the profile ID restriction
        $profile->applyIdRestrictions($request_data);

        // update the 'return' fields
        $return_fields = $request->getReturnFields();
        $request_data['return'] = array_unique(array_merge($return_fields, $profile->getReturnFields()));
    }

    /**
     * Apply profile
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function applyProfileValueFormatting($request)
    {
        $profile = $request->getProfile();
        $profile->formatResult($request->result);
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
            $request = $request->getRequest();
            try {
                $request->result = false; // mark es being executed
                $request->result = \civicrm_api3('Contact', 'get', $request);
                $request->reply = [];
            } catch (Exception $ex) {
                $request->addError($ex->getMessage());
            }
        }
    }

}
