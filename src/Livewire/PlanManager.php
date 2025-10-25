<?php

namespace Rusbelito\Billing\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Rusbelito\Billing\Models\Plan;
use Illuminate\Support\Str;

class PlanManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editMode = false;
    public $planId;

    // Form fields
    public $name;
    public $slug;
    public $type = 'subscription';
    public $price = 0;
    public $is_active = true;
    public $is_visible = true;

    // Filters
    public $filterType = '';
    public $filterStatus = '';
    public $search = '';

    protected $rules = [
        'name' => 'required|min:3',
        'slug' => 'required|unique:plans,slug',
        'type' => 'required|in:subscription,donation,consumption,mixed',
        'price' => 'required|numeric|min:0',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function updatedName()
    {
        if (!$this->editMode) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->editMode = false;
    }

    public function edit($id)
    {
        $plan = Plan::findOrFail($id);
        
        $this->planId = $plan->id;
        $this->name = $plan->name;
        $this->slug = $plan->slug;
        $this->type = $plan->type;
        $this->price = $plan->price;
        $this->is_active = $plan->is_active;
        $this->is_visible = $plan->is_visible;
        
        $this->showModal = true;
        $this->editMode = true;
    }

    public function save()
    {
        if ($this->editMode) {
            $this->rules['slug'] = 'required|unique:plans,slug,' . $this->planId;
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'is_visible' => $this->is_visible,
        ];

        if ($this->editMode) {
            Plan::find($this->planId)->update($data);
            session()->flash('message', 'Plan actualizado correctamente');
        } else {
            Plan::create($data);
            session()->flash('message', 'Plan creado correctamente');
        }

        $this->closeModal();
    }

    public function delete($id)
    {
        $plan = Plan::findOrFail($id);
        
        // Verificar si tiene suscripciones activas
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            session()->flash('error', 'No se puede eliminar un plan con suscripciones activas');
            return;
        }

        $plan->delete();
        session()->flash('message', 'Plan eliminado correctamente');
    }

    public function toggleActive($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);
        
        session()->flash('message', 'Estado actualizado');
    }

    public function toggleVisible($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['is_visible' => !$plan->is_visible]);
        
        session()->flash('message', 'Visibilidad actualizada');
    }

    protected function resetForm()
    {
        $this->planId = null;
        $this->name = '';
        $this->slug = '';
        $this->type = 'subscription';
        $this->price = 0;
        $this->is_active = true;
        $this->is_visible = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function render()
    {
        $query = Plan::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus);
        }

        $plans = $query->withCount('subscriptions')
            ->latest()
            ->paginate(15);

        return view('billing::livewire.plan-manager', [
            'plans' => $plans,
        ]);
    }
}