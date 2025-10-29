<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\PoolInvoice;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Status;
use App\Models\Order;


class AdminInvoiceController extends Controller
{
    private $statuses;
    // payment-status
    private $paymentStatuses = [
        "Pending" => "warning",
        "Paid" => "success",
        "Failed" => "danger",
        "Refunded" => "secondary"
    ];
    public function __construct()
    {
        $this->statuses = Status::pluck('badge', 'name')->toArray();
    }
    // index to load table
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Check if this is for trial invoices (pool_invoices)
            $invoiceType = $request->get('invoice_type', 'normal');
            
            if ($invoiceType === 'trial') {
                return $this->getTrialInvoices($request);
            }
            
            // Normal invoices
            $query = Invoice::with(['order.user']);
    
            // Apply filters
            if ($request->status) {
                $query->where('status', $request->status);
            }
    
            if ($request->startDate) {
                $query->whereDate('created_at', '>=', $request->startDate);
            }
    
            if ($request->endDate) {
                $query->whereDate('created_at', '<=', $request->endDate);
            }
    
            if ($request->priceRange) {
                list($min, $max) = explode('-', $request->priceRange);
                if ($max === '+') {
                    $query->where('amount', '>=', $min);
                } else {
                    $query->whereBetween('amount', [$min, $max]);
                }
            }
    
            if ($request->orderId) {
                $query->where('order_id', $request->orderId);
            }
    
            if ($request->orderStatus) {
                $query->whereHas('order', function ($q) use ($request) {
                    $q->where('status_manage_by_admin', $request->orderStatus);
                });
            }
    
            $data = $query->select([
                'invoices.id',
                'invoices.id',
                'invoices.order_id',
                'invoices.amount',
                'invoices.status',
                'invoices.paid_at',
                'invoices.chargebee_subscription_id',
                'invoices.created_at',
                'invoices.updated_at',
            ]);
    
            return DataTables::of($data)
                ->addColumn('customer_name', function ($row) {
                    return $row->order->user->name ?? 'N/A';
                })
                ->addColumn('action', function ($row) {
                    return view('admin.invoices.actions', ['row'=>$row])->render();
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at ? $row->created_at->format('d F, Y') : '';
                })
                ->editColumn('paid_at', function ($row) {
                    return $row->paid_at ? date('d F, Y', strtotime($row->paid_at)) : '';
                })
                ->editColumn('amount', function ($row) {
                    return '$' . number_format($row->amount, 2);
                })
                ->editColumn('status', function ($row) {
                    $statusClass = $row->status == 'paid' ? 'success' : 'warning';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' .
                        ucfirst($row->status) . '</span>';
                })
                ->editColumn('status_manage_by_admin', function ($row) {
                    $status = strtolower($row->order->status_manage_by_admin ?? 'n/a');
                    $statusKey = $status;
                    $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                // Add customer name filter and sorting support
                ->orderColumn('customer_name', function ($query, $direction) {
                    $query->join('orders', 'orders.id', '=', 'invoices.order_id')
                          ->join('users', 'users.id', '=', 'orders.user_id')
                          ->orderBy('users.name', $direction);
                })
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereHas('order.user', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('status_manage_by_admin', function ($query, $direction) {
                    $query->whereHas('order', function ($q) use ($direction) {
                        $q->orderBy('status_manage_by_admin', $direction);
                    });
                })
                ->filterColumn('status_manage_by_admin', function ($query, $keyword) {
                    $query->whereHas('order', function ($q) use ($keyword) {
                        $q->where('status_manage_by_admin', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action', 'status', 'status_manage_by_admin'])
                ->addIndexColumn()
                ->with([
                    'counters' => [
                        'total' => Invoice::count(),
                        'paid' => Invoice::where('status', 'paid')->count(),
                        'pending' => Invoice::where('status', 'pending')->count(),
                        'failed' => Invoice::where('status', 'failed')->count(),
                    ]
                ])
                ->make(true);
        }
        $statuses = $this->statuses;
        return view('admin.invoices.index', compact('statuses'));
    }
    

    public function getInvoices(Request $request)
    {
        try {
            $invoices = Invoice::with(['user', 'order'])
                ->select([
                    'invoices.id',
                    'invoices.chargebee_invoice_id',
                    'invoices.order_id',
                    'invoices.amount',
                    'invoices.status',
                    'invoices.paid_at',
                    'invoices.chargebee_subscription_id',
                    'invoices.created_at',
                    'invoices.updated_at',
                ]);

            // Filter by order_id if provided
            if ($request->has('order_id') && $request->order_id != '') {
                $invoices->where('order_id', $request->order_id);
            }

            // Filter by invoice status
            if ($request->has('status') && $request->status != '') {
                $invoices->where('status', $request->status);
            }

            // Filter by order status
            if ($request->has('order_status') && $request->order_status != '') {
                $invoices->whereHas('order', function($q) use ($request) {
                    $q->where('status_manage_by_admin', $request->order_status);
                });
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date != '') {
                $invoices->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date != '') {
                $invoices->whereDate('created_at', '<=', $request->end_date);
            }

            // Filter by price range
            if ($request->has('price_range') && $request->price_range != '') {
                list($min, $max) = explode('-', str_replace('$', '', $request->price_range));
                if ($max === '1000+') {
                    $invoices->where('amount', '>=', 1000);
                } else {
                    $invoices->whereBetween('amount', [(float)$min, (float)$max]);
                }
            }
            
            return DataTables::of($invoices)
                ->addColumn('action', function($invoice) {
                    $viewUrl = route('admin.invoices.show', $invoice->chargebee_invoice_id);
                    $downloadUrl = route('admin.invoices.download', $invoice->chargebee_invoice_id);
                    return '<div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="' . $viewUrl . '">View</a></li>
                            <li><a class="dropdown-item" href="' . $downloadUrl . '">Download</a></li>
                        </ul>
                    </div>';
                })
                ->editColumn('created_at', function($invoice) {
                    return $invoice->created_at ? $invoice->created_at->format('d F, Y') : '';
                })
                ->editColumn('paid_at', function($invoice) {
                    return $invoice->paid_at ? date('d F, Y', strtotime($invoice->paid_at)) : '';
                })
                ->editColumn('amount', function($invoice) {
                    return '$' . number_format($invoice->amount, 2);
                })
                ->editColumn('status', function($invoice) {
                    $statusKey = ucfirst(strtolower($invoice->status ?? 'N/A'));
                    return '<span class="py-1 px-2 text-' . ($this->paymentStatuses[$statusKey] ?? 'secondary') . ' border border-' . ($this->statuses[$statusKey] ?? 'secondary') . ' rounded-2 bg-transparent">' 
                        . $statusKey . '</span>';
                })
                ->editColumn('status_manage_by_admin', function($invoice) {
                    $statusKey = ucfirst($invoice->order->status_manage_by_admin ?? 'N/A');
                    return '<span class="py-1 px-2 text-' . ($this->statuses[$statusKey] ?? 'secondary') . ' border border-' . ($this->statuses[$statusKey] ?? 'secondary') . ' rounded-2 bg-transparent">' 
                        . $statusKey . '</span>';
                })
                ->filterColumn('status_manage_by_admin', function($query, $keyword) {
                    $query->whereHas('order', function($q) use ($keyword) {
                        $q->where('status_manage_by_admin', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('status_manage_by_admin', function($query, $direction) {
                    $query->whereHas('order', function($q) use ($direction) {
                        $q->orderBy('status_manage_by_admin', $direction);
                    });
                })
                ->rawColumns(['action', 'status', 'status_manage_by_admin'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error in getInvoices: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading invoices'], 500);
        }
    }
    
    // Get trial invoices from pool_invoices table
    private function getTrialInvoices(Request $request)
    {
        $query = PoolInvoice::with(['user', 'poolOrder']);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->startDate) {
            $query->whereDate('created_at', '>=', $request->startDate);
        }

        if ($request->endDate) {
            $query->whereDate('created_at', '<=', $request->endDate);
        }

        if ($request->priceRange) {
            list($min, $max) = explode('-', $request->priceRange);
            if ($max === '+') {
                $query->where('amount', '>=', $min);
            } else {
                $query->whereBetween('amount', [$min, $max]);
            }
        }

        if ($request->orderId) {
            $query->where('pool_order_id', $request->orderId);
        }

        $data = $query->select([
            'pool_invoices.id',
            'pool_invoices.user_id',
            'pool_invoices.pool_order_id',
            'pool_invoices.amount',
            'pool_invoices.status',
            'pool_invoices.paid_at',
            'pool_invoices.chargebee_invoice_id',
            'pool_invoices.created_at',
            'pool_invoices.updated_at',
        ]);

        return DataTables::of($data)
            ->addColumn('customer_name', function ($row) {
                return $row->user->name ?? 'N/A';
            })
            ->addColumn('action', function ($row) {
                return view('admin.invoices.actions', ['row' => $row, 'isTrial' => true])->render();
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('d F, Y') : '';
            })
            ->editColumn('paid_at', function ($row) {
                return $row->paid_at ? date('d F, Y', strtotime($row->paid_at)) : '';
            })
            ->editColumn('amount', function ($row) {
                return '$' . number_format($row->amount, 2);
            })
            ->editColumn('status', function ($row) {
                $statusClass = $row->status == 'paid' ? 'success' : 'warning';
                return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' .
                    ucfirst($row->status) . '</span>';
            })
            ->filterColumn('customer_name', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action', 'status'])
            ->addIndexColumn()
            ->with([
                'counters' => [
                    'total' => PoolInvoice::count(),
                    'paid' => PoolInvoice::where('status', 'paid')->count(),
                    'pending' => PoolInvoice::where('status', 'pending')->count(),
                    'failed' => PoolInvoice::where('status', 'failed')->count(),
                ]
            ])
            ->make(true);
    }
    
    public function show($invoiceId)
    {
        try {
            $invoice = Invoice::with(['user', 'order'])
                ->where('chargebee_invoice_id', $invoiceId)
                ->firstOrFail();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $invoice
                ]);
            }

            return view('admin.invoices.show', compact('invoice'));
        } catch (Exception $e) {
            Log::error('Error showing invoice: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found or access denied'
                ], 404);
            }

            return abort(404);
        }
    }

    public function download($invoiceId)
    {
        try {
            // Check if user has access to this invoice
            $invoice = Invoice::with(['user', 'order.plan'])
                ->where('chargebee_invoice_id', $invoiceId)
                ->firstOrFail();
               

            // Generate PDF using dompdf  
            // return view('admin.invoices.pdf', compact('invoice'));
             $pdf = \PDF::loadView('admin.invoices.pdf', compact('invoice'));
            
            // Generate filename
            $filename = 'invoice_' . $invoiceId . '.pdf';

            // Return PDF file as download
            return $pdf->download($filename);

        } catch (Exception $e) {
            Log::error('Error downloading invoice: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error downloading invoice'
                ], 500);
            }

            return back()->with('error', 'Error downloading invoice');
        }
    }

    // Show pool invoice
    public function showPoolInvoice($invoiceId)
    {
        try {
            $invoice = PoolInvoice::with(['user', 'poolOrder'])
                ->where('chargebee_invoice_id', $invoiceId)
                ->firstOrFail();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $invoice
                ]);
            }

            // You can create a separate view for pool invoices or reuse the same view
            return view('admin.invoices.show-pool', compact('invoice'));
        } catch (Exception $e) {
            Log::error('Error showing pool invoice: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pool invoice not found or access denied'
                ], 404);
            }

            return abort(404);
        }
    }

    // Download pool invoice (redirect to existing pool domain controller method)
    public function downloadPoolInvoice($invoiceId)
    {
        try {
            // Find the pool invoice by chargebee_invoice_id
            $poolInvoice = PoolInvoice::with(['user', 'poolOrder.poolPlan'])
                ->where('chargebee_invoice_id', $invoiceId)
                ->firstOrFail();

            // Generate PDF using dompdf
            $pdf = \PDF::loadView('customer.pool-invoices.pdf', compact('poolInvoice'));
            
            // Generate filename
            $filename = 'pool_invoice_' . $poolInvoice->chargebee_invoice_id . '.pdf';

            // Return PDF file as download
            return $pdf->download($filename);

        } catch (Exception $e) {
            Log::error('Error downloading pool invoice: ' . $e->getMessage());
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error downloading invoice'
                ], 500);
            }

            return back()->with('error', 'Error downloading invoice');
        }
    }
}

