<?php

use MichielRoos\H5p\Controller\H5pModuleController;

return [
    'web_H5pManager' => [
        'parent'         => 'web',
        'position'       => ['after' => 'web_info'],
        'access'         => 'user,group',
        'workspaces'     => 'live',
        'path'           => '/module/web/h5p',
        'iconIdentifier' => 'h5p-logo',
        'labels'         => 'LLL:EXT:h5p/Resources/Private/Language/BackendModule.xlf',
        'extensionName'  => 'H5p',

        'controllerActions' => [
            H5pModuleController::class => ['content', 'index', 'new', 'edit', 'create', 'libraries', 'show', 'update', 'consent', 'error'],
        ],
//        'routes'         => [
//            '_default' => [
//                'target' => H5pModuleController::class . '::contentAction',
//            ],
//            'index'    => [
//                'target' => H5pModuleController::class . '::indexAction',
//            ],
//        ],
    ],
];
