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
 * Collection of upgrade steps.
 */
class CRM_Remotetools_Upgrader extends CRM_Remotetools_Upgrader_Base {

  /**
   * During installation, add the 'Remote Contact' type
   *  to the identity tracker
   */
  public function install()
  {
      // check if an entry already exists
      $exists_count = civicrm_api3(
          'OptionValue',
          'getcount',
          [
              'option_group_id' => 'contact_id_history_type',
              'value'           => 'remote_contact'
          ]
      );
      switch ($exists_count) {
          case 0:
              // not there -> create
              civicrm_api3(
                  'OptionValue',
                  'create',
                  [
                      'option_group_id' => 'contact_id_history_type',
                      'value'           => 'remote_contact',
                      'is_reserved'     => 1,
                      'description'     => E::ts(
                          "Used by the RemoteTools extension to map CiviCRM contacts to remote users oder contacts."
                      ),
                      'name'            => 'remote_contact',
                      'label'           => E::ts("Remote Contact")
                  ]
              );
              break;

          case 1:
              // does exist, nothing to do here
              break;

          default:
              // more than one exists: that's not good!
              CRM_Core_Session::setStatus(
                  E::ts(
                      "Multiple identiy types 'remote_contact' contact exist in IdentityTracker's types! Please fix!"
                  ),
                  E::ts("Warning"),
                  'warn'
              );
              break;
      }
  }
}
