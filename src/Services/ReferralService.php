<?php

namespace Rusbelito\Billing\Services;

use Rusbelito\Billing\Models\ReferralProgram;
use Rusbelito\Billing\Models\ReferralCode;
use Rusbelito\Billing\Models\Referral;
use Rusbelito\Billing\Models\ReferralReward;
use Rusbelito\Billing\Models\ReferralRewardApplication;
use Rusbelito\Billing\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Crear código de referido para un usuario
     */
    public function createReferralCode(User $user, ?ReferralProgram $program = null): ReferralCode
    {
        // Verificar si ya tiene código
        $existingCode = $user->referralCode;
        if ($existingCode) {
            return $existingCode;
        }

        // Obtener programa predeterminado si no se especifica
        $program = $program ?? ReferralProgram::getDefault();

        // Generar código único basado en el nombre del usuario
        $code = ReferralCode::generateUniqueCode($user->name);

        return ReferralCode::create([
            'user_id' => $user->id,
            'referral_program_id' => $program?->id,
            'code' => $code,
            'is_active' => true,
        ]);
    }

    /**
     * Registrar un referido
     */
    public function registerReferral(User $referred, string $referralCode): ?Referral
    {
        $code = ReferralCode::where('code', $referralCode)
            ->where('is_active', true)
            ->first();

        if (!$code) {
            Log::warning('Invalid referral code', ['code' => $referralCode]);
            return null;
        }

        $referrer = $code->user;
        $program = $code->referralProgram;

        // Validar que no se refiera a sí mismo
        if ($referrer->id === $referred->id) {
            Log::warning('User tried to refer themselves', ['user_id' => $referred->id]);
            return null;
        }

        // Validar que no exceda el límite
        if ($program && $program->max_referrals_per_user) {
            $referrerCount = $referrer->referrals()->count();
            if ($referrerCount >= $program->max_referrals_per_user) {
                Log::warning('Referrer exceeded max referrals', [
                    'referrer_id' => $referrer->id,
                    'count' => $referrerCount,
                ]);
                return null;
            }
        }

        // Verificar que no haya sido referido antes
        $existing = Referral::where('referred_id', $referred->id)->first();
        if ($existing) {
            Log::info('User already referred', ['user_id' => $referred->id]);
            return $existing;
        }

        // Crear referral
        $referral = Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'referral_code_id' => $code->id,
            'referral_program_id' => $program?->id,
            'status' => 'registered',
            'registered_at' => now(),
        ]);

        // Actualizar estadísticas del código
        $code->incrementReferrals();

        // Verificar si debe otorgar recompensa por registro
        if ($program && $program->trigger_event === 'referral_registered') {
            $this->grantReward($referral);
        }

        Log::info('Referral registered', [
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'code' => $referralCode,
        ]);

        return $referral;
    }

    /**
     * Marcar que el referido se suscribió
     */
    public function markReferralSubscribed(User $referred): void
    {
        $referral = Referral::where('referred_id', $referred->id)->first();

        if (!$referral) {
            return;
        }

        $referral->markAsSubscribed();

        // Verificar si debe otorgar recompensa
        $program = $referral->referralProgram;
        if ($program && $program->trigger_event === 'referral_subscribed') {
            $this->grantReward($referral);
        }

        Log::info('Referral subscribed', ['referral_id' => $referral->id]);
    }

    /**
     * Marcar que el referido hizo su primer pago
     */
    public function markReferralConverted(User $referred, float $amount): void
    {
        $referral = Referral::where('referred_id', $referred->id)->first();

        if (!$referral) {
            return;
        }

        $referral->markAsConverted();
        $referral->addRevenue($amount);

        // Actualizar estadísticas del código
        $referral->referralCode->incrementSuccessfulReferrals();

        // Verificar si debe otorgar recompensa
        $program = $referral->referralProgram;
        if ($program && $program->trigger_event === 'referral_first_payment') {
            $this->grantReward($referral);
        }

        Log::info('Referral converted', [
            'referral_id' => $referral->id,
            'amount' => $amount,
        ]);
    }

    /**
     * Otorgar recompensa al referidor
     */
    protected function grantReward(Referral $referral): ReferralReward
    {
        $program = $referral->referralProgram;
        $referrer = $referral->referrer;

        $reward = ReferralReward::create([
            'user_id' => $referrer->id,
            'referral_id' => $referral->id,
            'referral_program_id' => $program->id,
            'reward_type' => $program->reward_type,
            'reward_value' => $program->reward_value,
            'remaining_value' => $program->reward_type === 'account_credit' ? $program->reward_value : null,
            'duration_months' => $program->reward_duration_months,
            'duration_cycles' => $program->reward_duration_cycles,
            'status' => 'pending',
            'earned_at' => now(),
            'upgrade_plan_id' => $program->upgrade_plan_id,
        ]);

        // Activar automáticamente ciertos tipos
        if (in_array($program->reward_type, ['account_credit', 'one_time_credit'])) {
            $reward->activate();
        }

        // Incrementar uso del programa
        $program->incrementUsage();

        Log::info('Reward granted', [
            'referrer_id' => $referrer->id,
            'reward_id' => $reward->id,
            'type' => $program->reward_type,
            'value' => $program->reward_value,
        ]);

        return $reward;
    }

    /**
     * Aplicar recompensas a una factura
     */
    public function applyRewardsToInvoice(Invoice $invoice): float
    {
        $user = $invoice->user;
        $totalDiscount = 0;

        // Obtener recompensas activas del usuario
        $rewards = ReferralReward::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($rewards as $reward) {
            if (!$reward->canApply()) {
                continue;
            }

            // Calcular descuento
            $discount = $reward->applyDiscount($invoice->total - $totalDiscount);

            if ($discount > 0) {
                // Registrar aplicación
                ReferralRewardApplication::create([
                    'referral_reward_id' => $reward->id,
                    'user_id' => $user->id,
                    'invoice_id' => $invoice->id,
                    'applied_amount' => $discount,
                    'original_amount' => $invoice->total,
                    'final_amount' => $invoice->total - $totalDiscount - $discount,
                ]);

                $totalDiscount += $discount;

                Log::info('Reward applied to invoice', [
                    'invoice_id' => $invoice->id,
                    'reward_id' => $reward->id,
                    'discount' => $discount,
                ]);
            }
        }

        return $totalDiscount;
    }

    /**
     * Obtener recompensas activas de un usuario
     */
    public function getUserActiveRewards(User $user)
    {
        return ReferralReward::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->with('referralProgram')
            ->get();
    }

    /**
     * Obtener estadísticas de referidos de un usuario
     */
    public function getUserReferralStats(User $user): array
    {
        $code = $user->referralCode;

        if (!$code) {
            return [
                'has_code' => false,
                'code' => null,
                'total_referrals' => 0,
                'successful_referrals' => 0,
                'conversion_rate' => 0,
                'total_earned' => 0,
                'active_rewards' => 0,
            ];
        }

        $totalEarned = ReferralReward::where('user_id', $user->id)
            ->sum('reward_value');

        $activeRewards = ReferralReward::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->count();

        return [
            'has_code' => true,
            'code' => $code->code,
            'referral_url' => $code->referral_url,
            'total_referrals' => $code->total_referrals,
            'successful_referrals' => $code->successful_referrals,
            'conversion_rate' => $code->conversion_rate,
            'total_earned' => $totalEarned,
            'active_rewards' => $activeRewards,
        ];
    }

    /**
     * Actualizar ingresos del referido
     */
    public function updateReferralRevenue(User $referred, float $amount): void
    {
        $referral = Referral::where('referred_id', $referred->id)->first();

        if (!$referral) {
            return;
        }

        $referral->addRevenue($amount);

        // Si es percentage_revenue, otorgar recompensa al referidor
        $activeRewards = ReferralReward::where('referral_id', $referral->id)
            ->where('reward_type', 'percentage_revenue')
            ->where('status', 'active')
            ->get();

        foreach ($activeRewards as $reward) {
            if ($reward->canApply()) {
                $rewardAmount = ($amount * $reward->reward_value) / 100;
                
                // Crear recompensa de crédito
                ReferralReward::create([
                    'user_id' => $referral->referrer_id,
                    'referral_id' => $referral->id,
                    'referral_program_id' => $reward->referral_program_id,
                    'reward_type' => 'account_credit',
                    'reward_value' => $rewardAmount,
                    'remaining_value' => $rewardAmount,
                    'status' => 'active',
                    'earned_at' => now(),
                    'activated_at' => now(),
                ]);

                Log::info('Revenue-based reward granted', [
                    'referrer_id' => $referral->referrer_id,
                    'amount' => $rewardAmount,
                    'referred_payment' => $amount,
                ]);
            }
        }
    }
}