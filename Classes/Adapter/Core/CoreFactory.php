<?php
namespace MichielRoos\H5p\Adapter\Core;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
    public function orderDependenciesByWeight(array $dependencies)
    {
        uasort($dependencies, static function ($a, $b) {
            if ($a['weight'] === $b['wieight']) {
                return 0;
            }
            return ($a['weight'] > $b['weight']) ? 1 : -1;
        });

        return $dependencies;
    }
}
