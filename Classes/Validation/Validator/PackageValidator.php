<?php
namespace MichielRoos\H5p\Validation\Validator;

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

use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Domain\Model\FileReference;
use MichielRoos\H5p\Adapter\Core\CoreFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Class PackageValidator
 */
class PackageValidator extends AbstractValidator
{
    /**
     * Validates the given package
     *
     * @param FileReference $package
     * @return bool
     */
    protected function isValid($package)
    {
        $storage = $package->getOriginalResource()->getStorage();
        $h5pFramewok    = GeneralUtility::makeInstance(Framework::class, $storage);
        $h5pFramewok->setPackageFile($package);
        $h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $h5pCore        = GeneralUtility::makeInstance(CoreFactory::class, $h5pFramewok, $h5pFileStorage, '');
        $validator      = GeneralUtility::makeInstance(\H5PValidator::class, $h5pFramewok, $h5pCore);

        $success = $validator->isValidPackage();

        if (!$success) {
            /** @var Framework $framework */
            $framework = $validator->h5pF;
            $errorMessages = $framework->getMessages('error');
            foreach ($errorMessages as $code => $errorMessage) {
                $this->addError($errorMessage, $code);
            }
            unlink($validator->h5pF->getUploadedH5pPath());
            return false;
        }

        return $success;
    }
}
