<?php

namespace yanivgal;

use yanivgal\Exceptions\ConfigFileNotFoundException;
use yanivgal\Exceptions\ConfigFileNotSupportedException;
use yanivgal\Exceptions\FolderNotFoundException;
use yanivgal\Exceptions\CustomManagerNotFoundException;
use yanivgal\Exceptions\CustomManagerNotSupportedException;
use yanivgal\Parsers\ConfigurParserInterface;
use yanivgal\Parsers\IniParser;

class Configur
{
    const SETTINGS_KEY_CONFIG_FILES= 'configFiles';
    const SETTINGS_KEY_CONFIG_FOLDERS= 'configFolders';
    const SETTINGS_KEY_CUSTOM_MANAGERS= 'customManagers';

    const CONFIG_EXT_INI = 'ini';
    const CONFIG_EXT_PHP = 'php';
    const CONFIG_EXT_JSON = 'json';

    /**
     * @var array
     */
    private $configFiles = [];

    /**
     * @var array
     */
    private $customManagers = [];

    /**
     * Configur constructor.
     *
     * @param array $settings
     */
    public function __construct(array $settings = [])
    {
        $this->initSettings($settings);
    }

    /**
     * @param array $settings
     */
    private function initSettings(array $settings)
    {
        if (empty($settings)) {
            return;
        }

        if ($this->isKeyExistsAndNotEmpty(
            self::SETTINGS_KEY_CONFIG_FILES,
            $settings
        )) {
            $this->initConfigFiles(
                $settings[self::SETTINGS_KEY_CONFIG_FILES]
            );
        }

        if ($this->isKeyExistsAndNotEmpty(
            self::SETTINGS_KEY_CONFIG_FOLDERS,
            $settings
        )) {
            $this->initConfigFolders(
                $settings[self::SETTINGS_KEY_CONFIG_FOLDERS]
            );
        }

        if ($this->isKeyExistsAndNotEmpty(
            self::SETTINGS_KEY_CUSTOM_MANAGERS,
            $settings
        )) {
            $this->initCustomManagers(
                $settings[self::SETTINGS_KEY_CUSTOM_MANAGERS]
            );
        }
    }

    /**
     * @param array $configFiles
     */
    private function initConfigFiles($configFiles)
    {
        foreach ($configFiles as $configFile) {
            $this->addConfigFile($configFile);
        }
    }

    /**
     * @param array $configFolders
     */
    private function initConfigFolders($configFolders)
    {
        foreach ($configFolders as $configFolder) {
            $this->addConfigFolder($configFolder);
        }
    }

    /**
     * @param array $customManagers
     */
    private function initCustomManagers($customManagers)
    {
        foreach ($customManagers as $customManager) {
            $this->addCustomManager($customManager);
        }
    }

    /**
     * @param string $configFile
     * @return bool
     * @throws ConfigFileNotFoundException
     * @throws ConfigFileNotSupportedException
     */
    public function addConfigFile($configFile)
    {
        if (!file_exists($configFile)) {
            throw new ConfigFileNotFoundException(
                "Config file {$configFile} not found"
            );
        }

        $pathInfo = pathinfo($configFile);

        if (!$this->configFileSupported($pathInfo['extension'])) {
            throw new ConfigFileNotSupportedException(
                "{$pathInfo['extension']} config files are not supported"
            );
        }

        $configFileId = strtolower($pathInfo['filename']);

        if (array_key_exists($configFileId, $this->configFiles)) {
            return false;
        }

        $this->configFiles[$configFileId] = $pathInfo;
        $this->configFiles[$configFileId]['fullPath'] = $configFile;

        return true;
    }

    /**
     * * Adds config folder
     *
     * @param string $configFolder Config folder path
     * @throws ConfigFileNotFoundException
     * @throws ConfigFileNotSupportedException
     * @throws FolderNotFoundException
     */
    public function addConfigFolder($configFolder)
    {
        if (!file_exists($configFolder)) {
            throw new FolderNotFoundException(
                "Folder {$configFolder} not found."
            );
        }

        $configFiles = glob($configFolder . "*.{ini,php,json}", GLOB_BRACE);
        foreach ($configFiles as $configFile) {
            $this->addConfigFile($configFile);
        }
    }

    /**
     * Adds custom manager
     *
     * @param string $customManager
     * @throws CustomManagerNotFoundException
     * @throws CustomManagerNotSupportedException
     */
    public function addCustomManager($customManager)
    {
        if (!class_exists($customManager)) {
            throw new CustomManagerNotFoundException(
                "Custom manager {$customManager} not found."
            );
        }
        if (!is_subclass_of($customManager, 'yanivgal\ConfigurManager')) {
            throw new CustomManagerNotSupportedException(
                "Custom manager {$customManager} must extend yanivgal\\ConfigurManager."
            );
        }
        if (!in_array($customManager, $this->customManagers)) {
            $this->customManagers[] = $customManager;
        }
    }

    /**
     * @param string $name
     * @return ConfigurManager
     * @throws ConfigFileNotFoundException
     * @throws ConfigFileNotSupportedException
     */
    public function __get($name)
    {
        $configFile = $this->getConfigFile($name);
        $configParser = $this->getConfigParser($configFile);
        return $this->getConfigManager($configFile, $configParser);
    }

    /**
     * @param string $name
     * @return array
     * @throws ConfigFileNotFoundException
     */
    private function getConfigFile($name)
    {
        $configFiles = $this->configFiles;
        $configFilesIds = array_keys($configFiles);
        foreach ($configFilesIds as $configFileId) {
            if (strtolower($name) == $configFileId) {
                return $configFiles[$configFileId];
            }
        }
        throw new ConfigFileNotFoundException("Config file {$name} not found");
    }

    /**
     * @param array $configFile
     * @return ConfigurParserInterface
     * @throws ConfigFileNotSupportedException
     */
    private function getConfigParser($configFile)
    {
        switch ($configFile['extension']) {
            case self::CONFIG_EXT_INI:
                return new IniParser($configFile['fullPath']);
            case self::CONFIG_EXT_PHP:
            case self::CONFIG_EXT_JSON:
            default:
                throw new ConfigFileNotSupportedException(
                    "{$configFile['extension']} config files are not supported"
                );
        }
    }

    /**
     * @param array $configFile
     * @param ConfigurParserInterface $configParser
     * @return ConfigurManager
     */
    private function getConfigManager($configFile, $configParser)
    {
        // Check for custom config manager
        foreach ($this->customManagers as $customManager) {
            $customManagerName = $this->parseCustomManagerName($customManager);
            if ($customManagerName == strtolower($configFile['filename'])) {
                return new $customManager($configFile['fullPath'], $configParser);
            }
        }

        // Return default config manager
        return new ConfigurManager($configFile['fullPath'], $configParser);
    }

    /**
     * @param string $customManager
     * @return string
     */
    private function parseCustomManagerName($customManager)
    {
        $customManagerParts = explode('\\', $customManager);
        $customManagerName = end($customManagerParts);
        $customManagerName = rtrim($customManagerName, 'Manager');
        $customManagerName = rtrim($customManagerName, 'Configur');
        $customManagerName = strtolower($customManagerName);
        return $customManagerName;
    }

    /**
     * @param string $configFileExtension
     * @return bool
     */
    private function configFileSupported($configFileExtension)
    {
        switch ($configFileExtension) {
            case self::CONFIG_EXT_INI:
            case self::CONFIG_EXT_PHP:
            case self::CONFIG_EXT_JSON:
                return true;
            default:
                return false;
        }
    }

    /**
     * @param string|int $key
     * @param array $array
     * @return bool
     */
    private function isKeyExistsAndNotEmpty($key, array $array)
    {
        return array_key_exists($key, $array) && !empty($array[$key]);
    }
}
