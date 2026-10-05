<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;
use Tests\UsesTestDatabase;

/**
 * Regression net for the hardening pass.
 *
 * The API layer was always protected; routes/web.php sat outside every
 * middleware group and was anonymously reachable. These assertions exist so
 * that can't silently rot back.
 *
 * Note: no setUp() here on purpose -- it would shadow UsesTestDatabase's hook.
 * Per-test config goes through defineEnvironment().
 */
class RouteAccessTest extends TestCase
{
    // Aliased because a setUp() defined here would otherwise shadow the trait's
    // database hook. Laravel 11's TestCase has no defineEnvironment() hook.
    use UsesTestDatabase { setUp as private baseSetUp; }

    protected function setUp(): void
    {
        $this->baseSetUp();

        config(['admin.email' => 'admin@test.local']);
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@test.local']);
    }

    private function orderFor(User $user, string $status = 'pending'): Order
    {
        return Order::create(['user_id' => $user->id, 'total_price' => 10, 'status' => $status]);
    }

    /**
     * The API authenticates via Sanctum's stateful (cookie) guard, which only
     * engages when the request looks like it came from the frontend. The
     * Referer header is what makes it stateful in a test.
     */
    private function asApiUser(User $user)
    {
        return $this->withHeader('referer', config('app.url'))->actingAs($user);
    }

    public static function protectedWebRoutes(): array
    {
        return [
            'dashboard'      => ['get', '/dashboard'],
            'cart'           => ['get', '/cart'],
            'cart add'       => ['post', '/cart/add/'],
            'cart process'   => ['post', '/cart/process'],
            'order'          => ['post', '/order'],
            'orders'         => ['get', '/orders'],
            'logout'         => ['post', '/logout'],
            'admindashboard' => ['get', '/admindashboard'],
            'addproduct ui'  => ['get', '/addproduct'],
            'addproduct'     => ['post', '/addproduct'],
            'adminallusers'  => ['get', '/adminallusers'],
            'adminorders'    => ['get', '/adminorders'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('protectedWebRoutes')]
    public function test_guest_is_redirected_away_from_protected_web_routes($method, $uri)
    {
        $this->{$method}($uri)->assertRedirect('/login');
    }

    public static function legacyAdminRoutes(): array
    {
        return [
            'admindashboard'    => ['get', '/admindashboard'],
            'addproduct ui'     => ['get', '/addproduct'],
            'addproduct'        => ['post', '/addproduct'],
            'adminallusers'     => ['get', '/adminallusers'],
            'adminallusers del' => ['post', '/adminallusers'],
            'adminorders'       => ['get', '/adminorders'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('legacyAdminRoutes')]
    public function test_non_admin_gets_403_on_the_legacy_blade_admin_panel($method, $uri)
    {
        $this->actingAs(User::factory()->create())
            ->{$method}($uri)
            ->assertForbidden();
    }

    public function test_user_cannot_edit_another_users_profile()
    {
        $victim = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/profile/{$victim->id}/update", [
                'name'     => 'pwned',
                'email'    => $victim->email,
                'password' => 'password123',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['id' => $victim->id, 'name' => 'pwned']);
    }

    public function test_user_cannot_delete_another_user()
    {
        $victim = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete("/delete/{$victim->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $victim->id]);
    }

    public function test_user_cannot_view_another_users_profile()
    {
        $victim = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get("/profile/{$victim->id}")
            ->assertNotFound();
    }

    public function test_user_cannot_cancel_another_users_order()
    {
        $order = $this->orderFor(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->delete("/orders/{$order->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_user_cannot_cancel_an_order_once_it_leaves_the_cancellable_states()
    {
        $order = $this->orderFor(User::factory()->create(), 'completed');

        $this->actingAs($order->user)
            ->delete("/orders/{$order->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_admin_cannot_delete_their_own_account_via_the_admin_api()
    {
        $admin = $this->admin();

        $this->asApiUser($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_delete_the_last_admin_account()
    {
        $admin = $this->admin();

        $this->asApiUser($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(422);

        // The identity must survive: otherwise POST /api/register can claim it.
        $this->assertSame(1, User::where('email', config('admin.email'))->count());
    }

    public function test_admin_cannot_delete_a_user_with_open_orders()
    {
        $admin = $this->admin();
        $buyer = User::factory()->create();
        $this->orderFor($buyer, 'pending');

        $this->asApiUser($admin)
            ->deleteJson("/api/admin/users/{$buyer->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $buyer->id]);
    }

    public function test_admin_can_delete_a_user_whose_orders_are_all_closed()
    {
        $admin = $this->admin();
        $buyer = User::factory()->create();
        $this->orderFor($buyer, 'completed');

        $this->asApiUser($admin)
            ->deleteJson("/api/admin/users/{$buyer->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('users', ['id' => $buyer->id]);
    }

    public function test_admin_cannot_delete_a_product_referenced_by_an_order()
    {
        $admin = $this->admin();
        $product = Product::create([
            'product_name' => 'Kept', 'product_desc' => 'd',
            'product_quantity' => 1, 'product_price' => 5, 'product_type' => 'x',
        ]);
        $order = $this->orderFor(User::factory()->create());
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id,
            'quantity' => 1, 'price' => 5,
        ]);

        $this->asApiUser($admin)
            ->deleteJson("/api/admin/products/{$product->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_cart_add_rejects_a_negative_quantity()
    {
        $product = Product::create([
            'product_name' => 'P', 'product_desc' => 'd',
            'product_quantity' => 5, 'product_price' => 5, 'product_type' => 'x',
        ]);

        $this->actingAs(User::factory()->create())
            ->from('/cart')
            ->post('/cart/add/', ['product_id' => $product->id, 'quantity' => -5])
            ->assertRedirect('/cart')
            ->assertSessionHasErrors('quantity');
    }

    public function test_web_login_is_rate_limited()
    {
        foreach (range(1, 6) as $i) {
            $this->post('/login', ['email' => 'nobody@test.local', 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => 'nobody@test.local', 'password' => 'wrong'])
            ->assertStatus(429);
    }
}