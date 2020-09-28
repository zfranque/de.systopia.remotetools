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
 * RemoteContact.get_roles specs
 *
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_contact_get_roles_spec(&$spec)
{
    $spec['remote_contact_id'] = [
        'name'         => 'remote_contact_id',
        'title'        => E::ts('Remote Contact ID'),
        'api.required' => 1,
        'description'  => E::ts("Use the key that you were given by RemoteContact.match to access this contact's roles"),
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
function civicrm_api3_remote_contact_get_roles($params)
{
    unset($params['check_permissions']);

    // identify contact
    $contact_id = CRM_Remotetools_Contact::getByKey($params['remote_contact_id']);
    if (empty($contact_id)) {
        return civicrm_api3_create_error(E::ts("A contact with this key is not registered."));
    }

    // get roles
    $roles = CRM_Remotetools_ContactRoles::getRoles($contact_id);
    return civicrm_api3_create_success($roles);
}
