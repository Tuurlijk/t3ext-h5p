<?php

return [
    'ctrl'     => [
        'adminOnly'      => true,
        'title'          => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_configsetting',
        'label'          => 'config_key',
        'label_userFunc' => \MichielRoos\H5p\Backend\TCA::class . '->getConfigSettingTitle',
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'enablecolumns'  => [
            'disabled' => 'hidden',
        ],
        'delete'         => 'deleted',
        'sortby'         => 'config_key',
        'searchFields'   => 'config_key,config_value',
        'security'       => true,
        'iconfile'       => 'EXT:h5p/Resources/Public/Icon/h5p.gif',
    ],
    'columns'  => [
        'hidden'       => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config'  => [
                'type' => 'check'
            ]
        ],
        'config_key'   => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_configsetting.config_key',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
        'config_value' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_configsetting.config_value',
            'config'  => [
                'type' => 'input',
                'size' => 80,
                'eval' => 'trim',
            ]
        ],
    ],
    'types'    => [
        '1' => ['showitem' => 'config_key, config_value']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
