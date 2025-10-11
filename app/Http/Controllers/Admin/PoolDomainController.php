<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PoolDomainService;
use Yajra\DataTables\DataTables;

class PoolDomainController extends Controller
{
    protected $poolDomainService;

    public function __construct(PoolDomainService $poolDomainService)
    {
        $this->poolDomainService = $poolDomainService;
    }

    /**
     * Display a listing of pool domains across pools and pool orders.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = $this->poolDomainService->getPoolDomainsData();
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('prefixes_formatted', function ($row) {
                    return $this->poolDomainService->formatPrefixes($row['prefixes'], $row['domain_name']);
                })
                ->addColumn('status_badge', function ($row) {
                    $colorMap = [
                        'available' => 'success',
                        'subscribed' => 'primary',
                        'used' => 'warning',
                        'inactive' => 'secondary',
                        'unknown' => 'dark',
                    ];
                    $color = $colorMap[$row['status']] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row['status']) . '</span>';
                })
                ->addColumn('pool_order_status_badge', function ($row) {
                    if ($row['pool_order_status'] === 'no_order') {
                        return '<span class="badge bg-light text-dark">No Order</span>';
                    }
                    
                    $colorMap = [
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'failed' => 'danger',
                        'unknown' => 'secondary',
                    ];
                    $color = $colorMap[$row['pool_order_status']] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row['pool_order_status']) . '</span>';
                })
                ->addColumn('usage_badge', function ($row) {
                    $isUsed = $row['is_used'] ?? false;
                    if ($isUsed) {
                        return '<span class="badge bg-warning">Used</span>';
                    } else {
                        return '<span class="badge bg-light text-dark">Available</span>';
                    }
                })
                ->rawColumns(['status_badge', 'pool_order_status_badge', 'usage_badge'])
                ->make(true);
        }

        return view('admin.pool_domains.index');
    }
}
