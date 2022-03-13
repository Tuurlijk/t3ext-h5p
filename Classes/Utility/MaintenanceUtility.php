<?php

namespace MichielRoos\H5p\Utility;

use MichielRoos\H5p\Exception\MethodNotImplementedException;

/**
 * Class MaintenanceUtility
 * @package MichielRoos\H5p\Utility
 */
class MaintenanceUtility
{
    /**
     * @param string $class
     * @param string $function
     * @param string $message
     * @throws MethodNotImplementedException
     */
    public static function methodMissing(string $class = '', string $function = '', string $message = ''): void
    {
        if (defined('TYPO3_CONTEXT') && TYPO3_CONTEXT === 'Development') {
            throw new MethodNotImplementedException('Method not implemented: ' . $class . '->' . $function . ' ' . $message);
        }
    }
}