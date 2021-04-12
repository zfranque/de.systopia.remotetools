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

use Civi\Test\Api3TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

use CRM_Remotetools_ExtensionUtil as E;

/**
 * Some very basic tests around RemoteEvents
 *
 * @group headless
 */
class CRM_Remotetools_MultivalueCustomFieldsTest extends CRM_Remotetools_RemoteContactTestBase
{
    use Api3TestTrait {
        callAPISuccess as protected traitCallAPISuccess;
    }

    public function setUp()
    {
        parent::setUp();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    /**
     * Some very basic RemoteEvent.get tests
     */
    public function testMultiValueCustomOR()
    {
        // add profile with custom fields
        $this->registerRemoteContactProfile('testMultiValueCustomProfile',
                                            'CRM_Remotetools_RemoteContactProfile_OwnFirstNameLastName');

        // generate test data
        $contactA = $this->createContact();
        $contactB = $this->createContact();

        // test1: run with

    }

    /**
     *
     */
    public static function getMultiValueCustomProfile()
    {

    }

}
