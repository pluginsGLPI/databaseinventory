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

class PluginDatabaseinventoryDatabaseParam_ComputerGroup extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'PluginDatabaseinventoryDatabaseParam';
    public static $items_id_1 = 'plugin_databaseinventory_databaseparams_id';
    public static $itemtype_2 = 'PluginDatabaseinventoryComputerGroup';
    public static $items_id_2 = 'plugin_databaseinventory_computergroups_id';

    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;
    public $auto_message_on_action     = false;

    public static $rightname  = 'database_inventory';

    public static function canCreate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public function canCreateItem()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Computer Group', 'Computers Group', $nb, 'databaseinventory');
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
        switch ($tabnum) {
            case 1:
                self::showForItem($item);
                break;
        }
        return true;
    }

    private static function showForItem(PluginDatabaseinventoryDatabaseParam $databaseparams)
    {
        global $DB;

        $ID = $databaseparams->getField('id');
        if (!$databaseparams->can($ID, UPDATE)) {
            return false;
        }

        $datas = [];
        $used  = [];
        $params = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_databaseinventory_databaseparams_id' => $ID],
        ];

        $iterator = $DB->request($params);
        foreach ($iterator as $data) {
            $datas[]           = $data;
            $used[] = $data['plugin_databaseinventory_computergroups_id'];
        }
        $number = count($datas);

        $rand = mt_rand();

        echo "<div class='spaced'>";
        if ($databaseparams->canAddItem('itemtype')) {
            echo "<div class='firstbloc'>";
            echo "<form method='post' name='computergroup_form$rand' id='computergroup_form$rand'
                        action='" . Toolbox::getItemTypeFormURL("PluginDatabaseinventoryDatabaseParam") . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th colspan='2'>" . __('Add computer group', 'databaseinventory') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='left'>";
            Dropdown::show(
                "PluginDatabaseinventoryComputerGroup",
                [
                    "name" => "plugin_databaseinventory_computergroups_id",
                    "used" => $used,
                ]
            );
            echo "</td><td class='center' class='tab_bg_1'>";

            echo Html::hidden('plugin_databaseinventory_databaseparams_id', ['value' => $ID]);
            echo Html::submit(_x('button', 'Add'), ['name' => 'add_computergroup']);
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
        echo "</div>";

        $canread = $databaseparams->can($ID, READ);
        $canedit = $databaseparams->can($ID, UPDATE);
        echo "<div class='spaced'>";
        if ($canread) {
            echo "<div class='spaced'>";
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'num_displayed' => min($_SESSION['glpilist_limit'], $number),
                    'specific_actions' => ['purge' => _x('button', 'Remove')],
                    'container' => 'mass' . __CLASS__ . $rand,
                ];
                Html::showMassiveActions($massiveactionparams);
            }
            echo "<table class='tab_cadre_fixehov'>";
            $header_begin  = "<tr>";
            $header_top    = '';
            $header_bottom = '';
            $header_end    = '';

            if ($canedit) {
                $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_top    .= "</th>";
                $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header_bottom .=  "</th>";
            }

            $header_end .= "<th>" . __('Name') . "</th>";
            $header_end .= "<th>" . __('Comment') . "</th>";
            $header_end .= "<th>" . __('Number of dynamic items', 'databaseinventory') . "</th>";
            $header_end .= "<th>" . __('Number of static items', 'databaseinventory') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($datas as $data) {
                $computergroup = new PluginDatabaseinventoryComputerGroup();
                $computergroup->getFromDB($data["plugin_databaseinventory_computergroups_id"]);
                $linkname = $computergroup->fields["name"];
                $itemtype = PluginDatabaseinventoryComputerGroup::getType();
                if ($_SESSION["glpiis_ids_visible"] || empty($computergroup->fields["name"])) {
                    $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $computergroup->fields["id"]);
                }
                $link = $itemtype::getFormURLWithID($computergroup->fields["id"]);
                $name = "<a href=\"" . $link . "\">" . $linkname . "</a>";
                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td>" . $name . "</td>";
                echo "<td>" . $computergroup->fields["comment"] . "</td>";
                echo "<td>" . $computergroup->countDynamicItem() . "</td>";
                echo "<td>" . $computergroup->countStaticItem() . "</td>";
                echo "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;

            echo "</table>";
            if ($canedit && $number) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            }
            echo "</div>";
        }
        echo "</div>";
        return true;
    }


    public static function install(Migration $migration)
    {
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
                    `plugin_databaseinventory_databaseparams_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_databaseinventory_computergroups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                   PRIMARY KEY (`id`),
                   UNIQUE KEY `unicity` (`plugin_databaseinventory_computergroups_id`, `plugin_databaseinventory_databaseparams_id`),
                   KEY `plugin_databaseinventory_databaseparams_id` (`plugin_databaseinventory_databaseparams_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());
        } else {
            // Drop useless `type` field
            $migration->dropField($table, 'type');
        }
    }

    public static function uninstall(Migration $migration)
    {
        global $DB;
        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`") or die($DB->error());
        }
    }
}
