<?php

/**
 * -------------------------------------------------------------------------
 * DatabaseInventory plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of DatabaseInventory.
 *
 * DatabaseInventory is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * DatabaseInventory is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DatabaseInventory. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2021-2023 by Teclib'.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://services.glpi-network.com
 * -------------------------------------------------------------------------
 */

class PluginDatabaseinventoryTask extends CommonGLPI
{
    public static function inventoryGetParams(array $params)
    {
        /** @var DBmysql $DB */
        global $DB;
        $agent            = $params['item'];
        $content          = $params['options']['content'];
        $credential_found = [];

        $databaseparam_credential_table = PluginDatabaseinventoryDatabaseParam_Credential::getTable();
        $credential_table               = PluginDatabaseinventoryCredential::getTable();
        $credential_type_table          = PluginDatabaseinventoryCredentialType::getTable();

        // load all credential type
        $criteria = [
            'SELECT' => [
                $credential_table . '.id',
            ],
            'FROM' => $credential_table,
            'JOIN' => [
                $credential_type_table => [
                    'ON' => [
                        $credential_table      => 'plugin_databaseinventory_credentialtypes_id',
                        $credential_type_table => 'id',
                    ],
                ],
                $databaseparam_credential_table => [
                    'ON' => [
                        $databaseparam_credential_table => 'plugin_databaseinventory_credentials_id',
                        $credential_table               => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                $credential_type_table . '.id'          => PluginDatabaseinventoryCredentialType::getModuleKeyByName($content->use),
                $databaseparam_credential_table . '.plugin_databaseinventory_credentials_id' => $content->params_id
            ],
        ];

        // store credentials found
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $credential_found[] = $data['id'];
        }

        /*
         * Construct response
         * Array
         *  (
         *     [0] => Array
         *        (
         *              [id] => PluginDatabaseinventoryCredential id
         *              [type] => login_password
         *              [use] => mysql
         *              [login] => login
         *              [password] => password
         *              [socket] => socket
         *              [port] => credential
         *        )
         *  )
         */
        if (count($credential_found)) {
            foreach ($credential_found as $crendential_id) {
                $crendential = new PluginDatabaseinventoryCredential();
                $crendential->getFromDB($crendential_id);
                $data = [
                    'id'       => $crendential->fields['id'],
                    'type'     => $crendential->getCredentialMode(),
                    'use'      => $content->use,
                    'login'    => $crendential->fields['login'],
                    'password' => (new GLPIKey())->decrypt($crendential->fields['password']),
                ];

                if (!empty($crendential->fields['socket'])) {
                    $data['socket'] = $crendential->fields['socket'];
                }

                if ($crendential->fields['port'] != 0) {
                    $data['port'] = $crendential->fields['port'];
                }

                $params['options']['response']['credentials'][] = $data;

                // store requested credentials
                $contact_log = new PluginDatabaseinventoryContactLog();
                $log         = [
                    'agents_id'                                  => $agent->fields['id'],
                    'plugin_databaseinventory_credentials_id'    => $crendential_id,
                    'plugin_databaseinventory_databaseparams_id' => $content->params_id,
                    'date_creation '                             => $_SESSION['glpi_currenttime'],
                ];
                $contact_log->add($log);
            }
        }

        return $params;
    }

    public static function handleInventoryTask(array $params)
    {
        /** @var DBmysql $DB */
        global $DB;

        // get asset related to the agent
        $computer = $params['item']->getLinkedItem();

        $database_param_found = [];

        // only Computer type
        if (get_class($computer) == Computer::getType()) {
            $database_param_table               = PluginDatabaseinventoryDatabaseParam::getTable() ;
            $database_param_computergroup_table = PluginDatabaseinventoryDatabaseParam_ComputerGroup::getTable();
            $computer_group_static_table        = PluginDatabaseinventoryComputerGroupStatic::getTable();
            $computer_group_dynamic_table       = PluginDatabaseinventoryComputerGroupDynamic::getTable();
            $computer_group_table               = PluginDatabaseinventoryComputerGroup::getTable();

            /*
             * First step :
             * try to load all active 'PluginDatabaseinventoryDatabaseParam'
             * related to the computer (from 'PluginDatabaseinventoryComputerGroupStatic')
             */
            $criteria = [
                'SELECT' => [
                    $database_param_table . '.id',
                ],
                'FROM' => $database_param_table,
                'JOIN' => [
                    $database_param_computergroup_table => [
                        'ON' => [
                            $database_param_computergroup_table => 'plugin_databaseinventory_databaseparams_id',
                            $database_param_table               => 'id',
                        ],
                    ],
                    $computer_group_table => [
                        'ON' => [
                            $database_param_computergroup_table => 'plugin_databaseinventory_computergroups_id',
                            $computer_group_table               => 'id',
                        ],
                    ],
                    $computer_group_static_table => [
                        'ON' => [
                            $computer_group_static_table => 'plugin_databaseinventory_computergroups_id',
                            $computer_group_table        => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    $computer_group_static_table . '.computers_id' => $computer->fields['id'],
                    $database_param_table . '.is_active'           => 1,
                ],
            ];

            // store databaseparam found
            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $database_param_found[] = $data['id'];
            }

            /*
             * Second step :
             * Try to load all 'PluginDatabaseinventoryComputerGroupDynamic'
             * linked to an active 'PluginDatabaseinventoryDatabaseParam'
             * and check if the computer is part of it
             */
            $criteria = [
                'SELECT' => [
                    $computer_group_dynamic_table . '.id',
                    $database_param_table . '.id AS database_param_id',
                ],
                'FROM' => $computer_group_dynamic_table,
                'JOIN' => [
                    $computer_group_table => [
                        'ON' => [
                            $computer_group_dynamic_table => 'plugin_databaseinventory_computergroups_id',
                            $computer_group_table         => 'id',
                        ],
                    ],
                    $database_param_computergroup_table => [
                        'ON' => [
                            $database_param_computergroup_table => 'plugin_databaseinventory_computergroups_id',
                            $computer_group_table               => 'id',
                        ],
                    ],
                    $database_param_table => [
                        'ON' => [
                            $database_param_computergroup_table => 'plugin_databaseinventory_databaseparams_id',
                            $database_param_table               => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    $database_param_table . '.is_active' => 1,
                ],
            ];

            if (!empty($database_param_found)) {
                $criteria['WHERE'] = [
                    $database_param_table . '.is_active' => 1,
                    ['NOT'                               => [$database_param_table . '.id' => $database_param_found]], //no need to look for what is already found
                ];
            } else {
                $criteria['WHERE'] = [
                    $database_param_table . '.is_active' => 1,
                ];
            }

            // check if Dynamic group match computer
            // if true, store databaseparam
            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $dynamic_group = new PluginDatabaseinventoryComputerGroupDynamic();
                $dynamic_group->getFromDB($data['id']);
                if ($dynamic_group->isDynamicSearchMatchComputer($computer)) {
                    if (!in_array($data['database_param_id'], $database_param_found)) {
                        $database_param_found[] = $data['database_param_id'];
                    }
                }
            }
        }

        /*
         * Construct response
         * Array
         *  (
         *     [0] => Array
         *        (
         *              [category] => database
         *              [use] => Array //list of credential types
         *                 (
         *                    [0] => oracle
         *                 )
         *              [params_id] => 2 //PluginDatabaseinventoryDatabaseParam id
         *              [delay] => 2
         *        )
         *     [1] => Array
         *        (
         *              [category] => database
         *              [use] => Array
         *                 (
         *                    [0] => oracle
         *                    [1] => mysql
         *                 )
         *              [params_id] => 1
         *              [delay] => 1
         *        )
         *  )
         */
        if (count($database_param_found)) {
            foreach ($database_param_found as $database_params_id) {
                $database_params = new PluginDatabaseinventoryDatabaseParam();
                $database_params->getFromDB($database_params_id);

                $json              = [];
                $json['category']  = 'database';
                $json['use']       = $database_params->getCredentialTypeLinked();
                $json['params_id'] = $database_params_id;
                if ($database_params->fields['partial_inventory']) {
                    $json['delay'] = $database_params->fields['execution_delay'];
                }
                $params['options']['response']['inventory']['params'][] = $json;
            }
        }

        return $params;
    }
}
