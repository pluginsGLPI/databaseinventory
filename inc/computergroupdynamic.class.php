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

class PluginDatabaseinventoryComputerGroupDynamic extends CommonDBTM
{
    public static $rightname = 'database_inventory';

    public static function getTypeName($nb = 0)
    {
        return _n('Dynamic group', 'Dynamic groups', $nb, 'databaseinventory');
    }

    public static function canCreate(): bool
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
            $count                 = 0;
            $computergroup_dynamic = new self();
            if (
                $computergroup_dynamic->getFromDBByCrit([
                    'plugin_databaseinventory_computergroups_id' => $item->getID(),
                ])
            ) {
                $count = $computergroup_dynamic->countDynamicItems();
            }

            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $count);
        }

        return '';
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'search':
                $count = 0;
                if (isset($values['id'])) {
                    if (strpos($values['id'], Search::NULLVALUE) === false) {
                        $computergroup_dynamic = new PluginDatabaseinventoryComputerGroupDynamic();
                        $computergroup_dynamic->getFromDB($values['id']);
                        $count = $computergroup_dynamic->countDynamicItems();
                    }
                }

                return  ($count) ? $count : ' 0 ';

            case '_virtual_dynamic_list':
                /** @var array $CFG_GLPI */
                global $CFG_GLPI;
                $value = ' ';
                $out   = ' ';
                if (strpos($values['id'], Search::NULLVALUE) === false) {
                    $search_params = Search::manageParams('Computer', unserialize($values['search']));
                    $data          = Search::prepareDatasForSearch('Computer', $search_params);
                    Search::constructSQL($data);
                    Search::constructData($data);

                    foreach ($data['data']['rows'] as $colvalue) {
                        $value .= "<a href='" . Computer::getFormURLWithID($colvalue['id']) . "'>";
                        $value .= Dropdown::getDropdownName('glpi_computers', $colvalue['id']) . '</a>' . Search::LBBR;
                    }
                }

                if (!preg_match('/' . Search::LBHR . '/', $value)) {
                    $values         = preg_split('/' . Search::LBBR . '/i', $value);
                    $line_delimiter = '<br>';
                } else {
                    $values         = preg_split('/' . Search::LBHR . '/i', $value);
                    $line_delimiter = '<hr>';
                }

                // move full list to tooltip if needed
                if (
                    count($values)             > 1
                    && Toolbox::strlen($value) > $CFG_GLPI['cut']
                ) {
                    $value = '';
                    foreach ($values as $v) {
                        $value .= $v . $line_delimiter;
                    }
                    $value  = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
                    $value  = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
                    $value  = '<div class="fup-popup">' . $value . '</div>';
                    $valTip = '&nbsp;' . Html::showToolTip(
                        $value,
                        [
                            'awesome-class' => 'fa-comments',
                            'display'       => false,
                            'autoclose'     => false,
                            'onclick'       => true,
                        ],
                    );
                    $out .= $values[0] . $valTip;
                } else {
                    $value = preg_replace('/' . Search::LBBR . '/', '<br>', $value);
                    $value = preg_replace('/' . Search::LBHR . '/', '<hr>', $value);
                    $out .= $value;
                }

                return $out;
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

    private function countDynamicItems()
    {
        $search_params = Search::manageParams('Computer', unserialize($this->fields['search']));
        $data          = Search::prepareDatasForSearch('Computer', $search_params);
        Search::constructSQL($data);
        Search::constructData($data);
        $count = $data['data']['totalcount'];

        return $count;
    }

    public function isDynamicSearchMatchComputer(Computer $computer)
    {
        // add new criteria to force computer ID
        $search               = unserialize($this->fields['search']);
        $search['criteria'][] = [
            'link'       => 'AND',
            'field'      => 2, // computer ID
            'searchtype' => 'contains',
            'value'      => $computer->fields['id'],
        ];

        $search_params = Search::manageParams('Computer', $search);
        $data          = Search::prepareDatasForSearch('Computer', $search_params);
        Search::constructSQL($data);
        Search::constructData($data);
        $count = $data['data']['totalcount'];

        return $count;
    }

    private static function showForItem(PluginDatabaseinventoryComputerGroup $computergroup)
    {
        $ID = $computergroup->getField('id');
        if (!$computergroup->can($ID, UPDATE)) {
            return false;
        }

        $canedit = $computergroup->canEdit($ID);
        if ($canedit) {
            $firsttime = true;
            // load dynamic search criteria from DB if exist
            $computergroup_dynamic = new self();
            if (
                $computergroup_dynamic->getFromDBByCrit([
                    'plugin_databaseinventory_computergroups_id' => $ID,
                ])
            ) {
                $p         = $search_params = Search::manageParams('Computer', unserialize($computergroup_dynamic->fields['search']));
                $firsttime = false;
            } else {
                // retrieve filter value from search if exist and reset it
                $p = $search_params = Search::manageParams('Computer', $_GET);
                if (isset($_SESSION['glpisearch']['Computer'])) {
                    unset($_SESSION['glpisearch']['Computer']);
                }
            }

            // redirect to computergroup dynamic tab after saved search
            $target = PluginDatabaseinventoryComputerGroup::getFormURLWithID($ID);
            $target .= '&_glpi_tab=PluginDatabaseinventoryComputerGroupDynamic$1';
            $p['target']    = $target;
            $p['addhidden'] = [
                'plugin_databaseinventory_computergroups_id' => $computergroup->getID(),
                'id'                                         => $computergroup->getID(),
                'start'                                      => 0,
            ];
            $p['actionname']   = 'save';
            $p['actionvalue']  = _sx('button', 'Save');
            $p['showbookmark'] = false;
            Search::showGenericSearch(Computer::getType(), $p);

            //display result from search
            if (!$firsttime) {
                $data = Search::prepareDatasForSearch('Computer', $search_params);
                Search::constructSQL($data);
                Search::constructData($data);
                $data['search']['target']             = $target;
                $data['search']['showmassiveactions'] = false;
                $data['search']['is_deleted']         = false;
                Search::displayData($data);

                //remove trashbin switch
                echo Html::scriptBlock("
               $(document).ready(
                  function() {
                     $('div.switch.grey_border').hide();
                  }
               );
            ");
            }
        }

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
                    `search` text,
                    PRIMARY KEY (`id`),
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
