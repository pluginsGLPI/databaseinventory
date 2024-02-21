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

class PluginDatabaseinventoryComputerGroup extends CommonDBTM
{
    public $dohistory  = true;
    public static $rightname  = 'database_inventory';

    public static function getTypeName($nb = 0)
    {
        return _n('Computer Group', 'Computers Group', $nb, 'databaseinventory');
    }

    public static function canCreate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab('PluginDatabaseinventoryComputerGroupDynamic', $ong, $options)
            ->addStandardTab('PluginDatabaseinventoryComputerGroupStatic', $ong, $options)
            ->addStandardTab('Log', $ong, $options);
        return $ong;
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
            'field'              => 'comment',
            'name'               => __('Comment'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'               => '5',
            'table'            => PluginDatabaseinventoryComputerGroupDynamic::getTable(),
            'field'            => 'search',
            'name'             => __('Number of dynamic items', 'databaseinventory'),
            'nosearch'         => true,
            'massiveaction'    => false,
            'forcegroupby'     => true,
            'additionalfields' => ['id'],
            'joinparams'       => ['jointype' => 'child'],
            'datatype'         => 'specific',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => PluginDatabaseinventoryComputerGroupStatic::getTable(),
            'field'              => 'id',
            'name'               => __('Number of static items', 'databaseinventory'),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'nosearch'           => true,
            'datatype'           => 'count',
            'massiveaction'      => false,
            'joinparams'       => ['jointype' => 'child'],
        ];

        $tab[] = [
            'id'               => '7',
            'table'            => PluginDatabaseinventoryComputerGroupDynamic::getTable(),
            'field'            => '_virtual_dynamic_list',
            'name'             => __('List of dynamic items', 'databaseinventory'),
            'massiveaction'    => false,
            'forcegroupby'     => true,
            'nosearch'         => true,
            'additionalfields' => ['id', 'search'],
            'searchtype'       => ['equals', 'notequals'],
            'joinparams'       => ['jointype' => 'child'],
            'datatype'         => 'specific',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => Computer::getTable(),
            'field'              => 'name',
            'datatype'           => 'itemlink',
            'name'               => __('List of static items', 'databaseinventory'),
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => PluginDatabaseinventoryComputerGroupStatic::getTable(),
                    'joinparams'         => [
                        'jointype'           => 'child',
                    ]
                ]
            ]
        ];

        return $tab;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display(
            '@databaseinventory/computergroup.html.twig',
            [
                'item' => $this
            ]
        );
        return true;
    }

    public function countDynamicItem()
    {
        /** @var DBmysql $DB */
        global $DB;
        $count = 0;

        $params = [
            'SELECT' => '*',
            'FROM'   => PluginDatabaseinventoryComputerGroupDynamic::getTable(),
            'WHERE'  => ['plugin_databaseinventory_computergroups_id' => $this->fields['id']],
        ];

        $iterator = $DB->request($params);
        foreach ($iterator as $computergroup_dynamic) {
            $search_params = Search::manageParams('Computer', unserialize($computergroup_dynamic['search']));
            $data = Search::prepareDatasForSearch('Computer', $search_params);
            Search::constructSQL($data);
            Search::constructData($data);
            $count += $data['data']['totalcount'];
        }

        return $count;
    }

    public function countStaticItem()
    {
        /** @var DBmysql $DB */
        global $DB;

        $params = [
            'SELECT' => '*',
            'FROM'   => PluginDatabaseinventoryComputerGroupStatic::getTable(),
            'WHERE'  => ['plugin_databaseinventory_computergroups_id' => $this->fields['id']],
        ];

        $iterator = $DB->request($params);
        $count = count($iterator);

        return $count;
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
                    `comment` text,
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `date_creation` (`date_creation`),
                    KEY `date_mod` (`date_mod`)
              ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());

            // install default display preferences
            $migration->updateDisplayPrefs(
                [
                    PluginDatabaseinventoryComputerGroup::class => [3, 5, 6]
                ]
            );
        } else {
            // Fix `comment` field type (was a varchar prior to v1.0.0)
            $migration->dropKey($table, 'comment');
            $migration->changeField($table, 'comment', 'comment', 'text');
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
        return "ti ti-sitemap";
    }

    public function post_purgeItem()
    {
        $c_dynamic = new PluginDatabaseinventoryComputerGroupDynamic();
        $c_dynamic->deleteByCriteria(['plugin_databaseinventory_computergroups_id' => $this->fields['id']]);

        $c_static = new PluginDatabaseinventoryComputerGroupStatic();
        $c_static->deleteByCriteria(['plugin_databaseinventory_computergroups_id' => $this->fields['id']]);

        $databaseparam_credential = new PluginDatabaseinventoryDatabaseParam_ComputerGroup();
        $databaseparam_credential->deleteByCriteria(['plugin_databaseinventory_computergroups_id' => $this->fields['id']]);
    }
}
