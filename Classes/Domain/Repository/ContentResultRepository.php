<?php
namespace MichielRoos\H5p\Domain\Repository;

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
        if ($this->defaultQuerySettings === null) {
            $this->defaultQuerySettings = $this->objectManager->get(QuerySettingsInterface::class);
        }
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
        $results = $query->matching(
            $query->logicalAnd([$query->equals('user', $userId), $query->equals('content', $contentId)])
        )->execute();
        if ($results->count()) {
            return $results->getFirst();
        }
        return null;
    }
}
