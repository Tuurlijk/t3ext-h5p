<?php
namespace MichielRoos\H5p\Adapter\Core;


use TYPO3\CMS\Core\SingletonInterface;

/**
 * Class Event
 */
class Event extends \H5PEventBase implements SingletonInterface
{

    /**
     * Adds event type, h5p library and timestamp to event before saving it.
     *
     * Common event types with sub type:
     *  content, <none> – content view
     *           embed – viewed through embed code
     *           shortcode – viewed through internal shortcode
     *           edit – opened in editor
     *           delete – deleted
     *           create – created through editor
     *           create upload – created through upload
     *           update – updated through editor
     *           update upload – updated through upload
     *           upgrade – upgraded
     *
     *  results, <none> – view own results
     *           content – view results for content
     *           set – new results inserted or updated
     *
     *  settings, <none> – settings page loaded
     *
     *  library, <none> – loaded in editor
     *           create – new library installed
     *           update – old library updated
     *
     * @param string $type
     *  Name of event type
     * @param string $sub_type
     *  Name of event sub type
     * @param string $content_id
     *  Identifier for content affected by the event
     * @param string $content_title
     *  Content title (makes it easier to know which content was deleted etc.)
     * @param string $library_name
     *  Name of the library affected by the event
     * @param string $library_version
     *  Library version
     */
    function __construct(string $type = '', $sub_type = NULL, $content_id = NULL, $content_title = NULL, $library_name = NULL, $library_version = NULL) {
        parent::__construct($type = '', $sub_type = NULL, $content_id = NULL, $content_title = NULL, $library_name = NULL, $library_version = NULL);
    }

    /**
     * Stores the event data in the database.
     *
     * Must be overridden by plugin.
     */
    protected function save()
    {
        // TODO: Implement save() method.
    }

    /**
     * Add current event data to statistics counter.
     *
     * Must be overridden by plugin.
     */
    protected function saveStats()
    {
        // TODO: Implement saveStats() method.
    }
}
