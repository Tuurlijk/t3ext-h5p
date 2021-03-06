<?php
defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'h5p',
    'view',
    'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.contentelement',
    'EXT:h5p/Resources/Public/Icon/h5p.gif'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'h5p',
    'statistics',
    'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.statistics',
    'EXT:h5p/Resources/Public/Icon/h5p.gif'
);

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'MichielRoos.h5p',
        'web',
        'Manager',
        '',
        [
            'H5pModule' => 'content,index,new,edit,create,libraries,show,update,consent',
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:h5p/ext_icon.gif',
            'labels' => 'LLL:EXT:h5p/Resources/Private/Language/BackendModule.xml',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:h5p/Configuration/TsConfig/ContentElementWizard.ts">');

    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'h5p-logo',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        ['source' => 'EXT:h5p/ext_icon.gif']
    );

    call_user_func(
        function ($extKey) {
            $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extKey]);
            if (!isset($extConf['onlyAllowRecordsInSysfolders']) || (int)$extConf['onlyAllowRecordsInSysfolders'] === 0) {
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_content');
            }
        },
        'h5p'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_configsetting');
}
