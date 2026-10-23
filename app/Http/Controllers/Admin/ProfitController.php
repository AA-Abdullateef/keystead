<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProfitRequest;
use App\Models\Earning;
use App\Models\InvestmentProject;
use App\Models\ProfitDistribution;
use App\Services\ProfitDistributionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfitController extends Controller
{
    public function __construct(private readonly ProfitDistributionService $profits)
    {
    }

    /** One page for preview + confirmation + history. The preview is read-only. */
    public function index(Request $request)
    {
        $data = [
            'projects' => InvestmentProject::with('property')->where('status', '!=', 'closed')->get(),
            'history' => ProfitDistribution::with('project.property')->latest('distributed_at')->limit(15)->get(),
            'preview' => null, 'previewError' => null, 'alreadyDistributed' => false,
            'project' => null, 'month' => $request->integer('month') ?: (int) now()->subMonth()->format('n'),
            'year' => $request->integer('year') ?: (int) now()->subMonth()->format('Y'),
        ];

        if ($request->filled('investment_project_id')) {
            $params = $request->validate((new ProfitRequest)->rules());
            $data['project'] = InvestmentProject::with('property')->findOrFail($params['investment_project_id']);
            $data['month'] = (int) $params['month'];
            $data['year'] = (int) $params['year'];
            $data['alreadyDistributed'] = $this->profits->distributionExists($data['project']->id, $data['month'], $data['year']);

            try {
                $data['preview'] = $this->profits->preview($data['project']->id, $data['month'], $data['year']);
            } catch (ValidationException $e) {
                $data['previewError'] = collect($e->errors())->flatten()->first();
            }
        }

        return view('admin.profit_distribution', $data);
    }

    public function distribute(ProfitRequest $request): RedirectResponse
    {
        $v = $request->validated();
        $d = $this->profits->distribute($v['investment_project_id'], (int) $v['month'], (int) $v['year'], $request->user()->id);
        $issues = $this->profits->verify($d);

        return redirect()->route('admin.profit.distribution', $v)
            ->with('success', $issues === [] ? 'Profit distributed. Wallet credits reconcile with the distribution.' : 'Distributed, but reconciliation reported: ' . implode('; ', $issues));
    }

    public function earnings(Request $request)
    {
        return view('admin.earnings', [
            'earnings' => Earning::with('user')->latest()->paginate(25),
        ]);
    }
}
