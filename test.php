<?php

use yanivgal\Configur;
use yanivgal\Custom\CustomConfigur;

require __DIR__ . '/vendor/autoload.php';

$settings = [
    Configur::SETTINGS_KEY_CUSTOM_MANAGERS => ['yanivgal\Custom\CustomManagers\ConfigTestManager']
];
$configur = new CustomConfigur($settings);
print_r($configur->configTest->key());