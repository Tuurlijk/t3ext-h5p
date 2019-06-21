<?php

return [
    'ctrl'     => [
        'hideTable'     => true,
        'title'         => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentdependency',
        'label'         => 'library',
        'tstamp'        => 'tstamp',
        'crdate'        => 'crdate',
        'dividers2tabs' => true,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'delete'        => 'deleted',
        'sortby'        => 'library',
        'searchFields'  => 'library',
        'iconfile'      => 'EXT:h5p/Resources/Public/Icon/h5p.gif',
    ],
    'columns'  => [
        'hidden'          => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config'  => [
                'type' => 'check'
            ]
        ],
        'library'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentdependency.library',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'content'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentdependency.content',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'dependency_type' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentdependency.dependencytype',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'weight'          => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentdependency.weight',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'drop_css'        => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentdependency.dropCss',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ]
    ],
    'types'    => [
        '1' => ['showitem' => '--palette--;;library, weight, drop_css']
    ],
    'palettes' => [
        'library' => ['showitem' => 'library,required_library,dependency_type']
    ]
];
