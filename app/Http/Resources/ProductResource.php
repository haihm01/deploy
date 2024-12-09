<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $minSalePrice = $this->variants->flatMap(function ($variant) {
            return $variant->pluck('price');
        })->min();

        $maxSalePrice = $this->variants->flatMap(function ($variant) {
            return $variant->pluck('price');
        })->max();

        $minImportPrice = $this->variants->flatMap(function ($variant) {
            return $variant->inventoryImports->pluck('import_price');
        })->min();

        $maxImportPrice = $this->variants->flatMap(function ($variant) {
            return $variant->inventoryImports->pluck('import_price');
        })->max();

        $quantities = $this->variants->flatMap(function ($variant) {
            return $variant->inventoryStock ? [$variant->inventoryStock->quantity] : [];
        });

        $minQuantity = $quantities->min();
        $maxQuantity = $quantities->max();

        // Xây dựng phạm vi giá trị
        $quantityRange = $minQuantity === $maxQuantity ? $minQuantity : $minQuantity . ' - ' . $maxQuantity;
        $priceRange = $minSalePrice === $maxSalePrice
            ? number_format($minSalePrice, 0, ',', '.')
            : number_format($minSalePrice, 0, ',', '.') . ' - ' . number_format($maxSalePrice, 0, ',', '.');

        $importPriceRange = $minImportPrice === $maxImportPrice
            ? number_format($minImportPrice, 0, ',', '.')
            : number_format($minImportPrice, 0, ',', '.') . ' - ' . number_format($maxImportPrice, 0, ',', '.');

        // Trả về dữ liệu
        return [
            'id' => $this->id,
            'quantity_range' => $quantityRange,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_range' => $priceRange,
            'import_price_range' => $importPriceRange,
            'images' => json_decode($this->images, associative: true),
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,
            'variants' => VariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}
