<?php
namespace MichielRoos\H5p\Domain\Repository;

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
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContentDependencyRepository
 */
class ContentDependencyRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = [
        'weight' => QueryInterface::ORDER_ASCENDING
    ];

    /**
     * initializes any required object
     */
    public function initializeObject()
    {
        if ($this->defaultQuerySettings === null) {
            $this->defaultQuerySettings = $this->objectManager->get(QuerySettingsInterface::class);
        }
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * @param $content
     * @param $type
     * @return array|QueryResultInterface
     */
    public function findByContentAndType($content, $type)
    {
        $query = $this->createQuery();
        $dependencies = $query->matching(
            $query->logicalAnd([$query->equals('content', $content), $query->equals('dependency_type', $type)])
        )->execute();
        return $dependencies;
    }
}
