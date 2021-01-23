<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use ProtoneMedia\LaravelMixins\Request\ConvertsBase64ToFiles;

class ImageRequest extends FormRequest
{
    use ConvertsBase64ToFiles;

    protected function base64FileKeys(): array
    {
        return [
            'photo' => 'photo_cropped.png',
        ];
    }

    // public function authorize()
    // {
    //     return true;
    // }

    public function rules()
    {
        return [
            'photo' => ['required', 'file', 'image/png'],
        ];
    }
}
