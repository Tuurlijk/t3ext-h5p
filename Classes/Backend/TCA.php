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
            $parameters['title'] = sprintf(
                '%s: %s',
                $row['config_key'],
                date(DATE_RFC822, (int)$row['config_value'])
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
        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d',
            $row['title'],
            $row['machine_name'],
            $row['major_version'],
            $row['minor_version'],
            $row['patch_version']
        );
    }

    /**
     * Get library dependency title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getContentTitle(&$parameters, $parentObject)
    {
        $libraryRow = $this->getDBHandle()->exec_SELECTgetSingleRow(
            '*',
            'tx_h5p_domain_model_library',
            sprintf('uid=%d', $parameters['row']['library'])
        );

        $parameters['title'] = sprintf(
            '%s - %s %d.%d.%d',
            $parameters['row']['title'],
            $libraryRow['title'],
            $libraryRow['major_version'],
            $libraryRow['minor_version'],
            $libraryRow['patch_version']
        );
    }

    /**
     * Get library dependency title
     *
     * @param $parameters
     * @param $parentObject
     */
    public function getLibraryDependencyTitle(&$parameters, $parentObject)
    {
        $libraryRow = $this->getDBHandle()->exec_SELECTgetSingleRow(
            '*',
            'tx_h5p_domain_model_library',
            sprintf('uid=%d', $parameters['row']['library'])
        );
        $dependencyRow = $this->getDBHandle()->exec_SELECTgetSingleRow(
            '*',
            'tx_h5p_domain_model_library',
            sprintf('uid=%d', $parameters['row']['requiredlibrary'])
        );

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
        $libraryRow = $this->getDBHandle()->exec_SELECTgetSingleRow(
            '*',
            'tx_h5p_domain_model_library',
            sprintf('uid=%d', $parameters['row']['library'])
        );

        $parameters['title'] = sprintf(
            '%s: %s %d.%d.%d - %s',
            $libraryRow['title'],
            $libraryRow['machine_name'],
            $libraryRow['major_version'],
            $libraryRow['minor_version'],
            $libraryRow['patch_version'],
            $parameters['row']['languagecode']
        );
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection $dbHandle
     */
    protected function getDBHandle()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
