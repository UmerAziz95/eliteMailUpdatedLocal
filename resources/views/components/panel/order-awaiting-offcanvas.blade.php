@props([
    'offcanvasId' => 'secondOffcanvas',
    'openButtonId' => 'openSecondOffcanvasBtn',
])

<div class="offcanvas offcanvas-start" style="min-width: 70%;  background-color: var(--filter-color); backdrop-filter: blur(5px); border: 3px solid var(--second-primary);" tabindex="-1" id="{{ $offcanvasId }}" aria-labelledby="{{ $offcanvasId }}Label">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="{{ $offcanvasId }}Label">Order Awaited Panel Allocation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="counters mb-3">
            <div class="p-3 filter">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Number of Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="orders_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-brands fa-first-order fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 filter">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        
                        <div class="content-left">
                            <h6 class="text-heading">Number of Inboxes</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="inboxes_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-inbox fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-3 filter">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Panels Required</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="panels_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-solar-panel fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="myTable" class="w-100 display">
                <thead style="position: sticky; top: 0;">
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Plan</th>
                        <th>Domain URL</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody class="overflow-y-auto" id="orderTrackingTableBody">
                    <!-- Dynamic data will be loaded here -->
                </tbody>
            </table>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            let orderTrackingTable = null;

            function initializeOrderTrackingTable() {
                if (orderTrackingTable) {
                    orderTrackingTable.destroy();
                }

                orderTrackingTable = $('#myTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    ordering: true,
                    searching: false,
                    scrollX: true,
                    processing: true,
                    ajax: {
                        url: '/admin/panels/order-tracking',
                        type: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        dataSrc: function (json) {
                            if (json.success) {
                                if (json.counters) {
                                    $('#orders_counter').text(json.counters.total_orders || 0);
                                    $('#inboxes_counter').text(json.counters.total_inboxes || 0);
                                    $('#panels_counter').text(json.counters.panels_required || 0);
                                }
                                return json.data;
                            }
                            console.error('Error fetching data:', json.message);
                            return [];
                        }
                    },
                    columns: [
                        { data: 'order_id', title: 'Order ID', render: function (data) { return '#' + data; } },
                        { data: 'date', title: 'Date' },
                        { data: 'plan', title: 'Plan' },
                        { data: 'domain_url', title: 'Domain URL' },
                        { data: 'total', title: 'Total' },
                        {
                            data: 'status',
                            title: 'Status',
                            render: function (data) {
                                let badgeClass = 'bg-label-secondary';
                                switch (data) {
                                    case 'completed':
                                    case 'active':
                                        badgeClass = 'bg-label-success';
                                        break;
                                    case 'pending':
                                        badgeClass = 'bg-label-warning';
                                        break;
                                    case 'failed':
                                        badgeClass = 'bg-label-danger';
                                        break;
                                }
                                return '<span class="badge ' + badgeClass + ' rounded-1 px-2 py-1">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                            }
                        }
                    ]
                });
            }

            async function updateCounters(data = null) {
                try {
                    if (data && typeof data === 'object' && data.counters) {
                        $('#orders_counter').text(data.counters.total_orders || 0);
                        $('#inboxes_counter').text(data.counters.total_inboxes || 0);
                        $('#panels_counter').text(data.counters.panels_required || 0);
                        return;
                    }

                    const response = await fetch('/admin/panels/order-tracking', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const result = await response.json();

                    if (result.success && result.counters) {
                        $('#orders_counter').text(result.counters.total_orders || 0);
                        $('#inboxes_counter').text(result.counters.total_inboxes || 0);
                        $('#panels_counter').text(result.counters.panels_required || 0);
                    } else {
                        $('#orders_counter').text(0);
                        $('#inboxes_counter').text(0);
                        $('#panels_counter').text(0);
                    }
                } catch (error) {
                    console.error('Error updating counters:', error);
                    $('#orders_counter').text(0);
                    $('#inboxes_counter').text(0);
                    $('#panels_counter').text(0);
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const triggerBtn = document.getElementById(@json($openButtonId));
                if (triggerBtn) {
                    triggerBtn.addEventListener('click', function () {
                        const offcanvasElement = document.getElementById(@json($offcanvasId));
                        const offcanvas = new bootstrap.Offcanvas(offcanvasElement, {
                            backdrop: false,
                            scroll: true
                        });
                        offcanvas.show();
                        setTimeout(function () {
                            initializeOrderTrackingTable();
                        }, 300);
                    });
                }
            });
        </script>
    @endpush
@endonce
