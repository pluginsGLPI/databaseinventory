<?php

use Glpi\Application\View\TemplateRenderer;

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

class PluginDatabaseinventoryDatabaseParam extends CommonDBTM
{
    public $dohistory  = true;
    public static $rightname  = 'database_inventory';

    public static function getTypeName($nb = 0)
    {
        return _n('Database params', 'Databases params', $nb, 'databaseinventory');
    }

    public static function canCreate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false, // implicit field is id
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '3',
            'table'              => $this->getTable(),
            'field'              => 'is_active',
            'name'               => __('Active'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'partial_inventory',
            'name'               => __('Partial inventory', 'databaseinventory'),
            'datatype'           => 'bool'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'execution_delay',
            'name'               => __('Execution frequency for partial inventory', 'databaseinventory'),
            'datatype'           => 'number',
            'min'                => 0,
            'max'                => 24,
            'step'               => 1,
            'unit'               => 'hour',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => PluginDatabaseinventoryComputerGroup::getTable(),
            'field'              => 'name',
            'datatype'           => 'itemlink',
            'name'               => PluginDatabaseinventoryComputerGroup::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => PluginDatabaseinventoryDatabaseParam_ComputerGroup::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ]
                ]
            ]
        ];

        $tab[] = [
            'id'                 => '7',
            'table'              => PluginDatabaseinventoryCredential::getTable(),
            'field'              => 'name',
            'datatype'           => 'itemlink',
            'name'               => PluginDatabaseinventoryCredential::getTypeName(1),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => PluginDatabaseinventoryDatabaseParam_Credential::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ]
                ]
            ]
        ];

        return $tab;
    }

    public function getCredentialTypeLinked()
    {
        /** @var DBmysql $DB */
        global $DB;

        $databaseparam_credential_table = PluginDatabaseinventoryDatabaseParam_Credential::getTable();
        $credential_table = PluginDatabaseinventoryCredential::getTable();
        $credential_type_table = PluginDatabaseinventoryCredentialType::getTable();
        $types = [];

        // load all credential type
        $criteria = [
            'SELECT'       => [
                $credential_type_table . '.id',
                $credential_type_table . '.name',
            ],
            'FROM'         =>  $credential_type_table,
            'JOIN'   => [
                $credential_table => [
                    'ON' => [
                        $credential_table    => 'plugin_databaseinventory_credentialtypes_id',
                        $credential_type_table => 'id'
                    ]
                ],
                $databaseparam_credential_table => [
                    'ON' => [
                        $databaseparam_credential_table => 'plugin_databaseinventory_credentials_id',
                        $credential_table    => 'id'
                    ]
                ],
            ],
            'WHERE'        => [
                $databaseparam_credential_table . ".plugin_databaseinventory_databaseparams_id" => $this->fields['id'],
            ]
        ];

        // store types found
        $iterator = $DB->request($criteria);
        foreach ($iterator as $credential_type) {
            $types[] = PluginDatabaseinventoryCredentialType::getModuleKeyById($credential_type['id']);
        }

        return $types;
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab('PluginDatabaseinventoryDatabaseParam_ComputerGroup', $ong, $options)
            ->addStandardTab('PluginDatabaseinventoryDatabaseParam_Credential', $ong, $options)
            ->addStandardTab('PluginDatabaseinventoryContactLog', $ong, $options)
            ->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display(
            '@databaseinventory/databaseparam.html.twig',
            [
                'item' => $this
            ]
        );
        return true;
    }

    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                    `name` varchar(255) DEFAULT NULL,
                    `is_active` tinyint NOT NULL DEFAULT '0',
                    `partial_inventory` tinyint NOT NULL DEFAULT '0',
                    `execution_delay` int NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `is_active` (`is_active`),
                    KEY `partial_inventory` (`partial_inventory`),
                    KEY `date_creation` (`date_creation`),
                    KEY `date_mod` (`date_mod`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());

            // install default display preferences
            $migration->updateDisplayPrefs(
                [
                    PluginDatabaseinventoryDatabaseParam::class => [3, 4, 5, 6, 7]
                ]
            );
        }
    }

    public static function uninstall(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;
        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`") or die($DB->error());
        }
    }

    public static function getIcon()
    {
        return "fas fa-database";
    }
}
