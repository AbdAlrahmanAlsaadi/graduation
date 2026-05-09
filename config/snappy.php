<?php

return [

    'pdf' => [
        'enabled' => true,
        'binary'  => env('WKHTML_PDF_BINARY', 'C:/Program Files/wkhtmltopdf/bin/wkhtmltopdf.exe'),
        'timeout' => false,
        'options' => [],
    ],

    'image' => [
        'enabled' => true,
        'binary'  => env('WKHTML_IMG_BINARY', 'C:/Program Files/wkhtmltopdf/bin/wkhtmltoimage.exe'),
        'timeout' => false,
        'options' => [],
    ],

];
