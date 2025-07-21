<div class="dropdown">
    <button class="btn btn-sm text-white" type="button" id="actionDropdown-{{ $coupon->id }}" data-bs-toggle="dropdown"
        aria-expanded="false" style="border:none;">
        <i class="ti ti-dots-vertical fs-5"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="actionDropdown-{{ $coupon->id }}">
        <li>
            <a href="javascript:void(0);" class="dropdown-item delete-coupon" data-id="{{ $coupon->id }}">
                <i class="ti ti-trash me-2"></i> Delete
            </a>
        </li>
    </ul>
</div>