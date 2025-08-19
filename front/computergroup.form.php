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

$computergroup       = new PluginDatabaseinventoryComputerGroup();
$computergroupstatic = new PluginDatabaseinventoryComputerGroupStatic();

if (isset($_POST['add'])) {
    // Add a new computergroup
    $computergroup->check(-1, CREATE, $_POST);
    if ($newID = $computergroup->add($_POST)) {
        Event::log(
            $newID,
            'PluginDatabaseinventoryComputerGroup',
            4,
            'inventory',
            sprintf(__s('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name']),
        );

        if ($_SESSION['glpibackcreated']) {
            Html::redirect($computergroup->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['add_staticcomputer'])) {
    if (!$_POST['computers_id']) {
        Session::addMessageAfterRedirect(__s('Please select a computer', 'databaseinventory'), false, ERROR);
        Html::back();
    }

    $computergroupstatic->check(-1, CREATE, $_POST);
    if ($newID = $computergroupstatic->add($_POST)) {
        Event::log(
            $newID,
            'PluginDatabaseinventoryComputerGroupStatic',
            4,
            'inventory',
            sprintf(__s('%1$s adds the item %2$s'), $_SESSION['glpiname'], $computergroupstatic::getTypeName(0)),
        );

        if ($_SESSION['glpibackcreated']) {
            $computergroup->getFromDB($_POST['plugin_databaseinventory_computergroups_id']);
            Html::redirect($computergroup->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST['purge'])) {
    // purge a computergroup
    $computergroup->check($_POST['id'], PURGE);
    if ($computergroup->delete($_POST, true)) {
        Event::log(
            $_POST['id'],
            'PluginDatabaseinventoryComputerGroup',
            4,
            'inventory',
            //TRANS: %s is the user login
            sprintf(__s('%s purges an item'), $_SESSION['glpiname']),
        );
    }
    $computergroup->redirectToList();
} elseif (isset($_POST['update'])) {
    // update a computergroup
    $computergroup->check($_POST['id'], UPDATE);
    $computergroup->update($_POST);
    Event::log(
        $_POST['id'],
        'PluginDatabaseinventoryComputerGroup',
        4,
        'inventory',
        //TRANS: %s is the user login
        sprintf(__s('%s updates an item'), $_SESSION['glpiname']),
    );
    Html::back();
} else {
    // print computergroup information
    $computergroup_dynamic = new PluginDatabaseinventoryComputerGroupDynamic();

    // save search parameters for dynamic group
    if (isset($_GET['save'])) {
        $input  = ['plugin_databaseinventory_computergroups_id' => $_GET['plugin_databaseinventory_computergroups_id']];
        $search = serialize([
            'is_deleted'   => isset($_GET['is_deleted']) ? $_GET['is_deleted'] : 0 ,
            'as_map'       => isset($_GET['as_map']) ? $_GET['as_map'] : 0,
            'criteria'     => $_GET['criteria'],
            'metacriteria' => isset($_GET['metacriteria']) ? $_GET['metacriteria'] : [],
        ]);

        if (!$computergroup_dynamic->getFromDBByCrit($input)) {
            $input['search'] = $search;
            $computergroup_dynamic->add($input);
        } else {
            $input           = $computergroup_dynamic->fields;
            $input['search'] = $search;
            $computergroup_dynamic->update($input);
        }
    } elseif (isset($_GET['reset'])) {
        $computergroup_dynamic->deleteByCriteria(['plugin_databaseinventory_computergroups_id' => $_GET['id']]);
    }

    Html::header(PluginDatabaseinventoryComputerGroup::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'admin', 'PluginDatabaseinventoryMenu', 'computergroup');

    // show computergroup form to add
    if ($_GET['id'] == '') {
        $computergroup->showForm(-1, ['withtemplate' => $_GET['withtemplate']]);
    } else {
        $computergroup->display($_GET);
    }
    Html::footer();
}
