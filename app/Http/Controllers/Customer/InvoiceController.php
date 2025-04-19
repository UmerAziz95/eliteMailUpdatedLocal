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
    public function getInvoices(Request $request)
    {
        try {
            $invoices = Invoice::with(['user'])
                ->where('user_id', auth()->id());

            // Filter by order_id if provided
            if ($request->has('order_id') && $request->order_id != '') {
                $invoices->where('order_id', $request->order_id);
            }

            $invoices = $invoices->select('invoices.*');
            
            return DataTables::of($invoices)
                ->addColumn('action', function($invoice) {
                    return '<div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">View</a></li>
                            <li><a class="dropdown-item" href="#">Download</a></li>
                        </ul>
                    </div>';
                })
                ->editColumn('created_at', function($invoice) {
                    return $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i:s') : '';
                })
                ->editColumn('paid_at', function($invoice) {
                    return $invoice->paid_at ? date('Y-m-d H:i:s', strtotime($invoice->paid_at)) : '';
                })
                ->editColumn('amount', function($invoice) {
                    return '$' . number_format($invoice->amount, 2);
                })
                ->editColumn('status', function($invoice) {
                    $statusClass = $invoice->status == 'paid' ? 'success' : 'warning';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($invoice->status) . '</span>';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error in getInvoices: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading invoices'], 500);
        }
    }
}