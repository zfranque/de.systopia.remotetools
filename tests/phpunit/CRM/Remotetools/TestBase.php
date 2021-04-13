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
 * This is the test base class with lots of utility functions
 *
 * @group headless
 */
abstract class CRM_Remotetools_TestBase extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface,
                                                                            TransactionalInterface
{
    use Api3TestTrait {
        callAPISuccess as protected traitCallAPISuccess;
    }

    /** @var CRM_Core_Transaction current transaction */
    protected $transaction = null;

    public function setUpHeadless()
    {
        // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
        // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
        return \Civi\Test::headless()
            ->install(['de.systopia.xcm'])
            ->install(['de.systopia.identitytracker'])
            ->installMe(__DIR__)
            ->apply();
    }

    public function setUp()
    {
        parent::setUp();
        $this->transaction = new CRM_Core_Transaction();
        $this->setUpXCMProfile('default');

        $profile = CRM_Xcm_Configuration::getConfigProfile('default');

        //Civi::settings()->set('remote_event_get_performance_enhancement', true);
    }

    public function tearDown()
    {
        $this->transaction->rollback();
        $this->transaction = null;
        parent::tearDown();
    }

    /**
     * Create a new contact
     *
     * @param array $contact_details
     *   overrides the default values
     *
     * @return array
     *  contact data
     */
    public function createContact($contact_details = [])
    {
        // prepare event
        $contact_data = [
            'contact_type' => 'Individual',
            'first_name'   => $this->randomString(10),
            'last_name'    => $this->randomString(10),
            'email'        => $this->randomString(10) . '@' . $this->randomString(10) . '.org',
            'prefix_id'    => 1,
        ];
        foreach ($contact_details as $key => $value) {
            $contact_data[$key] = $value;
        }
        CRM_Remotetools_CustomData::resolveCustomFields($contact_data);

        // create contact
        $result = $this->traitCallAPISuccess('Contact', 'create', $contact_data);
        $contact = $this->traitCallAPISuccess('Contact', 'getsingle', ['id' => $result['id']]);
        CRM_Remotetools_CustomData::labelCustomFields($contact);
        return $contact;
    }

    /**
     * Create a number of new contacts
     *  using the createContact function above
     *
     * @param integer $count
     * @param array $contact_details
     *
     * @return array [event_id => $event_data]
     */
    public function createContacts($count, $contact_details = [])
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $contact = $this->createContact($contact_details);
            $result[$contact['id']] = $contact;
        }
        return $result;

    }

    /**
     * Generate a random string, and make sure we don't collide
     *
     * @param int $length
     *   length of the string
     *
     * @return string
     *   random string
     */
    public function randomString($length = 32)
    {
        static $generated_strings = [];
        $candidate = substr(sha1(random_bytes(32)), 0, $length);
        if (isset($generated_strings[$candidate])) {
            // simply try again (recursively). Is this dangerous? Yes, but veeeery unlikely... :)
            return $this->randomString($length);
        }
        // mark as 'generated':
        $generated_strings[$candidate] = 1;
        return $candidate;
    }

    /**
     * Make sure the given profile exists, and has
     *   a basic amount of matching options
     *
     * @param string $profile_name
     *   name of the profile
     * @param array $profile_data_override
     *   XCM profile spec that differs from the default
     */
    public function setUpXCMProfile($profile_name, $profile_data_override = null)
    {
        // load XCM profile data
        static $profile_data = null;
        if ($profile_data === null) {
            $profile_data = json_decode(file_get_contents(E::path('tests/resources/xcm_profile_testing.json')), 1);
        }

        // set profile
        $profiles = Civi::settings()->get('xcm_config_profiles');
        if ($profile_data_override) {
            $profiles[$profile_name] = $profile_data_override;
        } else {
            $profiles[$profile_name] = $profile_data;
        }
        Civi::settings()->set('xcm_config_profiles', $profiles);
    }

    /**
     * Get a random value subset of the array
     *
     * @param array $array
     *   the array to pick the values
     *
     * @param integer $count
     *   number of elements to pick, will be randomised if not given
     *
     * @return array
     *   subset (keys not retained)
     */
    public function randomSubset($array, $count = null)
    {
        if ($count === null) {
            $count = mt_rand(1, count($array) - 1);
        }

        $random_keys = array_rand($array, $count);
        if (!is_array($random_keys)) {
            $random_keys = [$random_keys];
        }

        // create result array
        $result = [];
        foreach ($random_keys as $random_key) {
            $result[] = $array[$random_key];
        }
        return $result;
    }

    /**
     * Get a remote contact key for the given contact.
     *  if no such key exists, create one
     *
     * @param integer $contact_id
     *
     * @return string
     *   contact key
     */
    public function getRemoteContactKey($contact_id)
    {
        $contact_id = (int) $contact_id;
        $key = CRM_Core_DAO::singleValueQuery("
            SELECT identifier
            FROM civicrm_value_contact_id_history
            WHERE identifier_type = 'remote_contact'
              AND entity_id = {$contact_id}
            LIMIT 1
        ");
        if (!$key) {
            $key = $this->randomString();
            CRM_Core_DAO::executeQuery("
                INSERT INTO civicrm_value_contact_id_history (entity_id, identifier, identifier_type, used_since)
                VALUES ({$contact_id}, '{$key}', 'remote_contact', NOW())
            ");
        }

        $verify_contact_id = CRM_Remotetools_Contact::getByKey($key);
        $this->assertEquals($contact_id, $verify_contact_id, "Couldn't generate remote contact key.");
        return $key;
    }


    /**
     * Create a new, unique campaign
     */
    public function getCampaign() {
        $campaign_name = $this->randomString();
        $campaign = $this->traitCallAPISuccess('Campaign', 'create', [
            'name' => $campaign_name,
            'title' => $campaign_name,
            'campaign_type_id' => 1,
            'status_id' => 1,
        ]);
        return $this->traitCallAPISuccess('Campaign', 'getsingle', ['id' => $campaign['id']]);
    }

    /**
     * Get a (within this test) unique
     *  timestamp. It starts with now+1h and
     *  increments in 5 minute interval
     *
     * @return string timestamp
     */
    public function getUniqueDateTime()
    {
        static $last_timestamp = null;
        if ($last_timestamp === null) {
            $last_timestamp = strtotime('now + 1 hour');
        } else {
            $last_timestamp = strtotime('+5 minutes', $last_timestamp);
        }
        return date('YmdHis', $last_timestamp);
    }
}
