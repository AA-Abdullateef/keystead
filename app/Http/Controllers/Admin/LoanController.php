<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoanProductRequest;
use App\Models\LoanProject;
use App\Models\Property;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LoanController extends Controller
{
    public function __construct(private readonly LoanService $loans)
    {
    }

    /* ---------------------------------------------------------------- products */

    public function products(Request $request)
    {
        return $this->productsPage($request);
    }

    private function productsPage(Request $request, ?LoanProject $edit = null)
    {
        return view('admin.loan_projects', [
            'products' => LoanProject::with('property')->when($request->query('type'), fn ($q, $t) => $q->where('loan_type', $t))->latest()->get(),
            'properties' => Property::where('type', 'loan')->orderBy('name')->get(),
            'edit' => $edit?->load('property'),
        ]);
    }

    public function editProduct(Request $request, LoanProject $product)
    {
        return $this->productsPage($request, $product);
    }

    public function storeProduct(LoanProductRequest $request): RedirectResponse
    {
        $v = $request->validated();
        $this->assertTerms($v);
        LoanProject::create($v + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);

        return redirect()->route('admin.loan.projects')->with('success', 'Loan product created.');
    }

    public function updateProduct(LoanProductRequest $request, LoanProject $product): RedirectResponse
    {
        $v = $request->validated();
        $this->assertTerms($v);

        // Terms of products with applications are frozen; only the active/inactive switch may change.
        $hasLoans = $product->mortgageLoans()->exists() || $product->propertyLoans()->exists();
        if ($hasLoans) {
            $v = ['status' => $v['status']];
        }

        $product->update($v + ['updated_by' => $request->user()->id]);

        return redirect()->route('admin.loan.projects')->with('success', $hasLoans
            ? 'Product has applications, so only its status was changed.' : 'Loan product updated.');
    }

    private function assertTerms(array $v): void
    {
        if (isset($v['property_id'])) {
            abort_unless(Property::whereKey($v['property_id'])->where('type', 'loan')->exists(), 422, 'The selected property is not available for loans.');
        }
    }

    /* ------------------------------------------------------------------- loans */

    public function index(LoanType $type, Request $request)
    {
        $f = $request->validate(['status' => ['nullable', Rule::enum(LoanStatus::class)]]);
        $loans = $type->model()::with(['user', 'loanProject.property'])
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.loans', ['type' => $type, 'loans' => $loans, 'statuses' => LoanStatus::cases()]);
    }

    public function show(LoanType $type, string $id)
    {
        $loan = $type->model()::with(['user', 'loanProject.property', 'payments', 'approver'])->findOrFail($id);

        return view('admin.show_loans', ['type' => $type, 'loan' => $loan]);
    }

    public function approve(LoanType $type, string $id, Request $request): RedirectResponse
    {
        $this->loans->approve($type, $id, $request->user()->id);

        return back()->with('success', 'Loan approved and activated.');
    }

    public function reject(LoanType $type, string $id): RedirectResponse
    {
        $this->loans->reject($type, $id);

        return back()->with('success', 'Application rejected and the initial payment refunded.');
    }
}
