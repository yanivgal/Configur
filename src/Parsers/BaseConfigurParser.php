<?php

namespace yanivgal\Parsers;

class BaseConfigurParser
{
    /**
     * @var string
     */
    protected $configFile;

    /**
     * @param string $configFile
     */
    public function __construct($configFile)
    {
        $this->configFile = $configFile;
    }

    /**
     * @param string $fileName
     * @param string $dataToSave
     */
    protected function safeFileRewrite($fileName, $dataToSave)
    {
        if ($fp = fopen($fileName, 'w')) {
            $startTime = microtime();
            do {
                $canWrite = flock($fp, LOCK_EX);
                // In order to avoid collision and CPU load,
                // if lock not obtained sleep for 0 - 100 milliseconds
                if (!$canWrite) {
                    usleep(round(rand(0, 100)*1000));
                }
            } while ((!$canWrite) and ((microtime()-$startTime) < 1000));

            //file was locked so now we can store information
            if ($canWrite) {
                fwrite($fp, $dataToSave);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }
}