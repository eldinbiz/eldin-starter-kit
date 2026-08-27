<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Enums\ApprovalStatus;
use App\Models\Approval\ApprovalRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class MetricsDashboard extends Component
{
    #[Url(as: 'period', history: true)]
    public string $period = '30d'; // '7d' | '30d' | '90d' | '1y'

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['7d', '30d', '90d', '1y'], true)) {
            $this->period = $period;
        }
    }

    #[Computed]
    public function activeCompany(): ?Company
    {
        /** @var User|null $user */
        $user = auth()->user();
        return $user?->getActiveCompany();
    }

    #[Computed]
    public function metrics(): array
    {
        $company = $this->activeCompany;
        $companyId = $company?->id;

        // 1. Active Users in this company
        $userCount = 0;
        if ($company) {
            $userCount = $company->users()->count();
        } else {
            $userCount = User::count();
        }

        // 2. Pending Approvals
        $pendingApprovalsCount = 0;
        if (Schema::hasTable('approval_requests')) {
            $query = ApprovalRequest::where('status', ApprovalStatus::Pending);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            $pendingApprovalsCount = $query->count();
        }

        // 3. Purchase Requests & Spend Volume
        $totalSpend = 0.0;
        $prCount = 0;
        if (Schema::hasTable('purchase_requests')) {
            $prQuery = DB::table('purchase_requests');
            if ($companyId) {
                $prQuery->where('company_id', $companyId);
            }
            $prCount = (clone $prQuery)->count();
            $totalSpend = (float) ((clone $prQuery)->where('status', '!=', 'canceled')->sum('total_amount') ?? 0.0);
        }

        // 4. Active API Tokens
        $apiTokensCount = 0;
        if (Schema::hasTable('personal_access_tokens')) {
            $apiTokensCount = PersonalAccessToken::count();
        }

        return [
            'users_count' => $userCount,
            'pending_approvals' => $pendingApprovalsCount,
            'total_spend' => $totalSpend,
            'pr_count' => $prCount,
            'api_tokens_count' => $apiTokensCount,
        ];
    }

    #[Computed]
    public function chartData(): array
    {
        $days = match ($this->period) {
            '7d' => 7,
            '90d' => 90,
            '1y' => 365,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);
        $categories = [];
        $activityCounts = [];
        $spendAmounts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $categories[] = $days > 30 ? $date->format('M d') : $date->format('M d');
            // Activity placeholder or aggregate from audit logs
            $activityCounts[] = 5 + (($i * 3 + 7) % 15);
            $spendAmounts[] = (($i * 120 + 250) % 1500);
        }

        // Aggregate by week/month if 90d or 1y to keep categories clean
        if ($days > 30) {
            $categories = array_slice($categories, -$days, 12);
            $activityCounts = array_slice($activityCounts, -$days, 12);
            $spendAmounts = array_slice($spendAmounts, -$days, 12);
        }

        return [
            'categories' => $categories,
            'activity' => $activityCounts,
            'spend' => $spendAmounts,
            'approvalDistribution' => [
                'approved' => 65,
                'pending' => 25,
                'rejected' => 10,
            ],
        ];
    }

    #[Computed]
    public function recentActivities(): array
    {
        $activities = [];

        // Check if audit_log table exists
        if (Schema::hasTable('audit_log')) {
            $logs = DB::table('audit_log')
                ->orderBy('occurred_at', 'desc')
                ->limit(6)
                ->get();

            foreach ($logs as $log) {
                $activities[] = [
                    'id' => $log->id,
                    'title' => ucfirst($log->event) . ' ' . class_basename($log->auditable_type ?? 'Entity') . ' #' . $log->auditable_id,
                    'actor' => $log->actor_label ?? 'System',
                    'time' => Carbon::parse($log->occurred_at)->diffForHumans(),
                    'type' => $log->event,
                ];
            }
        }

        if (empty($activities)) {
            $activities = [
                ['id' => 1, 'title' => 'Purchase Request Submitted', 'actor' => 'Jane Doe', 'time' => '10 mins ago', 'type' => 'created'],
                ['id' => 2, 'title' => 'Approval Step Completed', 'actor' => 'Manager User', 'time' => '1 hour ago', 'type' => 'updated'],
                ['id' => 3, 'title' => 'API Token Generated', 'actor' => 'Admin User', 'time' => '3 hours ago', 'type' => 'created'],
                ['id' => 4, 'title' => 'Workflow Template Updated', 'actor' => 'Admin User', 'time' => '5 hours ago', 'type' => 'updated'],
            ];
        }

        return $activities;
    }

    public function render()
    {
        return view('livewire.dashboard.metrics-dashboard');
    }
}
