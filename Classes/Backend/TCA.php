<?php
namespace MichielRoos\H5p\Backend;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TCA
{
    /**
     * Get config setting title
     *
     * @param $parameters
     * @param $parentObject
     * @throws \Exception
     */
    public function getConfigSettingTitle(&$parameters, $parentObject): void
    {
        $row = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);
        if ($row['config_key'] === 'content_type_cache_updated_at') {
            $date = \DateTime::createFromFormat('U', (int)$row['config_value']);
            $parameters['title'] = sprintf(
                '%s: %s',
                $row['config_key'],
                $date->format($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
            );
        } else {
            $parameters['title'] = sprintf(
                '%s: %s',
                $row['config_key'],
                $row['config_value'] ?? ''
            );
        }
    }

    /**
     * Get library title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getLibraryTitle(&$parameters, $parentObject): void
    {
        $row = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        $updatedAt = \DateTime::createFromFormat('U', (int)$row['updated_at']);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d - %s',
            $row['title'],
            $row['machine_name'],
            $row['major_version'],
            $row['minor_version'],
            $row['patch_version'],
            $updatedAt->format($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
        );
    }

    /**
     * Get content title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getContentTitle(&$parameters, $parentObject): void
    {
        $row = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        $updatedAt = \DateTime::createFromFormat('U', $row['updated_at'] ?? 0);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d - %s',
            $parameters['row']['title'],
            $row['title'] ?? '',
            $row['major_version'] ?? '',
            $row['minor_version'] ?? '',
            $row['patch_version'] ?? '',
            $updatedAt->format($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
        );
    }

    /**
     * Fetch library record by uid
     *
     * @param $uid
     * @return mixed
     */
    protected function getLibraryByUid($uid): mixed
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_h5p_domain_model_library');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $libraryRow = $queryBuilder->select('*')
            ->from('tx_h5p_domain_model_library')->where($queryBuilder->expr()->eq(
            'uid',
            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
        ))->executeQuery()
            ->fetch();
        return $libraryRow;
    }

    /**
     * Get content result title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getContentResultTitle(&$parameters, $parentObject): void
    {
        $row = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        $parameters['title'] = sprintf(
            '%s, user: %s, score: %d/%d, time: %d s',
            $row['title'],
            $parameters['row']['user'],
            $parameters['row']['score'],
            $parameters['row']['max_score'],
            $parameters['row']['finished'] - $parameters['row']['opened']
        );
    }

    /**
     * Fetch content record by uid
     *
     * @param $uid
     * @return mixed
     */
    protected function getContentByUid($uid): mixed
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_h5p_domain_model_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $contentRow = $queryBuilder->select('*')
            ->from('tx_h5p_domain_model_content')->where($queryBuilder->expr()->eq(
            'uid',
            $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
        ))->executeQuery()
            ->fetch();
        return $contentRow;
    }

    /**
     * Get library dependency title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getLibraryDependencyTitle(&$parameters, $parentObject): void
    {
        $row = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);
        $dependencyRow = $this->getLibraryByUid($row['required_library']);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d -> %s: %s %d.%d.%d',
            $row['title'],
            $row['machine_name'],
            $row['major_version'],
            $row['minor_version'],
            $row['patch_version'],
            $dependencyRow['title'],
            $dependencyRow['machine_name'],
            $dependencyRow['major_version'],
            $dependencyRow['minor_version'],
            $dependencyRow['patch_version']
        );
    }

    /**
     * Get library translation title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getLibraryTranslationTitle(&$parameters, $parentObject): void
    {
        $row = BackendUtility::getRecord($parameters['table'], $parameters['row']['uid']);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d - %s',
            $row['title'],
            $row['machine_name'],
            $row['major_version'],
            $row['minor_version'],
            $row['patch_version'],
            $parameters['row']['language_code']
        );
    }
}
