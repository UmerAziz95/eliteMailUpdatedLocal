@extends('admin.layouts.app')

@section('title', 'Orders')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
    .avatar {
        position: relative;
        block-size: 2.5rem;
        cursor: pointer;
        inline-size: 2.5rem;
    }

    .avatar .avatar-initial {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--second-primary);
        font-size: 1.5rem;
        font-weight: 500;
        inset: 0;
        text-transform: uppercase;
    }

    .nav-tabs .nav-link {
        color: var(--extra-light);
        border: none
    }

    .nav-tabs .nav-link:hover {
        color: var(--white-color);
        border: none
    }

    .nav-tabs .nav-link.active {
        background-color: var(--second-primary);
        color: #fff;
        border: none;
        border-radius: 6px
    }
</style>
<style>
    .dt-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 1rem;
        border-radius: 4px;
    }
    
    .loading {
        position: relative;
        pointer-events: none;
        opacity: 0.6;
    }
</style>
@endpush

@section('content')
<section class="py-3">

    <div class="row gy-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($totalOrders) }}</h4>
                                <p class="text-{{ $percentageChange >= 0 ? 'success' : 'danger' }} mb-0">({{ $percentageChange >= 0 ? '+' : '' }}{{ number_format($percentageChange, 1) }}%)</p>
                            </div>
                            <small class="mb-0">Last week vs previous week</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Pending Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($pendingOrders) }}</h4>
                                <!-- <p class="text-success mb-0">Admin Status</p> -->
                            </div>
                            <small class="mb-0">Awaiting admin review</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Complete Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($completedOrders) }}</h4>
                                <!-- <p class="text-success mb-0">Admin Status</p> -->
                            </div>
                            <small class="mb-0">Fully processed orders</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">In-Progress Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($inProgressOrders) }}</h4>
                                <!-- <p class="text-success mb-0">Admin Status</p> -->
                            </div>
                            <small class="mb-0">Currently processing</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card py-3 px-4">
        <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tab-pane"
                    type="button" role="tab" aria-controls="all-tab-pane" aria-selected="true">All Orders</button>
            </li>
            @foreach($plans as $plan)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="plan-{{ $plan->id }}-tab" data-bs-toggle="tab" 
                    data-bs-target="#plan-{{ $plan->id }}-tab-pane" type="button" role="tab" 
                    aria-controls="plan-{{ $plan->id }}-tab-pane" aria-selected="false">{{ $plan->name }}</button>
            </li>
            @endforeach
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab" tabindex="0">
                @include('admin.orders._orders_table')
            </div>
            @foreach($plans as $plan)
            <div class="tab-pane fade" id="plan-{{ $plan->id }}-tab-pane" role="tabpanel" 
                aria-labelledby="plan-{{ $plan->id }}-tab" tabindex="0">
                @include('admin.orders._orders_table', ['plan_id' => $plan->id])
            </div>
            @endforeach
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin"
        aria-labelledby="offcanvasAddAdminLabel" aria-modal="true" role="dialog">
        <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
            <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">View Detail</h5>
            <button class="border-0 bg-transparent" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><i
                    class="fa-solid fa-xmark fs-5"></i></button>
        </div>
        <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">

        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Debug AJAX calls
    $(document).ajaxSend(function(event, jqXHR, settings) {
        console.log('AJAX Request:', {
            url: settings.url,
            type: settings.type,
            data: settings.data,
            headers: jqXHR.headers
        });
    });

    function viewOrder(id) {
        window.location.href = `{{ url('/admin/orders/${id}/view') }}`;
    }
    
    function initDataTable(planId = '') {
        console.log('Initializing DataTable for planId:', planId);
        var tableId = planId ? `#myTable-${planId}` : '#myTable';
        console.log('Looking for table with selector:', tableId);
        var $table = $(tableId);
        if (!$table.length) {
            console.error('Table not found with selector:', tableId);
            return null;
        }
        console.log('Found table:', $table);
        
        try {
            var table = $table.DataTable({
                processing: true,
                serverSide: true,
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                return 'Order Details';
                            }
                        }),
                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                    }
                },
                autoWidth: false,
                columnDefs: [
                    { width: '10%', targets: 0 }, // ID
                    { width: '15%', targets: 1 }, // Date
                    ...(planId ? [] : [{ width: '15%', targets: 2 }]), // Plan (only for All Orders)
                    { width: '20%', targets: planId ? 2 : 3 }, // Email
                    { width: '15%', targets: planId ? 3 : 4 }, // Domain URL
                    { width: '15%', targets: planId ? 4 : 5 }, // Status
                    { width: '15%', targets: planId ? 4 : 5 }, // Status
                    { width: '10%', targets: planId ? 5 : 6 }  // Actions
                ],
                ajax: {
                    url: "{{ route('admin.orders.data') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: function(d) {
                        d.draw = d.draw || 1;
                        d.length = d.length || 10;
                        d.start = d.start || 0;
                        d.search = d.search || { value: '', regex: false };
                        d.plan_id = planId;

                        // Debug request parameters
                        console.log('Request parameters:', {
                            draw: d.draw,
                            start: d.start,
                            length: d.length,
                            search: d.search,
                            plan_id: d.plan_id,
                            columns: d.columns,
                            order: d.order
                        });

                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('Server response:', json);
                        return json.data;
                    },
                    error: function (xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        console.error('Server response:', xhr.responseText);
                        console.error('Status:', xhr.status);
                        console.error('Full XHR:', xhr);
                        
                        if (xhr.status === 401) {
                            window.location.href = "{{ route('login') }}";
                        } else if (xhr.status === 403) {
                            toastr.error('You do not have permission to view this data');
                        } else {
                            toastr.error('Error loading orders data: ' + error);
                        }
                    }
                },
                columns: [
                    { data: 'id', name: 'orders.id' },
                    { data: 'created_at', name: 'orders.created_at' },
                    ...(planId ? [] : [{ data: 'plan_name', name: 'plans.name' }]),
                    { data: 'email', name: 'email' },
                    { data: 'name', name: 'name' },
                    { data: 'domain_forwarding_url', name: 'domain_forwarding_url' },
                    { data: 'status', name: 'orders.status' },
                    { data: 'total_inboxes', name: 'total_inboxes' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[1, 'desc']],
                drawCallback: function(settings) {
                    console.log('Table draw complete. Response:', settings.json);
                    if (settings.json && settings.json.error) {
                        toastr.error(settings.json.message || 'Error loading data');
                    }
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    
                    // Adjust columns after draw
                    this.api().columns.adjust();
                    this.api().responsive.recalc();
                },
                initComplete: function(settings, json) {
                    console.log('Table initialization complete');
                    this.api().columns.adjust();
                    this.api().responsive.recalc();
                }
            });

            // Handle processing state visually
            table.on('processing.dt', function(e, settings, processing) {
                const wrapper = $(tableId + '_wrapper');
                if (processing) {
                    console.log('DataTable processing started');
                    wrapper.addClass('loading');
                    wrapper.append('<div class="dt-loading">Loading...</div>');
                } else {
                    console.log('DataTable processing completed');
                    wrapper.removeClass('loading');
                    wrapper.find('.dt-loading').remove();
                }
            });

            return table;
        } catch (error) {
            console.error('Error initializing DataTable:', error);
            toastr.error('Error initializing table. Please refresh the page.');
        }
    }

    $(document).ready(function() {
        try {
            console.log('Document ready, initializing tables');
            
            // Initialize DataTables object to store all table instances
            window.orderTables = {};

            // Initialize table for all orders
            window.orderTables.all = initDataTable();

            // Initialize tables for each plan
            @foreach($plans as $plan)
            window.orderTables['plan{{ $plan->id }}'] = initDataTable('{{ $plan->id }}');
            @endforeach

            // Handle tab changes
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const tabId = $(e.target).attr('id');
                console.log('Tab changed to:', tabId);
                
                // Force recalculation of column widths for visible tables
                setTimeout(function() {
                    Object.values(window.orderTables).forEach(function(table) {
                        if ($(table.table().node()).is(':visible')) {
                            table.columns.adjust();
                            table.responsive.recalc();
                            console.log('Adjusting columns for table:', table.table().node().id);
                        }
                    });
                }, 10);
            });

            // Initial column adjustment for the active tab
            setTimeout(function() {
                const activeTable = $('.tab-pane.active .table').DataTable();
                if (activeTable) {
                    activeTable.columns.adjust();
                    activeTable.responsive.recalc();
                    console.log('Initial column adjustment for active table');
                }
            }, 10);

            // Add global error handler for AJAX requests
            $(document).ajaxError(function(event, xhr, settings, error) {
                console.error('AJAX Error:', error);
                if (xhr.status === 401) {
                    window.location.href = "{{ route('login') }}";
                } else if (xhr.status === 403) {
                    toastr.error('You do not have permission to perform this action');
                }
            });
        } catch (error) {
            console.error('Error in document ready:', error);
        }
    });

    $(document).on('change', '.status-dropdown', function () {
    let selectedStatus = $(this).val();
    let orderId = $(this).data('id');


    $.ajax({
        url: '/admin/update-order-status',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            order_id: orderId,
            status_manage_by_admin: selectedStatus
        },
        success: function (response) {
            // Reload the correct table instead of re-initializing it
            if (window.orderTables && window.orderTables.all) {
                window.orderTables.all.ajax.reload(null, false); // false to stay on the current page
            }

            alert('Status updated successfully!');
        },
        error: function (xhr) {
              if (window.orderTables && window.orderTables.all) {
                window.orderTables.all.ajax.reload(null, false); // false to stay on the current page
            }
            alert('Something went wrong!');
            console.error(xhr.responseText);
        }
    });
});

function CancelSubscription(subscriptionId) {
        console.log('Cancel Subscription clicked for ID:', subscriptionId);
        // Get the subscription details
        const subscription = subscriptionId;
        
        // Set the subscription ID in the modal form 
        $('#subscription_id_to_cancel').val(subscription);
        
        // Show the modal
        $('#cancel_subscription').modal('show');
    }

    // Handle form submission
    $('#cancelSubscriptionForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Cancel Subscription form submitted');
        // Check if reason is provided
        const reason = $('#cancellation_reason').val().trim();
        if (!reason) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'The reason field is required.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Get form data and ensure remove_accounts is boolean
        const formData = new FormData(this);
        formData.set('remove_accounts', $('#remove_accounts').is(':checked'));
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: Object.fromEntries(formData),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we cancel your subscription',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        // Close the modal
                        $('#cancel_subscription').modal('hide');
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Your subscription has been cancelled successfully.',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            // Reload the page to reflect changes
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while cancelling your subscription.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: errorMessage,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    });

</script>

@endpush