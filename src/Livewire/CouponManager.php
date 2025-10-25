<?php

namespace Rusbelito\Billing\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Rusbelito\Billing\Models\Coupon;
use Rusbelito\Billing\Models\Plan;

class CouponManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $couponId;

    // Form fields
    public $code;
    public $description;
    public $discount_type = 'percentage';
    public $discount_value;
    public $starts_at;
    public $expires_at;
    public $minimum_amount;
    public $usage_type = 'reusable';
    public $max_uses;
    public $is_active = true;
    public $applicable_plans = [];

    // Filters
    public $filterStatus = '';
    public $filterType = '';
    public $search = '';

    protected $rules = [
        'code' => 'required|unique:billing_coupons,code',
        'description' => 'nullable',
        'discount_type' => 'required|in:percentage,fixed',
        'discount_value' => 'required|numeric|min:0',
        'starts_at' => 'nullable|date',
        'expires_at' => 'nullable|date|after:starts_at',
        'minimum_amount' => 'nullable|numeric|min:0',
        'usage_type' => 'required|in:single,reusable,limited',
        'max_uses' => 'required_if:usage_type,limited|nullable|integer|min:1',
        'is_active' => 'boolean',
        'applicable_plans' => 'nullable|array',
    ];

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editMode = false;
    }

    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);
        
        $this->couponId = $coupon->id;
        $this->code = $coupon->code;
        $this->description = $coupon->description;
        $this->discount_type = $coupon->discount_type;
        $this->discount_value = $coupon->discount_value;
        $this->starts_at = $coupon->starts_at?->format('Y-m-d');
        $this->expires_at = $coupon->expires_at?->format('Y-m-d');
        $this->minimum_amount = $coupon->minimum_amount;
        $this->usage_type = $coupon->usage_type;
        $this->max_uses = $coupon->max_uses;
        $this->is_active = $coupon->is_active;
        $this->applicable_plans = $coupon->applicable_plans ?? [];
        
        $this->showModal = true;
        $this->editMode = true;
    }

    public function save()
    {
        if ($this->editMode) {
            $this->rules['code'] = 'required|unique:billing_coupons,code,' . $this->couponId;
        }

        $this->validate();

        $data = [
            'code' => strtoupper($this->code),
            'description' => $this->description,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'starts_at' => $this->starts_at,
            'expires_at' => $this->expires_at,
            'minimum_amount' => $this->minimum_amount,
            'usage_type' => $this->usage_type,
            'max_uses' => $this->usage_type === 'limited' ? $this->max_uses : null,
            'is_active' => $this->is_active,
            'applicable_plans' => !empty($this->applicable_plans) ? $this->applicable_plans : null,
        ];

        if ($this->editMode) {
            Coupon::find($this->couponId)->update($data);
            session()->flash('message', 'Cupón actualizado correctamente');
        } else {
            Coupon::create($data);
            session()->flash('message', 'Cupón creado correctamente');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        
        session()->flash('message', 'Cupón eliminado correctamente');
    }

    public function toggleActive($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => !$coupon->is_active]);
        
        session()->flash('message', 'Estado actualizado');
    }

    public function expire($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update([
            'is_active' => false,
            'expires_at' => now(),
        ]);
        
        session()->flash('message', 'Cupón expirado');
    }

    protected function resetForm()
    {
        $this->couponId = null;
        $this->code = '';
        $this->description = '';
        $this->discount_type = 'percentage';
        $this->discount_value = null;
        $this->starts_at = null;
        $this->expires_at = null;
        $this->minimum_amount = null;
        $this->usage_type = 'reusable';
        $this->max_uses = null;
        $this->is_active = true;
        $this->applicable_plans = [];
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function render()
    {
        $query = Coupon::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        if ($this->filterType) {
            $query->where('discount_type', $this->filterType);
        }

        $coupons = $query->withCount('usages')
            ->latest()
            ->paginate(15);

        $plans = Plan::where('is_active', true)->get();

        return view('billing::livewire.coupon-manager', [
            'coupons' => $coupons,
            'plans' => $plans,
        ]);
    }
}