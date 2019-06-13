<?php
defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'h5p',
    'view',
    'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:h5p.contentelement',
    'EXT:h5p/Resources/Public/Icon/h5p.gif'
);

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'MichielRoos.h5p',
        'web',
        'Manager',
        '',
        [
            'H5pModule' => 'index,new,edit,create,libraries,show',
        ],
        [
            'access' => 'user,group',
            'icon' => 'EXT:h5p/ext_icon.gif',
            'labels' => 'LLL:EXT:h5p/Resources/Private/Language/BackendModule.xml',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:h5p/Configuration/TsConfig/ContentElementWizard.ts">');

}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_configsetting');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_content');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_contentdependency');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_contenttypecacheentry');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_library');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_h5p_domain_model_librarydependency');
