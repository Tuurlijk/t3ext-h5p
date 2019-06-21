<?php

return [
    'ctrl'     => [
        'title'          => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult',
        'label'          => 'user',
        'label_userFunc' => \MichielRoos\H5p\Backend\TCA::class . '->getContentResultTitle',
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'dividers2tabs'  => true,
        'enablecolumns'  => [
            'disabled' => 'hidden',
        ],
        'delete'         => 'deleted',
        'sortby'         => 'content',
        'searchFields'   => 'library',
        'iconfile'       => 'EXT:h5p/Resources/Public/Icon/h5p.gif',
    ],
    'columns'  => [
        'content'   => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.content',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'user'      => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.user',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'score'     => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.score',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'opened'    => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.opened',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'max_score' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.max_score',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'finished'  => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.finished',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'time'      => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_contentresult.time',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
    ],
    'types'    => [
        '1' => ['showitem' => '--palette--;;stuff']
    ],
    'palettes' => [
        'stuff' => ['showitem' => 'user,content,score,time']
    ]
];
