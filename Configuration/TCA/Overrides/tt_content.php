<?php
defined('TYPO3') or die('¯\_(ツ)_/¯');


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_h5p_content'         => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_content',
            'config'  => [
                'type'          => 'group',
                'rootLevel'     => true,
                'allowed'       => 'tx_h5p_domain_model_content',
                'items'         => [
                    ['', 0]
                ],
                'default'       => 0,
                'size'          => 1,
                'minitems'      => 0,
                'maxitems'      => 1,
                'wizards'       => [
                    'suggest' => [
                        'type' => 'suggest',
                    ]
                ]
            ]
        ],
        'tx_h5p_display_options' => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options',
            'config'  => [
                'type'    => 'check',
                'items'   => [
                    ['LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options.I.0', ''], // 1
                    ['LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options.I.1', ''], // 2
                    ['LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options.I.2', ''], // 4
                    ['LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options.I.3', ''], // 8
                    ['LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options.I.4', ''], // 16
                ],
                'cols'    => 2,
                'default' => \H5PCore::DISABLE_FRAME + \H5PCore::DISABLE_COPYRIGHT // 1 + 8
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'tt_content',
    'h5p',
    'tx_h5p_content,--linebreak--,tx_h5p_display_options'
);

// This is needed for addToAllTCAtypes to work
$GLOBALS['TCA']['tt_content']['types']['h5p_view']['showitem'] = '';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
     --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,
     --palette--;LLL:EXT:h5p/Resources/Private/Language/Tca.xlf:tt_content.tx_h5p_display_options;h5p,
     --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
     --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
     --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
     --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
     --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
     --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
    ',
    'h5p_view',
    'after:CType'
);
