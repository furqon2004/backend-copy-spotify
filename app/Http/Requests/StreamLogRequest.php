<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StreamLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'song_id' => ['required', 'uuid', 'exists:songs,id'],
            'duration_played_ms' => ['required', 'integer', 'min:30000'],
            'source' => ['required', 'string', 'in:PLAYLIST,SEARCH,AI_RECOMMENDATION'],
        ];
    }

    public function messages(): array
    {
        return [
            'song_id.required' => 'Song ID wajib diisi.',
            'song_id.uuid' => 'Song ID harus berformat UUID.',
            'song_id.exists' => 'Song tidak ditemukan.',
            'duration_played_ms.required' => 'Durasi diputar wajib diisi.',
            'duration_played_ms.min' => 'Durasi minimal 30 detik (30000ms).',
            'source.required' => 'Sumber stream wajib diisi.',
            'source.in' => 'Sumber harus salah satu dari: PLAYLIST, SEARCH, AI_RECOMMENDATION.',
        ];
    }
}
