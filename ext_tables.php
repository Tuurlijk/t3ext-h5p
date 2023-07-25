<?php

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
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

// Only evaluate this in the backend
if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
    && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
) {

    ExtensionManagementUtility::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:h5p/Configuration/TsConfig/ContentElementWizard.ts">');

    /** @var IconRegistry $iconRegistry */
    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $iconRegistry->registerIcon(
        'h5p-logo',
        BitmapIconProvider::class,
        ['source' => 'EXT:h5p/Resources/Public/Icon/h5p.gif']
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
