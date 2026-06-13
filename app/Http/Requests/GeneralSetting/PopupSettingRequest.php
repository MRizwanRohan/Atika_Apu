<?php

namespace App\Http\Requests\GeneralSetting;

use Illuminate\Foundation\Http\FormRequest;

class PopupSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'popup_enabled' => 'boolean',
            'popup_image' => 'nullable|string',
            'popup_image_file' => 'nullable|image|max:2048',
            'popup_link' => 'nullable|string',
        ];
    }
}
