<?php

namespace MichielRoos\H5p\Domain\Model;


use MichielRoos\H5p\Validation\Validator\PackageValidator;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * Class Content
 */
class Content extends AbstractEntity
{
    /**
     * @var string
     */
    protected string $author = '';

    /**
     * @var string
     */
    protected string $authorComments = '';

    /**
     * @var string
     */
    protected string $authors = '';

    /**
     * @var string
     */
    protected string $changes = '';

    /**
     * @var string
     */
    protected string $contentType = '';

    /**
     * @var \DateTime
     */
    protected \DateTime $createdAt;

    /**
     * @var int
     */
    protected int|bool $disable = false;

    /**
     * @var string
     */
    protected string $embedType = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var string
     */
    protected string $filtered = '{}';

    /**
     * @var bool
     */
    protected bool $hidden = false;

    /**
     * @var string
     */
    protected string $keywords = '';

    /**
     * @var Library
     */
    protected Library|int $library = 0;

    /**
     * @var string
     */
    protected string $license = '';

    /**
     * @var string
     */
    protected string $licenseVersion = '';

    /**
     * @var string
     */
    protected string $licenseExtras = '';

    /**
     * @var string
     */
    protected string $slug = '';

    /**
     * Title
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Package
     *
     * @var FileReference
     */
    #[Extbase\Validate(['validator' => PackageValidator::class])]
    protected FileReference $package;

    /**
     * @var string
     */
    protected string $parameters = '{}';

    /**
     * @var \DateTime
     */
    protected \DateTime $updatedAt;

    /**
     * @var string
     */
    protected string $source = '';

    /**
     * @var integer
     */
    protected int $yearFrom;

    /**
     * @var integer
     */
    protected int $yearTo;

    /**
     * Content constructor.
     */
    public function __construct()
    {
    }

    /**
     * Creates a Content from a metadata array.
     *
     * @param array $contentData
     * @param Library $library
     * @return Content
     * @throws \Exception
     */
    public static function createFromContentData(array $contentData, Library $library): Content
    {
        $content = new Content();
        $content->setCreatedAt(new \DateTime());

        $content->updateFromContentData($contentData, $library);

        // Set by h5p later, but must not be null
        $content->setSlug('');

        /**
         * The Wordpress plugin only determines this at render-time, but it always yields the same result unless the
         * library changes. So we should be fine with setting it here and triggering a re-determine if the
         * library is updated.
         * @see Library::updateFromLibraryData()
         */
        $content->determineEmbedType();

        return $content;
    }

    /**
     * @param array $contentData
     * @param Library $library
     * @throws \Exception
     */
    public function updateFromContentData(array $contentData, Library $library): void
    {
        $this->setUpdatedAt(new \DateTime());
        $this->setFiltered('{}');
        $this->setLibrary($library);
        if (isset($contentData['disable'])) {
            $this->setDisable($contentData['disable']);
        }

        if (isset($contentData['params'])) {
            $this->setParameters($contentData['params']);

            // "H5P Metadata"
            $this->setTitle(html_entity_decode($contentData['metadata']->title));
            $this->setAuthors(empty($contentData['metadata']->authors) ? '' : json_encode($contentData['metadata']->authors));
            $this->setSource(empty($contentData['metadata']->source) ? '' : $contentData['metadata']->source);
            $this->setLicense(empty($contentData['metadata']->license) ? '' : $contentData['metadata']->license);
            $this->setLicenseVersion(empty($contentData['metadata']->licenseVersion) ? '' : $contentData['metadata']->licenseVersion);
            $this->setLicenseExtras(empty($contentData['metadata']->licenseExtras) ? '' : $contentData['metadata']->licenseExtras);
            $this->setAuthorComments(empty($contentData['metadata']->authorComments) ? '' : $contentData['metadata']->authorComments);
            $this->setChanges(empty($contentData['metadata']->changes) ? '' : json_encode($contentData['metadata']->changes));
        }
    }

    public function determineEmbedType(): void
    {
        $this->setEmbedType(\H5PCore::determineEmbedType('div', $this->getLibrary()->getEmbedTypes()));
    }

    /**
     * @return int|Library
     */
    public function getLibrary(): int|Library
    {
        return $this->library;
    }

    /**
     * @param Library $library
     */
    public function setLibrary(Library $library): void
    {
        $this->library = $library;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getAuthorComments(): string
    {
        return $this->authorComments;
    }

    /**
     * @param string $authorComments
     */
    public function setAuthorComments($authorComments): void
    {
        $this->authorComments = $authorComments;
    }

    /**
     * @return string
     */
    public function getAuthors(): string
    {
        return $this->authors;
    }

    /**
     * @param string $authors
     */
    public function setAuthors(string $authors): void
    {
        $this->authors = $authors;
    }

    /**
     * @return string
     */
    public function getChanges(): string
    {
        return $this->changes;
    }

    /**
     * @param string $changes
     */
    public function setChanges(string $changes): void
    {
        $this->changes = $changes;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getDisable(): int
    {
        return (int)$this->disable;
    }

    /**
     * @param int $disable
     */
    public function setDisable(int $disable): void
    {
        $this->disable = (int)$disable;
    }

    /**
     * @return string
     */
    public function getEmbedType(): string
    {
        return $this->embedType;
    }

    /**
     * @param string $embedType
     */
    public function setEmbedType(string $embedType): void
    {
        $this->embedType = $embedType;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFiltered(): string
    {
        return $this->filtered ?: '{}';
    }

    /**
     * @param string $filtered
     */
    public function setFiltered(string $filtered): void
    {
        $this->filtered = $filtered;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    /**
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     */
    public function setKeywords(string $keywords): void
    {
        $this->keywords = $keywords;
    }

    /**
     * @return string
     */
    public function getLicense(): string
    {
        return $this->license;
    }

    /**
     * @param string $license
     */
    public function setLicense(string $license): void
    {
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getLicenseVersion(): string
    {
        return $this->licenseVersion;
    }

    /**
     * @param string $licenseVersion
     */
    public function setLicenseVersion(string $licenseVersion): void
    {
        $this->licenseVersion = $licenseVersion;
    }

    /**
     * @return string
     */
    public function getLicenseExtras(): string
    {
        return $this->licenseExtras;
    }

    /**
     * @param string $licenseExtras
     */
    public function setLicenseExtras(string $licenseExtras): void
    {
        $this->licenseExtras = $licenseExtras;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return FileReference
     */
    public function getPackage(): FileReference
    {
        return $this->package;
    }

    /**
     * @param FileReference $package
     */
    public function setPackage(FileReference $package): void
    {
        $this->package = $package;
    }

    /**
     * @return string
     */
    public function getParameters(): string
    {
        return $this->parameters ?: '{}';
    }

    /**
     * @param string $parameters
     */
    public function setParameters(string $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getYearFrom(): int
    {
        return $this->yearFrom;
    }

    /**
     * @param int $yearFrom
     */
    public function setYearFrom(int $yearFrom): void
    {
        $this->yearFrom = $yearFrom;
    }

    /**
     * @return int
     */
    public function getYearTo(): int
    {
        return $this->yearTo;
    }

    /**
     * @param int $yearTo
     */
    public function setYearTo(int $yearTo): void
    {
        $this->yearTo = $yearTo;
    }
}
