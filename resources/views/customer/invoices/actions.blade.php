@php
    $invoiceId = isset($row->isTrial) && $row->isTrial ? $row->id : $row->chargebee_invoice_id;
    $isTrialParam = isset($row->isTrial) && $row->isTrial ? ', true' : '';
@endphp

<div class="dropdown">
    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="javascript:void(0)"
                onclick="viewInvoice('{{ $invoiceId }}'{{ $isTrialParam }})">
                View
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="javascript:void(0)"
                onclick="downloadInvoice('{{ $invoiceId }}'{{ $isTrialParam }})">
                Download
            </a>
        </li>
    </ul>
</div>
