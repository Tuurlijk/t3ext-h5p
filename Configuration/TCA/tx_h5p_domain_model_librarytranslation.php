<?php

return [
    'ctrl'     => [
        'hideTable'      => true,
        'title'          => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_librarytranslation',
        'label'          => 'library',
        'label_userFunc' => \MichielRoos\H5p\Backend\TCA::class . '->getLibraryTranslationTitle',
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'enablecolumns'  => [
            'disabled' => 'hidden',
        ],
        'delete'         => 'deleted',
        'sortby'         => 'library',
        'searchFields'   => 'library',
        'iconfile'       => 'EXT:h5p/Resources/Public/Icon/h5p.gif',
    ],
    'columns'  => [
        'hidden'        => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config'  => [
                'type' => 'check'
            ]
        ],
        'library'       => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_librarytranslation.library',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'language_code' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_librarytranslation.languageCode',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'translation'   => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_librarytranslation.translation',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ]
    ],
    'types'    => [
        '1' => ['showitem' => '--palette--;;library']
    ],
    'palettes' => [
        'library' => ['showitem' => 'library,languagecode,translation']
    ]
];
