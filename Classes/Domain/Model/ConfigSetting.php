<?php
namespace MichielRoos\H5p\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
class ConfigSetting extends AbstractEntity
{

    protected ?string $configKey = '';

    protected ?string $configValue = '';

    /**
     * ConfigSetting constructor.
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key = '', string $value = '')
    {
        $this->configKey = $key;
        $this->configValue = $value;
    }

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * @param string $configKey
     */
    public function setConfigKey(string $configKey): void
    {
        $this->configKey = $configKey;
    }

    /**
     * @return string
     */
    public function getConfigValue(): string
    {
        return $this->configValue;
    }

    /**
     * @param string $configValue
     */
    public function setConfigValue(string $configValue): void
    {
        $this->configValue = $configValue;
    }
}
