<?php
/**
 *  Notes:
 *  1. Create ids in the following format: plugin_loyalty_type_of_resource_name
 *  2. Once the config has been changed run /bin/console cache:clear
 *  3. This does not need to be done when changing other types of resources such as controllers or views
 */

return array(
    'name'        => 'Loyalty',
    'description' => 'Plugin that extends Mautic functionality to provide loyalty features',
    'author'      => 'Bushido',
    'version'     => '1.0.0',
    'routes' => [
        'main'   => [
            'plugin_loyalty_private_web_route_users' => [
                'path'         => '/loyalty/users',
                'controller'   => 'MauticPlugin\LoyaltyBundle\Controller\PrivateWebController::users',
                'method' => 'GET'
            ]
        ],
        'api'    => [
            'plugin_loyalty_api_route_user' => [
                'path'       => '/user',
                'controller' => 'MauticPlugin\LoyaltyBundle\Controller\ApiController::user',
                'method'     => 'GET',
            ],
        ],
    ],
    'menu'     => array(
        'main' => array(
            'priority' => 2,
            'items'    => array(
                # Keys are translations found in .ini files in the Translations module
                'plugin.loyalty.index' => array(
                    'id'        => 'plugin_loyalty_menu_item_users',
                    'access'    => 'admin',
                    'iconClass' => 'fa-globe',
                    'children'  => array(
                        'plugin.loyalty.users'     => array(
                            'route' => 'plugin_loyalty_private_web_route_users'
                        ),
                    )
                ),
            )
        )
    ),
);
