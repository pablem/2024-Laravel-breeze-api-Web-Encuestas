<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $toIgnore = $this->filled('previous_email_id') ? $this->input('previous_email_id') : $this->user()->id;
        return [
            'name' => ['string', 'max:50'],
            'email' => ['email', 'max:255', Rule::unique(User::class)->ignore($toIgnore)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['nullable', 'string', Rule::in(['Administrador', 'Editor', 'Publicador'])],
        ];
    }
}
