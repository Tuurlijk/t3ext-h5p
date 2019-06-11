<?php
defined('TYPO3_MODE') or die('¯\_(ツ)_/¯');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'MichielRoos.h5p',
    'view',
    [
        'View' => 'index',
    ],
    [
        'View' => 'index',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'MichielRoos.h5p',
    'ajax',
    [
        'Ajax' => 'index,finish,contentUserData',
    ],
    [
        'Ajax' => 'index,finish,contentUserData',
    ]
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\MichielRoos\H5p\Property\TypeConverter\UploadedFileReferenceConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\MichielRoos\H5p\Property\TypeConverter\ObjectStorageConverter::class);
