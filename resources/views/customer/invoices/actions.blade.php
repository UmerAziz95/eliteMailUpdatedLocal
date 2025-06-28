<div class="dropdown">
    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">

        <div class="actions d-flex align-items-center justify-content-between position-relative">
            <div class="board d-flex justify-content-start ps-2"
                style="background-color: var(--secondary-color); height: 18px;">
                <span class="text-white">Click</span>
            </div>

            <div class="action-icon"
                style="position: absolute; left: 0; top: -1px; z-index: 2; background-color: orange; height: 20px; width: 20px; border-radius: 50px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-chevron-right text-dark font-bold"></i>
            </div>

        </div>
    </button>
    <ul class="dropdown-menu">
        <li>
            <a class="dropdown-item" href="javascript:void(0)"
                onclick="viewInvoice('{{ $row->chargebee_invoice_id }}')">
                View
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="javascript:void(0)"
                onclick="downloadInvoice('{{ $row->chargebee_invoice_id }}')">
                Download
            </a>
        </li>
    </ul>
</div>
