<?php
namespace MichielRoos\H5p\Domain\Model;

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
 * Class Content
 */
class Content extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var string
     */
    protected $author;

    /**
     * @var string
     */
    protected $authorComments;

    /**
     * @var string
     */
    protected $authors;

    /**
     * @var string
     */
    protected $changes;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var bool
     */
    protected $disable;

    /**
     * @var string
     */
    protected $embedType;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $filtered;

    /**
     * @var bool
     */
    protected $hidden;

    /**
     * @var string
     */
    protected $keywords;

    /**
     * @var \MichielRoos\H5p\Domain\Model\Library
     */
    protected $library;

    /**
     * @var string
     */
    protected $license;

    /**
     * @var string
     */
    protected $licenseVersion;

    /**
     * @var string
     */
    protected $licenseExtras;

    /**
     * @var string
     */
    protected $slug;

    /**
     * Title
     *
     * @var string
     */
    protected $title = '';

    /**
     * Package
     *
     * @var \TYPO3\CMS\Extbase\Domain\Model\FileReference
     * @validate \MichielRoos\H5p\Validation\Validator\PackageValidator
     */
    protected $package;

    /**
     * @var string
     */
    protected $parameters;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var string
     */
    protected $source;

    /**
     * @var integer
     */
    protected $yearFrom;

    /**
     * @var integer
     */
    protected $yearTo;

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
    public static function createFromContentData(array $contentData, Library $library)
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
    public function updateFromContentData(array $contentData, Library $library)
    {
        $this->setUpdatedAt(new \DateTime());
        $this->setFiltered('');
        $this->setLibrary($library);
        if (isset($contentData['disable'])) {
            $this->setHidden($contentData['disable']);
        }

        if (isset($contentData['params'])) {
            // Yes, twice. they added metadata later and didnt rename the top level.
            $parameters = json_decode($contentData['params'], true);
            $this->setParameters(json_encode($parameters));

            // "H5P Metadata"
            $this->setTitle(html_entity_decode($contentData['metadata']->title));
            $this->setAuthors(empty($contentData['metadata']->authors) ? null : json_encode($contentData['metadata']->authors));
            $this->setSource(empty($contentData['metadata']->source) ? null : $contentData['metadata']->source);
            $this->setLicense(empty($contentData['metadata']->license) ? '' : $contentData['metadata']->license);
            $this->setLicenseVersion(empty($contentData['metadata']->licenseVersion) ? '' : $contentData['metadata']->licenseVersion);
            $this->setLicenseExtras(empty($contentData['metadata']->licenseExtras) ? null : $contentData['metadata']->licenseExtras);
            $this->setAuthorComments(empty($contentData['metadata']->authorComments) ? null : $contentData['metadata']->authorComments);
            $this->setChanges(empty($contentData['metadata']->changes) ? null : json_encode($contentData['metadata']->changes));
        }
    }

    public function determineEmbedType()
    {
        $this->setEmbedType(\H5PCore::determineEmbedType('div', $this->getLibrary()->getEmbedTypes()));
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getAuthorComments()
    {
        return $this->authorComments;
    }

    /**
     * @param string $authorComments
     */
    public function setAuthorComments($authorComments)
    {
        $this->authorComments = $authorComments;
    }

    /**
     * @return string
     */
    public function getAuthors()
    {
        return $this->authors;
    }

    /**
     * @param string $authors
     */
    public function setAuthors($authors)
    {
        $this->authors = $authors;
    }

    /**
     * @return string
     */
    public function getChanges()
    {
        return $this->changes;
    }

    /**
     * @param string $changes
     */
    public function setChanges($changes)
    {
        $this->changes = $changes;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return bool
     */
    public function isDisable()
    {
        return $this->disable;
    }

    /**
     * @param bool $disable
     */
    public function setDisable($disable)
    {
        $this->disable = $disable;
    }

    /**
     * @return string
     */
    public function getEmbedType()
    {
        return $this->embedType;
    }

    /**
     * @param string $embedType
     */
    public function setEmbedType($embedType)
    {
        $this->embedType = $embedType;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getFiltered()
    {
        return $this->filtered;
    }

    /**
     * @param string $filtered
     */
    public function setFiltered($filtered)
    {
        $this->filtered = $filtered;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;
    }

    /**
     * @return \MichielRoos\H5p\Domain\Model\Library
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * @param \MichielRoos\H5p\Domain\Model\Library $library
     */
    public function setLibrary($library)
    {
        $this->library = $library;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @param string $license
     */
    public function setLicense($license)
    {
        $this->license = $license;
    }

    /**
     * @return string
     */
    public function getLicenseVersion()
    {
        return $this->licenseVersion;
    }

    /**
     * @param string $licenseVersion
     */
    public function setLicenseVersion($licenseVersion)
    {
        $this->licenseVersion = $licenseVersion;
    }

    /**
     * @return string
     */
    public function getLicenseExtras()
    {
        return $this->licenseExtras;
    }

    /**
     * @param string $licenseExtras
     */
    public function setLicenseExtras($licenseExtras)
    {
        $this->licenseExtras = $licenseExtras;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FileReference
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Domain\Model\FileReference $package
     */
    public function setPackage($package)
    {
        $this->package = $package;
    }

    /**
     * @return string
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param string $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getYearFrom()
    {
        return $this->yearFrom;
    }

    /**
     * @param int $yearFrom
     */
    public function setYearFrom($yearFrom)
    {
        $this->yearFrom = $yearFrom;
    }

    /**
     * @return int
     */
    public function getYearTo()
    {
        return $this->yearTo;
    }

    /**
     * @param int $yearTo
     */
    public function setYearTo($yearTo)
    {
        $this->yearTo = $yearTo;
    }
}
