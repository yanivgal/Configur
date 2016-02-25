<?php

namespace yanivgal\Parsers;

use yanivgal\Exceptions\IniDepthException;

class IniParser extends BaseConfigurParser implements ConfigurParserInterface
{
    /**
     * @var array
     */
    private $configArr;

    /**
     * IniParser constructor.
     *
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        parent::__construct($configFile);

        $this->configArr = $this->parseConfigFile($configFile);
    }

    /**
     * Gets a value from config file
     *
     * @param string $name
     * @return mixed
     */
    public function getValue($name)
    {
        if (array_key_exists($name, $this->configArr)) {
            return $this->configArr[$name];
        }
        return null;
    }

    /**
     * Sets a value in config file
     *
     * @param string $name
     * @param mixed $value
     */
    public function setValue($name, $value)
    {
        $this->configArr[$name] = $value;
        $this->writeConfigFile($this->configArr, $this->configFile);
    }

    /**
     * Gets all data in the config file
     *
     * @return array
     */
    public function getAll()
    {
        return $this->configArr;
    }

    /**
     * Overwrites all config file's data
     *
     * @param array $data
     */
    public function overwrite(array $data)
    {
        $this->configArr = $data;
        $this->writeConfigFile($this->configArr, $this->configFile);
    }

    /**
     * Parses config file to an array
     *
     * @param string $configFile
     * @return array parsed config file as array
     */
    private function parseConfigFile($configFile)
    {
        return parse_ini_file($configFile, true);
    }

    /**
     * Writes array into an ini file
     *
     * @param array $array Data to write to ini file
     * @param string $file The filename of the ini file
     * @throws IniDepthException
     */
    private function writeConfigFile($array, $file)
    {
        $res = $this->arr2Ini($array);
        $this->safeFileRewrite($file, $res);
    }

    /**
     * @param $array
     * @return string
     * @throws IniDepthException
     */
    private function arr2Ini($array)
    {
        $res = [];
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $res[] = "[{$key}]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $sskey => $ssval) {
                            if (is_array($ssval)) {
                                throw new IniDepthException(
                                    'INI files support only 3 multidimensional depth, sorry.'
                                );
                            }
                            $res[] = $this->parseIniArrRow($skey, $ssval, $sskey);
                        }
                    } else {
                        $res[] = $this->parseIniRow($skey, $sval);
                    }
                }
            } else {
                $res[] = $this->parseIniRow($key, $val);
            }
        }
        return implode("\r\n", $res);
    }

    /**
     * @param string $key
     * @param string|int $val
     * @return string
     */
    private function parseIniRow($key, $val)
    {
        return "{$key} = " . (is_numeric($val) ? $val : '"' . $val . '"');
    }

    /**
     * @param string $key
     * @param string|int $val
     * @param string|int $arrKey
     * @return string
     */
    private function parseIniArrRow($key, $val, $arrKey)
    {
        $arrKey = is_numeric($arrKey) ? '' : $arrKey;
        return "{$key}[{$arrKey}] = " . (is_numeric($val) ? $val : '"' . $val . '"');
    }
}