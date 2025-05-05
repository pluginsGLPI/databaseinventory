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

use Glpi\Http\Response;

$AJAX_INCLUDE = 1;
include('../../../inc/includes.php');
header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

if (isset($_POST['action']) && isset($_POST['id'])) {
    $agent = new Agent();
    if (!$agent->getFromDB($_POST['id'])) {
        throw new \Glpi\Exception\Http\NotFoundHttpException();
    };
    $answer = [];

    switch ($_POST['action']) {
        case PluginDatabaseinventoryInventoryAction::MA_PARTIAL:
            $answer = PluginDatabaseinventoryInventoryAction::runPartialInventory($agent);
            break;
    }

    echo json_encode($answer);
}
