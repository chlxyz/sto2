<?php

namespace App\Http\Controllers\Api;

use App\Support\ImageUrl;

trait PresentsProducts
{
    protected static function presentProduct($p): array
    {
        $p->loadMissing(['types', 'images', 'colors', 'sizes', 'sizeChart']);

        return [
            'id' => $p->id,
            'name' => $p->product_name,
            'description' => $p->product_desc,
            'price' => (float) $p->product_price,
            'discount_percent' => (int) ($p->discount_percent ?? 0),
            'effective_price' => $p->effectivePrice(),
            'quantity' => $p->stockAvailable(),
            'is_listed' => (bool) $p->is_listed,
            'released_at' => $p->released_at,
            'is_preorder' => $p->isPreorder(),
            'id' => $p->id,
            'name' => $p->product_name,
            'description' => $p->product_desc,
            'price' => (float) $p->product_price,
            'discount_percent' => (int) ($p->discount_percent ?? 0),
            'effective_price' => $p->effectivePrice(),
            'quantity' => $p->stockAvailable(),
            'released_at' => $p->released_at,
            'is_preorder' => $p->isPreorder(),
            'types' => $p->types->pluck('name')->all(),
            'images' => $p->images->map(fn ($img) => ['id' => $img->id, 'url' => ImageUrl::make($img->path)])->all()
                ?: ($p->product_photo ? [['id' => null, 'url' => ImageUrl::make($p->product_photo)]] : []),
            'colors' => $p->colors->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'hex' => $c->hex,
                'image_url' => ImageUrl::make($c->pivot->image_path),
            ])->all(),
            'sizes' => $p->sizes->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->label,
                'stock' => (int) $s->pivot->stock,
            ])->all(),
            'size_chart' => $p->sizeChart ? [
                'title' => $p->sizeChart->title,
                'image_url' => ImageUrl::make($p->sizeChart->image_path),
            ] : null,
        ];
    }
}
