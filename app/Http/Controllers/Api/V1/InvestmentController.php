<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvestRequest;
use App\Http\Requests\Api\PaginationRequest;
use App\Http\Resources\InvestmentProjectResource;
use App\Http\Resources\InvestmentResource;
use App\Http\Resources\ProfitShareResource;
use App\Enums\ProjectStatus;
use App\Models\Investment;
use App\Models\InvestmentProject;
use App\Services\InvestmentService;
use App\Services\ProfitDistributionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function __construct(
        private readonly InvestmentService $investments,
        private readonly ProfitDistributionService $profits,
    ) {
    }

    /** Projects that can still be invested in. */
    public function projects(PaginationRequest $request): JsonResponse
    {
        $page = InvestmentProject::with('property')
            ->where('status', ProjectStatus::Open)
            ->orderByDesc('start_date')
            ->paginate($request->perPage());

        return $this->paginated('Open investment projects.', $page, InvestmentProjectResource::class);
    }

    public function showProject(InvestmentProject $project): JsonResponse
    {
        // Only the caller's own row is exposed; other investors' amounts stay private.
        $project->load(['property', 'investments' => fn ($q) => $q->where('user_id', request()->user()->id)]);
        $project->setAttribute('investors_count', $project->investments()->count());

        return $this->success('Investment project.', new InvestmentProjectResource($project));
    }

    public function invest(InvestRequest $request): JsonResponse
    {
        $investment = $this->investments->invest(
            $request->user()->id,
            $request->validated('investment_project_id'),
            $request->validated('amount'),
        );

        return $this->created('Investment successful.', new InvestmentResource($investment));
    }

    public function mine(PaginationRequest $request): JsonResponse
    {
        $page = Investment::with('project.property')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->perPage());

        return $this->paginated('Your investments.', $page, InvestmentResource::class);
    }

    public function summary(Request $request): JsonResponse
    {
        return $this->success('Investment summary.', $this->profits->userSummary($request->user()->id));
    }

    public function profitHistory(PaginationRequest $request): JsonResponse
    {
        return $this->paginated('Profit history.', $this->profits->userProfitHistory($request->user()->id, null, $request->perPage()), ProfitShareResource::class);
    }

    public function projectProfitHistory(PaginationRequest $request, InvestmentProject $project): JsonResponse
    {
        $owns = Investment::where('user_id', $request->user()->id)->where('investment_project_id', $project->id)->exists();
        abort_unless($owns, 403, 'You have no investment in this project.');

        return $this->paginated('Project profit history.', $this->profits->userProfitHistory($request->user()->id, $project->id, $request->perPage()), ProfitShareResource::class);
    }
}
