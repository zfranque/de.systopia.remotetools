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


namespace Civi;

use Civi\Setup\PackageUtil;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class RemoteEvent
 *
 * @package Civi\RemoteEvent\Event
 *
 * Abstract event class to provide some basic functions
 */
class RemoteToolsRequest extends Event
{
    const BEFORE_INITIALISATION     = 2250;
    const INITIALISATION            = 2000;
    const AFTER_INITIALISATION      = 1750;

    const BEFORE_EXECUTE_REQUEST    = 500;
    const EXECUTE_REQUEST           = 0;
    const AFTER_EXECUTE_REQUEST     = -500;

    /** @var array original request paramters */
    protected $original_request = [];

    /** @var array request paramters */
    protected $request = [];

    /** @var array the result of the request */
    protected $result = null;

    /** @var array the reply to be returned */
    protected $reply = [];

    /** @var array holds the list of error messages */
    protected $error_list = [];

    /** @var array holds the list of warning messages */
    protected $warning_list = [];

    /** @var array holds the list of info/status messages */
    protected $info_list = [];

    /** @var integer the contact ID of the caller */
    protected $caller_contact_id = null;

    /**
     * Create RemoteToolsRequest with the original query
     *
     * @param array $original_request
     */
    public function __construct($original_request)
    {
        $this->original_request = $original_request;
        $this->request = $original_request;

        // clear return parameter
        $this->request['return'] = '';
    }


    /**
     * Get the contact_id of the caller
     *
     * @return int
     *   a contact ID, 0 (zero) if not found/identified
     */
    public function getCallerContactID()
    {
        if (!isset($this->caller_contact_id)) {
            if (empty($this->getRequest()['remote_contact_id'])) {
                $this->caller_contact_id = 0; // no ID given
            } else {
                $contact_id = \CRM_Remotetools_Contact::getByKey($this->getRequest()['remote_contact_id']);
                if ($contact_id) {
                    $this->caller_contact_id = $contact_id;
                } else {
                    $this->caller_contact_id = 0; // contact not found
                }
            }
        }

        return $this->caller_contact_id;
    }

    /**
     * Set/override the contact ID of the caller
     *
     * @param integer $contact_id
     */
    public function setCallerContactID($contact_id)
    {
        $this->caller_contact_id = $contact_id;
    }

    /**
     * Get a parameter from the original request
     *
     * @param string $name
     *   parameter name
     *
     * @param mixed $default
     *   default return value, if not set
     */
    public function getOriginalRequestParameter($name, $default = null)
    {
        return \CRM_Utils_Array::value($name, $this->original_request, $default);
    }

    /**
     * Get the full original request
     *
     * @return array
     *   request data
     */
    public function getOriginalRequest()
    {
        return $this->original_request;
    }

    /**
     * Get the current sorting instructions as an array
     *
     * @param array $request_data
     *   the API request. If empty, the original request will be used
     *
     * @return array
     *   list of [field_name, 'ASC'|'DESC']  tuples
     */
    public function getSorting($request_data = null)
    {
        // use original request
        if (empty($request_data) || !is_array($request_data)) {
            $request_data = $this->original_request;
        }

        return \CRM_Remotetools_DataTools::getSortingTuples($request_data);
    }

    /**
     * Get the current sorting instructions as an array
     *
     * @param array $sorting_tuples
     *   list of [field_name, 'ASC'|'DESC']  tuples
     *
     * @param array $request_data
     *   the API request. If empty, the compiled request will be used
     *
     */
    public function setSorting($sorting_tuples, &$request_data = null)
    {
        // use original request
        if (empty($request_data) || !is_array($request_data)) {
            $request_data = &$this->request;
        }

        \CRM_Remotetools_DataTools::setSortingString($sorting_tuples, $request_data);
    }

    /**
     * Map the search parameters keys with the given mapping
     * @param array $mapping
     *   string -> string mapping
     */
    public function mapParameters($mapping = [])
    {
        foreach ($mapping as $old_key => $new_key)
        {
            if ($old_key != $new_key) {
                if (isset($this->request[$old_key])) {
                    $this->request[$new_key] = $this->request[$old_key];
                    unset($this->request[$old_key]);
                }
            }
        }
    }

    /**
     * Get a parameter from the (current) request
     *
     * @param string $name
     *   parameter name
     *
     * @param mixed $default
     *   default return value, if not set
     */
    public function getRequestParameter($name, $default = null)
    {
        return \CRM_Utils_Array::value($name, $this->request, $default);
    }

    /**
     * Remove a parameter from the (current) request
     *
     * @param string $name
     *   parameter name
     *
     * @param mixed $default
     *   previous value, or null if not set
     */
    public function removeRequestParameter($name)
    {
        $old_value = \CRM_Utils_Array::value($name, $this->request);
        unset($this->request[$name]);
        return $old_value;
    }

    /**
     * Get an API option from the (current) request
     *
     * @param string $name
     *   parameter name
     *
     * @param boolean $json_parse
     *   should the raw string (tried to) be parsed as json?
     *
     * @param string $explode_string
     *   if not empty, a potential string will be exploded by that character before return
     *
     * @return mixed
     *   the option value, or null if not set
     */
    public function getRequestOption($name, $json_parse = true, $explode_string = ',')
    {
        // get the value
        $value = null;
        if (isset($this->request['options'][$name])) {
            $value = $this->request['options'][$name];
        }
        if (!$value && isset($this->request["option.{$name}"])) {
            $value = $this->request["option.{$name}"];
        }

        // try to parse as JSON (if requested)
        if (!$json_parse) {
            $parsed_value = json_decode($value, true);
            if ($parsed_value !== null) {
                $value = $parsed_value;
            }
        }

        // try to explode (if requested)
        if (is_string($value) && !empty($explode_string)) {
            $values = explode($explode_string, $value);
            if (count($values) > 1) {
                $value = $values;
            }
        }

        return $value;
    }

    /**
     * Get an API option from the (current) request
     *
     * @param string $name
     *   parameter name
     *
     * @param mixed $value
     *   default return value, if not set
     */
    public function setRequestOption($name, $value)
    {
        unset($this->request['options'][$name]);
        unset($this->request["option.{$name}"]);
        $this->request['options'][$name] = $value;
    }

    /**
     * Get the current restriction of entity IDs
     *
     * Remark: this returns
     *   null  if not set
     *   fail  if couldn't be parsed
     *   array with a list of entity IDs, if everything's fine
     *
     * @return array|null|string
     *   list of requested IDs
     */
    public function getRequestedEntityIDs()
    {
        if (isset($this->request['id'])) {
            $id_param = $this->request['id'];
            if (is_string($id_param)) {
                // this is a single integer, or a list of integers
                $id_list = explode(',', $id_param);
                return array_map('intval', $id_list);

            } else if (is_array($id_param)) {
                // this is an array. we can deal with the 'IN' => [] notation
                if (count($id_param) == 2) {
                    if (strtolower($id_param[0]) == 'in' && is_array($id_param[1])) {
                        // this should be a list of IDs
                        return array_map('intval', $id_param[1]);
                    }
                }
            }

            // if we get here, we couldn't parse it
            \Civi::log()->debug("RemoteEntity.get: couldn't parse 'id' parameter: " . json_encode($id_param));
            return 'fail';

        } else {
            // 'id' field not set
            return null;
        }
    }

    /**
     * Restrict the query to the given entity IDs.
     *  Existing restrictions will be taken into account (intersection)
     *
     * @param array $entity_ids
     *   list of entity IDs
     */
    public function restrictToEntityIds($entity_ids)
    {
        if (empty($entity_ids)) {
            // this basically means: restrict to empty set:
            $this->request['id'] = 0;
        } else {
            $current_restriction = $this->getRequestedEntityIDs();
            if ($current_restriction === null) {
                // no restriction set so far
                $this->request['id'] = ['IN' => $entity_ids];

            } else if (is_array($current_restriction)) {
                // there is a restriction -> intersect
                $intersection = array_intersect($current_restriction, $entity_ids);
                $this->request['id'] = ['IN' => $intersection];

            } else {
                // something's wrong here
                \Civi::log()->debug("RemoteEntity.get: couldn't restrict 'id' parameter: " . json_encode($current_restriction));
            }
        }
    }

    /**
     * Get the currently compiled request for editing
     *
     * @return array
     *   request data
     */
    public function &getRequest()
    {
        return $this->request;
    }

    /**
     * Get the currently compiled reply
     *
     * @return array
     *   reply data
     */
    public function &getReply()
    {
        return $this->reply;
    }

    /**
     * Check whether the underlying query was executed
     * Caution: does not imply execution was succesfull
     */
    public function wasExecuted()
    {
        return $this->result !== null;
    }

    /**
     * Get the list of fields currently requested to be returned
     */
    public function getOriginalReturnFields()
    {
        $return_string = \CRM_Utils_Array::value('return', $this->original_request, '');
        if ($return_string) {
            return explode(',', $return_string);
        } else {
            return [];
        }
    }

    /**
     * Get the list of fields currently requested to be returned
     */
    public function getReturnFields()
    {
        $return_string = \CRM_Utils_Array::value('return', $this->request, '');
        if ($return_string) {
            return explode(',', $return_string);
        } else {
            return null;
        }
    }

    /**
     * Check if the submission has errors
     * @return bool
     *   true if there is errors
     */
    public function hasErrors()
    {
        return !empty($this->error_list);
    }

    /**
     * Add an error message to the remote context
     *
     * @param string $message
     *   the error message, localised
     *
     * @param string $reference
     *   a reference, e.g. e a field name
     */
    public function addError($message, $reference = '')
    {
        $this->error_list[] = [$message, $reference];
    }

    /**
     * Get a list of all errors
     *
     * @return array
     *   complete error list
     */
    public function getErrors()
    {
        return $this->error_list;
    }

    /**
     * Check if the submission has errors
     * @return bool
     *   true if there is errors
     */
    public function hasWarnings()
    {
        return !empty($this->warning_list);
    }

    /**
     * Add a warning to the remote context
     *
     * @param string $message
     *   the warning, localised
     *
     * @param string $reference
     *   a reference, e.g. e a field name
     */
    public function addWarning($message, $reference = '')
    {
        $this->warning_list[] = [$message, $reference];
    }

    /**
     * Add a warning to the remote context
     *
     * @param string $message
     *   status message, localised
     *
     * @param string $reference
     *   a reference, e.g. e a field name
     */
    public function addStatus($message, $reference = '')
    {
        $this->info_list[] = [$message, $reference];
    }

    /**
     * Get a list of status messages in the following form
     * [
     *   message: the status message,
     *   severity: status|warning|error
     *   reference: (optional) message reference, e.g. field name
     */
    public function getStatusMessageList()
    {
        $messages = [];
        foreach ($this->error_list as $error) {
            $messages[] = [
                'message' => $error[0],
                'severity' => 'error',
                'reference' => $error[1]
            ];
        }
        foreach ($this->warning_list as $warning) {
            $messages[] = [
                'message' => $warning[0],
                'severity' => 'warning',
                'reference' => $warning[1]
            ];
        }
        foreach ($this->info_list as $info) {
            $messages[] = [
                'message' => $info[0],
                'severity' => 'status',
                'reference' => $info[1]
            ];
        }
        return $messages;
    }

    /**
     * Get in indexed array of all status messages (of the given classes)
     *   indexed by reference
     *
     * @param string[] $classes
     *   list of classes to consider
     *
     * @return array
     *  [reference => message/error] list
     */
    public function getReferencedStatusList($classes = ['error'])
    {
        $result = [];
        foreach ($this->getStatusMessageList() as $message) {
            if (in_array($message['severity'], $classes) && !empty($message['reference'])) {
                $result[$message['reference']] = $message['message'];
            }
        }
        return $result;
    }

    /**
     * Generate an API3 error
     */
    public function createAPI3Error()
    {
        $first_error = reset($this->error_list);
        return civicrm_api3_create_error($first_error[0], ['status_messages' => $this->getStatusMessageList()]);
    }

    /**
     * Generate an API3 error
     */
    public function createAPI3Success($entity, $action, $extraReturnValues = [])
    {
        // add status messages
        $extraReturnValues['status_messages'] = $this->getStatusMessageList();

        // compile standard result
        static $null = null;
        return civicrm_api3_create_success($this->reply['values'], [], $entity, $action, $null, $extraReturnValues);
    }

    /**
     * Generate a RemoteEntity conform API3 error
     *
     * @param $error_message
     *
     *
     */
    public static function createStaticAPI3Error($error_message)
    {
        return civicrm_api3_create_error($error_message, [
            'status_messages' => [
                [
                    'message' => $error_message,
                    'severity' => 'error',
                    'reference' => '',
                ]
            ]
        ]);
    }

}
