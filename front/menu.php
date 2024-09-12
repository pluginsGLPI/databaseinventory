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

include('../../../inc/includes.php');

/** @var array $CFG_GLPI */
global $CFG_GLPI;

Html::header(
    __('Database Inventory', 'databaseinventory'),
    $_SERVER['PHP_SELF'],
    'admin',
    'PluginDatabaseInventoryMenu',
);

if (PluginDatabaseinventoryDatabaseParam::canView()) {
    echo "<div class='center'>";
    echo "<table class='tab_cadre'>";
    echo "<tr><th colspan='2'>" . __('Database Inventory', 'databaseinventory') . '</th></tr>';

    echo "<tr class='tab_bg_1 center'>";
    echo "<td><i class='fas fa-cog'></i></td>";
    echo "<td><a href='" . Toolbox::getItemTypeSearchURL('PluginDatabaseinventoryDatabaseParam') . "'>"
        . PluginDatabaseinventoryDatabaseParam::getTypeName(2) . '</a></td></tr>';

    if (PluginDatabaseinventoryCredential::canView()) {
        echo "<tr class='tab_bg_1 center'>";
        echo "<td><i class='" . PluginDatabaseinventoryCredential::getIcon() . "'></i></td>";
        echo "<td><a href='" . Toolbox::getItemTypeSearchURL('PluginDatabaseinventoryCredential') . "'>"
            . PluginDatabaseinventoryCredential::getTypeName(2) . '</a></td></tr>';
    }

    if (PluginDatabaseinventoryComputerGroup::canView()) {
        echo "<tr class='tab_bg_1 center'>";
        echo "<td><i class='" . PluginDatabaseinventoryComputerGroup::getIcon() . "'></i></td>";
        echo "<td><a href='" . Toolbox::getItemTypeSearchURL('PluginDatabaseinventoryComputerGroup') . "'>"
            . PluginDatabaseinventoryComputerGroup::getTypeName(2) . '</a></td></tr>';
    }

    echo '</table></div>';
} else {
    echo "<div class='center'><br><br><img src=\"" . $CFG_GLPI['root_doc'] . '/pics/warning.png" alt="warning"><br><br>';
    echo '<b>' . __('Access denied') . '</b></div>';
}

Html::footer();
