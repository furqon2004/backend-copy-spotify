<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

file_put_contents('test.txt', 'test');
$file = new \Illuminate\Http\UploadedFile('test.txt', 'test.txt', 'text/plain', null, true);

try {
    $service = app(App\Services\CloudinaryService::class);
    $url = $service->uploadAudio($file, 'test');
    echo "URL: $url\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
