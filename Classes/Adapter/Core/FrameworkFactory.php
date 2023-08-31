<?php

namespace MichielRoos\H5p\Adapter\Core;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FrameworkFactory
 */
class FrameworkFactory implements SingletonInterface
{
    /**
     * @return Framework
     */
    public function create(): Framework
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage         = $resourceFactory->getDefaultStorage();
        return GeneralUtility::makeInstance(Framework::class, $storage);
    }
}