<?php

namespace MichielRoos\H5p\Controller;


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
use MichielRoos\H5p\Domain\Model\Library;
use MichielRoos\H5p\Domain\Repository\ContentRepository;
use MichielRoos\H5p\Domain\Repository\LibraryRepository;
use MichielRoos\H5p\Property\TypeConverter\UploadedFileReferenceConverter;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Module 'H5P' for the 'h5p' extension.
 */
class H5pModuleController
{
    public string $perms_clause;
    protected bool $h5pContentAllowedOnPage = false;
    protected string $relativePath;
    protected array $pageRecord = [];
    protected bool $isAccessibleForCurrentUser = false;
    protected int $id;
    protected int $limit = 20;

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
    private string $language;

    /**
     * @var H5peditor|object
     */
    private $h5pEditor;

    private ModuleTemplate $moduleTemplate;
    private int $itemsPerPage = 50;

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly PageRenderer          $pageRenderer,
        private readonly IconFactory           $iconFactory,
        private readonly BackendUriBuilder     $backendUriBuilder
    )
    {
    }

    /**
     * Initializes the Module
     *
     * @return void
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->setTitle(LocalizationUtility::translate('LLL:EXT:h5p/Resources/Private/Language/BackendModule.xlf:mlang_tabs_tab'));

        $this->id                         = (int)GeneralUtility::_GP('id');
        $backendUser                      = $this->getBackendUser();
        $this->perms_clause               = $backendUser->getPagePermsClause(1);
        $this->pageRecord                 = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        $this->isAccessibleForCurrentUser = ($this->id && is_array($this->pageRecord)) || (!$this->id && $this->isCurrentUserAdmin());

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:h5p/Resources/Private/Language/locallang.xlf');
        if ($this->isAccessibleForCurrentUser) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageRecord);
        }

        // don't access in workspace
        if ($backendUser->workspace !== 0) {
            $this->isAccessibleForCurrentUser = false;
        }

        // Get extension configuration
        $allowContentOnStandardPages = false;
        $extConf                     = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('h5p');
        if (!isset($extConf['onlyAllowRecordsInSysfolders']) || (int)$extConf['onlyAllowRecordsInSysfolders'] === 0) {
            $allowContentOnStandardPages = true;
        }
        $pageIsSysfolder               = (int)$this->pageRecord['doktype'] === 254;
        $this->h5pContentAllowedOnPage = $allowContentOnStandardPages || $pageIsSysfolder;

        $this->language = ($this->getLanguageService()->lang === 'default') ? 'en' : $this->getLanguageService()->lang;

        $resourceFactory           = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage                   = $resourceFactory->getDefaultStorage();
        $this->h5pFramework        = GeneralUtility::makeInstance(Framework::class, $storage);
        $this->h5pFileStorage      = GeneralUtility::makeInstance(FileStorage::class, $storage);
        $this->h5pCore             = GeneralUtility::makeInstance(CoreFactory::class, $this->h5pFramework, $this->h5pFileStorage, $this->language);
        $this->h5pContentValidator = GeneralUtility::makeInstance(H5PContentValidator::class, $this->h5pFramework, $this->h5pCore);
        $editorAjax                = GeneralUtility::makeInstance(EditorAjax::class);
        $editorStorage             = GeneralUtility::makeInstance(EditorStorage::class);
        $this->h5pEditor           = GeneralUtility::makeInstance(H5peditor::class, $this->h5pCore, $editorStorage, $editorAjax);
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Determines whether the current user is admin.
     *
     * @return bool Whether the current user is admin
     */
    protected function isCurrentUserAdmin(): bool
    {
        return (bool)$this->getBackendUser()->user['admin'];
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

    /**
     * Initialize the view
     * @param ViewInterface $view The view
     * @return void
     * @todo v12: Change signature to TYPO3Fluid\Fluid\View\ViewInterface when extbase ViewInterface is dropped.
     *
     */
    public function initializeView(ViewInterface $view): void
    {
        $view->assignMultiple([
            'dateFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'],
            'timeFormat' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'],
        ]);

        $this->registerDocheaderButtons();
        $this->generateMenu();
        $this->moduleTemplate->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons(): void
    {
        $buttonBar      = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName     = $currentRequest->getPluginName();
        $getVars        = $this->request->getArguments();

        $extensionName = $currentRequest->getControllerExtensionName();
        if (count($getVars) === 0) {
            $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
            $getVars      = ['id', 'M', $modulePrefix];
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName($moduleName)
            ->setGetVariables($getVars);
        $buttonBar->addButton($shortcutButton);

        if ($this->h5pContentAllowedOnPage && in_array($this->request->getControllerActionName(), ['content', 'index', 'show'])) {
            $title         = $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.new');
            $icon          = $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL);
            $addUserButton = $buttonBar->makeLinkButton()
                ->setHref($this->getHref('H5pModule', 'new'))
                ->setTitle($title)
                ->setIcon($icon);
            $buttonBar->addButton($addUserButton);
        }

        if (in_array($this->request->getControllerActionName(), ['show'])) {
            $title         = $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.edit');
            $icon          = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL);
            $addUserButton = $buttonBar->makeLinkButton()
                ->setHref($this->getHref('H5pModule', 'edit', ['contentId' => $this->request->getArgument('contentId')]))
                ->setTitle($title)
                ->setIcon($icon);
            $buttonBar->addButton($addUserButton);
        }
    }

    /**
     * Creates te URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref(string $controller, string $action, array $parameters = []): string
    {
        $this->uriBuilder->setRequest($this->request);
        return $this->uriBuilder->reset()->uriFor($action, $parameters, $controller);
    }

    /**
     * Generates the action menu
     */
    protected function generateMenu()
    {
        $menuItems = [
            'choose' => [
                'controller' => 'H5pModule',
                'action'     => 'content',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.choose')
            ]
        ];
        if ($this->isCurrentUserAdmin()) {
            $menuItems['index'] = [
                'controller' => 'H5pModule',
                'action'     => 'index',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.index')
            ];
        }
        $menuItems['content'] = [
            'controller' => 'H5pModule',
            'action'     => 'content',
            'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.content')
        ];
        if ($this->h5pContentAllowedOnPage) {
            $menuItems['new'] = [
                'controller' => 'H5pModule',
                'action'     => 'new',
                'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.new')
            ];
        }
        $menuItems['libraries'] = [
            'controller' => 'H5pModule',
            'action'     => 'libraries',
            'label'      => $this->getLanguageService()->sL('LLL:EXT:h5p/Resources/Private/Language/locallang.xlf:module.menu.libraries')
        ];
        $this->uriBuilder->setRequest($this->request);

        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('IndexedSearchModuleMenu');

        foreach ($menuItems as $menuItem) {
            $isActive = $this->request->getControllerActionName() === $menuItem['action'];
            $menuItem = $menu->makeMenuItem()
                ->setTitle($menuItem['label'])
                ->setHref($this->uriBuilder->uriFor($menuItem['action']))
                ->setActive($isActive);
            $menu->addMenuItem($menuItem);
        }

        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Shows a list of h5p content
     *
     * @param int $currentPage
     * @return ResponseInterface
     */
    public function indexAction(int $currentPage = 1): ResponseInterface
    {
        $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
        $content           = $contentRepository->findAll();

        $paginator  = new QueryResultPaginator($content, $currentPage, $this->itemsPerPage);
        $pagination = new SimplePagination($paginator);


        $this->view->assignMultiple([
            'action'                  => 'index',
            'paginator'               => $paginator,
            'pagination'              => $pagination,
            'h5pContentAllowedOnPage' => $this->h5pContentAllowedOnPage,
            'id'                      => $this->id,
            'h5pContent'              => $content
        ]);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Shows a list of h5p content on selected page
     *
     * @param int $currentPage
     * @return ResponseInterface
     */
    public function contentAction(int $currentPage = 1): ResponseInterface
    {
        $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
        $content           = $contentRepository->findByPid($this->id);

        $paginator  = new QueryResultPaginator($content, $currentPage, $this->itemsPerPage);
        $pagination = new SimplePagination($paginator);

        $this->view->assignMultiple([
            'action'                  => 'content',
            'h5pContentAllowedOnPage' => $this->h5pContentAllowedOnPage,
            'id'                      => $this->id,
            'h5pContent'              => $content,
            'paginator'               => $paginator,
            'pagination'              => $pagination,
        ]);
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Renders the available libraries
     *
     * @param int $currentPage
     * @return ResponseInterface
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function librariesAction(int $currentPage = 1): ResponseInterface
    {
        $libraryRepository = GeneralUtility::makeInstance(LibraryRepository::class);
        $libraries         = $libraryRepository->findAll();

        // Check if any libraries need an update
        $librariesThatNeedUpdate = [];
        $resourceFactory         = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage                 = $resourceFactory->getDefaultStorage();
        if ($storage !== null) {
            foreach ($libraries as $library) {
                try {
                    $libraryJson = $storage->getFile('/h5p/libraries/' . $library->getFolderName() . '/library.json');
                    if ($libraryJson instanceof FileInterface && $libraryJson->getSize() > 0) {
                        $libraryContent = json_decode($libraryJson->getContents(), true);
                        $preloadedCss   = self::pathsToCsv($libraryContent, 'preloadedCss');
                        $preloadedJs    = self::pathsToCsv($libraryContent, 'preloadedJs');
                        if (($preloadedCss !== '' && $library->getPreloadedCss() !== $preloadedCss)
                            || ($preloadedJs !== '' && $library->getPreloadedJs() !== $preloadedJs)) {
                            $library->setPreloadedCss($preloadedCss);
                            $library->setPreloadedJs($preloadedJs);
                            $librariesThatNeedUpdate[] = $library;
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }
        if (count($librariesThatNeedUpdate) > 0) {
            $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
            foreach ($librariesThatNeedUpdate as $library) {
                $persistenceManager->add($library);
            }
            $persistenceManager->persistAll();
        }

        $paginator  = new QueryResultPaginator($libraries, $currentPage, $this->itemsPerPage);
        $pagination = new SimplePagination($paginator);

        $this->view->assignMultiple([
            'action'     => 'libraries',
            'libraries'  => $libraries,
            'paginator'  => $paginator,
            'pagination' => $pagination,
        ]);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Convert list of file paths to csv
     *
     * @param array $library
     *  Library data as found in library.json files
     * @param string $key
     *  Key that should be found in $libraryData
     *
     * @return string
     *  file paths separated by ', '
     */
    private static function pathsToCsv(array $library, string $key): string
    {
        if (isset($library[$key])) {
            $paths = [];
            foreach ($library[$key] as $file) {
                $paths[] = $file['path'];
            }
            return implode(', ', $paths);
        }
        return '';
    }

    /**
     * Create action
     *
     * @throws StopActionException
     * @throws NoSuchArgumentException
     */
    public function createAction(): ResponseInterface
    {
        // Keep track of the old library and params
        $oldLibrary = null;
        $oldParams  = null;
        $content    = [
            'disable' => H5PCore::DISABLE_NONE
        ];

        // Get library
        $content['library'] = H5PCore::libraryFromString($this->request->getArgument('library'));
        if (!$content['library']) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid library.');
            return new ForwardResponse('new');
        }
        if ($this->h5pCore->h5pF->libraryHasUpgrade($content['library'])) {
            // We do not allow storing old content due to security concerns
            $this->h5pCore->h5pF->setErrorMessage('Something unexpected happened. We were unable to save this content.');
            $this->addFlashMessage('Something unexpected happened. We were unable to save this content.');
            return new ForwardResponse('new');
        }

        // Check if library exists.
        $content['library']['libraryId'] = $this->h5pCore->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'],
            $content['library']['minorVersion']);
        if (!$content['library']['libraryId']) {
            $this->h5pCore->h5pF->setErrorMessage('No such library.');
            $this->addFlashMessage('No such library.');
            return new ForwardResponse('new');
        }

        // Check parameters
        $content['params'] = $this->request->getArgument('parameters');
        if ($content['params'] === null) {
            return false;
        }
        $params = json_decode($content['params']);
        if ($params === null) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid parameters.');
            $this->addFlashMessage('Invalid parameters.');
            return new ForwardResponse('new');
        }

        $content['params']   = json_encode($params->params);
        $content['metadata'] = $params->metadata;

        // Trim title and check length
        $trimmed_title = empty($content['metadata']->title) ? '' : trim($content['metadata']->title);
        if ($trimmed_title === '') {
            $this->addFlashMessage('Missing title');
            return new ForwardResponse('new');
        }

        if (strlen($trimmed_title) > 255) {
            $this->addFlashMessage('Title is too long. Must be 256 letters or shorter.', '', AbstractMessage::ERROR);
            return new ForwardResponse('new');
        }

        $this->setDisabledContentFeatures($this->h5pCore, $content);

        try {
            // Save new content
            $content['id'] = $this->h5pCore->saveContent($content);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), $e->getCode(), AbstractMessage::ERROR);
            return new ForwardResponse('new');
        }

        // Move images and find all content dependencies
        $this->h5pEditor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);

        // Used to generate the slug
        $content['title'] = $content['metadata']->title;

        // Store content dependencies
        $this->h5pCore->filterParameters($content);

        $this->addFlashMessage('Content stored successfully.');
        return (new ForwardResponse('show'))->withControllerName('H5pModule')->withExtensionName('h5p')->withArguments(['contentId' => $content['id']]);
    }

    /**
     * Extract disabled content features from input post.
     *
     * @param H5PCore $core
     * @param $content
     * @return void
     * @throws NoSuchArgumentException
     */
    private function setDisabledContentFeatures(H5PCore $core, &$content): void
    {
        $set                = [
            H5PCore::DISPLAY_OPTION_FRAME     => (bool)$this->request->getArgument('frame'),
            H5PCore::DISPLAY_OPTION_DOWNLOAD  => (bool)$this->request->getArgument('download'),
            H5PCore::DISPLAY_OPTION_EMBED     => (bool)$this->request->getArgument('embed'),
            H5PCore::DISPLAY_OPTION_COPYRIGHT => (bool)$this->request->getArgument('copyright')
        ];
        $content['disable'] = $core->getStorableDisplayOptions($set, $content['disable']);
    }

    /**
     * Update action
     *
     * @throws StopActionException
     * @throws NoSuchArgumentException
     */
    public function updateAction(): ResponseInterface
    {
        // Content id
        $contentId = null;
        if ($this->request->hasArgument('contentId')) {
            $contentId = $this->request->getArgument('contentId');
        }

        // Keep track of the old library and params
        $oldLibrary = null;
        $oldParams  = null;
        $content    = [
            'disable' => H5PCore::DISABLE_NONE
        ];

        // Get library
        $content['library'] = H5PCore::libraryFromString($this->request->getArgument('library'));
        if (!$content['library']) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid library.');
            return new ForwardResponse('new');
        }
        if ($this->h5pCore->h5pF->libraryHasUpgrade($content['library'])) {
            // We do not allow storing old content due to security concerns
            $this->h5pCore->h5pF->setErrorMessage('Something unexpected happened. We were unable to save this content.');
            $this->addFlashMessage('Something unexpected happened. We were unable to save this content.');
            return new ForwardResponse('new');
        }

        // Check if library exists.
        $content['library']['libraryId'] = $this->h5pCore->h5pF->getLibraryId($content['library']['machineName'], $content['library']['majorVersion'],
            $content['library']['minorVersion']);
        if (!$content['library']['libraryId']) {
            $this->h5pCore->h5pF->setErrorMessage('No such library.');
            $this->addFlashMessage('No such library.');
            return new ForwardResponse('new');
        }

        // Check parameters
        $content['params'] = $this->request->getArgument('parameters');
        if ($content['params'] === null) {
            return false;
        }
        $params = json_decode($content['params']);
        if ($params === null) {
            $this->h5pCore->h5pF->setErrorMessage('Invalid parameters.');
            $this->addFlashMessage('Invalid parameters.');
            return new ForwardResponse('new');
        }

        $content['params']   = json_encode($params->params);
        $content['metadata'] = $params->metadata;

        // Trim title and check length
        $trimmed_title = empty($content['metadata']->title) ? '' : trim($content['metadata']->title);
        if ($trimmed_title === '') {
            $this->addFlashMessage('Missing title');
            return new ForwardResponse('new');
        }

        if (strlen($trimmed_title) > 255) {
            $this->addFlashMessage('Title is too long. Must be 256 letters or shorter.', '', AbstractMessage::ERROR);
            return new ForwardResponse('new');
        }

        $this->setDisabledContentFeatures($this->h5pCore, $content);

        try {
            // Save new content
            $content['id'] = $contentId;
            $content['id'] = $this->h5pCore->saveContent($content, $contentId);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), $e->getCode(), AbstractMessage::ERROR);
            return new ForwardResponse('new');
        }

        // Move images and find all content dependencies
        $this->h5pEditor->processParameters($content['id'], $content['library'], $params->params, $oldLibrary, $oldParams);

        // Used to generate the slug
        $content['title'] = $content['metadata']->title;

        // Store content dependencies
        $this->h5pCore->filterParameters($content);

        $this->addFlashMessage('Content stored successfully.');
        return (new ForwardResponse('show'))->withControllerName('H5pModule')->withExtensionName('h5p')->withArguments(['contentId' => $content['id']]);
    }

    /**
     * Edit action
     * @param int $contentId
     * @throws RouteNotFoundException
     */
    public function editAction(int $contentId): ResponseInterface
    {
        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getEditorSettings($this->getCoreSettings())) . ';'
        );

        if ($contentId > 0) {
            $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
            $content           = $contentRepository->findByUid($contentId);

            if (!$content instanceof Content) {
                $this->addFlashMessage(sprintf('Content element with id %d not found', $contentId), 'Record not found', AbstractMessage::ERROR);
                $this->redirect('error', 'H5pModule', 'h5p');
            }

            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary();
            if ($contentLibrary instanceof Library) {
                $contentLibraryArray = $contentLibrary->toAssocArray();
                $this->view->assign('library',
                    sprintf('%s %d.%d', $contentLibraryArray['machineName'], $contentLibraryArray['majorVersion'], $contentLibraryArray['minorVersion']));
            }
            $this->view->assign('content', $content);
            $parameters     = (array)json_decode($content->getFiltered());
            $displayOptions = $this->h5pCore->getDisplayOptionsForEdit($content->getDisable());
            $this->view->assign('displayOptions', $displayOptions);
            $parameters = $this->injectMetadataIntoParameters($parameters, $content);
            $parameters = json_encode($parameters, JSON_THROW_ON_ERROR);
            // Unbreak wrongly encoded parameters (Content.php updateFromContentData())
            $parameters = str_replace([
                '"globalBackgroundSelector":[]',
                '"slideBackgroundSelector":[]',
                '"image":[]'
            ], [
                '"globalBackgroundSelector":{}',
                '"slideBackgroundSelector":{}',
                '"image":{}'
            ], $parameters);
            $this->view->assign('parameters', $parameters);
        }

        $this->embedEditorScriptsAndStyles();
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param $settings
     * @return mixed
     * @throws RouteNotFoundException|NoSuchArgumentException
     */
    public function getEditorSettings($settings)
    {
        $absoluteWebPath = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('h5p'));

        $cacheBuster = '?v=' . Framework::$version;

        // Add JavaScript settings
        $settings['editor'] = [
            'filesPath'          => '/fileadmin/h5p/editor',
            'fileIcon'           => [
                'path'   => $absoluteWebPath . 'Resources/Public/Lib/h5p-editor/images/binary-file.png',
                'width'  => 50,
                'height' => 50,
            ],
            'ajaxPath'           => (string)$this->backendUriBuilder->buildUriFromRoute('h5p_editor_action', ['action' => 'h5p_']),
            'libraryUrl'         => $absoluteWebPath . 'Resources/Public/Lib/h5p-editor/',
            'copyrightSemantics' => $this->h5pContentValidator->getCopyrightSemantics(),
            'metadataSemantics'  => $this->h5pContentValidator->getMetadataSemantics(),
            'assets'             => [],
            'deleteMessage'      => 'Are you sure you wish to delete this content?',
            'apiVersion'         => CoreFactory::$coreApi,
            'language'           => $this->language
        ];

        $webCorePath = $absoluteWebPath . 'Resources/Public/Lib/h5p-core/';
        foreach (H5PCore::$styles as $style) {
            $settings['editor']['assets']['css'][] = $webCorePath . $style . $cacheBuster;
        }
        foreach (H5PCore::$scripts as $script) {
            $settings['editor']['assets']['js'][] = $webCorePath . $script . $cacheBuster;
        }

        $webEditorPAth = $absoluteWebPath . 'Resources/Public/Lib/h5p-editor/';
        foreach (H5peditor::$styles as $style) {
            $settings['editor']['assets']['css'][] = $webEditorPAth . $style . $cacheBuster;
        }
        foreach (H5peditor::$scripts as $script) {
            if (strpos($script, 'h5peditor-editor') === false) {
                $settings['editor']['assets']['js'][] = $webEditorPAth . $script . $cacheBuster;
            }
        }

        $id = null;
        if ($this->request->hasArgument('contentId')) {
            $id = $this->request->getArgument('contentId');
        }
        if ($id !== null) {
            $settings['editor']['nodeVersionId'] = $id;
        }
        return $settings;
    }

    /**
     * Get generic h5p settings
     *
     * @return array;
     * @throws RouteNotFoundException|\TYPO3\CMS\Extbase\Object\Exception
     */
    public function getCoreSettings(): array
    {
        $backendUser = $this->getBackendUser()->user;

        $absoluteWebPath = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('h5p'));

        $url = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');

        $cacheBuster = '?v=' . Framework::$version;

        $settings = [
            'baseUrl'            => $url,
            'url'                => '/fileadmin/h5p',
            'postUserStatistics' => false,
            'ajax'               => [
                'setFinished'     => (string)$this->backendUriBuilder->buildUriFromRoute('h5p_editor_action', ['type' => 'setFinished', 'action' => 'h5p_']),
                'contentUserData' => (string)$this->backendUriBuilder->buildUriFromRoute('h5p_editor_action', [
                    'type'           => 'contentUserData',
                    'action'         => 'h5p_',
                    'content_id'     => ':contentId',
                    'data_type'      => ':dataType',
                    'sub_content_id' => ':subContentId'
                ]),
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
            'libraryUrl'         => $absoluteWebPath . 'Resources/Public/Lib/h5p-core/js',
            'contents'           => []
        ];

        if ($backendUser['uid']) {
            $settings['user'] = [
                'name' => $backendUser['realName'],
                'mail' => $backendUser['email']
            ];
        }

        $webCorePath = $absoluteWebPath . 'Resources/Public/Lib/h5p-core/';
        foreach (H5PCore::$styles as $style) {
            $settings['core']['styles'][] = $webCorePath . $style . $cacheBuster;
        }
        foreach (H5PCore::$scripts as $script) {
            $settings['core']['scripts'][] = $webCorePath . $script . $cacheBuster;
        }
        $settings['loadedJs']  = [];
        $settings['loadedCss'] = [];

        return $settings;
    }

    /**
     * @param array $parameters
     * @param Content $content
     * @return array
     */
    private function injectMetadataIntoParameters(array $parameters, Content $content): array
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

    /**
     * Embed scripts and styles
     */
    protected function embedEditorScriptsAndStyles(): void
    {
        $absoluteWebPath = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('h5p'));
        $webCorePath     = $absoluteWebPath . 'Resources/Public/Lib/h5p-core/';
        $webEditorPath   = $absoluteWebPath . 'Resources/Public/Lib/h5p-editor/';
        $webScriptPath   = $absoluteWebPath . 'Resources/Public/JavaScript/';

        $paths = [
            'h5p-jquery'              => $webCorePath . 'js/jquery',
            'h5p'                     => $webCorePath . 'js/h5p',
            'h5p-event-dispatcher'    => $webCorePath . 'js/h5p-event-dispatcher',
            'h5p-x-api-event'         => $webCorePath . 'js/h5p-x-api-event',
            'h5p-x-api'               => $webCorePath . 'js/h5p-x-api',
            'h5p-content-type'        => $webCorePath . 'js/h5p-content-type',
            'h5p-confirmation-dialog' => $webCorePath . 'js/h5p-confirmation-dialog',
            'h5p-action-bar'          => $webCorePath . 'js/h5p-action-bar',
            'h5peditor-editor'        => $webEditorPath . 'scripts/h5peditor-editor',
            'h5peditor-init'          => $webEditorPath . 'scripts/h5peditor-init',
            'h5p-display-options'     => $webCorePath . 'js/h5p-display-options',
            'TYPO3/CMS/H5p/editor'    => $webScriptPath . 'editor',
        ];

        $languageFile = ExtensionManagementUtility::extPath('h5p') . 'Resources/Public/Lib/h5p-editor/language/' . $this->language . '.js';
        if (file_exists($languageFile)) {
            $paths['h5peditor-editor-language'] = $webEditorPath . 'language/' . $this->language;
        } else {
            $paths['h5peditor-editor-language'] = $webEditorPath . 'language/en';
        }

        $this->pageRenderer->addRequireJsConfiguration([
                'paths' => $paths,
                'shim'  => [
                    'h5p-jquery'                => [
                        'exports' => 'h5p-jquery'
                    ],
                    'h5peditor-editor'          => [
                        'deps'    => ['h5p-action-bar'],
                        'exports' => 'h5peditor-editor'
                    ],
                    'h5peditor-init'            => [
                        'deps'    => ['h5peditor-editor', 'h5peditor-editor-language', 'h5p-display-options'],
                        'exports' => 'h5peditor-init'
                    ],
                    'h5p-content-type'          => [
                        'deps'    => ['h5p-x-api'],
                        'exports' => 'h5p-content-type'
                    ],
                    'h5p-confirmation-dialog'   => [
                        'deps'    => ['h5p-content-type'],
                        'exports' => 'h5p-confirmation-dialog'
                    ],
                    'h5p-event-dispatcher'      => [
                        'deps'    => ['h5p'],
                        'exports' => 'h5p-event-dispatcher'
                    ],
                    'h5p-display-options'       => [
                        'deps'    => ['h5peditor-editor'],
                        'exports' => 'h5p-display-options'
                    ],
                    'h5p-x-api-event'           => [
                        'deps'    => ['h5p-event-dispatcher'],
                        'exports' => 'h5p-x-api-event'
                    ],
                    'h5p-x-api'                 => [
                        'deps'    => ['h5p-x-api-event'],
                        'exports' => 'h5p-x-api'
                    ],
                    'h5peditor-editor-language' => [
                        'deps'    => ['h5peditor-editor'],
                        'exports' => 'h5peditor-editor-language'
                    ],
                    'h5p-action-bar'            => [
                        'deps'    => ['h5p-confirmation-dialog'],
                        'exports' => 'h5p-action-bar'
                    ],
                    'h5p'                       => [
                        'deps'    => ['h5p-jquery'],
                        'exports' => 'h5p'
                    ],
                    'TYPO3/CMS/H5p/editor'      => [
                        'deps'    => ['h5peditor-init'],
                        'exports' => 'TYPO3/CMS/H5p/editor'
                    ],
                ],
            ]
        );

        foreach (H5PCore::$styles as $style) {
            $this->pageRenderer->addCssFile($webCorePath . $style, 'stylesheet', 'all', '', false, false, '', true);
        }
        foreach (H5peditor::$styles as $style) {
            $this->pageRenderer->addCssFile($webEditorPath . $style, 'stylesheet', 'all', '', false, false, '', true);
        }
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/H5p/editor');
    }

    /**
     * Consent action
     */
    public function consentAction(): ResponseInterface
    {
        if ($this->request->getArgument('collectStatistics')) {
            $this->h5pFramework->setOption('track_user', 1);
            $this->addFlashMessage('Usage tracking has been enabled.', 'Tracking enabled');
        }
        $this->h5pFramework->setOption('hub_is_enabled', 1);
        $this->addFlashMessage('The hub has been enabled.', 'H5P hub enabled');
        return new ForwardResponse('new');
    }

    /**
     * New action / upload form
     * @param int $contentId
     * @return ResponseInterface
     * @throws NoSuchArgumentException
     * @throws RouteNotFoundException
     * @throws StopActionException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function newAction(int $contentId = 0): ResponseInterface
    {
        $this->view->assign('didConsent', (int)$this->h5pFramework->getOption('hub_is_enabled') === 1);
        $this->view->assign('h5pContentAllowedOnPage', $this->h5pContentAllowedOnPage);

        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getEditorSettings($this->getCoreSettings())) . ';'
        );

        if ($contentId > 0) {
            $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
            $content           = $contentRepository->findByUid($contentId);

            if (!$content instanceof Content) {
                $this->addFlashMessage(sprintf('Content element with id %d not found', $contentId), 'Record not found', AbstractMessage::ERROR);
                $this->redirect('error');
            }

            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary()->toAssocArray();
            $this->view->assign('library',
                sprintf('%s %d.%d', $contentLibrary['machineName'], $contentLibrary['majorVersion'], $contentLibrary['minorVersion']));
            $this->view->assign('parameters', $content->getFiltered());
        }

        $this->embedEditorScriptsAndStyles();
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Show action
     * @param int $contentId
     * @return ResponseInterface
     * @throws RouteNotFoundException
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function showAction(int $contentId): ResponseInterface
    {
        $contentRepository = GeneralUtility::makeInstance(ContentRepository::class);
        $content           = $contentRepository->findByUid($contentId);

        if (!$content instanceof Content) {
            $this->addFlashMessage(sprintf('Content element with id %d not found', $contentId), 'Record not found', AbstractMessage::ERROR);
            return new ForwardResponse('error');
        }

        if (!$content->getLibrary()) {
            $this->addFlashMessage('Content element has no H5P library', 'H5P library not found on content', AbstractMessage::ERROR);
            return new ForwardResponse('error');
        }

        $cacheBuster = '?v=' . Framework::$version;

        $absoluteWebPath  = PathUtility::getAbsoluteWebPath(ExtensionManagementUtility::extPath('h5p'));
        $relativeCorePath = $absoluteWebPath . 'Resources/Public/Lib/h5p-core/';

        foreach (\H5PCore::$scripts as $script) {
            $this->pageRenderer->addJsFile($relativeCorePath . $script, 'text/javascript', false, false, '', true);
        }
        foreach (\H5PCore::$styles as $style) {
            $this->pageRenderer->addCssFile($relativeCorePath . $style, 'stylesheet', 'all', '', false, false, '', true);
        }

        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration',
            'H5PIntegration = ' . json_encode($this->getCoreSettings()) . ';'
        );

        $contentSettings                                = $this->getContentSettings($content);
        $contentSettings['displayOptions']              = [];
        $contentSettings['displayOptions']['frame']     = true;
        $contentSettings['displayOptions']['export']    = false;
        $contentSettings['displayOptions']['embed']     = false;
        $contentSettings['displayOptions']['copyright'] = false;
        $contentSettings['displayOptions']['icon']      = true;
        $this->pageRenderer->addJsInlineCode(
            'H5PIntegration contents',
            'H5PIntegration.contents[\'cid-' . $content->getUid() . '\'] = ' . json_encode($contentSettings) . ';'
        );
        if ($content->getEmbedType() !== 'iframe') {
            // load JS and CSS requirements
            $contentLibrary = $content->getLibrary()->toAssocArray();

            // JS and CSS required by all libraries
            $contentLibraryWithDependencies = $this->h5pCore->loadLibrary($contentLibrary['machineName'], $contentLibrary['majorVersion'],
                $contentLibrary['minorVersion']);
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
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Get content settings
     *
     * @return array;
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public function getContentSettings(Content $content): array
    {
        $settings = [
            'url'            => '/fileadmin/h5p',
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
            $contentLibrary    = $content->getLibrary()->toAssocArray();
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
    private function setJsAndCss(array $library, array &$settings): void
    {
        $name       = $library['machineName'] . '-' . $library['majorVersion'] . '.' . $library['minorVersion'];
        $preloadCss = explode(',', $library['preloadedCss']);
        $preloadJs  = explode(',', $library['preloadedJs']);

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
    private function loadJsAndCss(array $library): void
    {
        $name       = $library['machineName'] . '-' . $library['majorVersion'] . '.' . $library['minorVersion'];
        $preloadCss = explode(',', $library['preloadedCss']);
        $preloadJs  = explode(',', $library['preloadedJs']);

        foreach ($preloadJs as $js) {
            $js = trim($js);
            if ($js) {
                $this->pageRenderer->addJsFile('/fileadmin/h5p/libraries/' . $name . '/' . $js, 'text/javascript', false, false, '', true);
            }
        }
        foreach ($preloadCss as $css) {
            $css = trim($css);
            if ($css) {
                $this->pageRenderer->addCssFile('/fileadmin/h5p/libraries/' . $name . '/' . $css, 'stylesheet', 'all', '', false, false, '', true);
            }
        }
    }

    /**
     * Error action
     */
    public function errorAction(): ResponseInterface
    {
        $this->moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Set type converter configuration for Package upload
     * @param string $argumentName
     */
    protected function setTypeConverterConfigurationForPackageUpload($argumentName): void
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
}
