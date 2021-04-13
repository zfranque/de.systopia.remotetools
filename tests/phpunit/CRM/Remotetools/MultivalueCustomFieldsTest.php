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
        // use the MultiValueCustom Profile
        $this->registerMultiValueCustomProfile();

        // generate custom data fields
        $customData = new CRM_Remotetools_CustomData(E::LONG_NAME);
        $customData->syncOptionGroup(E::path('tests/resources/option_group_test_number_list.json'));
        $customData->syncCustomGroup(E::path('tests/resources/custom_group_contact_test1.json'));

        // generate test data
        $contactA = $this->createContact([
            'contact_test1.contact_multi_test1' => [1,2,3],
            'contact_test1.contact_multi_test2' => [1,2,3],
        ]);
        $contactB = $this->createContact([
            'contact_test1.contact_multi_test1' => [2,3,4],
            'contact_test1.contact_multi_test2' => [2,3,4],
        ]);

        // test1: run with single common value
        $profile = CRM_Remotetools_RemoteContactTestBase::MULTI_VALUE_CUSTOM_PROFILE;
        $contacts = $this->remoteContactQuery($profile, [
            'contact_multi_test1' => 3,
            'contact_multi_test2' => 3,
        ]);
        $this->assertCount(2, $contacts, "This should have found both contacts");

        // test2: run with single unique value
        $profile = CRM_Remotetools_RemoteContactTestBase::MULTI_VALUE_CUSTOM_PROFILE;
        $contacts = $this->remoteContactQuery($profile, [
            'contact_multi_test1' => 1,
            'contact_multi_test2' => 1,
        ]);
        $this->assertCount(1, $contacts, "This should have found only one contact");

        // test3: run with exact multi-value
        $profile = CRM_Remotetools_RemoteContactTestBase::MULTI_VALUE_CUSTOM_PROFILE;
        $contacts = $this->remoteContactQuery($profile, [
            'contact_multi_test1' => [1,2,3],
            'contact_multi_test2' => [1,2,3],
        ]);
        $this->assertCount(1, $contacts, "This should have found only one contact");

        // test4: run with common multi-value
        $profile = CRM_Remotetools_RemoteContactTestBase::MULTI_VALUE_CUSTOM_PROFILE;
        $contacts = $this->remoteContactQuery($profile, [
            'contact_multi_test1' => [2,3],
            'contact_multi_test2' => [2,3],
        ]);
        $this->assertCount(2, $contacts, "This should have found both contacts");

        // test5: run with common single value -> should return empty, because it's ANDed (neither have 1 AND 4)
        $profile = CRM_Remotetools_RemoteContactTestBase::MULTI_VALUE_CUSTOM_PROFILE;
        $contacts = $this->remoteContactQuery($profile, [
            'contact_multi_test1' => [1,4],
            'contact_multi_test2' => [1,4],
        ]);
        $this->assertCount(0, $contacts, "This should have NOT found both contacts, because the API does AND");

        // test6: run with common single value, but this time in OR-mode
        $profile = CRM_Remotetools_RemoteContactTestBase::MULTI_VALUE_CUSTOM_PROFILE;
        $contacts = $this->remoteContactQuery($profile, [
            'contact_multi_test1' => [1,4],
            'contact_multi_test2' => [1,4],
            'option.multivalue_search_mode_or' => ['contact_multi_test1', 'contact_multi_test2'],
        ]);
        $this->assertCount(2, $contacts, "This should have found both contacts, because the API was instructed to OR");
    }
}
