<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\UsesTestDatabase;

/**
 * Cart variant switching: PATCH /api/cart/{id} can now change a line's size and
 * color, and must never leave more units in the cart than the target variant has
 * in stock.
 *
 * Note: no setUp() here on purpose -- it would shadow UsesTestDatabase's hook.
 */
class CartVariantTest extends TestCase
{
    use UsesTestDatabase { setUp as private baseSetUp; }

    protected function setUp(): void
    {
        $this->baseSetUp();
        config(['admin.email' => 'admin@test.local']);
    }

    /** Sanctum's stateful guard engages on the referer, same as RouteAccessTest. */
    private function asApiUser(User $user)
    {
        return $this->withHeader('referer', config('app.url'))->actingAs($user);
    }

    private function product(): Product
    {
        return Product::create([
            'product_name' => 'Tee', 'product_desc' => 'd',
            'product_quantity' => 99, 'product_price' => 10, 'product_type' => 'x',
        ]);
    }

    // Not named size(): that collides with a final PHPUnit method.
    private function makeSize(string $label): int
    {
        return (int) DB::table('sizes')->insertGetId(['label' => $label, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function stock(int $productId, int $sizeId, int $qty): void
    {
        DB::table('product_size')->insert([
            'product_id' => $productId, 'size_id' => $sizeId, 'stock' => $qty,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function line(User $user, Product $product, ?int $sizeId, int $qty): CartItem
    {
        return CartItem::create([
            'user_id' => $user->id, 'product_id' => $product->id,
            'size_id' => $sizeId, 'quantity' => $qty,
        ]);
    }

    public function test_switching_to_a_smaller_size_clamps_the_quantity()
    {
        $user = User::factory()->create();
        $product = $this->product();
        $xl = $this->makeSize('XL');
        $l = $this->makeSize('L');
        $this->stock($product->id, $xl, 5);
        $this->stock($product->id, $l, 2);

        $item = $this->line($user, $product, $xl, 5);

        // Asking for 5 of L, which only has 2 -- must land on 2, not 5.
        $this->asApiUser($user)
            ->patchJson("/api/cart/{$item->id}", ['quantity' => 5, 'size_id' => $l])
            ->assertOk()
            ->assertJsonPath('size.id', $l)
            ->assertJsonPath('quantity', 2);

        $this->assertDatabaseHas('cart_item', ['id' => $item->id, 'size_id' => $l, 'quantity' => 2]);
    }

    public function test_switching_onto_a_variant_already_in_the_cart_merges_the_rows()
    {
        $user = User::factory()->create();
        $product = $this->product();
        $xl = $this->makeSize('XL');
        $l = $this->makeSize('L');
        $this->stock($product->id, $xl, 9);
        $this->stock($product->id, $l, 9);

        $keep = $this->line($user, $product, $xl, 2);
        $move = $this->line($user, $product, $l, 3);

        $this->asApiUser($user)
            ->patchJson("/api/cart/{$move->id}", ['quantity' => 3, 'size_id' => $xl])
            ->assertOk()
            ->assertJsonPath('id', $keep->id)
            ->assertJsonPath('quantity', 5);

        // One row for (product, XL), the moved line is gone.
        $this->assertDatabaseMissing('cart_item', ['id' => $move->id]);
        $this->assertSame(
            1,
            CartItem::where('user_id', $user->id)->where('size_id', $xl)->count()
        );
    }

    public function test_merging_clamps_to_the_shared_variants_stock()
    {
        $user = User::factory()->create();
        $product = $this->product();
        $xl = $this->makeSize('XL');
        $l = $this->makeSize('L');
        $this->stock($product->id, $xl, 4);
        $this->stock($product->id, $l, 4);

        $this->line($user, $product, $xl, 3);
        $move = $this->line($user, $product, $l, 3);

        // 3 + 3 = 6 but XL only has 4.
        $this->asApiUser($user)
            ->patchJson("/api/cart/{$move->id}", ['quantity' => 3, 'size_id' => $xl])
            ->assertOk()
            ->assertJsonPath('quantity', 4);
    }

    public function test_switching_to_an_out_of_stock_size_is_rejected()
    {
        $user = User::factory()->create();
        $product = $this->product();
        $xl = $this->makeSize('XL');
        $gone = $this->makeSize('GONE');
        $this->stock($product->id, $xl, 5);
        $this->stock($product->id, $gone, 0);

        $item = $this->line($user, $product, $xl, 1);

        $this->asApiUser($user)
            ->patchJson("/api/cart/{$item->id}", ['quantity' => 1, 'size_id' => $gone])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Product is out of stock.');

        $this->assertDatabaseHas('cart_item', ['id' => $item->id, 'size_id' => $xl]);
    }

    public function test_a_size_belonging_to_another_product_is_rejected()
    {
        $user = User::factory()->create();
        $mine = $this->product();
        $theirs = $this->product();
        $xl = $this->makeSize('XL');
        $this->stock($mine->id, $xl, 5);
        $this->stock($theirs->id, $xl, 5);

        $item = $this->line($user, $mine, $xl, 1);

        $other = DB::table('sizes')->insertGetId(['label' => 'OTHER', 'created_at' => now(), 'updated_at' => now()]);
        $this->stock($theirs->id, $other, 5);

        $this->asApiUser($user)
            ->patchJson("/api/cart/{$item->id}", ['quantity' => 1, 'size_id' => $other])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid size for this product.');
    }

    public function test_omitting_the_variant_fields_leaves_size_and_color_untouched()
    {
        $user = User::factory()->create();
        $product = $this->product();
        $xl = $this->makeSize('XL');
        $this->stock($product->id, $xl, 5);

        $item = $this->line($user, $product, $xl, 2);

        $this->asApiUser($user)
            ->patchJson("/api/cart/{$item->id}", ['quantity' => 4])
            ->assertOk()
            ->assertJsonPath('size.id', $xl)
            ->assertJsonPath('quantity', 4);
    }

    public function test_stored_add_still_clamps_to_the_chosen_size()
    {
        $user = User::factory()->create();
        $product = $this->product();
        $xl = $this->makeSize('XL');
        $l = $this->makeSize('L');
        $this->stock($product->id, $xl, 5);
        $this->stock($product->id, $l, 2);

        // The frontend now blocks this, but the API is the real boundary.
        $this->asApiUser($user)
            ->postJson('/api/cart', ['product_id' => $product->id, 'size_id' => $l, 'quantity' => 9])
            ->assertStatus(201)
            ->assertJsonPath('quantity', 2);
    }
}