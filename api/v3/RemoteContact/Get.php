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
