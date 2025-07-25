@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h1>Coupons</h1>
            <button class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#createCouponCanvas" aria-controls="createCouponCanvas">
                Create New Coupon
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="couponTable" class="table table-striped table-bordered nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Usage Limit</th>
                        <th>Used</th>
                        <th>Status</th>
                        <th>Expires At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Offcanvas for Create Coupon -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="createCouponCanvas" aria-labelledby="createCouponCanvasLabel" style="width: 400px;">
    <div class="offcanvas-header">
        <h5 id="createCouponCanvasLabel">Create New Coupon</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="createCouponForm">
            <div class="mb-3">
                <label for="code" class="form-label">Coupon Code</label>
                <input type="text" id="code" name="code" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Type</label>
                <select id="type" name="type" class="form-select" required>
                    <option value="percentage" selected>Percentage</option>
                    <option value="fixed">Fixed</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="value" class="form-label">Value</label>
                <input type="number" step="0.01" id="value" name="value" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="usage_limit" class="form-label">Usage Limit</label>
                <input type="number" id="usage_limit" name="usage_limit" class="form-control" min="1" required>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select id="status" name="status" class="form-select" required>
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="expires_at" class="form-label">Expires At</label>
                <input type="date" id="expires_at" name="expires_at" class="form-control">
            </div>

            <div class="mb-3">
                <label for="plan_id" class="form-label">Associated Plan</label>
                <select id="plan_id" name="plan_id" class="form-select" >
                    <option value="">-- Select Plan --</option>
                    <!-- Options will be dynamically loaded -->
                </select>
            </div>

            <button type="submit" class="btn btn-success w-100">Create Coupon</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = $('#couponTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: {
            url: "{{ route('admin.coupons.data') }}",
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            error: function(xhr, error, thrown) {
                console.error('DataTable error:', error);
                toastr.error('Error loading coupon data');
                if (xhr.status === 401) window.location.href = "{{ route('login') }}";
                else if (xhr.status === 403) toastr.error('You do not have permission.');
            }
        },
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'type', name: 'type' },
            { data: 'value', name: 'value' },
            { data: 'usage_limit', name: 'usage_limit' },
            { data: 'used', name: 'used' },
            { data: 'status', name: 'status', render: function(data) {
                return data === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
            }},
            { data: 'expires_at', name: 'expires_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        drawCallback: function(settings) {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });

    // Delete coupon logic
    $('#couponTable').on('click', '.delete-coupon', function () {
        const couponId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this coupon!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/coupons/${couponId}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function () {
                        toastr.success('Coupon deleted successfully');
                        table.ajax.reload(null, false);
                    },
                    error: function () {
                        toastr.error('Failed to delete coupon');
                    }
                });
            }
        });
    });

    // Load plans for select box in create coupon form
    function loadPlans() {
        // Example API URL for plans - adjust to your actual route
        $.ajax({
            url: "{{ route('admin.coupons.plan.list') }}", // Should return JSON list of plans [{id, name}]
            type: "GET",
            success: function(plans) {
                console.log(plans,"ppppppppp")
                const $planSelect = $('#plan_id');
                $planSelect.empty().append('<option value="">-- Select Plan --</option>');
                plans.data.forEach(plan => {
                    $planSelect.append(`<option value="${plan.id}">${plan.name}</option>`);
                });
            },
            error: function() {
                toastr.error('Failed to load plans');
            }
        });
    }

    // Load plans when offcanvas opens
    const offcanvas = document.getElementById('createCouponCanvas');
    offcanvas.addEventListener('show.bs.offcanvas', loadPlans);

    // Handle coupon creation form submission
    $('#createCouponForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();

        $.ajax({
            url: "{{ route('admin.coupons.store') }}", // Your route to create coupon
            type: "POST",
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                toastr.success('Coupon created successfully');
                $('#createCouponCanvas').offcanvas('hide');
                table.ajax.reload(null, false);
                $('#createCouponForm')[0].reset();
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errMsg = '';
                    for (const field in errors) {
                        errMsg += errors[field].join('<br>') + '<br>';
                    }
                    toastr.error(errMsg);
                } else {
                    toastr.error('Failed to create coupon');
                }
            }
        });
    });
});
</script>
@endpush
