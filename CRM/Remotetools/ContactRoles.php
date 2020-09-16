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
class CRM_Remotetools_ContactRoles
{

    /** @var array cache for all existing roles  */
    protected static $roles_cache = null;

    /** @var array cache for contacts  */
    protected static $contact_roles_cache = null;

    /**
     * Will flush the internal caches regarding
     *  roles, and which contact has which
     */
    public static function flushRolesCache()
    {
        self::$roles_cache = null;
        self::$contact_roles_cache = [];
    }

    /**
     * Check if the given contact has the
     *
     * @param integer $contact_id
     *   (internal) contact ID
     * @param string $role_name
     *   the role to check for
     *
     * @return boolean
     *  roles list [[name => label]]
     */
    public static function hasRole($contact_id, $role_name)
    {
        $roles = self::getRoles($contact_id);
        return isset($roles[$role_name]);
    }

    /**
     * Get a list of RemoteContact roles
     *
     * @param integer $contact_id
     *   (internal) contact ID
     *
     * @return array
     *  roles list [[name => label]]
     */
    public static function getRoles($contact_id)
    {
        $contact_id = (int) $contact_id;
        if (!isset(self::$contact_roles_cache[$contact_id])) {
            self::$contact_roles_cache[$contact_id] = [];

            // load contact roles
            $roles_field = CRM_Remotetools_CustomData::getCustomFieldKey('remote_contact_data', 'remote_contact_roles');
            CRM_Remotetools_CustomData::resolveCustomFields($query);
            $roles = civicrm_api3('Contact', 'getvalue', [
                'id'     => $contact_id,
                'return' => $roles_field
            ]);

            // map to the proper form
            $all_roles = self::getAllRoles();
            foreach ($roles as $role_value) {
                foreach ($all_roles as $role) {
                    if ($role['value'] == $role_value) {
                        self::$contact_roles_cache[$contact_id][$role['name']] = $role['label'];
                        break;
                    }
                }
            }
        }
        return self::$contact_roles_cache[$contact_id];
    }

    /**
     * Add a list of RemoteContact roles
     *
     * @param integer $contact_id
     *   (internal) contact ID
     *
     * @params $role_names array
     *  roles names (not labels)
     */
    public static function addRoles($contact_id, $role_names)
    {
        // check if we have to do anything...
        $given_roles = self::getRoles($contact_id);
        $roles_to_add = [];
        foreach ($role_names as $role_name) {
            if (!isset($given_roles[$role_name])) {
                $roles_to_add[] = $role_name;
            }
        }

        if (!empty($roles_to_add)) {
            // ...looks like we have to do something
            $new_role_values = [];
            $all_roles = self::getAllRoles();
            foreach ($given_roles as $role) {
                $new_role_values[] = $role['value'];
            }
            foreach ($roles_to_add as $role_name) {
                if (isset($all_roles[$role_name])) {
                    $new_role_values[] = $all_roles[$role_name]['value'];
                }
            }

            // set the new roles
            $roles_field = CRM_Remotetools_CustomData::getCustomFieldKey('remote_contact_data', 'remote_contact_roles');
            civicrm_api3('Contact', 'create', [
                'id'         => $contact_id,
                $roles_field => $new_role_values
            ]);
        }
    }

    /**
     * Get a list of all roles
     *
     * @return array
     *   roles list [[name => data]]
     */
    public static function getAllRoles()
    {
        if (self::$roles_cache === null) {
            self::$roles_cache = [];
            $query = civicrm_api3(
                'OptionValue',
                'get',
                [
                    'option.limit'    => 0,
                    'is_active'       => 1,
                    'option_group_id' => 'remote_contact_roles',
                    'return'          => 'value,name,label'
                ]
            );
            foreach ($query['values'] as $role) {
                self::$roles_cache[$role['name']] = $role;
            }
        }
        return self::$roles_cache;
    }
}
