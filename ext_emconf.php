<?php

$EM_CONF['h5p'] = [
    'title'            => 'H5p',
    'description'      => 'Create and add rich content to your website for free. Some examples of what you get with H5P are Interactive Video, Quizzes, Collage and Timeline.',
    'category'         => 'fe',
    'author'           => 'Michiel Roos',
    'author_company'   => 'Michiel Roos',
    'author_email'     => 'michiel@michielroos.com',
    'clearCacheOnLoad' => 0,
    'dependencies'     => '',
    'state'            => 'stable',
    'uploadfolder'     => 0,
    'version'          => '10.0.8',
    'constraints'      => [
        'depends'      => [
            'typo3' => '10.4.0-10.4.99',
        ], 'conflicts' => [],
        'suggests'     => [],
    ],
    'autoload'         => [
        'psr-4' => ['MichielRoos\\H5p\\' => 'Classes']
    ],
    'conflicts'        => '',
    'suggests'         => [],
];
