<?php

namespace Rusbelito\Billing\Tests\Unit;

use Rusbelito\Billing\Tests\TestCase;

class HasSubscriptionsTraitTest extends TestCase
{
    /** @test */
    public function user_has_subscriptions_relationship()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $subscription = $this->createSubscription($user, $plan);

        $this->assertTrue($user->subscriptions->contains($subscription));
    }

    /** @test */
    public function user_can_get_current_subscription()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        
        // Crear suscripción activa
        $currentSubscription = $this->createSubscription($user, $plan, [
            'status' => 'active',
        ]);

        $this->assertEquals($currentSubscription->id, $user->currentSubscription()->id);
        $this->assertNotEquals($oldSubscription->id, $user->currentSubscription()->id);
    }

    /** @test */
    public function user_has_usages_relationship()
    {
        $user = $this->createUser();

        \Rusbelito\Billing\Models\Usage::create([
            'user_id' => $user->id,
            'action_key' => 'api_call',
            'quantity' => 100,
            'recorded_at' => now(),
        ]);

        $this->assertCount(1, $user->usages);
    }

    /** @test */
    public function user_has_transactions_relationship()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();

        \Rusbelito\Billing\Models\Transaction::create([
            'user_id' => $user->id,
            'type' => 'one_time',
            'purchasable_type' => get_class($plan),
            'purchasable_id' => $plan->id,
            'amount' => 100,
            'discount' => 0,
            'total' => 100,
            'status' => 'pending',
        ]);

        $this->assertCount(1, $user->transactions);
    }

    /** @test */
    public function user_has_billing_addresses_relationship()
    {
        $user = $this->createUser();

        \Rusbelito\Billing\Models\BillingAddress::create([
            'user_id' => $user->id,
            'legal_name' => 'Test Company',
            'tax_id' => '123456789',
            'tax_id_type' => 'nit',
            'address_line_1' => 'Test St',
            'city' => 'Bogotá',
            'country' => 'CO',
            'email' => 'test@test.com',
            'is_default' => true,
        ]);

        $this->assertCount(1, $user->billingAddresses);
    }

    /** @test */
    public function user_can_get_default_billing_address()
    {
        $user = $this->createUser();

        $address1 = \Rusbelito\Billing\Models\BillingAddress::create([
            'user_id' => $user->id,
            'legal_name' => 'Test Company 1',
            'tax_id' => '111',
            'tax_id_type' => 'nit',
            'address_line_1' => 'Test St 1',
            'city' => 'Bogotá',
            'country' => 'CO',
            'email' => 'test1@test.com',
            'is_default' => false,
        ]);

        $address2 = \Rusbelito\Billing\Models\BillingAddress::create([
            'user_id' => $user->id,
            'legal_name' => 'Test Company 2',
            'tax_id' => '222',
            'tax_id_type' => 'nit',
            'address_line_1' => 'Test St 2',
            'city' => 'Bogotá',
            'country' => 'CO',
            'email' => 'test2@test.com',
            'is_default' => true,
        ]);

        $this->assertEquals($address2->id, $user->defaultBillingAddress->id);
    }

    /** @test */
    public function user_has_payment_methods_relationship()
    {
        $user = $this->createUser();
        $gateway = $this->createPaymentGateway();
        
        $method = $this->createPaymentMethod($user, $gateway);

        $this->assertCount(1, $user->paymentMethods);
    }

    /** @test */
    public function user_can_get_default_payment_method()
    {
        $user = $this->createUser();
        $gateway = $this->createPaymentGateway();

        $method1 = $this->createPaymentMethod($user, $gateway, [
            'is_default' => false,
        ]);

        $method2 = $this->createPaymentMethod($user, $gateway, [
            'is_default' => true,
        ]);

        $this->assertEquals($method2->id, $user->defaultPaymentMethod->id);
    }

    /** @test */
    public function user_has_invoices_relationship()
    {
        $user = $this->createUser();
        $plan = $this->createPlan();
        $subscription = $this->createSubscription($user, $plan);

        $invoice = \Rusbelito\Billing\Models\Invoice::create([
            'invoice_number' => 'INV-2025-0001',
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => 'subscription',
            'issued_at' => now(),
            'subtotal' => 100,
            'discount' => 0,
            'tax' => 0,
            'total' => 100,
            'status' => 'draft',
        ]);

        $this->assertCount(1, $user->invoices);
    }

    /** @test */
    public function user_has_payment_attempts_relationship()
    {
        $user = $this->createUser();
        $gateway = $this->createPaymentGateway();
        $method = $this->createPaymentMethod($user, $gateway);

        \Rusbelito\Billing\Models\PaymentAttempt::create([
            'user_id' => $user->id,
            'payment_gateway_id' => $gateway->id,
            'payment_method_id' => $method->id,
            'amount' => 100,
            'currency' => 'COP',
            'gateway_order_number' => 'ORD-TEST-123',
            'status' => 'pending',
        ]);

        $this->assertCount(1, $user->paymentAttempts);
    }

    /** @test */
    public function user_has_coupon_usages_relationship()
    {
        $user = $this->createUser();
        $coupon = $this->createCoupon();

        \Rusbelito\Billing\Models\CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'discount_amount' => 50,
            'original_amount' => 100,
            'final_amount' => 50,
        ]);

        $this->assertCount(1, $user->couponUsages);
    }
}
ción cancelada
        $oldSubscription = $this->createSubscription($user, $plan, [
            'status' => 'cancelled',
        ]);

        // Crear suscrip