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

class PluginDatabaseinventoryDatabaseParam_Credential extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'PluginDatabaseinventoryDatabaseParam';
    public static $items_id_1 = 'plugin_databaseinventory_databaseparams_id';
    public static $itemtype_2 = 'PluginDatabaseinventoryCredential';
    public static $items_id_2 = 'plugin_databaseinventory_credentials_id';

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
        return _n('Credential', 'Credentials', $nb, 'databaseinventory');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (get_class($item) == PluginDatabaseinventoryDatabaseParam::getType()) {
            $count = 0;
            $count = countElementsInTable(PluginDatabaseinventoryDatabaseParam_Credential::getTable(), ['plugin_databaseinventory_databaseparams_id' => $item->getID()]);
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

    private static function showForItem(PluginDatabaseinventoryDatabaseParam $databaseparams)
    {
        $ID = $databaseparams->getField('id');
        if (!$databaseparams->can($ID, UPDATE)) {
            return false;
        }


        $databaseparamcredentials = new PluginDatabaseinventoryDatabaseParam_Credential();
        $dbpcredentialslist = $databaseparamcredentials->find(
            [
                'plugin_databaseinventory_databaseparams_id' => $ID
            ]
        );

        $dbcredentials = new PluginDatabaseinventoryCredential();
        $listofcredentials = [];
        $used = [];
        foreach ($dbpcredentialslist as $dbpcredential) {
            $used[] = $dbpcredential['plugin_databaseinventory_credentials_id'];
            if ($dbcredentials->getFromDB($dbpcredential['plugin_databaseinventory_credentials_id'])) {
                $listofcredentials[] = $dbcredentials->fields +
                [
                    'type' => Dropdown::getDropdownName(
                        PluginDatabaseinventoryCredentialType::getTable(),
                        $dbcredentials->fields['plugin_databaseinventory_credentialtypes_id']
                    ),
                    'link' => $dbcredentials->getLinkURL(),
                ];
            }
        }
        TemplateRenderer::getInstance()->display(
            '@databaseinventory/databaseparam_credential.html.twig',
            [
                'item' => PluginDatabaseinventoryDatabaseParam::getById($ID),
                'credentiallist' => $listofcredentials,
                'credentialclass' => PluginDatabaseinventoryCredential::class,
                'canread' => $databaseparams->can($ID, READ),
                'canedit' => $databaseparams->can($ID, UPDATE),
                'used' => $used,
            ]
        );

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
                    `plugin_databaseinventory_credentials_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                   PRIMARY KEY (`id`),
                   UNIQUE KEY `unicity` (`plugin_databaseinventory_databaseparams_id`, `plugin_databaseinventory_credentials_id`),
                   KEY `plugin_databaseinventory_credentials_id` (`plugin_databaseinventory_credentials_id`)
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
