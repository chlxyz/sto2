<?php

namespace App\Http\Controllers\Api;

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
            'images' => $p->images->map(fn ($img) => ['id' => $img->id, 'url' => asset('storage/'.$img->path)])->all()
                ?: ($p->product_photo ? [['id' => null, 'url' => asset('storage/'.$p->product_photo)]] : []),
            'colors' => $p->colors->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'hex' => $c->hex,
                'image_url' => $c->pivot->image_path ? asset('storage/'.$c->pivot->image_path) : null,
            ])->all(),
            'sizes' => $p->sizes->map(fn ($s) => [
                'id' => $s->id,
                'label' => $s->label,
                'stock' => (int) $s->pivot->stock,
            ])->all(),
            'size_chart' => $p->sizeChart ? [
                'title' => $p->sizeChart->title,
                'image_url' => $p->sizeChart->image_path ? asset('storage/'.$p->sizeChart->image_path) : null,
            ] : null,
        ];
    }
}
