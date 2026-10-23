<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IncreaseInvestmentRequest;
use App\Http\Requests\Admin\InvestorRequest;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\Investment;
use App\Models\InvestmentProject;
use App\Models\Property;
use App\Models\User;
use App\Services\InvestmentService;
use Illuminate\Http\RedirectResponse;

class InvestmentController extends Controller
{
    public function __construct(private readonly InvestmentService $investments)
    {
    }

    public function index()
    {
        return $this->page();
    }

    private function page(?InvestmentProject $editProject = null, ?Investment $editInvestment = null)
    {
        return view('admin.investments', [
            'projects' => InvestmentProject::with('property')->withCount('investments')->latest()->get(),
            'investments' => Investment::with(['user', 'project.property'])->latest()->paginate(20),
            'properties' => Property::where('type', 'investment')->whereDoesntHave('investmentProjects')->orderBy('name')->get(),
            'investors' => User::investors()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'email']),
            'editProject' => $editProject?->load('property'),
            'editInvestment' => $editInvestment?->load(['user', 'project.property']),
        ]);
    }

    public function editProject(InvestmentProject $project)
    {
        return $this->page($project);
    }

    public function editInvestor(Investment $investment)
    {
        return $this->page(null, $investment);
    }

    public function storeProject(ProjectRequest $request): RedirectResponse
    {
        $this->investments->createProject($request->validated(), $request->user()->id);

        return redirect()->route('admin.investments.index')->with('success', 'Investment project created.');
    }

    public function updateProject(ProjectRequest $request, InvestmentProject $project): RedirectResponse
    {
        $this->investments->updateProject($project->id, $request->validated(), $request->user()->id);

        return redirect()->route('admin.investments.index')->with('success', 'Project updated.');
    }

    public function destroyProject(InvestmentProject $project): RedirectResponse
    {
        $this->investments->deleteProject($project->id);

        return back()->with('success', 'Project deleted.');
    }

    /** Admin invests on behalf of an investor — debits the investor's wallet exactly like the API. */
    public function storeInvestor(InvestorRequest $request): RedirectResponse
    {
        $this->investments->invest($request->validated('user_id'), $request->validated('investment_project_id'), $request->amount());

        return redirect()->route('admin.investments.index')->with('success', 'Contribution recorded and the investor wallet debited.');
    }

    public function updateInvestor(IncreaseInvestmentRequest $request, Investment $investment): RedirectResponse
    {
        $this->investments->increaseInvestment($investment->id, $request->amount('total_invested'));

        return redirect()->route('admin.investments.index')->with('success', 'Investment increased; the difference was debited from the wallet.');
    }

    public function destroyInvestor(Investment $investment): RedirectResponse
    {
        $this->investments->reverseInvestment($investment->id);

        return back()->with('success', 'Investment reversed and the full amount returned to the investor wallet.');
    }
}
