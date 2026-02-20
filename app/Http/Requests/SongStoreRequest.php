<?php 
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SongStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->artist !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'album_id' => ['nullable', 'exists:albums,id'],
            'genre_ids' => ['required', 'array', 'min:1'],
            'genre_ids.*' => ['exists:genres,id'],
            'cover_url' => ['nullable', 'string', 'url', 'max:500'],
            'audio' => [
                $this->isMethod('post') ? 'required' : 'nullable',
                File::types(['mp3', 'wav'])
                    ->max('20mb')
            ],
            'lyrics' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.max' => 'Ukuran file audio maksimal adalah 20MB.',
            'audio.mimes' => 'Format file harus berupa mp3 atau wav.',
            'cover.max' => 'Ukuran cover maksimal adalah 5MB.',
        ];
    }
}