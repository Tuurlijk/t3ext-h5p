<?php
namespace MichielRoos\H5p\Controller;

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

use MichielRoos\H5p\Domain\Model\Content;
use MichielRoos\H5p\Domain\Model\ContentResult;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use MichielRoos\H5p\Domain\Repository\ContentResultRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Class AjaxController
 */
class AjaxController extends ActionController
{
    /**
     * Content repository
     *
     * @var \MichielRoos\H5p\Domain\Repository\ContentRepository
     */
    protected $contentRepository;

    /**
     * @var string
     */
    private $language;

    /**
     * Finish action
     */
    public function finishAction()
    {
        $user = null;

        $error = [
            'message'    => 'Uanble to save result',
            'errorCode'  => 'error',
            'statusCode' => 200,
            'details'    => 'No user is logged in'
        ];

        if ($GLOBALS['TSFE']->loginUser) {
            $user = $GLOBALS['TSFE']->fe_user->user;
            $postData = GeneralUtility::_POST();

            $contentRepository = $this->objectManager->get(ContentRepository::class);

            $content = $contentRepository->findByUid($postData['contentId']);
            if (!$content instanceof Content) {
                $error['details'] = 'Content not found';
                \H5PCore::ajaxError($error['message'], $error['errorCode'], $error['statusCode'], $error['details']);
                exit;
            }

            $contentResultRepository = $this->objectManager->get(ContentResultRepository::class);

            /** @var ContentResult $existingContentResult */
            $existingContentResult = $contentResultRepository->findOneByUserAndContentId($user['uid'], $postData['contentId']);
            if ($existingContentResult) {
                $existingContentResult->setScore($postData['score']);
                $existingContentResult->setMaxScore($postData['maxScore']);
                $existingContentResult->setOpened($postData['opened']);
                $existingContentResult->setFinished($postData['finished']);
                $existingContentResult->setTime($postData['time']);
                $contentResultRepository->update($existingContentResult);
            } else {
                $contentResult = new ContentResult((int)$postData['contentId'], (int)$user['uid'], (int)$postData['score'], (int)$postData['maxScore'], (int)$postData['opened'], (int)$postData['finished'], (int)$postData['time']);
                $contentResultRepository->add($contentResult);
            }
            $persistenceManager = $this->objectManager->get(PersistenceManager::class);
            $persistenceManager->persistAll();
            \H5PCore::ajaxSuccess();
            exit;
        }
        \H5PCore::ajaxError($error['message'], $error['errorCode'], $error['statusCode'], $error['details']);
        exit;
    }

    /**
     * Finish action
     */
    public function contentUserDataAction()
    {
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
