<?php

use MichielRoos\H5p\Backend\EditorController;

return [
    // H5P Backend Editor action
    'h5p_editor_action' => [
        'path'   => '/h5p/editor/action',
        'target' => EditorController::class . '::defaultAction'
    ]
];
