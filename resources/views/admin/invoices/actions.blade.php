@php
    // Handle both normal invoices and trial invoices (pool_invoices)
    $invoiceId = isset($isTrial) && $isTrial 
        ? $row->chargebee_invoice_id 
        : ($row->chargebee_invoice_id ?? $row->order->chargebee_invoice_id ?? null);
    $isTrialParam = isset($isTrial) && $isTrial ? 'true' : 'false';
@endphp

<div class="dropdown">
    <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>
    <ul class="dropdown-menu">
        @if($invoiceId)
            <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="viewInvoice('{{ $invoiceId }}', {{ $isTrialParam }})">
                    View
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="javascript:void(0)" onclick="downloadInvoice('{{ $invoiceId }}', {{ $isTrialParam }})">
                    Download
                </a>
            </li>
        @else
            <li>
                <span class="dropdown-item text-muted">No invoice ID available</span>
            </li>
        @endif
    </ul>
</div>