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

use CRM_Remotetools_ExtensionUtil as E;

/**
 * RemoteContact function
 */
class CRM_Remotetools_Contact {

    /**
     * Identify by a remote key
     *
     * @param string $remote_key
     *   the key issued at some point for a contact
     *
     * @return integer|null contact ID or null if not found
     */
    public static function getByKey($remote_key) {
        // check if exists:
        $query = CRM_Core_DAO::executeQuery("
            SELECT entity_id AS contact_id
            FROM civicrm_value_contact_id_history
            WHERE identifier_type = 'remote_contact'
              AND identifier = %1
            LIMIT 1", [1 => [$remote_key, 'String']]);
        if ($query->fetch() && $query->contact_id) {
            return $query->contact_id;
        } else {
            return null;
        }
    }

    /**
     * Will match the contact/user represented by
     *  the given data blog to a (potentially new)
     *  CiviCRM contact. Will return the
     *  remote contact key, or throw an exception
     *
     * Also the implementation of the RemoteContact.match API
     *
     * @param array $data
     *    random contact data
     * @param string $prefix
     *    prefix for the generated ID
     *
     * @return string unique key representing this link/contact
     *
     * @throws Exception if the contact could not be matched or created
     */
    public static function match($data, $prefix = '') {
        // TODO: check if ID generation enabled

        // sanitise data
        unset($data['xcm_profile'], $data['contact_id'], $data['id']);
        $data['check_permissions'] = 0;

        // TODO: load xcm_profile

        // run XCM to find contact
        $result = civicrm_api3('Contact', 'getorcreate', $data);
        $contact_id = $result['id'];
        if (empty($contact_id)) {
            throw new Exception(E::ts("Couldn't identify contact"));
        }

        // contact found: generate key
        $new_key = self::generateRemoteKey($prefix);
        while (self::remoteKeyExists($new_key)) {
            $new_key = self::generateRemoteKey($prefix);
            // todo: counter and abort?
        }

        // store new key
        self::storeRemoteKey($new_key, $contact_id);

        // and return
        return $new_key;
    }

    /**
     * Generate a brand new key
     *
     * @param string $prefix
     *   prefix string (will be sanitised)
     *
     * @return string
     *   new key
     */
    public static function generateRemoteKey($prefix) {
        // contact found: generate key
        $new_key = strtoupper(substr(sha1(random_bytes(32)), 0, 16));

        // sanitise and add prefix
        $prefix = strtoupper($prefix);
        $prefix = preg_replace('/[^0-9A-Z_#-]/', '', $prefix);
        $prefix = substr($prefix, 0, 8);

        // return brand new key
        return $prefix . $new_key;
    }

    /**
     * Check if a potential key already exists
     *
     * @param string $key_candidate
     *      potential new key
     *
     * @return integer
     *   number of times the key exists
     */
    public static function remoteKeyExists($key_candidate) {
        // check if exists:
        return CRM_Core_DAO::singleValueQuery("
            SELECT COUNT(*) 
            FROM civicrm_value_contact_id_history
            WHERE identifier_type = 'remote_contact'
              AND identifier = %1", [1 => [$key_candidate, 'String']]);
    }

    /**
     * Store a new key with the given contact
     *
     * @param string $new_key
     * @param integer $contact_id
     */
    public static function storeRemoteKey($new_key, $contact_id) {
        CRM_Core_DAO::executeQuery("
         INSERT INTO civicrm_value_contact_id_history(entity_id,identifier_type,identifier,used_since)
         VALUES(%1, 'remote_contact', %2, NOW());", [
             1 => [$contact_id, 'Integer'],
             2 => [$new_key, 'String'],
        ]);
    }
}
