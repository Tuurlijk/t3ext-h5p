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

use Devlog\Devlog\Domain\Repository\EntryRepository;
use H5peditor;
use H5PEditorAjax;
use MichielRoos\H5p\Adapter\Core\CoreFactory;
use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Adapter\Editor\EditorAjax;
use MichielRoos\H5p\Adapter\Editor\EditorStorage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class EditorController extends ActionController
{
    /**
     * @var H5PEditorAjax
     */
    protected $h5pAjaxEditor;

    /**
     * @var EntryRepository
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
     *
     */
    public function initializeAction()
    {
        $this->language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;

        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage();
        $this->h5pFramework = GeneralUtility::makeInstance(Framework::class, $storage);
        $this->h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $this->h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $this->h5pFramework, $this->h5pFileStorage, 'en');
        $editorAjax = GeneralUtility::makeInstance(EditorAjax::class);
        $editorStorage = GeneralUtility::makeInstance(EditorStorage::class);
        $this->h5pEditor = GeneralUtility::makeInstance(H5PEditor::class, $this->h5pCore, $editorStorage, $editorAjax);
        $this->h5pAjaxEditor = GeneralUtility::makeInstance(H5PEditorAjax::class, $this->h5pCore, $this->h5pEditor, $editorStorage);
    }

    /**
     * Performs initializations of certain objects during calls in an AJAX context.
     *
     * In this particular context, the Extbase bootstrapping does not occur.
     * Some objects must be instantiated "manually".
     *
     * @return void
     */
    protected function initializeForAjaxAction()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->entryRepository = $this->objectManager->get(EntryRepository::class);
    }

    /**
     * Returns the list of all log entries, in JSON format.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function defaultAction(ServerRequestInterface $request, ResponseInterface $response)
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
        $data = '{message: "Action not yet implemented! - typo3conf/ext/h5p/Classes/Backend/EditorController.php:114"}';

        switch ($action) {
            case \H5PEditorEndpoints::FILES:
                $token = $parameters['token'] ?: 'dummy';
                $contentId = $parameters['contentId'];
                $this->h5pAjaxEditor->action(\H5PEditorEndpoints::FILES, $token, $contentId);
                exit;
                break;
            case \H5PEditorEndpoints::CONTENT_TYPE_CACHE:
                $this->h5pAjaxEditor->action(\H5PEditorEndpoints::CONTENT_TYPE_CACHE);
                exit;
                break;

                // Why h5P why . . .
            case 'libraries':
                $machineName = $parameters['machineName'] ?: '';

                if ($machineName === '') {
                    $this->h5pAjaxEditor->action(\H5PEditorEndpoints::LIBRARIES);
                } else {
                    $majorVersion = $parameters['majorVersion'] ?: '';
                    $minorVersion = $parameters['minorVersion'] ?: '';
                    $languageCode = $parameters['language'] ?: 'en';
                    $prefix = $parameters['prefix'] ?: '';
                    $fileDir = $parameters['fileDir'] ?: '';
                    $defaultLanguage = 'en';
                    $this->h5pAjaxEditor->action(\H5PEditorEndpoints::SINGLE_LIBRARY, $machineName, $majorVersion, $minorVersion, $languageCode, $prefix, $fileDir, $defaultLanguage);
                }
                exit;
                break;
            case \H5PEditorEndpoints::LIBRARY_INSTALL:
                $id = $parameters['id'];
                $token = $parameters['token'] ?: 'dummy';
                $this->h5pAjaxEditor->action(\H5PEditorEndpoints::LIBRARY_INSTALL, $token, $id);
                exit;
                break;
            case \H5PEditorEndpoints::LIBRARY_UPLOAD:
                $contentId = $parameters['contentId'];
                $uploadPath = $_FILES['h5p']['tmp_name'];
                $token = $parameters['token'] ?: 'dummy';
                $this->h5pAjaxEditor->action(\H5PEditorEndpoints::LIBRARY_UPLOAD, $token, $uploadPath, $contentId);
                exit;
                break;
            default;
        }
        // Send the response
        $response->getBody()->write($data);
        return $response;
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
