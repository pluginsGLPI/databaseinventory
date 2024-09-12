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

$databaseparam               = new PluginDatabaseinventoryDatabaseParam();
$databaseparam_credential    = new PluginDatabaseinventoryDatabaseParam_Credential();
$databaseparam_computergroup = new PluginDatabaseinventoryDatabaseParam_ComputerGroup();

if (isset($_POST['add'])) {
    // Add a new databaseparam
    $databaseparam->check(-1, CREATE, $_POST);
    if ($newID = $databaseparam->add($_POST)) {
        Event::log(
            $newID,
            'PluginDatabaseinventoryDatabaseParam',
            4,
            'inventory',
            sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name']),
        );

        if ($_SESSION['glpibackcreated']) {
            Html::redirect($databaseparam->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['add_credential'])) {
    // add credential
    $databaseparam_credential->check(-1, CREATE, $_POST);
    if ($newID = $databaseparam_credential->add($_POST)) {
        Event::log(
            $newID,
            'PluginDatabaseinventoryDatabaseParam_Credential',
            4,
            'inventory',
            sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $databaseparam_credential::getTypeName(0)),
        );

        if ($_SESSION['glpibackcreated']) {
            $databaseparam->getFromDB($_POST['plugin_databaseinventory_databaseparams_id']);
            Html::redirect($databaseparam->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['add_computergroup'])) {
    // add computer group
    $databaseparam_computergroup->check(-1, CREATE, $_POST);
    if ($newID = $databaseparam_computergroup->add($_POST)) {
        Event::log(
            $newID,
            'PluginDatabaseinventoryDatabaseParam_ComputerGroup',
            4,
            'inventory',
            sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $databaseparam_computergroup::getTypeName(0)),
        );

        if ($_SESSION['glpibackcreated']) {
            $databaseparam->getFromDB($_POST['plugin_databaseinventory_databaseparams_id']);
            Html::redirect($databaseparam->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['purge'])) {
    // purge a databaseparam
    $databaseparam->check($_POST['id'], PURGE);
    if ($databaseparam->delete($_POST, 1)) {
        Event::log(
            $_POST['id'],
            'PluginDatabaseinventoryDatabaseParam',
            4,
            'inventory',
            //TRANS: %s is the user login
            sprintf(__('%s purges an item'), $_SESSION['glpiname']),
        );
    }
    $databaseparam->redirectToList();
} elseif (isset($_POST['update'])) {
    // update a databaseparam
    $databaseparam->check($_POST['id'], UPDATE);
    $databaseparam->update($_POST);
    Event::log(
        $_POST['id'],
        'PluginDatabaseinventoryDatabaseParam',
        4,
        'inventory',
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION['glpiname']),
    );
    Html::back();
} else {
    // print databaseparam information
    Html::header(PluginDatabaseinventoryDatabaseParam::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'admin', 'PluginDatabaseinventoryMenu', 'databaseparam');
    // show databaseparam form to add
    if ($_GET['id'] == '') {
        $databaseparam->showForm(-1, ['withtemplate' => $_GET['withtemplate']]);
    } else {
        $databaseparam->display($_GET);
    }
    Html::footer();
}
