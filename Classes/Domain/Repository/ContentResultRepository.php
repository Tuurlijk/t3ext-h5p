<?php

namespace MichielRoos\H5p\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class ContentResultRepository
 */
class ContentResultRepository extends Repository
{
    /**
     * initializes any required object
     */
    public function initializeObject()
    {
        if ($this->defaultQuerySettings === null) $this->defaultQuerySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);
        $this->defaultQuerySettings->setRespectStoragePage(false);
    }

    /**
     * @param int $userId
     * @param int $contentId
     * @return object|null
     */
    public function findOneByUserAndContentId(int $userId, int $contentId)
    {
        $query = $this->createQuery();

        $query->matching(
            $query->logicalAnd(
                $query->equals('user', $userId),
                $query->equals('content', $contentId),
            )
        );

        $query->setLimit(1);

        return $query->execute()->getFirst();
    }
}
