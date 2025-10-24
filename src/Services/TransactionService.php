<?php

namespace Rusbelito\Billing\Services;

use App\Models\User;
use Rusbelito\Billing\Models\Transaction;
use Rusbelito\Billing\Exceptions\CouponException;

class TransactionService
{
    protected CouponService $couponService;

    public function __construct(CouponService $couponService = null)
    {
        $this->couponService = $couponService ?? new CouponService();
    }

    /**
     * Crear una transacción de pago único
     */
    public function createOneTimeTransaction(
        User $user,
        float $amount,
        string $purchasableType,
        int $purchasableId,
        ?string $couponCode = null,
        array $meta = []
    ): Transaction {
        $discount = 0;
        $couponId = null;
        $total = $amount;

        // Si hay cupón, aplicarlo
        if ($couponCode) {
            try {
                $couponResult = $this->couponService->apply(
                    $couponCode,
                    $user,
                    $amount,
                    null, // No hay plan específico en one-time
                    null, // No hay billing mode en one-time
                    $purchasableType,
                    $purchasableId
                );

                $discount = $couponResult['discount_amount'];
                $total = $couponResult['final_amount'];
                $couponId = $this->couponService->find($couponCode)->id;

            } catch (CouponException $e) {
                throw $e;
            }
        }

        // Crear transacción
        return Transaction::create([
            'user_id' => $user->id,
            'type' => 'one_time',
            'purchasable_type' => $purchasableType,
            'purchasable_id' => $purchasableId,
            'amount' => $amount,
            'discount' => $discount,
            'total' => $total,
            'status' => 'pending',
            'coupon_id' => $couponId,
            'meta' => $meta,
        ]);
    }

    /**
     * Calcular precio con cupón (sin crear transacción)
     */
    public function calculatePrice(
        User $user,
        float $amount,
        ?string $couponCode = null
    ): array {
        if (!$couponCode) {
            return [
                'original_amount' => $amount,
                'discount' => 0,
                'total' => $amount,
                'coupon_applied' => false,
            ];
        }

        try {
            // Solo validar, no aplicar
            $validation = $this->couponService->validate(
                $couponCode,
                $user,
                $amount
            );

            $discount = $validation['discount_amount'];

            return [
                'original_amount' => $amount,
                'discount' => $discount,
                'total' => $amount - $discount,
                'coupon_applied' => true,
                'coupon_code' => $couponCode,
            ];
        } catch (CouponException $e) {
            throw $e;
        }
    }

    /**
     * Obtener transacciones de un usuario
     */
    public function getUserTransactions(User $user, ?string $type = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Transaction::where('user_id', $user->id);

        if ($type) {
            $query->where('type', $type);
        }

        return $query->latest()->get();
    }

    /**
     * Marcar transacción como completada
     */
    public function completeTransaction(int $transactionId): Transaction
    {
        $transaction = Transaction::findOrFail($transactionId);
        $transaction->markAsCompleted();
        return $transaction;
    }

    /**
     * Marcar transacción como fallida
     */
    public function failTransaction(int $transactionId): Transaction
    {
        $transaction = Transaction::findOrFail($transactionId);
        $transaction->markAsFailed();
        return $transaction;
    }
}