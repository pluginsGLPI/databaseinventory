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
        switch (get_class($item)) {
            case PluginDatabaseinventoryDatabaseParam::class:
                $count = countElementsInTable(self::getTable(), ['plugin_databaseinventory_databaseparams_id' => $item->getID()]);
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
            case Agent::class:
                $count = countElementsInTable(self::getTable(), ['agents_id' => $item->getID()]);
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
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
        $ID = $databaseparams->getField('id');
        if (!$databaseparams->can($ID, UPDATE)) {
            return false;
        }

        $contactlog = new PluginDatabaseinventoryContactLog();
        $contactloglist = $contactlog->find(
            [
                'plugin_databaseinventory_databaseparams_id' => $ID
            ]
        );

        $credential = new PluginDatabaseinventoryCredential();
        $agent = new Agent();
        $listofctlog = [];
        foreach ($contactloglist as $dbpctlog) {
            if ($credential->getFromDB($dbpctlog['plugin_databaseinventory_credentials_id'])) {
                $linkcred = $credential->getLinkURL();
                $credname = $credential->fields['name'];
            }
            if ($agent->getFromDB($dbpctlog['agents_id'])) {
                $linkagent = $agent->getLinkURL();
                $agentname = $agent->fields['name'];
            }
            if (isset($linkcred) || isset($linkagent)) {
                $listofctlog[] = $dbpctlog + [
                    'linkcred' => $linkcred ?? '',
                    'linkagent' => $linkagent ?? '',
                    'credname' => $credname ?? '',
                    'agentname' => $agentname ?? '',
                ];
            }
        }
        TemplateRenderer::getInstance()->display(
            '@databaseinventory/contactlog.html.twig',
            [
                'itemtype' => PluginDatabaseinventoryDatabaseParam::getType(),
                'contactlogs' => $listofctlog,
                'canread' => $databaseparams->can($ID, READ)
            ]
        );
        return true;
    }

    private static function showForAgent(Agent $agent)
    {
        $ID = $agent->getField('id');
        if (!$agent->can($ID, UPDATE)) {
            return false;
        }

        $contactlog = new PluginDatabaseinventoryContactLog();
        $contactloglist = $contactlog->find(
            [
                'agents_id' => $ID
            ]
        );

        $credential = new PluginDatabaseinventoryCredential();
        $dbparam = new PluginDatabaseinventoryDatabaseParam();
        $listofctlog = [];
        foreach ($contactloglist as $dbpctlog) {
            if ($credential->getFromDB($dbpctlog['plugin_databaseinventory_credentials_id'])) {
                $linkcred = $credential->getLinkURL();
                $credname = $credential->fields['name'];
            }
            if ($dbparam->getFromDB($dbpctlog['plugin_databaseinventory_databaseparams_id'])) {
                $linkdbparam = $dbparam->getLinkURL();
                $dbparamname = $dbparam->fields['name'];
            }
            if (isset($linkcred)) {
                $listofctlog[] = $dbpctlog + [
                    'linkcred' => $linkcred,
                    'linkdbparam' => $linkdbparam ?? '',
                    'credname' => $credname ?? '',
                    'dbparamname' => $dbparamname ?? '',
                ];
            }
        }
        TemplateRenderer::getInstance()->display(
            '@databaseinventory/contactlog.html.twig',
            [
                'itemtype' => Agent::getType(),
                'contactlogs' => $listofctlog,
                'canread' => $agent->can($ID, READ)
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
        /** @var DBmysql $DB */
        global $DB;
        $table = self::getTable();
        if ($DB->tableExists($table)) {
            $DB->query("DROP TABLE IF EXISTS `" . self::getTable() . "`") or die($DB->error());
        }
    }
}
