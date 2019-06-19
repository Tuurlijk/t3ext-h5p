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

use H5P_Plugin;
use H5PContentValidator;
use H5PCore;
use H5peditor;
use MichielRoos\H5p\Adapter\Core\CoreFactory;
use MichielRoos\H5p\Adapter\Core\FileStorage;
use MichielRoos\H5p\Adapter\Core\Framework;
use MichielRoos\H5p\Adapter\Editor\EditorAjax;
use MichielRoos\H5p\Adapter\Editor\EditorStorage;
use MichielRoos\H5p\Domain\Model\Content;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use MichielRoos\H5p\Domain\Repository\LibraryRepository;
use MichielRoos\H5p\Property\TypeConverter\UploadedFileReferenceConverter;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;

/**
 * Module 'H5P' for the 'h5p' extension.
 */
class H5pModuleController extends ActionController
{
    /**
     * @var string
     */
    public $perms_clause;
    /**
     * @var string
     */
    protected $relativePath;
    /**
     * @var array
     */
    protected $pageRecord = [];

    /**
     * @var bool
     */
    protected $isAccessibleForCurrentUser = false;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * BackendTemplateView Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var FileStorage|object
     */
    private $h5pFileStorage;

    /**
     * @var CoreFactory|object
     */
    private $h5pCore;

    /**
     * @var Framework|object
     */
    private $h5pFramework;

    /**
     * @var H5PContentValidator|object
     */
    private $h5pContentValidator;

    /**
     * @var string
     */
    private $language;

    /**
     * @var H5peditor|object
     */
    private $h5pEditor;

    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     */
    private $pageRenderer;

    /**
     * Initializes the Module
     *
     * @return void
     */
    public function initializeAction()
    {
        $this->id = (int)GeneralUtility::_GP('id');
        $backendUser = $this->getBackendUser();
        $this->perms_clause = $backendUser->getPagePermsClause(1);
        $this->pageRecord = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->isAccessibleForCurrentUser = ($this->id && is_array($this->pageRecord)) || (!$this->id && $this->isCurrentUserAdmin());

        // don't access in workspace
        if ($backendUser->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }

        // read configuration
        $modTS = $backendUser->getTSConfig('mod.recycler');
        if ($this->isCurrentUserAdmin()) {
            $this->allowDelete = true;
        } else {
            $this->allowDelete = (bool)$modTS['properties']['allowDelete'];
        }

        if (isset($modTS['properties']['recordsPageLimit']) && (int)$modTS['properties']['recordsPageLimit'] > 0) {
            $this->recordsPageLimit = (int)$modTS['properties']['recordsPageLimit'];
        }

        $this->language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;

        $resourceFactory = ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage();
        $this->h5pFramework = GeneralUtility::makeInstance(Framework::class, $storage);
        $this->h5pFileStorage = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $this->h5pCore = GeneralUtility::makeInstance(CoreFactory::class, $this->h5pFramework, $this->h5pFileStorage, $this->language);
        $this->h5pContentValidator = GeneralUtility::makeInstance(H5PContentValidator::class, $this->h5pFramework, $this->h5pCore);
        $editorAjax = GeneralUtility::makeInstance(EditorAjax::class);
        $editorStorage = GeneralUtility::makeInstance(EditorStorage::class);
        $this->h5pEditor = GeneralUtility::makeInstance(H5peditor::class, $this->h5pCore, $editorStorage, $editorAjax);
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Determines whether the current user is admin.
     *
     * @return bool Whether the current user is admin
     */
    protected function isCurrentUserAdmin()
    {
        return (bool)$this->getBackendUser()->user['admin'];
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

    /**
     * Initialize the view
     *
     * @param ViewInterface $view The view
     * @return void
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    public function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $this->registerDocheaderButtons();
        $this->generateMenu();
        $this->prepareStorage();
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars = ['id', 'M', $modulePrefix];
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);

        if (in_array($this->request->getControllerActionName(), ['index', 'show'])) {
            $title = $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.new');
            $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $addUserButton = $buttonBar->makeLinkButton()
                ->setHref($this->getHref('H5pModule', 'new'))
                ->setTitle($title)
                ->setIcon($icon);
            $buttonBar->addButton($addUserButton, ButtonBar::BUTTON_POSITION_LEFT);
        }

        if (in_array($this->request->getControllerActionName(), ['show'])) {
            $title = $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.edit');
            $icon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-open', Icon::SIZE_SMALL);
            $addUserButton = $buttonBar->makeLinkButton()
                ->setHref($this->getHref('H5pModule', 'edit', ['contentId' => $this->request->getArgument('contentId')]))
                ->setTitle($title)
                ->setIcon($icon);
            $buttonBar->addButton($addUserButton, ButtonBar::BUTTON_POSITION_LEFT);
        }
    }

    /**
     * Generates the action menu
     */
    protected function generateMenu()
    {
        $menuItems = [
            'choose'     => [
                'controller' => 'H5pModule',
                'action'     => 'index',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.choose')
            ],
            'index'     => [
                'controller' => 'H5pModule',
                'action'     => 'index',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.index')
            ],
            'new'       => [
                'controller' => 'H5pModule',
                'action'     => 'new',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.new')
            ],
            'libraries' => [
                'controller' => 'H5pModule',
                'action'     => 'libraries',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.libraries')
            ],
        ];
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('IndexedSearchModuleMenu');

        foreach ($menuItems as $menuItemConfig) {
            $isActive = $this->request->getControllerActionName() === $menuItemConfig['action'];
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItemConfig['label'])
                ->setHref($this->getHref($menuItemConfig['controller'], $menuItemConfig['action']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Creates te URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);
        return $uriBuilder->reset()->uriFor($action, $parameters, $controller);
    }

    /**
     * Ensure base directories exist
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException
     * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException
     */
    protected function prepareStorage()
    {
        $resourceFactory = ResourceFactory::getInstance();
        $storage = $resourceFactory->getDefaultStorage();
        $basePath = 'h5p';
        foreach (['cachedassets', 'content', 'editor/images', 'exports', 'libraries', 'packages'] as $name) {
            $folder = GeneralUtility::makeInstance(Folder::class, $storage, $basePath . DIRECTORY_SEPARATOR . $name, $name);
            if (!$storage->hasFolder($folder->getIdentifier())) {
                $storage->createFolder($basePath . DIRECTORY_SEPARATOR . $name, null, true);
            }
        }
    }

    /**
     * Shows a list of h5p content
     *
     * @return void
     */
    public function indexAction()
    {
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabelFile('EXT:h5p/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $contentRepository = $this->objectManager->get(ContentRepository::class);
        $content = $contentRepository->findAll();

        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('id', $this->id);
        $this->view->assign('h5pContent', $content);
    }

    /**
     * Renders the available libraries
     *
     * @return void
     */
    public function librariesAction()
    {
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabelFile('EXT:h5p/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }
        $libraryRepository = $this->objectManager->get(LibraryRepository::class);
        $libraries = $libraryRepository->findAll();

        $this->view->assign('dateFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']);
        $this->view->assign('timeFormat', $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']);
        $this->view->assign('libraries', $libraries);
    }

    /**
     * Create action
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function createAction()
    {
        // Keep track of the old library and params
        $oldLibrary = NULL;
        $oldParams = NULL;
        $content = [
            'disable' => H5PCore::DISABLE_NONE
        ];

        // Get library
        $content['library'] = H5PCore::libraryFromString($this->request->getArgument('library'));
        if (!$content['library']) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid library.');
            $this->forward('new');
        }
        if ($this->h5pCore->h5pF->libraryHasUpgrade($content['library'])) {
            // We do not allow storing old content due to security concerns
            $this->h5pCore->h5pF->setErrorMessage('Something unexpected happened. We were unable to save this content.');
            $this->addFlashMessage('Something unexpected happened. We were unable to save this content.');
            $this->forward('new');
        }

        // Check if library exists.
        $content['library']['libraryId'] = $this->h5pCore->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']);
        if (!$content['library']['libraryId']) {
            $this->h5pCore->h5pF->setErrorMessage('No such library.');
            $this->addFlashMessage('No such library.');
            $this->forward('new');
        }

        // Check parameters
        $content['params'] = $this->request->getArgument('parameters');
        if ($content['params'] === NULL) {
            return FALSE;
        }
        $params = json_decode($content['params']);
        if ($params === NULL) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid parameters.');
            $this->addFlashMessage('Invalid parameters.');
            $this->forward('new');
        }

        $content['params'] = json_encode($params->params);
        $content['metadata'] = $params->metadata;

        // Trim title and check length
        $trimmed_title = empty($content['metadata']->title) ? '' : trim($content['metadata']->title);
        if ($trimmed_title === '') {
            $this->addFlashMessage('Missing title');
            $this->forward('new');
        }

        if (strlen($trimmed_title) > 255) {
            $this->addFlashMessage('Title is too long. Must be 256 letters or shorter.', '', FlashMessage::ERROR);
            $this->forward('new');
        }

        // Set disabled features
        $this->get_disabled_content_features($this->h5pCore, $content);

        try {
            // Save new content
            $content['id'] = $this->h5pCore->saveContent($content);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), $e->getCode(), FlashMessage::ERROR);
            $this->forward('new');
        }

        // Move images and find all content dependencies
        $this->h5pEditor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);

        // Used to generate the slug
        $content['title'] = $content['metadata']->title;

        // Store content dependencies
        $this->h5pCore->filterParameters($content);

        $this->addFlashMessage('Content stored successfully.');
        $this->forward('show', 'H5pModule', 'h5p', ['contentId' => $content['id']]);
    }

    /**
     * Update action
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    public function updateAction()
    {

        // Content id
        $contentId = $this->request->getArgument('contentId');

        // Keep track of the old library and params
        $oldLibrary = NULL;
        $oldParams = NULL;
        $content = [
            'disable' => H5PCore::DISABLE_NONE
        ];

        // Get library
        $content['library'] = H5PCore::libraryFromString($this->request->getArgument('library'));
        if (!$content['library']) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid library.');
            $this->forward('new');
        }
        if ($this->h5pCore->h5pF->libraryHasUpgrade($content['library'])) {
            // We do not allow storing old content due to security concerns
            $this->h5pCore->h5pF->setErrorMessage('Something unexpected happened. We were unable to save this content.');
            $this->addFlashMessage('Something unexpected happened. We were unable to save this content.');
            $this->forward('new');
        }

        // Check if library exists.
        $content['library']['libraryId'] = $this->h5pCore->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'], $content['library']['minorVersion']);
        if (!$content['library']['libraryId']) {
            $this->h5pCore->h5pF->setErrorMessage('No such library.');
            $this->addFlashMessage('No such library.');
            $this->forward('new');
        }

        // Check parameters
        $content['params'] = $this->request->getArgument('parameters');
        if ($content['params'] === NULL) {
            return FALSE;
        }
        $params = json_decode($content['params']);
        if ($params === NULL) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid parameters.');
            $this->addFlashMessage('Invalid parameters.');
            $this->forward('new');
        }

        $content['params'] = json_encode($params->params);
        $content['metadata'] = $params->metadata;

        // Trim title and check length
        $trimmed_title = empty($content['metadata']->title) ? '' : trim($content['metadata']->title);
        if ($trimmed_title === '') {
            $this->addFlashMessage('Missing title');
            $this->forward('new');
        }

        if (strlen($trimmed_title) > 255) {
            $this->addFlashMessage('Title is too long. Must be 256 letters or shorter.', '', FlashMessage::ERROR);
            $this->forward('new');
        }

        // Set disabled features
        $this->get_disabled_content_features($this->h5pCore, $content);

        try {
            // Save new content
            $content['id'] = $contentId;
            $content['id'] = $this->h5pCore->saveContent($content);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), $e->getCode(), FlashMessage::ERROR);
            $this->forward('new');
        }

        // Move images and find all content dependencies
        $this->h5pEditor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);

        // Used to generate the slug
        $content['title'] = $content['metadata']->title;

        // Store content dependencies
        $this->h5pCore->filterParameters($content);

        $this->addFlashMessage('Content stored successfully.');
        $this->forward('show', 'H5pModule', 'h5p', ['contentId' => $content['id']]);
    }

    /**
     * Extract disabled content features from input post.
     *
     * @param H5PCore $core
     * @param $content
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    private function get_disabled_content_features($core, &$content)
    {
        $set = [
            H5PCore::DISPLAY_OPTION_FRAME     => (bool)$this->request->getArgument('frame'),
            H5PCore::DISPLAY_OPTION_DOWNLOAD  => (bool)$this->request->getArgument('download'),
            H5PCore::DISPLAY_OPTION_EMBED     => (bool)$this->request->getArgument('embed'),
            H5PCore::DISPLAY_OPTION_COPYRIGHT => (bool)$this->request->getArgument('copyright')
        ];
        $content['disable'] = $core->getStorableDisplayOptions($set, $content['disable']);
    }

    /**
     * Edit action
     * @param int $contentId
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function editAction(int $contentId)
    {
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabelFile('EXT:h5p/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $this->view->getModuleTemplate()->getPageRenderer()->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getEditorSettings($this->getCoreSettings())) . ';'
        );

        if ($contentId > 0) {
            $contenRepository = $this->objectManager->get(ContentRepository::class);
            $content = $contenRepository->findByUid($contentId);

            if (!$content instanceof Content) {
                $this->addFlashMessage(sprintf('Content element with id %d not found', $contentId), 'Record not found', FlashMessage::ERROR);
                return;
            }

            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary()->toAssocArray();
            $this->view->assign('content', $content);
            $this->view->assign('library', sprintf('%s %d.%d', $contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']));
            $parameters = json_decode($content->getFiltered(), true);
            $parameters = $this->injectMetadataIntoParameters($parameters, $content);
            $this->view->assign('parameters', json_encode($parameters, true));
        }

        $this->embedEditorScriptsAndStyles();
    }

    /**
     * @param $settings
     * @return mixed
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function getEditorSettings($settings)
    {
        $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');

        $uriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        $relativeExtensionPath = ExtensionManagementUtility::extRelPath('h5p');
        $relativeExtensionPath = str_replace('../typo3conf', '/typo3conf', $relativeExtensionPath);

        $cacheBuster = '?v=' . $this->h5pFramework::$version;

        // Add JavaScript settings
        $settings['editor'] = [
            'filesPath'          => '/fileadmin/h5p/editor',
            'fileIcon'           => [
                'path'   => $url . $relativeExtensionPath . 'Resources/Public/Lib/h5p-editor/images/binary-file.png',
                'width'  => 50,
                'height' => 50,
            ],
            'ajaxPath'           => (string)$uriBuilder->buildUriFromRoute('h5p_editor_action', ['action' => 'h5p_']),
            'libraryUrl'         => $url . $relativeExtensionPath . 'Resources/Public/Lib/h5p-editor/',
            'copyrightSemantics' => $this->h5pContentValidator->getCopyrightSemantics(),
            'metadataSemantics'  => $this->h5pContentValidator->getMetadataSemantics(),
            'assets'             => [],
            'deleteMessage'      => 'Are you sure you wish to delete this content?',
            'apiVersion'         => $this->h5pCore::$coreApi,
            'language'           => $this->language
        ];

        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';
        foreach (H5PCore::$styles as $style) {
            $settings['editor']['assets']['css'][] = $relativeCorePath . $style . $cacheBuster;
        }
        foreach (H5PCore::$scripts as $script) {
            $settings['editor']['assets']['js'][] = $relativeCorePath . $script . $cacheBuster;
        }

        $relativeEditorPath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-editor/';
        foreach (H5peditor::$styles as $style) {
            $settings['editor']['assets']['css'][] = $relativeEditorPath . $style . $cacheBuster;
        }
        foreach (H5peditor::$scripts as $script) {
            if (strpos($script, 'h5peditor-editor') === false) {
                $settings['editor']['assets']['js'][] = $relativeEditorPath . $script . $cacheBuster;
            }
        }

        //        if ($id !== NULL) {
//            $settings['editor']['nodeVersionId'] = $id;
//        }
        return $settings;
    }

    /**
     * Get generic h5p settings
     *
     * @return array;
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function getCoreSettings()
    {
        $backendUser = $this->getBackendUser()->user;

        $uriBuilder = GeneralUtility::makeInstance(BackendUriBuilder::class);
        $relativeExtensionPath = ExtensionManagementUtility::extRelPath('h5p');
        $relativeExtensionPath = str_replace('../typo3conf', '/typo3conf', $relativeExtensionPath);

        $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');

        $cacheBuster = '?v=' . $this->h5pFramework::$version;

        $settings = [
            'baseUrl'            => $url,
            'url'                => '/fileamdin/h5p/',
            'postUserStatistics' => $this->h5pFramework->getOption('track_user') && (bool)$backendUser['uid'],
            'ajax'               => [
                'setFinished'     => (string)$uriBuilder->buildUriFromRoute('h5p_editor_action', ['type' => 'setFinished', 'action' => 'h5p_']),
                'contentUserData' => (string)$uriBuilder->buildUriFromRoute('h5p_editor_action', ['type' => 'contentUserData', 'action' => 'h5p_']),
            ],
            'saveFreq'           => $this->h5pFramework->getOption('save_content_state') ? $this->h5pFramework->getOption('save_content_frequency') : false,
            'siteUrl'            => $url,
            'l10n'               => [
                'H5P' => $this->h5pCore->getLocalization(),
            ],
            'hubIsEnabled'       => (int)$this->h5pFramework->getOption('hub_is_enabled') === 1,
            'reportingIsEnabled' => (int)$this->h5pFramework->getOption('enable_lrs_content_types') === 1,
            'libraryConfig'      => $this->h5pFramework->getLibraryConfig(),
            'crossorigin'        => defined('H5P_CROSSORIGIN') ? H5P_CROSSORIGIN : null,
            'pluginCacheBuster'  => $cacheBuster,
            'libraryUrl'         => $url . $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/js',
            'contents'           => []
        ];

        if ($backendUser['uid']) {
            $settings['user'] = [
                'name' => $backendUser['realName'],
                'mail' => $backendUser['email']
            ];
        }

        $relativeExtensionPath = ExtensionManagementUtility::extRelPath('h5p');
        $relativeExtensionPath = str_replace('../typo3conf', '/typo3conf', $relativeExtensionPath);
        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';
        foreach (H5PCore::$styles as $style) {
            $settings['core']['styles'][] = $relativeCorePath . $style . $cacheBuster;
        }
        foreach (H5PCore::$scripts as $script) {
            $settings['core']['scripts'][] = $relativeCorePath . $script . $cacheBuster;
        }
        $settings['loadedJs'] = [];
        $settings['loadedCss'] = [];

        return $settings;
    }

    /**
     * Embed scripts and styles
     */
    protected function embedEditorScriptsAndStyles()
    {
        $relativeExtensionPath = ExtensionManagementUtility::extRelPath('h5p');
        $relativeExtensionPath = str_replace('../typo3conf', '/typo3conf', $relativeExtensionPath);
        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';
        $relativeEditorPath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-editor/';
        $relativeScriptPath = $relativeExtensionPath . 'Resources/Public/JavaScript/';

        $this->view->getModuleTemplate()->getPageRenderer()->addRequireJsConfiguration([
                'paths' => [
                    'h5p-jquery'              => $relativeCorePath . 'js/jquery',
                    'h5p'                     => $relativeCorePath . 'js/h5p',
                    'h5p-event-dispatcher'    => $relativeCorePath . 'js/h5p-event-dispatcher',
                    'h5p-x-api-event'         => $relativeCorePath . 'js/h5p-x-api-event',
                    'h5p-x-api'               => $relativeCorePath . 'js/h5p-x-api',
                    'h5p-content-type'        => $relativeCorePath . 'js/h5p-content-type',
                    'h5p-confirmation-dialog' => $relativeCorePath . 'js/h5p-confirmation-dialog',
                    'h5p-action-bar'          => $relativeCorePath . 'js/h5p-action-bar',
                    'h5peditor-editor'        => $relativeEditorPath . 'scripts/h5peditor-editor',
                    'h5peditor-init'          => $relativeEditorPath . 'scripts/h5peditor-init',
                    'h5peditor-editor-en'     => $relativeEditorPath . 'language/en',
                    'h5peditor-editor-nl'     => $relativeEditorPath . 'language/nl',
                    'h5p-display-options'     => $relativeCorePath . 'js/h5p-display-options',
                    'TYPO3/CMS/H5p/editor'    => $relativeScriptPath . 'editor',
                ],
                'shim'  => [
                    'h5p-jquery'              => [
                        'exports' => 'h5p-jquery'
                    ],
                    'h5peditor-editor'        => [
                        'deps'    => ['h5p-action-bar'],
                        'exports' => 'h5peditor-editor'
                    ],
                    'h5peditor-init'          => [
                        'deps'    => ['h5peditor-editor', 'h5peditor-editor-en', 'h5p-display-options'],
                        'exports' => 'h5peditor-init'
                    ],
                    'h5p-content-type'        => [
                        'deps'    => ['h5p-x-api'],
                        'exports' => 'h5p-content-type'
                    ],
                    'h5p-confirmation-dialog' => [
                        'deps'    => ['h5p-content-type'],
                        'exports' => 'h5p-confirmation-dialog'
                    ],
                    'h5p-event-dispatcher'    => [
                        'deps'    => ['h5p'],
                        'exports' => 'h5p-event-dispatcher'
                    ],
                    'h5p-display-options'     => [
                        'deps'    => ['h5peditor-editor'],
                        'exports' => 'h5p-display-options'
                    ],
                    'h5p-x-api-event'         => [
                        'deps'    => ['h5p-event-dispatcher'],
                        'exports' => 'h5p-x-api-event'
                    ],
                    'h5p-x-api'               => [
                        'deps'    => ['h5p-x-api-event'],
                        'exports' => 'h5p-x-api'
                    ],
                    'h5peditor-editor-en'     => [
                        'deps'    => ['h5peditor-editor'],
                        'exports' => 'h5peditor-editor-en'
                    ],
                    'h5peditor-editor-nl'     => [
                        'deps'    => ['h5peditor-editor'],
                        'exports' => 'h5peditor-editor-nl'
                    ],
                    'h5p-action-bar'          => [
                        'deps'    => ['h5p-confirmation-dialog'],
                        'exports' => 'h5p-action-bar'
                    ],
                    'h5p'                     => [
                        'deps'    => ['h5p-jquery'],
                        'exports' => 'h5p'
                    ],
                    'TYPO3/CMS/H5p/editor'    => [
                        'deps'    => ['h5peditor-init'],
                        'exports' => 'TYPO3/CMS/H5p/editor'
                    ],
                ],
            ]
        );

        foreach (H5PCore::$styles as $style) {
            $this->view->getModuleTemplate()->getPageRenderer()->addCssFile($relativeCorePath . $style);
        }
        foreach (H5peditor::$styles as $style) {
            $this->view->getModuleTemplate()->getPageRenderer()->addCssFile($relativeEditorPath . $style);
        }
        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/H5p/editor');
    }

    /**
     * New action / upload form
     * @param int $contentId
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public function newAction(int $contentId = 0)
    {
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabelFile('EXT:h5p/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $this->pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();

        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getEditorSettings($this->getCoreSettings())) . ';'
        );

        if ($contentId > 0) {
            $contenRepository = $this->objectManager->get(ContentRepository::class);
            $content = $contenRepository->findByUid($contentId);

            if (!$content instanceof Content) {
                $this->addFlashMessage(sprintf('Content element with id %d not found', $contentId), 'Record not found', FlashMessage::ERROR);
                return;
            }

            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary()->toAssocArray();
            $this->view->assign('library', sprintf('%s %d.%d', $contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']));
            $this->view->assign('parameters', $content->getFiltered());
        }

        $this->embedEditorScriptsAndStyles();
    }

    /**
     * Show action
     * @param int $contentId
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function showAction(int $contentId)
    {
        $this->view->getModuleTemplate()->getPageRenderer()->addInlineLanguageLabelFile('EXT:h5p/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        $this->pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();

        $contenRepository = $this->objectManager->get(ContentRepository::class);
        $content = $contenRepository->findByUid($contentId);

        if (!$content instanceof Content) {
            $this->addFlashMessage(sprintf('Content element with id %d not found', $contentId), 'Record not found', FlashMessage::ERROR);
        }

        $cacheBuster = '?v=' . $this->h5pFramework::$version;

        $relativeExtensionPath = ExtensionManagementUtility::extRelPath('h5p');
        $relativeExtensionPath = str_replace('../typo3conf', '/typo3conf', $relativeExtensionPath);
        $relativeCorePath = $relativeExtensionPath . 'Resources/Public/Lib/h5p-core/';

        foreach (\H5PCore::$scripts as $script) {
            $this->pageRenderer->addJsFile($relativeCorePath . $script . $cacheBuster, 'text/javascript', false, false, '');
        }
        foreach (\H5PCore::$styles as $style) {
            $this->pageRenderer->addCssFile($relativeCorePath . $style . $cacheBuster);
        }

        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getCoreSettings()) . ';'
        );

        if ($content->getEmbedType() === 'iframe') {
            $contentSettings = $this->getContentSettings($content);
            $contentSettings['displayOptions'] = [];
            $contentSettings['displayOptions']['frame'] = true;
            $contentSettings['displayOptions']['export'] = false;
            $contentSettings['displayOptions']['embed'] = false;
            $contentSettings['displayOptions']['copyright'] = false;
            $contentSettings['displayOptions']['icon'] = true;
            $this->pageRenderer->addJsInlineCode(
                'H5PIntegration contents',
                'H5PIntegration.contents[\'cid-' . $content->getUid() . '\'] = ' . json_encode($contentSettings) . ';'
            );
        } else {
            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary()->toAssocArray();

            // JS and CSS required by all libraries
            $contentLibraryWithDependencies = $this->h5pCore->loadLibrary($contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']);
            $this->h5pCore->findLibraryDependencies($dependencies, $contentLibraryWithDependencies);
            if (is_array($dependencies)) {
                $dependencies = $this->h5pCore->orderDependenciesByWeight($dependencies);
                foreach ($dependencies as $key => $dependency) {
                    if (strpos($key, 'preloaded-') !== 0) {
                        continue;
                    }
                    $this->loadJsAndCss($dependency['library']);
                }
            }

            // JS and CSS required by the content
            $contentDependencies = $this->h5pFramework->loadContentDependencies($content->getUid(), 'preloaded');
            foreach ($contentDependencies as $dependency) {
                $this->loadJsAndCss($dependency);
            }

            // JS and CSS required by the main Library of the content
            $this->loadJsAndCss($contentLibrary);
        }

        $this->view->assign('content', $content);
    }

    /**
     * Get content settings
     *
     * @return array;
     */
    public function getContentSettings(Content $content)
    {
        $settings = [
            'url'            => '/fileamdin/h5p/',
            'library'        => sprintf(
                '%s %d.%d.%d',
                $content->getLibrary()->getMachineName(),
                $content->getLibrary()->getMajorVersion(),
                $content->getLibrary()->getMinorVersion(),
                $content->getLibrary()->getPatchVersion()
            ),
            'jsonContent'    => $content->getFiltered(),
            'fullScreen'     => false,
            'exportUrl'      => '/path/to/download.h5p',
            'embedCode'      => '',
            'resizeCode'     => '',
            'mainId'         => $content->getUid(),
            'title'          => $content->getTitle(),
            'displayOptions' => [
                'frame'     => false,
                'export'    => false,
                'embed'     => false,
                'copyright' => false,
                'icon'      => false
            ],
            'metadata'       => [
                'title' => $content->getTitle()
            ]
        ];

        if ($content->getEmbedType() === 'iframe') {
            $contentLibrary = $content->getLibrary()->toAssocArray();
            $dependencyLibrary = $this->h5pCore->loadLibrary($contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']);
            $this->h5pCore->findLibraryDependencies($dependencies, $dependencyLibrary);
            if (is_array($dependencies)) {
                $dependencies = $this->h5pCore->orderDependenciesByWeight($dependencies);
                foreach ($dependencies as $key => $dependency) {
                    if (strpos($key, 'preloaded-') !== 0) {
                        continue;
                    }
                    $this->setJsAndCss($dependency['library'], $settings);
                }
            }

            $contentDependencies = $this->h5pFramework->loadContentDependencies($content->getUid(), 'preloaded');
            foreach ($contentDependencies as $dependency) {
                $this->setJsAndCss($dependency, $settings);
            }

            $this->setJsAndCss($contentLibrary, $settings);
        }

        return $settings;
    }

    /**
     * Set JS and CSS
     * @param array $library
     * @param array $settings
     */
    private function setJsAndCss(array $library, array &$settings)
    {
        $name = $library['machineName'] . '-' . $library['majorVersion'] . '.' . $library['minorVersion'];
        $preloadCss = explode(',', $library['preloadedCss']);
        $preloadJs = explode(',', $library['preloadedJs']);

        if (!array_key_exists('scripts', $settings)) {
            $settings['scripts'] = [];
        }

        if (!array_key_exists('styles', $settings)) {
            $settings['styles'] = [];
        }

        foreach ($preloadJs as $js) {
            $js = trim($js);
            if ($js) {
                $settings['scripts'][] = '/fileadmin/h5p/libraries/' . $name . '/' . $js;
            }
        }
        foreach ($preloadCss as $css) {
            $css = trim($css);
            if ($css) {
                $settings['styles'][] = '/fileadmin/h5p/libraries/' . $name . '/' . $css;
            }
        }
    }

    /**
     * Load JS and CSS
     * @param array $library
     */
    private function loadJsAndCss($library)
    {
        $name = $library['machineName'] . '-' . $library['majorVersion'] . '.' . $library['minorVersion'];
        $preloadCss = explode(',', $library['preloadedCss']);
        $preloadJs = explode(',', $library['preloadedJs']);

        foreach ($preloadJs as $js) {
            $js = trim($js);
            if ($js) {
                $this->pageRenderer->addJsFile('/fileadmin/h5p/libraries/' . $name . '/' . $js, 'text/javascript', false, false, '');
            }
        }
        foreach ($preloadCss as $css) {
            $css = trim($css);
            if ($css) {
                $this->pageRenderer->addCssFile('/fileadmin/h5p/libraries/' . $name . '/' . $css);
            }
        }
    }

    /**
     * Gets data from the session of the current backend user.
     *
     * @param string $identifier The identifier to be used to get the data
     * @param string $default The default date to be used if nothing was found in the session
     * @return string The accordant data in the session of the current backend user
     */
    protected function getDataFromSession($identifier, $default = null)
    {
        $sessionData = &$this->getBackendUser()->uc['tx_h5p'];
        if (isset($sessionData[$identifier]) && $sessionData[$identifier]) {
            $data = $sessionData[$identifier];
        } else {
            $data = $default;
        }
        return $data;
    }

    /**
     * Returns an instance of DocumentTemplate
     *
     * @return \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected function getDocumentTemplate()
    {
        return $GLOBALS['TBE_TEMPLATE'];
    }

    /**
     * Set type converter configuration for Package upload
     * @param string $argumentName
     */
    protected function setTypeConverterConfigurationForPackageUpload($argumentName)
    {
        /** @var PropertyMappingConfiguration $newExampleConfiguration */
        $newExampleConfiguration = $this->arguments[$argumentName]->getPropertyMappingConfiguration();
        $newExampleConfiguration->forProperty('package')
            ->setTypeConverterOptions(
                UploadedFileReferenceConverter::class,
                [
                    UploadedFileReferenceConverter::CONFIGURATION_ALLOWED_FILE_EXTENSIONS => 'h5p',
                    UploadedFileReferenceConverter::CONFIGURATION_UPLOAD_FOLDER           => '1:/h5p/packages/',
                ]
            );
    }

    /**
     * @param array $parameters
     * @param Content $content
     * @return array
     */
    private function injectMetadataIntoParameters(array $parameters, Content $content)
    {

        $metadata = [
            'title'          => $content->getTitle(),
            'authors'        => json_decode($content->getAuthors(), true),
            'source'         => $content->getSource(),
            'yearFrom'       => $content->getYearFrom(),
            'yearTo'         => $content->getYearTo(),
            'license'        => $content->getLicense(),
            'licenseVersion' => $content->getLicenseVersion(),
            'licenseExtras'  => $content->getLicenseExtras(),
            'authorComments' => $content->getAuthorComments(),
            'changes'        => json_decode($content->getChanges(), true)
        ];

        $parameters['metadata'] = $metadata;
        return $parameters;
    }
}
