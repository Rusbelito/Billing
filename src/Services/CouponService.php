<?php

namespace Rusbelito\Billing\Services;

use App\Models\User;
use Rusbelito\Billing\Models\Coupon;
use Rusbelito\Billing\Models\CouponUsage;
use Rusbelito\Billing\Exceptions\CouponException;

class CouponService
{
    /**
     * Validar si un cupón es válido
     */
    public function validate(
        string $code,
        User $user,
        float $amount,
        ?int $planId = null,
        ?string $billingMode = null
    ): array {
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            throw new CouponException('Cupón no encontrado');
        }

        if (!$coupon->isValid()) {
            throw new CouponException('Cupón no está activo o ha expirado');
        }

        if ($coupon->hasReachedLimit()) {
            throw new CouponException('Cupón ha alcanzado su límite de uso');
        }

        if ($coupon->minimum_amount && $amount < $coupon->minimum_amount) {
            throw new CouponException(
                'Monto mínimo de compra: $' . $coupon->minimum_amount
            );
        }

        if (!$coupon->isApplicableToPlan($planId)) {
            throw new CouponException('Cupón no es aplicable a este plan');
        }

        if (!$coupon->isApplicableToMode($billingMode)) {
            throw new CouponException('Cupón no es aplicable a este tipo de facturación');
        }

        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount_amount' => $coupon->calculateDiscount($amount),
        ];
    }

    /**
     * Aplicar cupón y retornar monto final
     */
    public function apply(
        string $code,
        User $user,
        float $amount,
        ?int $planId = null,
        ?string $billingMode = null,
        ?string $billableType = null,
        ?int $billableId = null
    ): array {
        // Validar cupón
        $validation = $this->validate($code, $user, $amount, $planId, $billingMode);
        $coupon = $validation['coupon'];
        $discount = $validation['discount_amount'];

        $finalAmount = $amount - $discount;

        // Registrar uso
        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'billable_type' => $billableType,
            'billable_id' => $billableId,
            'discount_amount' => $discount,
            'original_amount' => $amount,
            'final_amount' => $finalAmount,
        ]);

        // Incrementar contador de uso
        $coupon->incrementUse();

        return [
            'success' => true,
            'coupon_code' => $coupon->code,
            'original_amount' => $amount,
            'discount_amount' => $discount,
            'discount_type' => $coupon->discount_type,
            'discount_value' => $coupon->discount_value,
            'final_amount' => round($finalAmount, 2),
        ];
    }

    /**
     * Obtener un cupón por código (sin validar)
     */
    public function find(string $code): ?Coupon
    {
        return Coupon::where('code', $code)->first();
    }

    /**
     * Crear un nuevo cupón
     */
    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }
}