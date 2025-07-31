<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDrugRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only allow if user is admin or doctor
        return auth()->check() && in_array(auth()->user()->role->slug, ['admin', 'doctor']);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:drugs,name',
            'slug' => 'nullable|string|max:255|unique:drugs,slug',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'status' => 'required|string|in:active,inactive',
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:drug_categories,id',
            'disease_ids' => 'nullable|array',
            'disease_ids.*' => 'exists:diseases,id',
            'image' => 'nullable|image|max:2048',
        ];
    }
}
