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

class PluginDatabaseinventoryContactLog extends CommonDBTM
{
    public $dohistory  = true;
    public static $rightname  = 'database_inventory';

    public static function canCreate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canUpdate()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge()
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Contact log', 'Contact logs', $nb, 'databaseinventory');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case PluginDatabaseinventoryDatabaseParam::getType():
                $count = 0;
                $count = countElementsInTable(self::getTable(), ['plugin_databaseinventory_databaseparams_id' => $item->getID()]);
                $ong = [];
                $ong[1] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
                return $ong;
            break;
            case Agent::getType():
                $count = 0;
                $count = countElementsInTable(self::getTable(), ['agents_id' => $item->getID()]);
                $ong = [];
                $ong[2] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
                return $ong;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        switch ($tabnum) {
            case 1:
                self::showForDatabaseParams($item);
                break;
            case 2:
                self::showForAgent($item);
                break;
        }
        return true;
    }

    private static function showForDatabaseParams(PluginDatabaseinventoryDatabaseParam $databaseparams)
    {
        global $DB;

        $ID = $databaseparams->getField('id');
        if (!$databaseparams->can($ID, UPDATE)) {
            return false;
        }

        $datas = [];
        $params = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => ['plugin_databaseinventory_databaseparams_id' => $ID],
        ];

        $iterator = $DB->request($params);
        foreach ($iterator as $data) {
            $datas[]           = $data;
        }
        $rand = mt_rand();

        $canread = $databaseparams->can($ID, READ);
        $canedit = false;
        echo "<div class='spaced'>";
        if ($canread) {
            echo "<div class='spaced'>";
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

            $header_end .= "<th>" . PluginDatabaseinventoryCredential::getTypeName(0) . "</th>";
            $header_end .= "<th>" . Agent::getTypeName(0) . "</th>";
            $header_end .= "<th>" . __('Date') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($datas as $data) {
                echo "<tr class='tab_bg_1'>";

                $credential = new PluginDatabaseinventoryCredential();
                $credential->getFromDB($data["plugin_databaseinventory_credentials_id"]);
                $credential_link = PluginDatabaseinventoryCredential::getFormURLWithID($credential->fields["id"]);
                $credential_linkname = $credential->fields["name"];
                $name = "<a href=\"" . $credential_link . "\">" . $credential_linkname . "</a>";

                echo "<td>" . $name . "</td>";

                $agent = new Agent();
                $agent->getFromDB($data["agents_id"]);
                $agent_link = Agent::getFormURLWithID($data["agents_id"]);
                $agent_linkname = $agent->fields["name"];
                $name = "<a href=\"" . $agent_link . "\">" . $agent_linkname . "</a>";
                echo "<td>" . $name . "</td>";

                echo "<td>" . $data["date_creation"] . "</td>";
                echo "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;

            echo "</table>";
            echo "</div>";
        }
        echo "</div>";
        return true;
    }

    private static function showForAgent(Agent $agent)
    {
        global $DB;

        $ID = $agent->getField('id');
        if (!$agent->can($ID, UPDATE)) {
            return false;
        }

        $datas = [];
        $params = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => ['agents_id' => $ID],
        ];

        $iterator = $DB->request($params);
        foreach ($iterator as $data) {
            $datas[]           = $data;
        }
        $rand = mt_rand();

        $canread = $agent->can($ID, READ);
        $canedit = false;
        echo "<div class='spaced'>";
        if ($canread) {
            echo "<div class='spaced'>";
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

            $header_end .= "<th>" . PluginDatabaseinventoryCredential::getTypeName(0) . "</th>";
            $header_end .= "<th>" . PluginDatabaseinventoryDatabaseParam::getTypeName(0) . "</th>";
            $header_end .= "<th>" . __('Date') . "</th>";
            $header_end .= "</tr>";
            echo $header_begin . $header_top . $header_end;

            foreach ($datas as $data) {
                echo "<tr class='tab_bg_1'>";

                $credential = new PluginDatabaseinventoryCredential();
                $credential->getFromDB($data["plugin_databaseinventory_credentials_id"]);
                $credential_link = PluginDatabaseinventoryCredential::getFormURLWithID($credential->fields["id"]);
                $credential_linkname = $credential->fields["name"];
                $name = "<a href=\"" . $credential_link . "\">" . $credential_linkname . "</a>";
                echo "<td>" . $name . "</td>";

                $databaseparams = new PluginDatabaseinventoryDatabaseParam();
                $databaseparams->getFromDB($data["plugin_databaseinventory_databaseparams_id"]);
                $databaseparams_link = PluginDatabaseinventoryDatabaseParam::getFormURLWithID($data["plugin_databaseinventory_databaseparams_id"]);
                $databaseparams_linkname = $databaseparams->fields["name"];
                $name = "<a href=\"" . $databaseparams_link . "\">" . $databaseparams_linkname . "</a>";
                echo "<td>" . $name . "</td>";

                echo "<td>" . $data["date_creation"] . "</td>";
                echo "</td>";
                echo "</tr>";
            }
            echo $header_begin . $header_bottom . $header_end;
            echo "</table>";
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
                    `agents_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_databaseinventory_credentials_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_databaseinventory_databaseparams_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `agents_id` (`agents_id`),
                    KEY `plugin_databaseinventory_credentials_id` (`plugin_databaseinventory_credentials_id`),
                    KEY `plugin_databaseinventory_databaseparams_id` (`plugin_databaseinventory_databaseparams_id`),
                    KEY `date_creation` (`date_creation`)
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
