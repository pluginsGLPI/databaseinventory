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

class PluginDatabaseinventoryComputerGroupStatic extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'PluginDatabaseinventoryComputerGroup';
    public static $items_id_1 = 'plugin_databaseinventory_computergroups_id';
    public static $itemtype_2 = 'Computer';
    public static $items_id_2 = 'computers_id';

    public static $checkItem_2_Rights  = self::DONT_CHECK_ITEM_RIGHTS;
    public static $logs_for_item_2     = false;
    public $auto_message_on_action     = false;

    public static $rightname  = 'database_inventory';

    public static function getTypeName($nb = 0)
    {
        return _n('Static group', 'Static groups', $nb, 'databaseinventory');
    }

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

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (get_class($item) == PluginDatabaseinventoryComputerGroup::getType()) {
            $count = 0;
            $count = countElementsInTable(self::getTable(), ['plugin_databaseinventory_computergroups_id' => $item->getID()]);
            $ong = [];
            $ong[1] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
            return $ong;
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

    private static function showForItem(PluginDatabaseinventoryComputerGroup $computergroup)
    {
        global $DB;

        $ID = $computergroup->getField('id');
        if (!$computergroup->can($ID, UPDATE)) {
            return false;
        }

        TemplateRenderer::getInstance()->display(
            '@databaseinventory/computergroupstatic.html.twig',
            [
            ]
        );
        return true;

        $datas = [];
        $used  = [];
        $params = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_databaseinventory_computergroups_id' => $ID],
        ];

        $iterator = $DB->request($params);
        foreach ($iterator as $data) {
            $datas[] = $data;
            $used [] = $data['computers_id'];
        }
        $number = count($datas);

        echo "<div class='spaced'>";
        if ($computergroup->canAddItem('itemtype')) {
            $rand = mt_rand();
            echo "<div class='firstbloc'>";
            echo "<form method='post' name='staticcomputer_form$rand'
                        id='staticcomputer$rand'
                        action='" . Toolbox::getItemTypeFormURL("PluginDatabaseinventoryComputerGroup") . "'>";

            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_2'>";
            echo "<th colspan='2'>" . __('Add an item') . "</th></tr>";

            echo "<tr class='tab_bg_1'><td class='left'>";
            Dropdown::show(
                "Computer",
                [
                    "name" => "computers_id",
                    "used" => $used,
                    "condition" => ["is_dynamic" => true]
                ]
            );
            echo "</td><td class='center' class='tab_bg_1'>";

            echo Html::hidden('plugin_databaseinventory_computergroups_id', ['value' => $ID]);
            echo Html::submit(_x('button', 'Add'), ['name' => 'add_staticcomputer']);
            echo "</td></tr>";
            echo "</table>";
            Html::closeForm();
            echo "</div>";
        }
        echo "</div>";

        $canread = $computergroup->can($ID, READ);
        $canedit = $computergroup->can($ID, UPDATE);
        echo "<div class='spaced'>";
        if ($canread) {
            echo "<div class='spaced'>";
            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = ['num_displayed'
                           => min($_SESSION['glpilist_limit'], $number),
                    'specific_actions'
                           => ['purge' => _x('button', 'Remove')],
                    'container'
                           => 'mass' . __CLASS__ . $rand
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
            $header_end .= "<th>" . __('Automatic inventory') . "</th>";
            $header_end .= "<th>" . Entity::getTypeName(1) . "</th>";
            $header_end .= "<th>" . __('Serial number') . "</th>";
            $header_end .= "<th>" . __('Inventory number') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($datas as $data) {
                $computer = new Computer();
                $computer->getFromDB($data["computers_id"]);
                $linkname = $computer->fields["name"];
                $itemtype = Computer::getType();
                if ($_SESSION["glpiis_ids_visible"] || empty($computer->fields["name"])) {
                    $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $computer->fields["id"]);
                }
                $link = $itemtype::getFormURLWithID($computer->fields["id"]);
                $name = "<a href=\"" . $link . "\">" . $linkname . "</a>";
                echo "<tr class='tab_bg_1'>";

                if ($canedit) {
                    echo "<td width='10'>";
                    Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                    echo "</td>";
                }
                echo "<td "
                    . ((isset($computer->fields['is_deleted']) && $computer->fields['is_deleted']) ? "class='tab_bg_2_2'" : "")
                    . ">" . $name . "</td>";
                echo "<td>" . Dropdown::getYesNo($computer->fields['is_dynamic']) . "</td>";
                echo "<td>" . Dropdown::getDropdownName(
                    "glpi_entities",
                    $computer->fields['entities_id']
                );
                echo "</td>";
                echo "<td>"
                    . (isset($computer->fields["serial"]) ? "" . $computer->fields["serial"] . "" : "-")
                    . "</td>";
                echo "<td>"
                    . (isset($computer->fields["otherserial"]) ? "" . $computer->fields["otherserial"] . "" : "-")
                    . "</td>";
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
                    `plugin_databaseinventory_computergroups_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `computers_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `computers_id` (`computers_id`),
                    KEY `plugin_databaseinventory_computergroups_id` (`plugin_databaseinventory_computergroups_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->query($query) or die($DB->error());
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
