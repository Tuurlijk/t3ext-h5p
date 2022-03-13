<?php
namespace MichielRoos\H5p\Backend;

use H5peditor;
use H5PEditorAjax;
use H5PEditorEndpoints;
use MichielRoos\H5p\Adapter\Core\CoreFactory;
use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Adapter\Editor\EditorAjax;
use MichielRoos\H5p\Adapter\Editor\EditorStorage;
use MichielRoos\H5p\Domain\Repository\ContentTypeCacheEntryRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class EditorController
 * @package MichielRoos\H5p\Backend
 */
class EditorController extends ActionController implements SingletonInterface
{
    /**
     * @var H5PEditorAjax
     */
    protected $h5pAjaxEditor;

    /**
     * @var ContentTypeCacheEntryRepository
     */
    protected $entryRepository;

    /**
     * @var CoreFactory|object
     */
    private $h5pCore;

    /**
     * @var Framework|object
     */
    private $h5pFramework;

    /**
     * @var string
     */
    private $language;

    /**
     * @var FileStorage|object
     */
    private $h5pFileStorage;

    /**
     * @var H5peditor|object
     */
    private $h5pEditor;

    /**
     * Catch all editor action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function defaultAction(ServerRequestInterface $request) :ResponseInterface
    {
        if ($this->h5pAjaxEditor === null) {
            $this->initializeAction();
        }

        $parameters = $request->getQueryParams();
        $prefix = 'h5p_';
        $action = $parameters['action'] ?: 'default';
        if (substr($action, 0, strlen($prefix)) == $prefix) {
            $action = substr($action, strlen($prefix));
        }

        switch ($action) {
            case H5PEditorEndpoints::FILES:
                $token = $parameters['token'] ?: 'dummy';
                $requestBody =  $request->getParsedBody();
                $contentId = $requestBody['contentId'];
                $this->h5pAjaxEditor->action(H5PEditorEndpoints::FILES, $token, $contentId);
                exit;
                break;
            case H5PEditorEndpoints::CONTENT_TYPE_CACHE:
                $this->h5pAjaxEditor->action(H5PEditorEndpoints::CONTENT_TYPE_CACHE);
                exit;
                break;

            // Why H5P why . . .
            case 'libraries':
                $machineName = $parameters['machineName'] ?: '';

                if ($machineName === '') {
                    $this->h5pAjaxEditor->action(H5PEditorEndpoints::LIBRARIES);
                } else {
                    $majorVersion = $parameters['majorVersion'] ?: '';
                    $minorVersion = $parameters['minorVersion'] ?: '';
                    $languageCode = $parameters['language'] ?: $this->language;
                    $prefix = $parameters['prefix'] ?: '/fileadmin/h5p';
                    $fileDir = $parameters['fileDir'] ?: '';
                    $defaultLanguage = 'en';
                    $this->h5pAjaxEditor->action(H5PEditorEndpoints::SINGLE_LIBRARY, $machineName, $majorVersion, $minorVersion, $languageCode, $prefix, $fileDir, $defaultLanguage);
                }
                exit;
                break;
            case H5PEditorEndpoints::LIBRARY_INSTALL:
                $id = $parameters['id'];
                $token = $parameters['token'] ?: 'dummy';
                $this->h5pAjaxEditor->action(H5PEditorEndpoints::LIBRARY_INSTALL, $token, $id);
                exit;
                break;
            case H5PEditorEndpoints::LIBRARY_UPLOAD:
                $contentId = $parameters['contentId'];
                $uploadPath = $_FILES['h5p']['tmp_name'];
                $token = $parameters['token'] ?: 'dummy';
                $this->h5pAjaxEditor->action(H5PEditorEndpoints::LIBRARY_UPLOAD, $token, $uploadPath, $contentId);
                exit;
                break;
            case H5PEditorEndpoints::FILTER:
                $token = $parameters['token'] ?: 'dummy';
                $libraryParameters = GeneralUtility::_POST('libraryParameters');
                $this->h5pAjaxEditor->action(H5PEditorEndpoints::FILTER, $token, $libraryParameters);
                exit;
                break;
            default;
        }

        // Send the response
        return (new JsonResponse())->setPayload(['message' =>  sprintf("Action \'%s\' not yet implemented! %s %s", $action, __METHOD__, __LINE__)]);
    }

    /**
     *
     */
    public function initializeAction()
    {
        $this->language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage = $resourceFactory->getDefaultStorage();
        $this->h5pFramework = GeneralUtility::makeInstance(Framework::class, $storage);
        $this->h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $this->h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $this->h5pFramework, $this->h5pFileStorage, '', $this->language);
        $editorAjax = GeneralUtility::makeInstance(EditorAjax::class);
        $editorStorage = GeneralUtility::makeInstance(EditorStorage::class);
        $this->h5pEditor = GeneralUtility::makeInstance(H5peditor::class, $this->h5pCore, $editorStorage, $editorAjax);
        $this->h5pAjaxEditor = GeneralUtility::makeInstance(H5PEditorAjax::class, $this->h5pCore, $this->h5pEditor, $editorStorage);
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        $languageService = GeneralUtility::makeInstance(LanguageService::class);
        $languageService->init($GLOBALS['BE_USER']->uc['lang']);
        return $languageService;
    }
}
