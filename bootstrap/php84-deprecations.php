<?php

if (PHP_VERSION_ID >= 80400) {
    // Laravel 8 dependencies still emit PHP 8.4 implicit-nullable deprecations.
    // Remove this once the framework and vendor packages are upgraded.
    error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}
