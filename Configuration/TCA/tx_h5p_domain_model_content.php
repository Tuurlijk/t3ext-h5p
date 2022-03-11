<?php

return [
    'ctrl'     => [
        'title'          => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content',
        'label'          => 'title',
        'label_userFunc' => \MichielRoos\H5p\Backend\TCA::class . '->getContentTitle',
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'enablecolumns'  => [
            'disabled' => 'hidden',
        ],
        'delete'         => 'deleted',
        'sortby'         => 'updated_at',
        'searchFields'   => 'title',
        'iconfile'       => 'EXT:h5p/Resources/Public/Icon/h5p.gif',
    ],
    'columns'  => [
        'hidden'          => [
            'exclude' => 1,
            'label'   => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config'  => [
                'type' => 'check'
            ]
        ],
        'title'           => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.title',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'license'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.license',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'author'          => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.author',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'library'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.library',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => 0
            ]
        ],
        'parameters'      => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.parameters',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => '{}'
            ]
        ],
        'filtered'        => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.filtered',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'slug'            => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.slug',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'embed_type'      => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.embed_type',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'disable'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.disable',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => 0
            ]
        ],
        'content_type'    => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.content_type',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'keywords'        => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.keywords',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'description'     => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.description',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'authors'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.authors',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'source'          => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.source',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'year_from'       => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.year_from',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => 0
            ]
        ],
        'year_to'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.year_to',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => 0
            ]
        ],
        'license_version' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.license_version',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'license_extras'  => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.license_extras',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'author_comments' => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.author_comments',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'changes'         => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.changes',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => ''
            ]
        ],
        'created_at'      => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.createdAt',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => 0
            ]
        ],
        'updated_at'      => [
            'exclude' => 0,
            'label'   => 'LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:tx_h5p_domain_model_content.updatedAt',
            'config'  => [
                'type'    => 'input',
                'size'    => 80,
                'eval'    => 'trim',
                'default' => 0
            ]
        ],
    ],
    'types'    => [
        '1' => ['showitem' => 'title,embed_type']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
