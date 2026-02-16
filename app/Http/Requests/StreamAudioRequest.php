<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StreamAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->hasValidSignature();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'signature' => ['required', 'string'],
            'expires' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'signature.required' => 'Signature wajib ada.',
            'expires.required' => 'Expiry time wajib ada.',
        ];
    }

    protected function failedAuthorization()
    {
        abort(response()->json(['message' => 'Invalid or expired stream link.'], 403));
    }
}
