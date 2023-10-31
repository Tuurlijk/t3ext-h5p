<?php

$EM_CONF['h5p'] = [
    'title'          => 'H5p',
    'description'    => 'Create and add rich content to your website for free. Some examples of what you get with H5P are Interactive Video, Quizzes, Collage and Timeline.',
    'category'       => 'fe',
    'author'         => 'Michiel Roos',
    'author_company' => 'Michiel Roos',
    'author_email'   => 'michiel@michielroos.com',
    'state'          => 'stable',
    'version'        => '12.4.0',
    'constraints'    => [
        'depends'   => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
    'autoload'       => [
        'classmap' => ["Resources/Public/Lib"],
        'psr-4'    => ['MichielRoos\\H5p\\' => 'Classes']
    ],
];
