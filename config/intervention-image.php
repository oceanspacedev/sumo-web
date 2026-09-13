<?php

return [
    'driver' => env('IMAGE_DRIVER', Intervention\Image\Drivers\Gd\Driver::class),
    'options' => [
        // Keep the legacy GD behavior: EXIF orientation is not applied again.
        'autoOrientation' => false,
        'decodeAnimation' => false,
        'backgroundColor' => 'ffffff',
        'strip' => true,
    ],
];
