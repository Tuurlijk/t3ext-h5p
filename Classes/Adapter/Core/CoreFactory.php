<?php

namespace MichielRoos\H5p\Adapter\Core;


use H5PFileStorage;
use H5PFrameworkInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class CoreFactory
 */
class CoreFactory extends \H5PCore implements SingletonInterface
{
    /**
     * Constructor for the H5PCore
     *
     * @param H5PFrameworkInterface $H5PFramework
     *  The frameworks implementation of the H5PFrameworkInterface
     * @param string|\H5PFileStorage $path H5P file storage directory or class.
     * @param string $url To file storage directory.
     * @param string $language code. Defaults to english.
     * @param boolean $export enabled?
     */
    public function __construct(H5PFrameworkInterface $H5PFramework, H5PFileStorage $path, string $url = '', string $language = 'en', bool $export = FALSE)
    {
        parent::__construct($H5PFramework, $path, $url, $language, $export);
    }

    /**
     * @param array $dependencies
     * @return array
     */
    public function orderDependenciesByWeight(array $dependencies): array
    {
        uasort($dependencies, static function ($a, $b) {
            if ($a['weight'] === $b['weight']) {
                return 0;
            }
            return ($a['weight'] > $b['weight']) ? 1 : -1;
        });

        return $dependencies;
    }
}
