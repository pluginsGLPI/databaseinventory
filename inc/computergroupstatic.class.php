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

class PluginDatabaseinventoryComputerGroupStatic extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'PluginDatabaseinventoryComputerGroup';

    public static $items_id_1 = 'plugin_databaseinventory_computergroups_id';

    public static $itemtype_2 = 'Computer';

    public static $items_id_2 = 'computers_id';

    public static $checkItem_2_Rights = self::DONT_CHECK_ITEM_RIGHTS;

    public static $logs_for_item_2    = false;

    public $auto_message_on_action    = false;

    public static $rightname = 'database_inventory';

    public static function getTypeName($nb = 0)
    {
        return _sn('Static group', 'Static groups', $nb, 'databaseinventory');
    }

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

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof PluginDatabaseinventoryComputerGroup) {
            $count = countElementsInTable(self::getTable(), ['plugin_databaseinventory_computergroups_id' => $item->getID()]);

            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof PluginDatabaseinventoryComputerGroup && $tabnum === 1) {
            self::showForItem($item);
        }

        return true;
    }

    private static function showForItem(PluginDatabaseinventoryComputerGroup $computergroup)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID = $computergroup->getField('id');
        if (!$computergroup->can($ID, UPDATE)) {
            return false;
        }

        $static_table   = self::getTable();
        $computer_table = Computer::getTable();
        $entity_table   = Entity::getTable();
        $where          = [$static_table . '.plugin_databaseinventory_computergroups_id' => $ID];

        // every member is excluded from the "add" dropdown, displayed or not
        $used = [];
        foreach ($DB->request(['SELECT' => 'computers_id', 'FROM' => $static_table, 'WHERE' => $where]) as $row) {
            $used[] = $row['computers_id'];
        }

        // one page of members, with their computer and entity, in one query
        $criteria = [
            'FROM'       => $static_table,
            'INNER JOIN' => [
                $computer_table => [
                    'ON' => [
                        $static_table   => 'computers_id',
                        $computer_table => 'id',
                    ],
                ],
            ],
            'LEFT JOIN'  => [
                $entity_table => [
                    'ON' => [
                        $computer_table => 'entities_id',
                        $entity_table   => 'id',
                    ],
                ],
            ],
            'WHERE'      => $where,
        ];
        $total = $DB->request($criteria + ['COUNT' => 'total'])->current()['total'];

        $start = max(0, (int) ($_GET['start'] ?? 0));
        if ($start >= $total) {
            $start = 0;
        }

        $iterator = $DB->request($criteria + [
            'SELECT' => [
                $computer_table . '.id',
                $computer_table . '.name',
                $computer_table . '.is_dynamic',
                $computer_table . '.serial',
                $computer_table . '.otherserial',
                $entity_table . '.completename AS entityname',
                $static_table . '.id AS idcompgroupstatic',
            ],
            'ORDER'  => $static_table . '.id',
            'START'  => $start,
            'LIMIT'  => (int) $_SESSION['glpilist_limit'],
        ]);

        $listofcomputers = [];
        foreach ($iterator as $row) {
            $listofcomputers[] = $row + ['link' => Computer::getFormURLWithID($row['id'])];
        }

        TemplateRenderer::getInstance()->display(
            '@databaseinventory/computergroupstatic.html.twig',
            [
                'item'              => PluginDatabaseinventoryComputerGroup::getById($ID),
                'pager'             => $total > 0 ? Html::printAjaxPager('', $start, $total, '', false) : '',
                'computerslist'     => $listofcomputers,
                'groupstaticclass'  => PluginDatabaseinventoryComputerGroupStatic::class,
                'canread'           => $computergroup->can($ID, READ),
                'canedit'           => $computergroup->can($ID, UPDATE),
                'canadd'            => $computergroup->canAddItem('itemtype'),
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
                    `plugin_databaseinventory_computergroups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `computers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `computers_id` (`computers_id`),
                    KEY `plugin_databaseinventory_computergroups_id` (`plugin_databaseinventory_computergroups_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
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
