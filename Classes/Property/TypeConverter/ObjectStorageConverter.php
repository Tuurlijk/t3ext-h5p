<?php
namespace MichielRoos\H5p\Property\TypeConverter;

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

/**
 * Class ObjectStorageConverter
 */
class ObjectStorageConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\ObjectStorageConverter
{
    /**
     * @var string[]
     */
    protected $sourceTypes = ['array'];

    /**
     * Take precedence over the available ObjectStorageConverter
     *
     * @var int
     */
    protected $priority = 20;

    /**
     * Return the source, if it is an array, otherwise an empty array.
     * Filter out empty uploads
     *
     * @param mixed $source
     * @return array
     * @api
     */
    public function getSourceChildPropertiesToBeConverted($source) :array
    {
        $propertiesToConvert = [];
        // TODO: Find a nicer way to throw away empty uploads
        foreach ($source as $propertyName => $propertyValue) {
            if ($this->isUploadType($propertyValue)) {
                if ($propertyValue['error'] !== \UPLOAD_ERR_NO_FILE || isset($propertyValue['submittedFile']['resourcePointer'])) {
                    $propertiesToConvert[$propertyName] = $propertyValue;
                }
            } else {
                $propertiesToConvert[$propertyName] = $propertyValue;
            }
        }

        return $propertiesToConvert;
    }

    /**
     * Check if this is an upload type
     *
     * @param mixed $propertyValue
     * @return bool
     */
    protected function isUploadType($propertyValue)
    {
        return is_array($propertyValue) && isset($propertyValue['tmp_name']) && isset($propertyValue['error']);
    }
}
