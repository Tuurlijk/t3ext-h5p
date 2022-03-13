<?php
namespace MichielRoos\H5p\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class PageRepository
 */
class PageRepository extends Repository
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
     * Find by uids
     * @param array $uids
     * @return array|QueryResultInterface
     * @throws InvalidQueryException
     */
    public function findByUids(array $uids)
    {
        $query = $this->createQuery();
        $pages = $query->matching(
            $query->in('uid', $uids)
        )->execute();
        return $pages;
    }
}
