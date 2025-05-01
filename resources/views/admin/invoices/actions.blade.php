<div class="dropdown">
    <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fa-solid fa-ellipsis-vertical"></i>
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="javascript:void(0)" onclick="viewInvoice('{{ $row->order->chargebee_invoice_id}}')">
                View
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="javascript:void(0)" onclick="downloadInvoice('{{$row->order->chargebee_invoice_id}}')">
                Download
            </a>
        </li>
    </ul>
</div>