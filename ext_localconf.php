<?php

use MichielRoos\H5p\Controller\AjaxController;
use MichielRoos\H5p\Controller\ViewController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die('¯\_(ツ)_/¯');

ExtensionUtility::configurePlugin(
    'h5p',
    'view',
    [
        ViewController::class => 'index',
    ],
    [
        ViewController::class => 'index',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

ExtensionUtility::configurePlugin(
    'h5p',
    'statistics',
    [
        ViewController::class => 'statistics',
    ],
    [
        ViewController::class => 'statistics',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

ExtensionUtility::configurePlugin(
    'h5p',
    'ajax',
    [
        AjaxController::class => 'index,finish,contentUserData',
    ],
    [
        AjaxController::class => 'index,finish,contentUserData',
    ]
);


// InsertH5p button for editor
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertH5p'] = [];
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertH5p']['objectReference'] = \MichielRoos\H5p\Rtehtmlarea\Extension\InsertH5p::class;
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertH5p']['disableInFE'] = 0;

// load Backend JavaScript modules - Seem not to be called in backend record edit mode
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = \MichielRoos\H5p\Backend\BackendJsLoader::class . '->loadJsModules';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \MichielRoos\H5p\Backend\BackendJsLoader::class . '->loadJsModules';
