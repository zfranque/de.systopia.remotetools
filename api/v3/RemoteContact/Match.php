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
    // @todo: better parameters
    require_once 'api/v3/Contact.php';
    _civicrm_api3_contact_create_spec($spec);

    // and remove some
    unset($spec['id']);

    // finally: add prefix (optional)

    // add extra fields
    $spec['key_prefix'] = [
        'name'         => 'key_prefix',
        'api.required' => 0,
        'title'        => E::ts('Key Prefix (optional)'),
        'description'  => E::ts('You can use a prefix to be added to the generated ID, so you can later identify where the ID came from. The value (up to 8 chars) can be A-Z, 0-9, _-#'),
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

    // assume it's a contact if not otherwise specified
    if (empty($params['contact_type'])) {
        $params['contact_type'] = 'Individual';
    }

    try {
        $prefix = CRM_Utils_Array::value('key_prefix', $params, '');
        $new_key = CRM_Remotetools_Contact::match($params, $prefix);
        return civicrm_api3_create_success(['key' => $new_key], $params, 'RemoteContact', 'match', $null, ['key' => $new_key]);
    } catch (Exception $ex) {
        return civicrm_api3_create_error($ex->getMessage());
    }
}
