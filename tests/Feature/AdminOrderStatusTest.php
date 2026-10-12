<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Tests\TestCase;
use Tests\UsesTestDatabase;

/**
 * Admin fulfilment track: paid -> prepared -> shipped -> completed, forward-only,
 * and nothing at or past 'prepared' while the order is unpaid.
 *
 * Note: no setUp() here on purpose -- it would shadow UsesTestDatabase's hook.
 */
class AdminOrderStatusTest extends TestCase
{
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

    private function asApiUser(User $user)
    {
        return $this->withHeader('referer', config('app.url'))->actingAs($user);
    }

    private function order(string $status, bool $paid): Order
    {
        return Order::create([
            'user_id' => User::factory()->create()->id,
            'total_price' => 10,
            'status' => $status,
            'is_paid' => $paid,
        ]);
    }

    private function move(Order $order, string $status, User $as)
    {
        return $this->asApiUser($as)->patchJson("/api/admin/orders/{$order->id}/status", ['status' => $status]);
    }

    public function test_paid_order_advances_to_prepared()
    {
        $order = $this->order('paid', true);

        $this->move($order, 'prepared', $this->admin())
            ->assertOk()
            ->assertJsonPath('status', 'prepared');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'prepared']);
    }

    public function test_order_cannot_move_backwards()
    {
        $order = $this->order('prepared', true);

        $this->move($order, 'paid', $this->admin())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Cannot move an order from prepared to paid.');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'prepared']);
    }

    public function test_forward_jump_within_the_track_is_allowed()
    {
        $order = $this->order('prepared', true);

        $this->move($order, 'completed', $this->admin())
            ->assertOk()
            ->assertJsonPath('status', 'completed');
    }

    public function test_unpaid_order_cannot_reach_prepared()
    {
        $order = $this->order('paid', false);

        $this->move($order, 'prepared', $this->admin())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Order is not paid.');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    // The gate covers the whole back half, otherwise pending -> shipped walks
    // straight past it.
    public function test_unpaid_order_cannot_skip_straight_to_shipped()
    {
        $order = $this->order('pending', false);

        $this->move($order, 'shipped', $this->admin())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Order is not paid.');
    }

    // The offline-payment path: pending/awaiting_scan sit before the track, so
    // an admin can record a bank transfer without a separate action.
    public function test_unpaid_order_can_be_marked_paid()
    {
        $order = $this->order('pending', false);

        $this->move($order, 'paid', $this->admin())
            ->assertOk()
            ->assertJsonPath('status', 'paid');
    }

    public function test_unknown_status_is_rejected()
    {
        $order = $this->order('paid', true);

        $this->move($order, 'teleported', $this->admin())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Unknown status "teleported".');
    }

    public function test_settling_the_same_status_twice_is_a_no_op_not_an_error()
    {
        $order = $this->order('prepared', true);

        $this->move($order, 'prepared', $this->admin())
            ->assertOk()
            ->assertJsonPath('status', 'prepared');
    }

    public function test_a_settled_order_cannot_be_reopened()
    {
        $order = $this->order('completed', true);

        $this->move($order, 'prepared', $this->admin())
            ->assertStatus(422)
            ->assertJsonPath('message', 'Order is already completed.');
    }

    public function test_a_customer_cannot_move_an_order()
    {
        $order = $this->order('paid', true);

        $this->move($order, 'prepared', User::factory()->create())
            ->assertStatus(403);
    }
}