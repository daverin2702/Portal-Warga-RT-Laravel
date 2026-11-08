<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('kelola-warga');
    }

    public function rules(): array
    {
        $id = $this->route('household')?->id;

        return [
            'head_name' => ['required', 'string', 'max:100'],
            'family_card_number' => ['nullable', 'digits_between:8,20', Rule::unique('households')->ignore($id)],
            'head_nik' => ['nullable', 'digits_between:8,20'],
            'birth_place' => ['nullable', 'string', 'max:60'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'religion' => ['nullable', Rule::in(config('rt.religions'))],
            'occupation' => ['nullable', 'string', 'max:60'],
            'street' => ['nullable', 'string', 'max:200'],
            'block' => ['nullable', 'string', 'max:20'],
            'house_number' => ['required', 'string', 'max:10'],
            'members_count' => ['required', 'integer', 'min:1', 'max:50'],
            'residency_status' => ['required', Rule::in(array_keys(Household::RESIDENCY))],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['block' => $this->filled('block') ? strtoupper(trim($this->input('block'))) : null]);
    }
}
