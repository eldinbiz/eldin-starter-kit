<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\v1\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Display a listing of the companies.
     */
    public function index(Request $request): JsonResponse
    {
        $companies = Company::query()
            ->when($request->query('active'), function ($query, $active) {
                return $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
            })
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'data' => CompanyResource::collection($companies),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ]);
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'data' => new CompanyResource($company),
        ]);
    }

    /**
     * Display company hierarchy.
     */
    public function hierarchy(): JsonResponse
    {
        $companies = Company::with('children')->whereNull('parent_id')->get();

        return response()->json([
            'data' => CompanyResource::collection($companies),
        ]);
    }
}
