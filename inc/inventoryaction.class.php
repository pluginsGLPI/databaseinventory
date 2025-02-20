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

use Glpi\Asset\Asset_PeripheralAsset;
use GuzzleHttp\Psr7\Response;

class PluginDatabaseinventoryInventoryAction extends CommonDBTM
{
    public const MA_PARTIAL        = 'partial_database_inventory';
    private const ENDPOINT_PARTIAL = 'now?';

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        if ($ma->getAction() !== self::MA_PARTIAL) {
            return parent::showMassiveActionsSubForm($ma);
        }
        echo Html::submit(__('Run', 'databaseinventory'), ['name' => 'submit']);

        return true;
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        if ($ma->getAction() !== self::MA_PARTIAL) {
            parent::processMassiveActionsForOneItemtype($ma, $item, $ids);

            return;
        }

        switch ($item->getType()) {
            case Computer::getType():
                foreach ($ids as $id) {
                    $computer = new Computer();
                    $computer->getFromDB($id);
                    if ($agent = self::findAgent($computer)) {
                        if (PluginDatabaseinventoryInventoryAction::runPartialInventory($agent, true)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage(__('Agent not found for computer', 'databaseinventory') . "<a href='" . \Computer::getFormURLWithID($id) . "'>" . $computer->getFriendlyName() . '</a>');
                    }
                }
                break;
            case Agent::getType():
                foreach ($ids as $id) {
                    $agent = new Agent();
                    if ($agent->getFromDB($id)) {
                        if (PluginDatabaseinventoryInventoryAction::runPartialInventory($agent, true)) {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                            $ma->addMessage($item->getErrorMessage(ERROR_ON_ACTION));
                        }
                    } else {
                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                        $ma->addMessage(sprintf(__('Agent %1$s not found', 'databaseinventory'), $id));
                    }
                }
                break;
        }
    }

    public static function runPartialInventory(Agent $agent, $fromMA = false)
    {
        try {
            // retrieve data to do database inventory
            $data   = PluginDatabaseinventoryTask::handleInventoryTask(['item' => $agent]);
            $params = $data['options']['response']['inventory']['params'];

            $arg = ['partial' => 'yes', 'category' => 'database'];
            foreach ($params as $value) {
                $arg['params_id']                = implode(',', array_column($params, 'params_id'));
                $arg['use'][$value['params_id']] = implode(',', $value['use']);
            }

            $endpoint = self::ENDPOINT_PARTIAL . Toolbox::append_params($arg);
            $response = $agent->requestAgent($endpoint);
            if ($fromMA) {
                return true;
            } else {
                // not authorized
                return self::handleAgentResponse($response, $endpoint);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) { // @phpstan-ignore-line
            if ($fromMA) {
                return false;
            } else {
                // not authorized
                return ['answer' => __('Not allowed')];
            }
        }
    }

    public static function handleAgentResponse($response, $request): array
    {
        $params           = [];
        $params['answer'] = sprintf(
            __('Requested at %s', 'databaseinventory'),
            Html::convDateTime(date('Y-m-d H:i:s')),
        );

        return $params;
    }

    private static function findAgent(Computer $item)
    {
        $agent     = new Agent();
        $has_agent = $agent->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->fields['id'],
        ]);

        // if no agent has been found, check if there is a linked item, and find its agent
        if (!$has_agent && $item->getType() == 'Computer') {
            $citem        = new Asset_PeripheralAsset();
            $has_relation = $citem->getFromDBByCrit([
                'itemtype' => $item->getType(),
                'items_id' => $item->fields['id'],
            ]);
            if ($has_relation) {
                $has_agent = $agent->getFromDBByCrit([
                    'itemtype' => \Computer::getType(),
                    'items_id' => $citem->fields['computers_id'],
                ]);
            }
        }

        if ($has_agent) {
            return $agent;
        } else {
            return false;
        }
    }

    public static function postItemForm($item)
    {
        if (!$item->isDynamic()) {
            return;
        }

        if ($item::getType() == Computer::getType()) {
            if ($agent = self::findAgent($item)) {
                $out = '<div class="mb-3 col-12 col-sm-6">';
                $out .= '<label class="form-label" >' . __('Request database inventory', 'database inventory');
                $out .= '<i id="request_database_inventory" class="fas fa-sync" style="cursor: pointer;" title="' . __s('Ask agent to proceed a database inventory', 'databaseinventory') . '"></i>';
                $out .= '</label>';
                $out .= '<span id="database_inventory_status">' . __('Unknown') . '</span>';
                $out .= '</div>';

                echo $out;

                $url = $CFG_GLPI['url_base'] . '/plugins/databaseinventory/ajax/agent.php';
                $key = PluginDatabaseinventoryInventoryAction::MA_PARTIAL;
                $js  = <<<JAVASCRIPT
                    $(function() {
                        $('#request_database_inventory').on('click', function() {
                            var icon = $(this);
                            icon.addClass('fa-spin');
                            $.ajax({
                                type: 'POST',
                                url: '{$url}',
                                timeout: 3000, //3 seconds timeout
                                data: {'action': '{$key}', 'id': '{$agent->fields['id']}'},
                                success: function(json) {
                                    icon.removeClass('fa-spin');
                                    $('#database_inventory_status').html(json.answer);
                                }
                            });
                        });
                    });
JAVASCRIPT;
                echo Html::scriptBlock($js);
            }
        }
    }
}
