<?php
defined('TYPO3') or die('¯\_(ツ)_/¯');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'h5p',
    'view',
    [
        \MichielRoos\H5p\Controller\ViewController::class => 'index',
    ],
    [
        \MichielRoos\H5p\Controller\ViewController::class => 'index',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'h5p',
    'statistics',
    [
        \MichielRoos\H5p\Controller\ViewController::class => 'statistics',
    ],
    [
        \MichielRoos\H5p\Controller\ViewController::class => 'statistics',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'h5p',
    'ajax',
    [
        \MichielRoos\H5p\Controller\AjaxController::class => 'index,finish,contentUserData',
    ],
    [
        \MichielRoos\H5p\Controller\AjaxController::class => 'index,finish,contentUserData',
    ]
);


// InsertH5p button for editor
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertH5p'] = [];
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertH5p']['objectReference'] = \MichielRoos\H5p\Rtehtmlarea\Extension\InsertH5p::class;
//$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['InsertH5p']['disableInFE'] = 0;

// load Backend JavaScript modules - Seem not to be called in backend record edit mode
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] = \MichielRoos\H5p\Backend\BackendJsLoader::class . '->loadJsModules';
//$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \MichielRoos\H5p\Backend\BackendJsLoader::class . '->loadJsModules';
