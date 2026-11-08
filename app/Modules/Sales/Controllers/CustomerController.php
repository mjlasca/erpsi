<?php

namespace App\Modules\Sales\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Models\Customer;
use App\Modules\Sales\Requests\StoreCustomerRequest;
use App\Modules\Sales\Requests\UpdateCustomerRequest;
use App\Modules\Sales\Resources\CustomerResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function search(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->active()
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        return CustomerResource::collection($customers);
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->when($request->string('search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Customers/Index', [
            'customers' => $customers->through(fn ($customer): array => (new CustomerResource($customer))->resolve()),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Customer::class);

        return Inertia::render('Sales/Customers/Create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::query()->create($request->validated());

        return redirect()
            ->route('sales.customers.show', $customer)
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        $customer->loadCount(['sales', 'payments']);

        return Inertia::render('Sales/Customers/Show', [
            'customer' => (new CustomerResource($customer))->resolve(),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        $this->authorize('update', $customer);

        return Inertia::render('Sales/Customers/Edit', [
            'customer' => (new CustomerResource($customer))->resolve(),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()
            ->route('sales.customers.show', $customer)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        if ($customer->sales()->exists() || $customer->payments()->exists()) {
            return back()->with('error', 'No se puede eliminar el cliente porque tiene registros relacionados.');
        }

        $customer->delete();

        return redirect()
            ->route('sales.customers.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
