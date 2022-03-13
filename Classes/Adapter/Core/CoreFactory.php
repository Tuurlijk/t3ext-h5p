<?php
namespace MichielRoos\H5p\Adapter\Core;


use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class CoreFactory
 */
class CoreFactory extends \H5PCore implements SingletonInterface
{
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
