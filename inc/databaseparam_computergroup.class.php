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

class PluginDatabaseinventoryDatabaseParam_ComputerGroup extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'PluginDatabaseinventoryDatabaseParam';

    public static $items_id_1 = 'plugin_databaseinventory_databaseparams_id';

    public static $itemtype_2 = 'PluginDatabaseinventoryComputerGroup';

    public static $items_id_2 = 'plugin_databaseinventory_computergroups_id';

    public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;

    public static $logs_for_item_2    = false;

    public $auto_message_on_action    = false;

    public static $rightname = 'database_inventory';

    public static function canCreate(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public function canCreateItem(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function getTypeName($nb = 0)
    {
        return _sn('Computer Group', 'Computers Group', $nb, 'databaseinventory');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof PluginDatabaseinventoryDatabaseParam) {
            $count = countElementsInTable(PluginDatabaseinventoryDatabaseParam_ComputerGroup::getTable(), ['plugin_databaseinventory_databaseparams_id' => $item->getID()]);

            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof PluginDatabaseinventoryDatabaseParam && $tabnum === 1) {
            self::showForItem($item);
        }

        return true;
    }

    private static function showForItem(PluginDatabaseinventoryDatabaseParam $databaseparams)
    {
        $ID = $databaseparams->getField('id');
        if (!$databaseparams->can($ID, UPDATE)) {
            return false;
        }

        /** @var DBmysql $DB */
        global $DB;

        $relation_table = self::getTable();
        $group_table    = PluginDatabaseinventoryComputerGroup::getTable();
        $group_fk       = 'plugin_databaseinventory_computergroups_id';

        // linked groups, the ids of relations whose group no longer exists are still "used"
        $used   = [];
        $groups = [];
        $iterator = $DB->request([
            'SELECT'    => [
                $relation_table . '.id AS iddbparamcgroup',
                $relation_table . '.' . $group_fk,
                $group_table . '.*',
            ],
            'FROM'      => $relation_table,
            'LEFT JOIN' => [
                $group_table => [
                    'ON' => [
                        $relation_table => $group_fk,
                        $group_table    => 'id',
                    ],
                ],
            ],
            'WHERE'     => [$relation_table . '.plugin_databaseinventory_databaseparams_id' => $ID],
            'ORDER'     => $relation_table . '.id',
        ]);
        foreach ($iterator as $row) {
            $used[] = $row[$group_fk];
            if ($row['id'] !== null) {
                $groups[] = $row;
            }
        }

        // static and dynamic members of all the groups in two queries
        $group_ids     = array_column($groups, 'id');
        $static_counts = [];
        $searches      = [];
        if ($group_ids !== []) {
            $count_iterator = $DB->request([
                'SELECT' => [$group_fk, 'COUNT' => '* AS cpt'],
                'FROM'   => PluginDatabaseinventoryComputerGroupStatic::getTable(),
                'WHERE'  => [$group_fk => $group_ids],
                'GROUPBY' => $group_fk,
            ]);
            foreach ($count_iterator as $row) {
                $static_counts[$row[$group_fk]] = (int) $row['cpt'];
            }

            $dynamic_iterator = $DB->request([
                'SELECT' => [$group_fk, 'search'],
                'FROM'   => PluginDatabaseinventoryComputerGroupDynamic::getTable(),
                'WHERE'  => [$group_fk => $group_ids],
            ]);
            foreach ($dynamic_iterator as $row) {
                $searches[$row[$group_fk]][] = (string) $row['search'];
            }
        }

        $listofcgroups = [];
        foreach ($groups as $group) {
            $nbdynamicitems = 0;
            foreach ($searches[$group['id']] ?? [] as $search) {
                $nbdynamicitems += PluginDatabaseinventoryComputerGroup::countComputersForSearch($search);
            }

            $iddbparamcgroup = $group['iddbparamcgroup'];
            unset($group['iddbparamcgroup'], $group[$group_fk]);
            $listofcgroups[] = $group + [
                'link'            => PluginDatabaseinventoryComputerGroup::getFormURLWithID($group['id']),
                'nbdynamicitems'  => $nbdynamicitems,
                'nbstaticitems'   => $static_counts[$group['id']] ?? 0,
                'iddbparamcgroup' => $iddbparamcgroup,
            ];
        }

        TemplateRenderer::getInstance()->display(
            '@databaseinventory/databaseparam_computergroup.html.twig',
            [
                'item'              => PluginDatabaseinventoryDatabaseParam::getById($ID),
                'compgrouplist'     => $listofcgroups,
                'compgroupclass'    => PluginDatabaseinventoryComputerGroup::class,
                'dbparamgroupclass' => PluginDatabaseinventoryDatabaseParam_ComputerGroup::class,
                'canread'           => $databaseparams->can($ID, READ),
                'canedit'           => $databaseparams->can($ID, UPDATE),
                'canadd'            => $databaseparams->canAddItem('itemtype'),
                'used'              => $used,
            ],
        );

        return true;
    }

    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage('Installing ' . $table);
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `{$table}` (
                    `id` int {$default_key_sign} NOT NULL AUTO_INCREMENT,
                    `plugin_databaseinventory_databaseparams_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_databaseinventory_computergroups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                   PRIMARY KEY (`id`),
                   UNIQUE KEY `unicity` (`plugin_databaseinventory_computergroups_id`, `plugin_databaseinventory_databaseparams_id`),
                   KEY `plugin_databaseinventory_databaseparams_id` (`plugin_databaseinventory_databaseparams_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
        } else {
            // Drop useless `type` field
            $migration->dropField($table, 'type');
        }
    }

    public static function uninstall(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;
        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $DB->doQuery('DROP TABLE IF EXISTS `' . self::getTable() . '`');
        }
    }
}
