<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize specification: accept JSON string, empty string or null and convert to array/null
        if ($this->has('specification')) {
            $spec = $this->input('specification');

            if (is_string($spec)) {
                $decoded = json_decode($spec, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['specification' => $decoded]);
                } elseif ($spec === '' || strtolower($spec) === 'null') {
                    $this->merge(['specification' => null]);
                } else {
                    // Non-parseable string -> null to avoid validation error
                    $this->merge(['specification' => null]);
                }
            }
        }

        // Normalize product_tags (similar handling)
        if ($this->has('product_tags')) {
            $tags = $this->input('product_tags');
            if (is_string($tags)) {
                $decoded = json_decode($tags, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge(['product_tags' => $decoded]);
                } elseif ($tags === '' || strtolower($tags) === 'null') {
                    $this->merge(['product_tags' => null]);
                } else {
                    $this->merge(['product_tags' => null]);
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'product_code' => 'required|string|max:50|unique:products,product_code,' . $this->product->id,
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $this->product->id,
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|in:Published,Unpublished',
            'is_daily_product' => 'nullable|boolean',
            'is_pre_order' => 'nullable|boolean',
            'type' => 'required|in:simple,variable',
            'price' => 'required|numeric|min:0',
            'previous_price' => 'nullable|numeric|min:0|gt:price',
            'youtube_video' => 'nullable|string',
            'feature_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'product_tags' => 'nullable|array',
            'product_tags.*' => 'string|max:255',
            'specification' => 'nullable|array',
            'stock' => 'nullable|integer|min:0',
            'is_free_delivery' => 'nullable|boolean',
            'remarks' => 'nullable|in:New,Popular,Trending,Hot,Special',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',

            // Validation for variations if product is variable
            'variations' => 'required_if:type,variable|array',
            'variations.*.price' => 'required_if:type,variable|numeric|min:0',
            'variations.*.stock' => 'nullable|integer|min:0',
            'variations.*.image_path' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'variations.*.attributes' => 'required_if:type,variable|array',
            'variations.*.attributes.*.attribute_value_id' => 'required_if:type,variable|integer|exists:attribute_values,id',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.required' => 'Product price is required.',
            'price.numeric' => 'Price must be a valid number.',
            'price.min' => 'Price cannot be negative.',

            'previous_price.numeric' => 'Previous price must be a valid number.',
            'previous_price.min' => 'Previous price cannot be negative.',
            'previous_price.gt' => 'Previous price must be greater than regular price. (Example: If regular price is 100, previous price should be 150)',
        ];
    }
}
