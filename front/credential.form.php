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

use Glpi\Event;

include('../../../inc/includes.php');

Session::checkRight('config', READ);

if (!isset($_GET['id'])) {
    $_GET['id'] = '';
}

if (!isset($_GET['withtemplate'])) {
    $_GET['withtemplate'] = '';
}

$credential = new PluginDatabaseinventoryCredential();

if (isset($_POST['add'])) {
    // Add a new credential
    $credential->check(-1, CREATE, $_POST);
    if ($newID = $credential->add($_POST)) {
        Event::log(
            $newID,
            'PluginDatabaseinventoryCredential',
            4,
            'inventory',
            sprintf(__s('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name']),
        );

        if ($_SESSION['glpibackcreated']) {
            Html::redirect($credential->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['purge'])) {
    // purge a credential
    $credential->check($_POST['id'], PURGE);
    if ($credential->delete($_POST, true)) {
        Event::log(
            $_POST['id'],
            'PluginDatabaseinventoryCredential',
            4,
            'inventory',
            //TRANS: %s is the user login
            sprintf(__s('%s purges an item'), $_SESSION['glpiname']),
        );
    }
    $credential->redirectToList();
} elseif (isset($_POST['update'])) {
    // update a credential
    $credential->check($_POST['id'], UPDATE);
    $credential->update($_POST);
    Event::log(
        $_POST['id'],
        'PluginDatabaseinventoryCredential',
        4,
        'inventory',
        //TRANS: %s is the user login
        sprintf(__s('%s updates an item'), $_SESSION['glpiname']),
    );
    Html::back();
} else {
    // print credential information
    Html::header(PluginDatabaseinventoryCredential::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'admin', 'PluginDatabaseinventoryMenu', 'credential');
    // show credential form to add
    if ($_GET['id'] == '') {
        $credential->showForm(-1, ['withtemplate' => $_GET['withtemplate']]);
    } else {
        $credential->display($_GET);
    }
    Html::footer();
}
