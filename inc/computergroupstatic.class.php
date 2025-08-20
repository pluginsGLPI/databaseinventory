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
        return _n('Static group', 'Static groups', $nb, 'databaseinventory');
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
        if ($item instanceof PluginDatabaseinventoryComputerGroup) {
            switch ($tabnum) {
                case 1:
                    self::showForItem($item);
                    break;
            }
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

        $staticsgroups   = new PluginDatabaseinventoryComputerGroupStatic();
        $staticgrouplist = $staticsgroups->find(
            [
                'plugin_databaseinventory_computergroups_id' => $ID,
            ],
        );

        $computers       = new Computer();
        $listofcomputers = [];
        $used            = [];
        foreach ($staticgrouplist as $staticgroup) {
            $used[] = $staticgroup['computers_id'];
            if ($computers->getFromDB($staticgroup['computers_id'])) {
                $listofcomputers[] = $computers->fields + [
                    'entityname'        => Entity::getById($computers->fields['entities_id'])->fields['completename'],
                    'link'              => $computers->getLinkURL(),
                    'idcompgroupstatic' => $staticgroup['id'],
                ];
            }
        }
        TemplateRenderer::getInstance()->display(
            '@databaseinventory/computergroupstatic.html.twig',
            [
                'item'             => PluginDatabaseinventoryDatabaseParam::getById($ID),
                'computerslist'    => $listofcomputers,
                'groupstaticclass' => PluginDatabaseinventoryComputerGroupStatic::class,
                'canread'          => $computergroup->can($ID, READ),
                'canedit'          => $computergroup->can($ID, UPDATE),
                'canadd'           => $computergroup->canAddItem('itemtype'),
                'used'             => $used,
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
            $migration->displayMessage("Installing $table");
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
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
