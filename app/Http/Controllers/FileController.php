<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{
    private $files;

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function posts()
    {
        $listFiles = [
            'video' => [],
            'image' => []
        ];
        $files = $this->files;
        if ($files) {
            foreach ($files as $file) {
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'image/')) {
                    $path = $file->store('posts/image','public');
                    $fullPath = custom_url('storage/' . $path);
                    $listFiles['image'][] = $fullPath;
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $path = $file->store('posts/video','public');
                    $fullPath = custom_url('storage/' . $path);
                    $listFiles['video'][] = $fullPath;
                }
            }
        }
        return $listFiles;
    }
}
