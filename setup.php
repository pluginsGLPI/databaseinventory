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

define('PLUGIN_DATABASEINVENTORY_VERSION', '1.0.0');

// Minimal GLPI version, inclusive
define('PLUGIN_DATABASEINVENTORY_MIN_GLPI', '10.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_DATABASEINVENTORY_MAX_GLPI', '10.0.99');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_databaseinventory()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['databaseinventory'] = true;

    $PLUGIN_HOOKS['config_page']['databaseinventory'] = 'front/databaseparam.php';

    if (!Plugin::isPluginActive('databaseinventory')) {
        return;
    }

    $PLUGIN_HOOKS['handle_inventory_task']['databaseinventory'] = ['PluginDatabaseinventoryTask', 'handleInventoryTask'];
    $PLUGIN_HOOKS['inventory_get_params']['databaseinventory']  = ['PluginDatabaseinventoryTask', 'inventoryGetParams'];
    $PLUGIN_HOOKS['handle_agent_response']['databaseinventory'] = ['PluginDatabaseinventoryInventoryAction', 'HandleAgentResponse'];

    $PLUGIN_HOOKS['item_purge']['databaseinventory'] = [
        'Computer' => 'cleanComputerFromStaticGroup',
        'Agent'    => 'cleanAgentFromContactLog'
    ];

    if (Session::haveRight("config", UPDATE)) {
        $PLUGIN_HOOKS['menu_toadd']['databaseinventory'] = [
            'admin' => 'PluginDatabaseinventoryMenu'
        ];

        Plugin::registerClass('PluginDatabaseinventoryContactLog', ['addtabon' => 'Agent']);
        Plugin::registerClass('PluginDatabaseinventoryProfile', ['addtabon' => ['Profile']]);

        $PLUGIN_HOOKS['use_massive_action']['databaseinventory']        = 1;
        $PLUGIN_HOOKS['autoinventory_information']['databaseinventory'] = [
            'Computer' => 'postItemForm'
        ];
    }
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_databaseinventory()
{
    return [
        'name'           => __('Database Inventory', 'databaseinventory'),
        'version'        => PLUGIN_DATABASEINVENTORY_VERSION,
        'author'         => '<a href="https://services.glpi-network.com">Teclib\'</a>',
        'license'        => 'GPL v3',
        'homepage'       => 'https://services.glpi-network.com',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_DATABASEINVENTORY_MIN_GLPI,
                'max' => PLUGIN_DATABASEINVENTORY_MAX_GLPI,
            ]
        ]
    ];
}
