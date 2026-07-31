<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Eloquents\BadgeRepository;
use App\Models\Refund;
use App\Models\ReturnRequest;
use App\Enums\RequestEnum;

class BadgeController extends Controller
{
    protected $repository;

    public function __construct(BadgeRepository $repository){
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Compute minimal badges needed for sidebar counters
        $pendingRefunds = Refund::where('status', RequestEnum::PENDING)->count();
        $pendingReturns = ReturnRequest::where('status', 'pending')->count();

        return response()->json([
            'refund' => [ 'total_pending_refunds' => $pendingRefunds ],
            'returns' => [ 'total_pending_returns' => $pendingReturns ],
        ]);
    }
}
