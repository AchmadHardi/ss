<?php

namespace App\Services;

class UploadService
{
    public function uploadMultipleBuffer(array $binaries)
    {
        foreach ($binaries as $file) {
            file_put_contents(
                $file['target_dir'],
                $file['buffer']
            );
        }
    }
}
