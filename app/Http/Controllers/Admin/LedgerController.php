<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LedgerEntryRequest;
use App\Models\Expense;
use App\Models\InvestmentProject;
use App\Models\RentalIncome;
use App\Services\InvestmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Rental income and expense entries per project and month. Both feed the profit distribution. */
class LedgerController extends Controller
{
    public function __construct(private readonly InvestmentService $investments)
    {
    }

    public function income(Request $request)
    {
        return $this->incomePage($request);
    }

    private function incomePage(Request $request, ?RentalIncome $edit = null)
    {
        return view('admin.rental_income', [
            'records' => RentalIncome::with('project.property')
                ->when($request->query('project'), fn ($q, $p) => $q->where('investment_project_id', $p))
                ->orderByDesc('year')->orderByDesc('month')->paginate(20)->withQueryString(),
            'projects' => $this->projects(),
            'edit' => $edit?->load('project.property'),
        ]);
    }

    public function editIncome(Request $request, RentalIncome $income)
    {
        return $this->incomePage($request, $income);
    }

    public function storeIncome(LedgerEntryRequest $r): RedirectResponse
    {
        $this->investments->recordIncome($r->validated('investment_project_id'), $r->amount(), (int) $r->validated('month'), (int) $r->validated('year'), $r->user()->id);

        return redirect()->route('admin.rental.index')->with('success', 'Rental income recorded.');
    }

    public function updateIncome(LedgerEntryRequest $r, RentalIncome $income): RedirectResponse
    {
        $this->investments->updateIncome($income->id, $r->validated());

        return redirect()->route('admin.rental.index')->with('success', 'Rental income updated.');
    }

    public function destroyIncome(RentalIncome $income): RedirectResponse
    {
        $this->investments->deleteIncome($income->id);

        return back()->with('success', 'Rental income removed.');
    }

    public function expenses(Request $request)
    {
        return $this->expensePage($request);
    }

    private function expensePage(Request $request, ?Expense $edit = null)
    {
        return view('admin.expenses', [
            'records' => Expense::with('project.property')
                ->when($request->query('project'), fn ($q, $p) => $q->where('investment_project_id', $p))
                ->orderByDesc('year')->orderByDesc('month')->paginate(20)->withQueryString(),
            'projects' => $this->projects(),
            'edit' => $edit?->load('project.property'),
        ]);
    }

    public function editExpense(Request $request, Expense $expense)
    {
        return $this->expensePage($request, $expense);
    }

    public function storeExpense(LedgerEntryRequest $r): RedirectResponse
    {
        $this->investments->recordExpense($r->validated('investment_project_id'), $r->validated('type'), $r->amount(), (int) $r->validated('month'), (int) $r->validated('year'), $r->validated('note'), $r->user()->id);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded.');
    }

    public function updateExpense(LedgerEntryRequest $r, Expense $expense): RedirectResponse
    {
        $this->investments->updateExpense($expense->id, $r->validated());

        return redirect()->route('admin.expenses.index')->with('success', 'Expense updated.');
    }

    public function destroyExpense(Expense $expense): RedirectResponse
    {
        $this->investments->deleteExpense($expense->id);

        return back()->with('success', 'Expense removed.');
    }

    private function projects()
    {
        return InvestmentProject::with('property')->where('status', '!=', 'closed')->latest()->get();
    }
}
