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
function civicrm_api3_remote_contact_get_self($params)
{
    unset($params['check_permissions']);

    // create Symfony execution event
    $request = new RemoteContactGetRequest($params);
    $request->setSelfRequest(true);

    // identify contact
    if (empty($params['remote_contact_id'])) {
        $request->addError(E::ts("A remote_contact_id needs to be given"));
    } else {
        $contact_id = CRM_Remotetools_Contact::getByKey($params['remote_contact_id']);
        if (empty($contact_id)) {
            $request->addError(E::ts("A contact with this remote_contact_id is not registered."));
        } else {
            // set contact ID to query
            $request_data = &$request->getRequest();
            $request_data['id'] = $contact_id;
        }
    }

    // check if the profile allows for get_self actions
    $profile = $request->getProfile();
    if ($profile) {
        if (!$profile->isOwnDataProfile($request)) {
            $request->addError(E::ts("Profile '%1' cannot be used for the RemoteContact.get_self action."));
        }
    }

    Civi::dispatcher()->dispatch('civi.remotecontact.get', $request);

    // prepare data for getsingle-style result
    $contact_data = reset($request->getReply()['values']);
    if (empty($contact_data)) {
        $request->addError(E::ts("Your own contact could not be identified"));

    } else {
        // reformat data as getsingle-style result
        $reply_data = &$request->getReply();
        foreach ($contact_data as $key => $value) {
            $reply_data[$key] = $value;
        }
        $reply_data['values'] = [];
        unset($reply_data['count']);
        unset($reply_data['id']);
    }

    if ($request->hasErrors()) {
        return $request->createAPI3Error();
    } else {
        // extract the only contact
        return $request->createAPI3Success('RemoteContact', 'get_self', $reply_data);
    }
}
