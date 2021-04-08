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

use Civi\RemoteContact\RemoteContactGetRequest;
use CRM_Remotetools_ExtensionUtil as E;

/**
 * RemoteContact: exchange contact data with a remote system
 */
class CRM_Remotetools_RemoteContact {

    /**
     * Process the (custom) multivalue_search_mode_or option.
     *
     * @param $request RemoteContactGetRequest
     *   the request to execute
     */
    public static function processMultivalueOrSearch($request)
    {
        // if there is already an error, we do nothing
        if ($request->hasErrors()) {
            return;
        }

        // get the list of fields that should be treated as multivalue OR search
        $multivalue_search_or_fields = $request->getRequestOption('multivalue_search_mode_or');
        if (empty($multivalue_search_or_fields)) {
            return;
        }

        // map list of fields with profile
        $profile = $request->getProfile();
        if ($profile) {
            $multivalue_search_or_fields = $profile->mapExternalFields($multivalue_search_or_fields);
        }

        // todo: find out, which of these are actually part of the request

        // todo: translate into SQL query

        // todo: add results as ID restriction

    }
}
