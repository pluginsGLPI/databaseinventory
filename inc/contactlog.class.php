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

class PluginDatabaseinventoryContactLog extends CommonDBTM
{
    public $dohistory        = true;

    public static $rightname = 'database_inventory';

    public static function canCreate(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function canPurge(): bool
    {
        return Session::haveRight(static::$rightname, UPDATE);
    }

    public static function getTypeName($nb = 0)
    {
        return _sn('Contact log', 'Contact logs', $nb, 'databaseinventory');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item::class) {
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
        switch ($item::class) {
            case (PluginDatabaseinventoryDatabaseParam::class):
                self::showForDatabaseParams($item);
                break;
            case (Agent::class):
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

        // lines whose credential and agent are both gone are not displayed
        [$listofctlog, $pager] = self::getContactLogPage(
            ['plugin_databaseinventory_databaseparams_id' => $ID],
            Agent::class,
            'agents_id',
            false,
        );
        foreach ($listofctlog as &$ctlog) {
            $ctlog['linkagent'] = $ctlog['linkother'];
            $ctlog['agentname'] = $ctlog['othername'];
        }

        unset($ctlog);

        TemplateRenderer::getInstance()->display(
            '@databaseinventory/contactlog.html.twig',
            [
                'itemtype'    => PluginDatabaseinventoryDatabaseParam::getType(),
                'pager'       => $pager,
                'contactlogs' => $listofctlog,
                'canread'     => $databaseparams->can($ID, READ),
            ],
        );

        return true;
    }

    /**
     * Load one page of contact logs with the names of their credential and of the other linked item
     * (agent or database param), in one query.
     *
     * @param array                    $where              criteria on the contact log table
     * @param class-string<CommonDBTM> $other_itemtype     Agent or PluginDatabaseinventoryDatabaseParam
     * @param string                   $other_fk           foreign key of the other item in the contact log table
     * @param bool                     $require_credential true to skip lines whose credential is gone,
     *                                                     false to skip lines whose credential and other item are both gone
     *
     * @return array{0: array<int, array<string, mixed>>, 1: string} the lines and the pager HTML
     */
    private static function getContactLogPage(array $where, string $other_itemtype, string $other_fk, bool $require_credential): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $log_table        = self::getTable();
        $credential_table = PluginDatabaseinventoryCredential::getTable();
        $other_table      = $other_itemtype::getTable();

        $full_where = [];
        foreach ($where as $field => $value) {
            $full_where[$log_table . '.' . $field] = $value;
        }

        if ($require_credential) {
            $full_where[] = ['NOT' => [$credential_table . '.id' => null]];
        } else {
            $full_where[] = ['OR' => [
                ['NOT' => [$credential_table . '.id' => null]],
                ['NOT' => [$other_table . '.id' => null]],
            ]];
        }

        $criteria = [
            'FROM'      => $log_table,
            'LEFT JOIN' => [
                $credential_table => [
                    'ON' => [
                        $log_table        => 'plugin_databaseinventory_credentials_id',
                        $credential_table => 'id',
                    ],
                ],
                $other_table => [
                    'ON' => [
                        $log_table   => $other_fk,
                        $other_table => 'id',
                    ],
                ],
            ],
            'WHERE'     => $full_where,
        ];
        $total = $DB->request($criteria + ['COUNT' => 'total'])->current()['total'];

        $start = max(0, (int) ($_GET['start'] ?? 0));
        if ($start >= $total) {
            $start = 0;
        }

        $iterator = $DB->request($criteria + [
            'SELECT' => [
                $log_table . '.*',
                $credential_table . '.id AS credential_id',
                $credential_table . '.name AS credname',
                $other_table . '.id AS other_id',
                $other_table . '.name AS othername',
            ],
            // newest first: the log grows at every agent contact
            'ORDER'  => [$log_table . '.date_creation DESC', $log_table . '.id DESC'],
            'START'  => $start,
            'LIMIT'  => (int) $_SESSION['glpilist_limit'],
        ]);

        $lines = [];
        foreach ($iterator as $row) {
            $row['linkcred']  = $row['credential_id'] !== null ? PluginDatabaseinventoryCredential::getFormURLWithID($row['credential_id']) : '';
            $row['credname'] ??= '';
            $row['linkother'] = $row['other_id'] !== null ? $other_itemtype::getFormURLWithID($row['other_id']) : '';
            $row['othername'] ??= '';
            $lines[] = $row;
        }

        return [$lines, $total > 0 ? Html::printAjaxPager('', $start, $total, '', false) : ''];
    }

    private static function showForAgent(Agent $agent)
    {
        $ID = $agent->getField('id');
        if (!$agent->can($ID, UPDATE)) {
            return false;
        }

        // lines whose credential is gone are not displayed
        [$listofctlog, $pager] = self::getContactLogPage(
            ['agents_id' => $ID],
            PluginDatabaseinventoryDatabaseParam::class,
            'plugin_databaseinventory_databaseparams_id',
            true,
        );
        foreach ($listofctlog as &$ctlog) {
            $ctlog['linkdbparam'] = $ctlog['linkother'];
            $ctlog['dbparamname'] = $ctlog['othername'];
        }

        unset($ctlog);

        TemplateRenderer::getInstance()->display(
            '@databaseinventory/contactlog.html.twig',
            [
                'itemtype'    => Agent::getType(),
                'pager'       => $pager,
                'contactlogs' => $listofctlog,
                'canread'     => $agent->can($ID, READ),
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
