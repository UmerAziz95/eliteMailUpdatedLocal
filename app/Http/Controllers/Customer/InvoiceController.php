<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    private $statuses = [
        "Pending" => "warning",
        "Approve" => "success",
        "Cancel" => "danger",
        "Expired" => "secondary",
        "In-Progress" => "primary",
        "Completed" => "success"
    ];
    // payment-status
    private $paymentStatuses = [
        "Pending" => "warning",
        "Paid" => "success",
        "Failed" => "danger",
        "Refunded" => "secondary"
    ];
    // index
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Invoice::with('order')->where('user_id', auth()->id());

            // Apply status filter
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Apply date range filter
            if ($request->startDate) {
                $query->whereDate('created_at', '>=', $request->startDate);
            }
            if ($request->endDate) {
                $query->whereDate('created_at', '<=', $request->endDate);
            }

            // Apply price range filter
            if ($request->priceRange) {
                list($min, $max) = explode('-', $request->priceRange);
                if ($max === '+') {
                    $query->where('amount', '>=', $min);
                } else {
                    $query->whereBetween('amount', [$min, $max]);
                }
            }

            // Apply order ID filter
            if ($request->orderId) {
                $query->where('order_id', $request->orderId);
            }

            // Apply order status filter
            if ($request->orderStatus) {
                $query->whereHas('order', function($q) use ($request) {
                    $q->where('status_manage_by_admin', $request->orderStatus);
                });
            }

            $data = $query->select([
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

            return DataTables::of($data)
                ->addColumn('action', function($row) {
                    return view('customer.invoices.actions', compact('row'))->render();
                })
                ->editColumn('created_at', function($row) {
                    return $row->created_at ? $row->created_at->format('d F, Y') : '';
                })
                ->editColumn('paid_at', function($row) {
                    return $row->paid_at ? date('d F, Y', strtotime($row->paid_at)) : '';
                })
                ->editColumn('amount', function($row) {
                    return '$' . number_format($row->amount, 2);
                })
                ->editColumn('status', function($row) {
                    $statusClass = $row->status == 'paid' ? 'success' : 'warning';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($row->status) . '</span>';
                })
                ->editColumn('status_manage_by_admin', function($row) {
                    $statusClass = match ($row->order->status_manage_by_admin ?? 'N/A') {
                        'active' => 'success',
                        'expired' => 'danger',
                        'pending' => 'warning',
                        'completed' => 'primary',
                        default => 'secondary',
                    };
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($row->order->status_manage_by_admin ?? 'N/A') . '</span>';
                })
                ->orderColumn('status_manage_by_admin', function($query, $direction) {
                    $query->whereHas('order', function($q) use ($direction) {
                        $q->orderBy('status_manage_by_admin', $direction);
                    });
                })
                ->filterColumn('status_manage_by_admin', function($query, $keyword) {
                    $query->whereHas('order', function($q) use ($keyword) {
                        $q->where('status_manage_by_admin', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action', 'status', 'status_manage_by_admin'])
                ->addIndexColumn()
                ->with([
                    'counters' => [
                        'total' => Invoice::where('user_id', auth()->id())->count(),
                        'paid' => Invoice::where('user_id', auth()->id())->where('status', 'paid')->count(),
                        'pending' => Invoice::where('user_id', auth()->id())->where('status', 'pending')->count(),
                        'failed' => Invoice::where('user_id', auth()->id())->where('status', 'failed')->count(),
                    ]
                ])
                ->make(true);
        }

        return view('customer.invoices.index');
    }

    public function getInvoices(Request $request)
    {
        try {
            $invoices = Invoice::with(['user', 'order'])
                ->where('user_id', auth()->id())
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
                    $viewUrl = route('customer.invoices.show', $invoice->chargebee_invoice_id);
                    $downloadUrl = route('customer.invoices.download', $invoice->chargebee_invoice_id);
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

    /**
     * Show the specified invoice
     *
     * @param string $invoiceId
     * @return \Illuminate\Http\Response
     */
    public function show($invoiceId)
    {
        try {
            $invoice = Invoice::with(['user', 'order'])
                ->where('user_id', auth()->id())
                ->where('chargebee_invoice_id', $invoiceId)
                ->firstOrFail();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $invoice
                ]);
            }

            return view('customer.invoices.show', compact('invoice'));
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

    /**
     * Download the specified invoice
     *
     * @param string $invoiceId
     * @return \Illuminate\Http\Response
     */
    public function download($invoiceId)
    {
        try {
            // Check if user has access to this invoice
            $invoice = Invoice::with(['user', 'order.plan'])
                ->where('user_id', auth()->id())
                ->where('chargebee_invoice_id', $invoiceId)
                ->firstOrFail();

            // Generate PDF using dompdf
            $pdf = \PDF::loadView('customer.invoices.pdf', compact('invoice'));
            
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
}