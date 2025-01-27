<?php

require __DIR__ . '/vendor/autoload.php';

use MuxPhp\Api\UploadsApi;

if (class_exists(UploadsApi::class)) {
    echo "UploadsApi class loaded successfully!";
} else {
    echo "UploadsApi class not found!";
}
