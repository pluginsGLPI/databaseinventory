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

use Glpi\Toolbox\Sanitizer;

class PluginDatabaseinventoryCredentialType extends CommonDropdown
{
    public static $rightname = "dropdown";

    private const MYSQL         = 1;
    private const ORACLE        = 2;
    private const DB2           = 3;
    private const MSSQL         = 4;
    private const POSTGRE_SQL   = 5;
    private const MONGO_DB      = 6;

    public static function canCreate()
    {
        return false;
    }

    public static function canUpdate()
    {
        return false;
    }

    public static function canPurge()
    {
        return false;
    }

    public static function canDelete()
    {
        return false;
    }

    public static function canView()
    {
        return false;
    }

    public function canViewItem()
    {
        return false;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Credential type', 'Credential types', $nb, 'databaseinventory');
    }

    public function pre_deleteItem()
    {
        Session::addMessageAfterRedirect(
            __("You cannot remove this type", "databaseinventory") . ": "
                                       . $this->fields['name'],
            false,
            ERROR
        );
        return false;
    }

    public static function getModuleKeyById($credential_type_id)
    {
        switch ($credential_type_id) {
            case self::MYSQL:
                return 'mysql';
            case self::ORACLE:
                return 'oracle';
            case self::DB2:
                return 'db2';
            case self::MSSQL:
                return 'mssql';
            case self::POSTGRE_SQL:
                return 'postgresql';
            case self::MONGO_DB:
                return 'mongodb';
        }
    }

    public static function getModuleKeyByName($credential_type)
    {
        switch ($credential_type) {
            case 'mysql':
                return self::MYSQL;
            case 'oracle':
                return self::ORACLE;
            case 'db2':
                return self::DB2;
            case 'mssql':
                return self::MSSQL;
            case 'postgresql':
                return self::POSTGRE_SQL;
            case 'mongodb':
                return self::MONGO_DB;
        }
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        if ($DB->tableExists('glpi_plugin_databaseinventory_credentials_types')) {
            // PluginDatabaseinventoryCredentialType was named PluginDatabaseinventoryCredential_Type prior to v1.0.0
            $migration->renameTable('glpi_plugin_databaseinventory_credentials_types', $table);
        } elseif (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL auto_increment,
                    `name` varchar(255) default NULL,
                    `comment` text,
                    PRIMARY KEY  (`id`),
                    KEY `name` (`name`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());
        }

        $state = new self();
        foreach (
            [
                1 => __("MySQL", "databaseinventory"),
                2 => __("Oracle", "databaseinventory"),
                3 => __("DB2", "databaseinventory"),
                4 => __("Microsoft SQL", "databaseinventory"),
                5 => __("PostgreSQL", "databaseinventory"),
                6 => __("MongoDB", "databaseinventory")
            ] as $id => $label
        ) {
            if (!countElementsInTable($table, ['id' => $id])) {
                $state->add([
                    'id'   => $id,
                    'name' => Sanitizer::sanitize($label)
                ]);
            }
        }
    }

    public static function uninstall()
    {
        global $DB;
        $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`") or die($DB->error());
    }
}
