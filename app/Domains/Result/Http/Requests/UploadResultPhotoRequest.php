<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadResultPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'template_item_id' => ['required', 'string'],
            'stamped_at' => ['nullable', 'string'],
            'is_camera_capture' => ['nullable'],
        ];
    }
}
