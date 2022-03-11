<?php

use MichielRoos\H5p\Controller\H5pModuleController;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die('¯\_(ツ)_/¯');

ExtensionUtility::registerPlugin(
    'h5p',
    'view',
    'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.contentelement',
    'EXT:h5p/Resources/Public/Icon/h5p.gif'
);

ExtensionUtility::registerPlugin(
    'h5p',
    'statistics',
    'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.statistics',
    'EXT:h5p/Resources/Public/Icon/h5p.gif'
);

if (TYPO3_MODE === 'BE') {
    ExtensionUtility::registerModule(
        'h5p',
        'web',
        'Manager',
        '',
        [
            H5pModuleController::class => 'content, index, new, edit, create, libraries, show, update, consent,error',
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:h5p/ext_icon.gif',
            'labels' => 'LLL:EXT:h5p/Resources/Private/Language/BackendModule.xlf',
        ]
    );

    ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:h5p/Configuration/TsConfig/ContentElementWizard.ts">');

    /** @var IconRegistry $iconRegistry */
    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerIcon(
        'h5p-logo',
        BitmapIconProvider::class,
        ['source' => 'EXT:h5p/ext_icon.gif']
    );

    call_user_func(
        function ($extKey) {
            $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get($extKey);
            if (!isset($extConf['onlyAllowRecordsInSysfolders']) || (int)$extConf['onlyAllowRecordsInSysfolders'] === 0) {
                ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_content');
            }
        },
        'h5p'
    );
    ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_configsetting');
}
