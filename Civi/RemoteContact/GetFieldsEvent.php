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
 * Class GetFieldsEvent
 *
 * @package Civi\RemoteContact
 *
 * This event will be triggered to populate the reply of the
 *     RemoteContact.getfields
 */
class GetFieldsEvent extends RemoteToolsRequest
{
    /** @var array holds the list of the RemoteContact.get field specs */
    protected $field_specs;

    /** @var \CRM_Remotetools_RemoteContactProfile caches the profile involved */
    protected $profile = null;

    public function __construct($request)
    {
        parent::__construct($request);
        $this->field_specs = [];
    }

    /**
     * Get the current field specs
     *
     * @return array
     *   the key => spec list
     */
    public function getFieldSpecs()
    {
        return $this->field_specs;
    }

    /**
     * Set/add a particular field spec
     *
     * @param string $field_name
     *   the field name
     * @param array $spec
     *   the field spec
     */
    public function setFieldSpec($field_name, $spec)
    {
        $this->field_specs[$field_name] = $spec;
    }

    /**
     * Remove a particular field spec
     *
     * @param string $field_name
     *   the field name
     */
    public function removeFieldSpec($field_name)
    {
        unset($this->field_specs[$field_name]);
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
                $this->addError(E::ts("The profile could not be determined."));
            } else {
                $this->profile = \CRM_Remotetools_RemoteContactProfile::getProfileByName($profile_name);
                if (!$this->profile) {
                    $this->addError("Profile {$profile_name} not valid");
                }
            }
        }
        return $this->profile;
    }


    /**
     * Add the profile fields
     *
     * @param $fields_collection GetFieldsEvent
     *   the request to execute
     */
    public static function addProfileFields($fields_collection)
    {
        if (!$fields_collection->hasErrors()) {
            $profile = $fields_collection->getProfile();
            if ($profile) {
                $profile->addFields($fields_collection);
            }
        }
    }
}
