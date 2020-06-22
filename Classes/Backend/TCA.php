<?php
namespace MichielRoos\H5p\Backend;
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
    public function getConfigSettingTitle(&$parameters, $parentObject)
    {
        $row = $parameters['row'];
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
                (string)$row['config_value']
            );
        }
    }

    /**
     * Get library title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getLibraryTitle(&$parameters, $parentObject)
    {

        $row = $parameters['row'];

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
    public function getContentTitle(&$parameters, $parentObject)
    {
        $libraryRow = $this->getLibraryByUid($parameters['row']['library']);

        $updatedAt = \DateTime::createFromFormat('U', (int)$libraryRow['updated_at']);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d - %s',
            $parameters['row']['title'],
            $libraryRow['title'],
            $libraryRow['major_version'],
            $libraryRow['minor_version'],
            $libraryRow['patch_version'],
            $updatedAt->format($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'])
        );
    }

    /**
     * Fetch library record by uid
     *
     * @param $uid
     * @return mixed
     */
    protected function getLibraryByUid($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('tx_h5p_domain_model_library');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction::class));
        $libraryRow = $queryBuilder->select('*')
            ->from('tx_h5p_domain_model_library')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return $libraryRow;
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection $dbHandle
     */
    protected function getDBHandle()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Get content result title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getContentResultTitle(&$parameters, $parentObject)
    {
        $contentRow = $this->getContentByUid($parameters['row']['content']);

        $parameters['title'] = sprintf(
            '%s, user: %s, score: %d/%d, time: %d s',
            $contentRow['title'],
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
    protected function getContentByUid($uid)
    {
        $queryBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)->getQueryBuilderForTable('tx_h5p_domain_model_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction::class));
        $contentRow = $queryBuilder->select('*')
            ->from('tx_h5p_domain_model_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return $contentRow;
    }

    /**
     * Get library dependency title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getLibraryDependencyTitle(&$parameters, $parentObject)
    {
        $libraryRow = $this->getLibraryByUid($parameters['row']['library']);
        $dependencyRow = $this->getLibraryByUid($parameters['row']['required_library']);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d -> %s: %s %d.%d.%d',
            $libraryRow['title'],
            $libraryRow['machine_name'],
            $libraryRow['major_version'],
            $libraryRow['minor_version'],
            $libraryRow['patch_version'],
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
    public function getLibraryTranslationTitle(&$parameters, $parentObject)
    {
        $libraryRow = $this->getLibraryByUid($parameters['row']['library']);

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d - %s',
            $libraryRow['title'],
            $libraryRow['machine_name'],
            $libraryRow['major_version'],
            $libraryRow['minor_version'],
            $libraryRow['patch_version'],
            $parameters['row']['language_code']
        );
    }
}
