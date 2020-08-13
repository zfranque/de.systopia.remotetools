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

require_once 'remotetools.civix.php';
use CRM_Remotetools_ExtensionUtil as E;

/**
 * RemoteContact.match specs
 *
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_contact_match_spec(&$spec)
{
    $spec['key_prefix'] = [
        'name'         => 'key_prefix',
        'api.required' => 0,
        'title'        => E::ts('Key Prefix (optional)'),
        'description'  => E::ts('You can use a prefix to be added to the generated ID, so you can later identify where the ID came from. The value (up to 8 chars) can be A-Z, 0-9, _-#'),
    ];
    $spec['contact_type'] = [
        'name'         => 'contact_type',
        'title'        => E::ts('Contact Type'),
        'api.default'  => 'Individual',
        'type'         => CRM_Utils_Type::T_STRING,
    ];
    $spec['first_name'] = [
        'name'         => 'first_name',
        'title'        => E::ts('First Name'),
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_STRING,
    ];
    $spec['last_name'] = [
        'name'         => 'last_name',
        'title'        => E::ts('Last Name'),
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_STRING,
    ];
    $spec['email'] = [
        'name'         => 'email',
        'title'        => E::ts('Email'),
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_STRING,
    ];
    $spec['phone'] = [
        'name'         => 'phone',
        'title'        => E::ts('Phone'),
        'api.required' => 0,
        'type'         => CRM_Utils_Type::T_STRING,
    ];
}

/**
 * RemoteContact.match implementation
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_contact_match($params)
{
    $null = null;

    try {
        $prefix = CRM_Utils_Array::value('key_prefix', $params, '');
        $new_key = CRM_Remotetools_Contact::match($params, $prefix);
        return civicrm_api3_create_success(['key' => $new_key], $params, 'RemoteContact', 'match', $null, ['key' => $new_key]);
    } catch (Exception $ex) {
        return civicrm_api3_create_error($ex->getMessage());
    }
}
