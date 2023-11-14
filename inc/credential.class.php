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

class PluginDatabaseinventoryCredential extends CommonDBTM
{
    public $dohistory  = true;
    public static $rightname  = 'database_inventory';

    public static function canCreate()
    {
        return Session::haveRight(static::$rightname, CREATE);
    }

    public static function canUpdate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, PURGE);
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Credential', 'Credentials', $nb, 'databaseinventory');
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
            'field'              => 'login',
            'name'               => __('Login'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => $this->getTable(),
            'field'              => 'port',
            'name'               => __('Port'),
            'datatype'           => 'number'
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'socket',
            'name'               => __('Socket'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => PluginDatabaseinventoryCredentialType::getTable(),
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        return $tab;
    }

    public function showForm($ID, array $options = [])
    {
        $rand = mt_rand();
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        $rand = mt_rand();
        echo "<tr><td><label for='textfield_name$rand'>" . __('Name') . "</label></td>";
        echo "<td>";
        echo Html::input(
            'name',
            [
                'value' => $this->fields["name"],
                'id'    => "textfield_name$rand",
            ]
        );
        echo "<td><label for='is_active$rand'>" . __('Port') . "</label></td>";
        echo "<td>";
        Dropdown::showNumber(
            'port',
            [
                'value' => empty($this->fields['port']) ? 0 : $this->fields['port'],
                'min'   => 0,
                'max'   => 99999,
                'step'  => 1,
                'rand'  => $rand
            ]
        );
        echo "</td></tr>";

        echo "<tr><td><label for='textfield_login$rand'>" . __('Login') . "</label></td>";
        echo "<td>";
        echo Html::input(
            'login',
            [
                'value' => $this->fields["login"],
                'id'    => "textfield_login$rand",
            ]
        );
        echo "<td><label for='textfield_password$rand'>" . __('Password') . "</label></td>";
        echo "<td>";

        echo "<input type='password' id='password' name='password' value='' autocomplete='password'>";
        if ($ID > 0) {
            echo "&nbsp;<input type='checkbox' name='_blank_passwd'>&nbsp;" . __('Clear');
        }
        echo "</td></tr>";

        echo "<tr><td><label for='textfield_socket$rand'>" . __('Socket') . "</label></td>";
        echo "<td>";
        echo Html::input(
            'socket',
            [
                'value' => $this->fields["socket"],
                'id'    => "textfield_login$rand",
            ]
        );

        echo "<td><label for='select_type$rand'>" . PluginDatabaseinventoryCredentialType::getFieldLabel() . "</label></td>";
        echo "<td>";
        PluginDatabaseinventoryCredentialType::dropdown(['value' => $this->fields["plugin_databaseinventory_credentialtypes_id"], 'rand' => $rand, 'comments' => false]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    public function getCredentialMode()
    {
        return "login_password";
    }

    public function prepareInput(array $input, $mode = 'add'): array
    {
        if (isset($input["password"])) {
            if (empty($input["password"])) {
                unset($input["password"]);
            } else {
                $input["password"] = (new GLPIKey())->encrypt($input["password"]);
            }
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInput($input, 'add');
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInput($input, 'update');

        if (isset($input["_blank_passwd"]) && $input["_blank_passwd"]) {
            $input['password'] = '';
        }

        return $input;
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
                    `login` varchar(255) DEFAULT NULL,
                    `password` varchar(255) DEFAULT NULL,
                    `socket` varchar(255) DEFAULT NULL,
                    `comment` text,
                    `port` int NOT NULL default 0,
                    `plugin_databaseinventory_credentialtypes_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `login` (`login`),
                    KEY `password` (`password`),
                    KEY `socket` (`socket`),
                    KEY `port` (`port`),
                    KEY `plugin_databaseinventory_credentialtypes_id` (`plugin_databaseinventory_credentialtypes_id`),
                    KEY `date_creation` (`date_creation`),
                    KEY `date_mod` (`date_mod`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());

            // install default display preferences
            $migration->updateDisplayPrefs(
                [
                    PluginDatabaseinventoryCredential::class => [3, 4, 5, 6]
                ]
            );
        } else {
            // Fix `comment` field type (was a varchar prior to v1.0.0)
            $migration->changeField($table, 'comment', 'comment', 'text');

            // PluginDatabaseinventoryCredentialType was named PluginDatabaseinventoryCredential_Type prior to v1.0.0
            $migration->dropKey($table, 'plugin_databaseinventory_credentials_types_id');
            $migration->changeField($table, 'plugin_databaseinventory_credentials_types_id', 'plugin_databaseinventory_credentialtypes_id', 'fkey');
            $migration->addKey($table, 'plugin_databaseinventory_credentialtypes_id');
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
        return "fas fa-lock";
    }

    public function post_purgeItem()
    {
        $c_dynamic = new PluginDatabaseinventoryDatabaseParam_Credential();
        $c_dynamic->deleteByCriteria(['plugin_databaseinventory_credentials_id' => $this->fields['id']]);
    }
}
