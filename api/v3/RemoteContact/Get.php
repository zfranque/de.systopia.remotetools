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
use Civi\RemoteContact\RemoteContactGetRequest;

/**
 * RemoteContact.get specs
 *
 * @param array $spec
 *   API specification blob
 */
function _civicrm_api3_remote_contact_get_spec(&$spec)
{
    $spec['profile']           = [
        'name'         => 'profile',
        'api.required' => 1,
        'title'        => E::ts('Profile Name'),
        'description'  => E::ts('If omitted, the default profile is used'),
    ];
    $spec['remote_contact_id'] = [
        'name'         => 'remote_contact_id',
        'title'        => E::ts('Remote Contact ID'),
        'api.required' => 0,
        'description'  => E::ts("Use the key that you were given by RemoteContact.match to access this contact's roles"),
    ];
}

/**
 * RemoteContact.get implementation,
 *  analogous to Contact.get, but requiring the remote_contact_id parameter
 *
 * @param array $params
 *   API call parameters
 *
 * @return array
 *   API3 response
 */
function civicrm_api3_remote_contact_get($params)
{
    unset($params['check_permissions']);

    // create Symfony execution event
    $request = new RemoteContactGetRequest($params);
    Civi::dispatcher()->dispatch('civi.remotecontact.get', $request);

    if ($request->hasErrors()) {
        return $request->createAPI3Error();
    } else {
        return $request->createAPI3Success('RemoteContact', 'get');
    }
}
