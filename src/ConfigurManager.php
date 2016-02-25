<?php

namespace yanivgal;

use ArrayObject;
use yanivgal\Parsers\ConfigurParserInterface;

class ConfigurManager extends ArrayObject
{
    /**
     * @var string
     */
    protected $configFile;

    /**
     * @var ConfigurParserInterface
     */
    protected $configParser;

    /**
     * ConfigurManager constructor.
     * @param string $configFile
     * @param ConfigurParserInterface $configParser
     */
    public function __construct($configFile, ConfigurParserInterface $configParser)
    {
        $this->configFile = $configFile;
        $this->configParser = $configParser;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->configParser->getValue($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $this->configParser->setValue($name, $value);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->configParser->getAll();
    }

    /**
     * @param $data
     */
    public function overwrite($data)
    {
        $this->configParser->overwrite($data);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->configParser->getValue($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->configParser->setValue($name, $value);
    }

    /**
     * @param string $name
     * @return string
     */
    public function offsetGet($name)
    {
        return $this->configParser->getValue($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function offsetSet($name, $value)
    {
        $this->configParser->setValue($name, $value);
    }
}