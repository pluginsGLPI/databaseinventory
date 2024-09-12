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

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_databaseinventory_install()
{
    $version   = plugin_version_databaseinventory();
    $migration = new Migration($version['version']);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php$/", $filepath, $matches)) {
            $classname = 'PluginDatabaseinventory' . ucfirst($matches[1]);
            include_once($filepath);
            // If the install method exists, load it
            if (method_exists($classname, 'install')) {
                $classname::install($migration);
            }
        }
    }
    $migration->executeMigration();

    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_databaseinventory_uninstall()
{
    $migration = new Migration(PLUGIN_DATABASEINVENTORY_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
        // Load *.class.php files and get the class name
        if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
            $classname = 'PluginDatabaseinventory' . ucfirst($matches[1]);
            include_once($filepath);
            // If the install method exists, load it
            if (method_exists($classname, 'uninstall')) {
                $classname::uninstall($migration);
            }
        }
    }
    $migration->executeMigration();

    return true;
}

function plugin_databaseinventory_MassiveActions($type)
{
    // Must be super-admin
    if (!Session::haveRight('database_inventory', UPDATE)) {
        return [];
    }

    switch ($type) {
        case 'Computer':
        case 'Agent':
            $class = PluginDatabaseinventoryInventoryAction::getType();
            $key   = PluginDatabaseinventoryInventoryAction::MA_PARTIAL;
            $label = __('Run partial databases inventory', 'databaseinventory');

            return [$class . MassiveAction::CLASS_ACTION_SEPARATOR . $key => $label];
    }

    return [];
}

function postItemForm(CommonDBTM $item)
{
    PluginDatabaseinventoryInventoryAction::postItemForm($item);
}

function cleanComputerFromStaticGroup(CommonDBTM $item)
{
    if ($item::getType() === Computer::getType()) {
        $c_static = new PluginDatabaseinventoryComputerGroupStatic();
        $c_static->deleteByCriteria(['computers_id' => $item->fields['id']]);
    }
}

function cleanAgentFromContactLog(CommonDBTM $item)
{
    if ($item::getType() === Agent::getType()) {
        $contactlog = new PluginDatabaseinventoryContactLog();
        $contactlog->deleteByCriteria(['agents_id' => $item->fields['id']]);
    }
}
