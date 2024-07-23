<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

abstract class Controller
{
    public function saveImage($image, $path = 'public')
    {
        if (!$image) {
            return null;
        }

        $filename = time() . '.png';





        //save image

        Storage::disk($path)->put($filename, file_get_contents($image));

        //return path to image
        return URL::to('/') . '/storage/' . $path . '/' . $filename;
    }
}
