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

require_once 'remotetools.civix.php';

use CRM_Remotetools_ExtensionUtil as E;
use \Civi\RemoteContact\GetFieldsEvent;

/**
 *
 * RemoteContact.getfields
 */
function civicrm_api3_remote_contact_getfields($params) {
    unset($params['check_permissions']);

    // we only support 'get' actions
    if (!empty($params['action']) && $params['action'] != 'get' && $params['action'] != 'get_self' && $params['action'] != 'getsingle') {
        return civicrm_api3('Contact', 'getfields', $params);
    }

    // create event to collect more fields
    $fields_collection = new GetFieldsEvent($params);

    // add some selected fields
    $fields_collection->setFieldSpec('remote_contact_id', [
        'name'          => 'remote_contact_id',
        'title'         => E::ts("Remote Contact Identification"),
        'description'   => E::ts("Use the key that you were given by RemoteContact.match to access this contact's roles"),
        'type'          => CRM_Utils_Type::T_STRING,
        'localizable'   => 0,
        'is_core_field' => false,
        'is_required'   => (strtolower($params['action']) == 'get_self'),
        'api.filter'    => 0,
        'api.sort'      => 0,
    ]);
    $fields_collection->setFieldSpec('profile', [
        'name'          => 'profile',
        'title'         => E::ts("Remote Contact Data Profile"),
        'description'   => E::ts('Defines the data you will be receiving. If omitted, the system will try to assign the a profile'),
        'type'          => CRM_Utils_Type::T_STRING,
        'localizable'   => 0,
        'is_core_field' => false,
        'is_required'   => false,
        'api.filter'    => 0,
        'api.sort'      => 0,
    ]);

    // dispatch to others
    Civi::dispatcher()->dispatch('civi.remotecontact.getfields', $fields_collection);

    // set results and return
    $fields['values'] = $fields_collection->getFieldSpecs();
    return $fields;
}
