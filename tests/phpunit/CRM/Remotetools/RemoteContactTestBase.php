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


use CRM_Remotetools_ExtensionUtil as E;
use Civi\RemoteContact\GetRemoteContactProfiles;
use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;


/**
 * This is the test base class with lots of utility functions
 *
 * @group headless
 */
abstract class CRM_Remotetools_RemoteContactTestBase extends CRM_Remotetools_TestBase implements HeadlessInterface, HookInterface,
                                                                            TransactionalInterface
{
    use Api3TestTrait {
        callAPISuccess as protected traitCallAPISuccess;
    }

    /** @var array list of known profiles to be used with registerRemoteContactProfile */
    private static $known_profiles = [];

    /**
     * Register the profiles provided by this module itself.
     *
     * @param GetRemoteContactProfiles $profiles
     */
    public static function registerKnownProfiles($profiles)
    {
        foreach (self::$known_profiles as $name => $class) {
            if ($profiles->matchesName($name)) {
                $profiles->addInstance(new $class());
            }
        }
    }


    /**
     * Will register a certain RemoteContact profile
     */
    public function registerRemoteContactProfile($name, $class_name)
    {
        // record profile
        self::$known_profiles[$name] = $class_name;

        // make sure we're registered
        $dispatcher = new \Civi\RemoteToolsDispatcher();
        $dispatcher->addUniqueListener(
            'civi.remotecontact.getprofiles',
            ['CRM_Remotetools_RemoteContactTestBase', 'getKnownProfiles']);
    }
}
