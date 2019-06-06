<?php
namespace MichielRoos\H5p\Domain\Model;

class ConfigSetting extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * @var string
     */
    protected $configKey;

    /**
     * @var string
     */
    protected $configValue;

    /**
     * ConfigSetting constructor.
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value)
    {
        $this->configKey = $key;
        $this->configValue = $value;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return $this->configKey;
    }

    /**
     * @param string $configKey
     */
    public function setConfigKey(string $configKey)
    {
        $this->configKey = $configKey;
    }

    /**
     * @return string
     */
    public function getConfigValue()
    {
        return $this->configValue;
    }

    /**
     * @param string $configValue
     */
    public function setConfigValue(string $configValue)
    {
        $this->configValue = $configValue;
    }
}
