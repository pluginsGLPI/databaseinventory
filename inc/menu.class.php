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

class PluginDatabaseinventoryMenu extends CommonGLPI
{
    public static function getMenuName()
    {
        return __('Database Inventory', 'databaseinventory');
    }

    public static function getMenuContent()
    {
        $links_class = [
            PluginDatabaseinventoryComputerGroup::class,
            PluginDatabaseinventoryDatabaseParam::class,
            PluginDatabaseinventoryCredential::class
<<<<<<< HEAD
        ];

        $links = [];
        foreach ($links_class as $link) {
            $link_text =
            "<span class='d-none d-xxl-block'>" . $link::getTypeName(Session::getPluralNumber()) . "</span>";
            $links["<i class='" . $link::getIcon() . "'></i>$link_text"] = $link::getSearchURL(false);
        }

        $menu = [
            'title' => self::getMenuName(2),
            'page'  => PluginDatabaseinventoryDatabaseParam::getSearchURL(false),
            'icon'  => 'fas fa-database',
            'options' => [],
            'links'   => $links,
        ];
=======
        ];

        $links = [];
        foreach ($links_class as $link) {
            $link_text =
            "<span class='d-none d-xxl-block'>" . $link::getTypeName(Session::getPluralNumber()) . "</span>";
            $links["<i class='" . $link::getIcon() . "'></i>$link_text"] = $link::getSearchURL(false);
        }

        $menu = [
            'title' => self::getMenuName(2),
            'page'  => PluginDatabaseinventoryDatabaseParam::getSearchURL(false),
            'icon'  => 'fas fa-database',
            'options' => [],
            'links'   => $links,
        ];

        if (PluginDatabaseinventoryDatabaseParam::canView()) {
            $menu['options']['databaseparam'] = [
                'title'  => PluginDatabaseinventoryDatabaseParam::getTypeName(2),
                'page'   => PluginDatabaseinventoryDatabaseParam::getSearchURL(false),
                'icon'   => PluginDatabaseinventoryDatabaseParam::getIcon(),
                'links'  => $links,
            ];

            if (true) {
                $menu['options']['databaseparam']['links'] = [
                    'search' => PluginDatabaseinventoryDatabaseParam::getSearchURL(false),
                    'add'    => PluginDatabaseinventoryDatabaseParam::getFormURL(false),
                ];
            }
        }

        if (PluginDatabaseinventoryComputerGroup::canView()) {
            $menu['options']['computergroup'] = [
                'title'  => PluginDatabaseinventoryComputerGroup::getTypeName(2),
                'page'   => PluginDatabaseinventoryComputerGroup::getSearchURL(false),
                'icon'   => PluginDatabaseinventoryComputerGroup::getIcon(),
                'links'  => $links,
            ];
>>>>>>> edcd9ee (create twig for databaseparam + set databaseparam on home page)

        $menu['options']['databaseparam'] = [
            'title'  => PluginDatabaseinventoryDatabaseParam::getTypeName(2),
            'page'   => PluginDatabaseinventoryDatabaseParam::getSearchURL(false),
            'icon'   => PluginDatabaseinventoryDatabaseParam::getIcon(),
            'links'  => $links,
        ];

        if (true) {
            $menu['options']['databaseparam']['links'] = [
                'search' => PluginDatabaseinventoryDatabaseParam::getSearchURL(false),
                'add'    => PluginDatabaseinventoryDatabaseParam::getFormURL(false),
            ] + $links;
        }
        $menu['options']['computergroup'] = [
            'title'  => PluginDatabaseinventoryComputerGroup::getTypeName(2),
            'page'   => PluginDatabaseinventoryComputerGroup::getSearchURL(false),
            'icon'   => PluginDatabaseinventoryComputerGroup::getIcon(),
            'links'  => $links,
        ];

<<<<<<< HEAD
        if (true) {
            $menu['options']['computergroup']['links'] = [
                'search' => PluginDatabaseinventoryComputerGroup::getSearchURL(false),
                'add'    => PluginDatabaseinventoryComputerGroup::getFormURL(false),
            ] + $links;
        }
        $menu['options']['credential'] = [
            'title'  => PluginDatabaseinventoryCredential::getTypeName(2),
            'page'   => PluginDatabaseinventoryCredential::getSearchURL(false),
            'icon'   => PluginDatabaseinventoryCredential::getIcon(),
            'links'  => $links,
        ];
=======
        if (PluginDatabaseinventoryCredential::canView()) {
            $menu['options']['credential'] = [
                'title'  => PluginDatabaseinventoryCredential::getTypeName(2),
                'page'   => PluginDatabaseinventoryCredential::getSearchURL(false),
                'icon'   => PluginDatabaseinventoryCredential::getIcon(),
                'links'  => $links,
            ];
>>>>>>> edcd9ee (create twig for databaseparam + set databaseparam on home page)

        if (true) {
            $menu['options']['credential']['links'] = [
                'search' => PluginDatabaseinventoryCredential::getSearchURL(false),
                'add'    => PluginDatabaseinventoryCredential::getFormURL(false),
            ] + $links;
        }

        return $menu;
    }
}
