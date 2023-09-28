<?php

namespace MichielRoos\H5p\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
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
        if ($this->defaultQuerySettings === null) $this->defaultQuerySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);
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

        $query->matching(
            $query->logicalAnd(
                $query->equals('content', $content),
                $query->equals('dependency_type', $type),
            )
        );

        return $query->execute();
    }
}
