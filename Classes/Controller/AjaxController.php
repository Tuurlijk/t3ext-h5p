<?php
namespace MichielRoos\H5p\Controller;

use TYPO3\CMS\Core\Context\Context;
use Psr\Http\Message\ResponseInterface;
use MichielRoos\H5p\Domain\Model\Content;
use MichielRoos\H5p\Domain\Model\ContentResult;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use MichielRoos\H5p\Domain\Repository\ContentResultRepository;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository;
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
     * @var ContentRepository
     */
    protected ContentRepository $contentRepository;

    /**
     * @var string
     */
    private $language;

    /**
     * Finish action
     */
    public function finishAction(): void
    {
        $user = null;

        $error = [
            'message'    => 'Uanble to save result',
            'errorCode'  => 'error',
            'statusCode' => 200,
            'details'    => 'No user is logged in'
        ];

        if (GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn')) {
            $user = $GLOBALS['TSFE']->fe_user->user;
            $postData = GeneralUtility::_POST();
            if (!array_key_exists('time', $postData)) {
                $postData['time'] = 0;
            }

            $contentRepository = $this->objectManager->get(ContentRepository::class);

            $content = $contentRepository->findByUid($postData['contentId']);
            if (!$content instanceof Content) {
                $error['details'] = 'Content not found';
                \H5PCore::ajaxError($error['message'], $error['errorCode'], $error['statusCode'], $error['details']);
                exit;
            }

            $frontendUserRepository = $this->objectManager->get(FrontendUserRepository::class);
            $frontendUser = $frontendUserRepository->findByUid((int)$user['uid']);

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
                $contentResult = new ContentResult($content, $frontendUser, (int)$postData['score'], (int)$postData['maxScore'], (int)$postData['opened'], (int)$postData['finished'], (int)$postData['time']);
                $contentResult->setPid($GLOBALS['TSFE']->id);
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
    public function contentUserDataAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return GeneralUtility::makeInstance(LanguageService::class);
    }
}
