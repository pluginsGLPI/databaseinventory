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

class PluginDatabaseinventoryProfile extends Profile
{
    public static $rightname = 'profile';

    public static function getTypeName($nb = 0)
    {
        return __('Database Inventory', 'databaseinventory');
    }

    private static function getAllRights($all = false)
    {
        $rights = [
            [
                'itemtype' => PluginDatabaseinventoryDatabaseParam::getType(),
                'label'    => PluginDatabaseinventoryProfile::getTypeName(),
                'field'    => 'database_inventory'
            ]
        ];
        return $rights;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Profile') {
            return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Profile && $item->getField('id')) {
            return self::showForProfile($item->getID());
        }
        return true;
    }

    private static function showForProfile($profiles_id = 0)
    {
        $canupdate = self::canUpdate();
        $profile = new Profile();
        $profile->getFromDB($profiles_id);
        echo "<div class='firstbloc'>";
        echo "<form method='post' action='" . $profile->getFormURL() . "'>";

        $rights = self::getAllRights();
        $profile->displayRightsChoiceMatrix($rights, array(
            'canedit'       => $canupdate,
            'title'         => self::getTypeName(),
        ));

        if ($canupdate) {
            echo "<div class='center'>";
            echo Html::hidden('id', array('value' => $profiles_id));
            echo Html::submit(_sx('button', 'Save'), array('name' => 'update'));
            echo "</div>\n";
            Html::closeForm();

            echo "</div>";
        }
    }

    public static function install(Migration $migration)
    {
        // Add right for administrators
        $migration->addRight('database_inventory', PURGE + CREATE + UPDATE + READ, ['config' => UPDATE]);

        // Add right to the current session
        $_SESSION['glpiactiveprofile']['database_inventory'] = PURGE + CREATE + UPDATE + READ;

        return true;
    }

    public static function uninstall(Migration $migration)
    {
        foreach (self::getAllRights() as $right) {
            ProfileRight::deleteProfileRights([$right['field']]);
        }
    }
}
