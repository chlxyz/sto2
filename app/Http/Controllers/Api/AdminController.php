<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Size;
use App\Models\Type;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    use PresentsProducts;

    public function products()
    {
        $products = Product::with(['types', 'images', 'colors', 'sizes', 'sizeChart'])
            ->orderBy('position')->latest()->get();

        return response()->json($products->map(fn ($p) => self::presentProduct($p)));
    }

    private function syncTypes(Product $product, $names): void
    {
        $ids = collect((array) $names)->filter()->map(fn ($n) => Type::firstOrCreate(['name' => trim($n)])->id);
        $product->types()->sync($ids);
    }

    // $colors: [{name, hex?}] — creates missing colors, attaches all, detaches the rest.
    private function syncColors(Product $product, $colors): void
    {
        $ids = [];
        foreach ((array) $colors as $c) {
            $name = trim($c['name'] ?? '');
            if (! $name) {
                continue;
            }
            $color = Color::firstOrCreate(['name' => $name]);
            if (! empty($c['hex'])) {
                $color->update(['hex' => $c['hex']]);
            }
            $ids[] = $color->id;
        }
        $product->colors()->sync($ids);
    }

    // $sizes: [{label, stock?}] — same pattern; returns total stock for quantity sync.
    private function syncSizes(Product $product, $sizes): int
    {
        $sync = [];
        foreach ((array) $sizes as $s) {
            $label = trim($s['label'] ?? '');
            if (! $label) {
                continue;
            }
            $size = Size::firstOrCreate(['label' => $label]);
            $sync[$size->id] = ['stock' => max(0, (int) ($s['stock'] ?? 0))];
        }
        $product->sizes()->sync($sync);

        return array_sum(array_column($sync, 'stock'));
    }

    private function saveSizeChart(Request $request, Product $product, $data): void
    {
        $title = $data['size_chart_title'] ?? null;
        $remove = ! empty($data['remove_size_chart']);
        $hasFile = $request->hasFile('size_chart_image');

        if (! $title && ! $hasFile && ! $remove) {
            return;
        }

        $chart = $product->sizeChart()->firstOrNew([]);
        if ($remove && ! $title && ! $hasFile) {
            $chart->image_path && Storage::disk('public')->delete($chart->image_path);
            $chart->delete();

            return;
        }
        if ($hasFile) {
            $chart->image_path && Storage::disk('public')->delete($chart->image_path);
            $chart->image_path = $request->file('size_chart_image')->store('size_charts', 'public');
        }
        if ($title !== null) {
            $chart->title = $title ?: 'Size chart';
        }
        $chart->save();
    }

    // Stock gate: a product must carry sellable units — either sized rows
    // summing to 1+, or (one-size) a base quantity of 1+.
    private function stockTotal(array $data, ?Product $product = null): int
    {
        if (array_key_exists('sizes', $data)) {
            return collect($data['sizes'])->sum(fn ($s) => max(0, (int) ($s['stock'] ?? 0)));
        }
        if ($product && $product->sizes()->exists()) {
            return (int) $product->sizes()->sum('product_size.stock');
        }

        return (int) ($data['product_quantity'] ?? $product?->product_quantity ?? 0);
    }

    public function storeProduct(Request $request)
    {
        $data = $request->validate([
            'product_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'product_name' => 'required|string|max:255',
            'product_desc' => 'required|string|max:255',
            'product_quantity' => 'required|integer|min:0',
            'product_price' => 'required|numeric|min:0',
            'discount_percent' => 'sometimes|integer|min:0|max:100',
            'released_at' => 'sometimes|nullable|date',
            'is_listed' => 'sometimes|boolean',
            'types' => 'sometimes|array',
            'types.*' => 'string|max:255',
            'colors' => 'sometimes|array',
            'colors.*.name' => 'required_with:colors|string|max:255',
            'colors.*.hex' => 'sometimes|nullable|string|max:7',
            'sizes' => 'sometimes|array',
            'sizes.*.label' => 'required_with:sizes|string|max:255',
            'sizes.*.stock' => 'sometimes|integer|min:0',
            'size_chart_title' => 'sometimes|nullable|string|max:255',
            'size_chart_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'remove_size_chart' => 'sometimes|boolean',
        ]);

        if (Product::where('product_name', $data['product_name'])->exists()) {
            return response()->json(['message' => 'Product already exists. Update its quantity instead.'], 422);
        }

        if ($this->stockTotal($data) < 1) {
            return response()->json(['message' => 'Add at least one size with stock, or set Stock to 1 or more.'], 422);
        }

        $paths = [];
        if ($request->hasFile('product_photo')) {
            $paths[] = $request->file('product_photo')->store('product_photos', 'public');
        }
        foreach ($request->file('images', []) as $file) {
            $paths[] = $file->store('product_photos', 'public');
        }

        $product = Product::create([
            'product_photo' => $paths[0] ?? null,
            'product_name' => $data['product_name'],
            'product_desc' => $data['product_desc'],
            'product_quantity' => $data['product_quantity'],
            'product_price' => $data['product_price'],
            'product_type' => $data['types'][0] ?? null,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'released_at' => $data['released_at'] ?? null,
            'is_listed' => $data['is_listed'] ?? true,
        ]);

        foreach ($paths as $i => $path) {
            $product->images()->create(['path' => $path, 'position' => $i]);
        }
        $this->syncTypes($product, $data['types'] ?? []);
        $this->syncColors($product, $data['colors'] ?? []);
        if (array_key_exists('sizes', $data)) {
            $product->update(['product_quantity' => $this->syncSizes($product, $data['sizes'])]);
        }
        $this->saveSizeChart($request, $product, $data);

        return response()->json(self::presentProduct($product->fresh()), 201);
    }

    public function updateProduct(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->validate([
            'product_photo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'product_name' => 'required|string|max:255',
            'product_desc' => 'required|string|max:255',
            'product_quantity' => 'required|integer|min:0',
            'product_price' => 'required|numeric|min:0',
            'discount_percent' => 'sometimes|integer|min:0|max:100',
            'released_at' => 'sometimes|nullable|date',
            'is_listed' => 'sometimes|boolean',
            'types' => 'sometimes|array',
            'types.*' => 'string|max:255',
            'colors' => 'sometimes|array',
            'colors.*.name' => 'required_with:colors|string|max:255',
            'colors.*.hex' => 'sometimes|nullable|string|max:7',
            'sizes' => 'sometimes|array',
            'sizes.*.label' => 'required_with:sizes|string|max:255',
            'sizes.*.stock' => 'sometimes|integer|min:0',
            'size_chart_title' => 'sometimes|nullable|string|max:255',
            'size_chart_image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'remove_size_chart' => 'sometimes|boolean',
            'remove_image_ids' => 'sometimes|array',
            'remove_image_ids.*' => 'integer',
        ]);

        if ($this->stockTotal($data, $product) < 1) {
            return response()->json(['message' => 'Add at least one size with stock, or set Stock to 1 or more.'], 422);
        }

        $paths = [];
        if ($request->hasFile('product_photo')) {
            $product->product_photo && Storage::disk('public')->delete($product->product_photo);
            $paths[] = $request->file('product_photo')->store('product_photos', 'public');
        }
        foreach ($request->file('images', []) as $file) {
            $paths[] = $file->store('product_photos', 'public');
        }
        if (! empty($data['remove_image_ids'])) {
            $gone = $product->images()->whereIn('id', $data['remove_image_ids'])->get();
            foreach ($gone as $img) {
                Storage::disk('public')->delete($img->path);
                $img->delete();
            }
        }
        $position = ($product->images()->max('position') ?? -1) + 1;
        foreach ($paths as $path) {
            $product->images()->create(['path' => $path, 'position' => $position++]);
        }

        $product->update([
            'product_photo' => $product->images()->orderBy('position')->value('path'),
            'product_name' => $data['product_name'],
            'product_desc' => $data['product_desc'],
            'product_quantity' => $data['product_quantity'],
            'product_price' => $data['product_price'],
            'discount_percent' => $data['discount_percent'] ?? $product->discount_percent,
            'released_at' => array_key_exists('released_at', $data) ? $data['released_at'] : $product->released_at,
            'product_type' => $data['types'][0] ?? $product->product_type,
            'is_listed' => $data['is_listed'] ?? $product->is_listed,
        ]);
        if (array_key_exists('types', $data)) {
            $this->syncTypes($product, $data['types']);
        }
        if (array_key_exists('colors', $data)) {
            $this->syncColors($product, $data['colors']);
        }
        if (array_key_exists('sizes', $data)) {
            $product->update(['product_quantity' => $this->syncSizes($product, $data['sizes'])]);
        }
        $this->saveSizeChart($request, $product, $data);

        return response()->json(self::presentProduct($product->fresh()));
    }

    public function reorderProducts(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer|exists:products,id']);

        foreach (array_values($data['ids']) as $i => $id) {
            Product::where('id', $id)->update(['position' => $i]);
        }

        return response()->json(null, 204);
    }

    public function sizes()
    {
        return response()->json(
            Size::withCount('products')->orderBy('label')->get(['id', 'label'])
        );
    }

    public function storeSize(Request $request)
    {
        $data = $request->validate(['label' => 'required|string|max:255|unique:sizes,label']);

        return response()->json(Size::create(['label' => trim($data['label'])]), 201);
    }

    public function destroySize($id)
    {
        $size = Size::findOrFail($id);

        if ($size->products()->exists()) {
            return response()->json(['message' => 'Size is used by products. Remove it from them first.'], 422);
        }
        $size->delete();

        return response()->json(null, 204);
    }

    public function vouchers()
    {
        return response()->json(Voucher::orderBy('code')->get());
    }
    public function storeVoucher(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:32|unique:vouchers,code',
            'type' => 'required|in:percent,fixed',
            'value' => 'required|numeric|min:0',
            'min_subtotal' => 'sometimes|numeric|min:0',
            'expires_at' => 'sometimes|nullable|date',
            'max_uses' => 'sometimes|nullable|integer|min:1',
            'max_discount' => 'sometimes|nullable|numeric|min:0',
            'exclude_sale_items' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['code'] = strtoupper(trim($data['code']));

        return response()->json(Voucher::create($data), 201);
    }

    public function updateVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);
        $data = $request->validate([
            'type' => 'sometimes|in:percent,fixed',
            'value' => 'sometimes|numeric|min:0',
            'min_subtotal' => 'sometimes|numeric|min:0',
            'expires_at' => 'sometimes|nullable|date',
            'max_uses' => 'sometimes|nullable|integer|min:1',
            'max_discount' => 'sometimes|nullable|numeric|min:0',
            'exclude_sale_items' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);
        $voucher->update($data);

        return response()->json($voucher->fresh());
    }

    public function destroyVoucher($id)
    {
        Voucher::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function settings()
    {
        return response()->json([
            'tax_percent' => (float) Setting::get('tax_percent', '10'),
            'shipping_flat' => (float) Setting::get('shipping_flat', '5'),
            'free_shipping_min' => (float) Setting::get('free_shipping_min', '50'),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'tax_percent' => 'required|numeric|min:0|max:100',
            'shipping_flat' => 'required|numeric|min:0',
            'free_shipping_min' => 'required|numeric|min:0',
        ]);
        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
        }

        return response()->json($this->settings()->getData());
    }

    public function destroyProduct($id)
    {
        $product = Product::findOrFail($id);
        foreach ($product->images as $img) {
            Storage::disk('public')->delete($img->path);
        }
        $product->product_photo && Storage::disk('public')->delete($product->product_photo);
        foreach ($product->colors as $color) {
            $color->pivot->image_path && Storage::disk('public')->delete($color->pivot->image_path);
        }
        $product->delete();

        return response()->json(null, 204);
    }

    public function setColorImage(Request $request, $productId, $colorId)
    {
        $data = $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048']);

        $product = Product::findOrFail($productId);
        if (! $product->colors->contains('id', (int) $colorId)) {
            return response()->json(['message' => 'Color not attached to this product.'], 422);
        }

        $old = DB::table('color_product')->where('product_id', $product->id)->where('color_id', $colorId)->value('image_path');
        $old && Storage::disk('public')->delete($old);
        $path = $request->file('image')->store('product_colors', 'public');
        $product->colors()->updateExistingPivot($colorId, ['image_path' => $path]);

        return response()->json(self::presentProduct($product->fresh()));
    }

    public function detachColor($productId, $colorId)
    {
        $product = Product::findOrFail($productId);
        $old = DB::table('color_product')->where('product_id', $product->id)->where('color_id', $colorId)->value('image_path');
        $old && Storage::disk('public')->delete($old);
        $product->colors()->detach($colorId);

        return response()->json(self::presentProduct($product->fresh()));
    }

    public function reorderImages(Request $request, $id)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $owned = Product::findOrFail($id)->images()->pluck('id')->all();
        foreach (array_values($data['ids']) as $i => $imgId) {
            if (in_array($imgId, $owned)) {
                \App\Models\ProductImage::where('id', $imgId)->update(['position' => $i]);
            }
        }
        $first = Product::find($id)->images()->orderBy('position')->value('path');
        Product::where('id', $id)->update(['product_photo' => $first]);

        return response()->json(null, 204);
    }

    public function users()
    {
        return response()->json(User::latest()->get(['id', 'name', 'email', 'created_at']));
    }

    public function destroyUser($id)
    {
        User::findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    public function orders()
    {
        return response()->json(Order::with(['user:id,name,email', 'orderItems.product', 'orderItems.color', 'orderItems.size'])
            ->latest()->get()->map(fn ($o) => [
                'id' => $o->id,
                'user' => $o->user,
                'total_price' => (float) $o->total_price,
                'subtotal' => (float) ($o->subtotal ?? $o->total_price),
                'discount_total' => (float) ($o->discount_total ?? 0),
                'voucher_codes' => $o->voucher_codes ?? [],
                'voucher_amount' => (float) ($o->voucher_amount ?? 0),
                'tax_amount' => (float) ($o->tax_amount ?? 0),
                'shipping_amount' => (float) ($o->shipping_amount ?? 0),
                'status' => $o->status,
                'payment_method' => $o->payment_method,
                'is_paid' => (bool) $o->is_paid,
                'created_at' => $o->created_at,
                'items' => $o->orderItems->map(fn ($i) => [
                    'product_name' => $i->product->product_name,
                    'quantity' => $i->quantity,
                    'price' => (float) $i->price,
                    'color' => $i->color?->name,
                    'size' => $i->size_label ?? $i->size?->label,
                    'image_url' => $i->product->product_photo
                        ? asset('storage/'.$i->product->product_photo)
                        : null,
                ]),
            ]));
    }
}
