<?php

namespace yanivgal\Parsers;

interface ConfigurParserInterface
{
    /**
     * Gets a value from config file
     *
     * @param string $name
     * @return mixed
     */
    public function getValue($name);

    /**
     * Sets a value in config file
     *
     * @param string $name
     * @param mixed $value
     */
    public function setValue($name, $value);

    /**
     * Gets all data in the config file
     *
     * @return array
     */
    public function getAll();

    /**
     * Overwrites all config file's data
     *
     * @param array $data
     */
    public function overwrite(array $data);
}