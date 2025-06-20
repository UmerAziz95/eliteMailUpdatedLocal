@extends('admin.layouts.app')

@section('title', 'Dashboard')

@push('styles')
<!-- Additional styles for statistics cards -->
<style>
    .stats-card {
        transition: all 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
    }

    .stats-icon {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    /* 
    .bg-soft-primary {
        background-color: rgba(115, 103, 240, 0.12);
    }

    .bg-soft-success {
        background-color: rgba(40, 199, 111, 0.12);
    }

    .bg-soft-warning {
        background-color: rgba(255, 159, 67, 0.12);
    } */
</style>
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
    .swiper {
        width: 100%;
    }

    .swiper-slide {
        background-color: #5750bf34;
        border: 1px solid var(--second-primary);
        backdrop-filter: blur(10px);
        border-radius: 6px;
        color: #fff;
    }

    .swiper-slide img {
        max-width: 100%;
        height: auto;
    }

    .form-select {
        font-size: 12px !important
    }

    .text-success {
        color: rgb(4, 229, 154) !important
    }

    label {
        font-size: 12px
    }

    span {
        font-size: 13px;
    }

    h5 {
        font-weight: 600
    }

    .slider_span_bg {
        background-color: #33333332;
    }

    .swiper-pagination {
        top: 0px !important;
        /* left: 260px !important */
    }

    .swiper-pagination-bullet-active {
        background-color: #fff !important;
    }

    /* .bg-label-info {
        background-color: rgba(0, 255, 255, 0.143);
    }

    .bg-label-primary {
        background-color: rgba(79, 0, 128, 0.203);
    } */

    .divider.divider-vertical {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: unset;
        block-size: 100%;
    }

    .divider {
        --bs-divider-color: #ffffff30;
        display: block;
        overflow: hidden;
        margin-block: 1rem;
        margin-inline: 0;
        text-align: center;
        white-space: nowrap;
    }

    .divider.divider-vertical:has(.badge-divider-bg)::before {
        inset-inline-start: 49%;
    }

    .divider.divider-vertical::before {
        inset-block: 0 50%;
    }

    .divider.divider-vertical::before,
    .divider.divider-vertical::after {
        position: absolute;
        border-inline-start: 1px solid var(--bs-divider-color);
        content: "";
        inset-inline-start: 50%;
    }

    .divider.divider-vertical:has(.badge-divider-bg)::after,
    .divider.divider-vertical:has(.badge-divider-bg)::before {
        inset-inline-start: 49%;
    }

    .divider.divider-vertical::after {
        inset-block: 50% 0;
    }

    .divider.divider-vertical::before,
    .divider.divider-vertical::after {
        position: absolute;
        border-inline-start: 1px solid var(--bs-divider-color);
        content: "";
        inset-inline-start: 50%;
    }

    .divider.divider-vertical .divider-text {
        z-index: 1;
        padding: .5125rem;
        background-color: var(--secondary-color);
    }

    .divider .divider-text {
        position: relative;
        display: inline-block;
        font-size: .9375rem;
        padding-block: 0;
        padding-inline: 1rem;
    }

    .divider.divider-vertical .divider-text .badge-divider-bg {
        border-radius: 50%;
        background-color: #8c8c8c47;
        color: var(--extra-light);
        /* color: var(--bs-secondary-color); */
        font-size: .75rem;
        padding-block: .313rem;
        padding-inline: .252rem;
    }

    .custom-dot {
        font-size: 12px;
        margin-bottom: .4rem
    }

    .custom-dot::marker {
        color: var(--second-primary);
    }

    /* .bg-label-primary {
        background-color:
            color-mix(in sRGB, #2f3349 84%, var(--second-primary)) !important;
    }

    .bg-label-danger {
        background-color:
            color-mix(in sRGB, #2f3349 84%, red) !important;
        color: rgb(255, 127, 127) !important;
    }

    .bg-label-warning {
        background-color:
            color-mix(in sRGB, #2f3349 84%, rgb(255, 150, 22)) !important;
        color: rgb(255, 191, 114) !important;
    }

    .bg-label-success {
        background-color:
            color-mix(in sRGB, #2f3349 84%, rgb(16, 186, 16)) !important;
        color: rgb(143, 255, 143) !important;
    } */

    .nav-pills .nav-link {
        background-color: transparent;
        color: var(--extra-light);
        font-size: 12px
    }

    .nav-pills .nav-link.active {
        background-color: var(--second-primary);
        color: #fff
    }
</style>
@endpush

@section('content')
<section class="py-3 ">
    <div class="row gy-4">

        <div class="col-md-6">
            @include('admin.dashboard.slider')
        </div>

        <div class="col-md-6">
            @include('admin.dashboard.counter')
        </div>

        <!-- Recent Orders -->
        <!-- <div class="col-xl-3 col-md-4 col-sm-6 order-1">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="mb-3 fw-semibold">Recent Orders</h6>
                    @forelse($recentOrders ?? [] as $order)
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h6 class="mb-0 fs-sm">#{{ $order->id }}</h6>
                            <span class="opacity-50 fs-xs">{{ $order->created_at?->diffForHumans() }}</span>
                        </div>
                        <span
                            class="bg-{{ $order->status->color ?? 'secondary' }} bg-label-success px-3 py-1 rounded-pill">
                            {{ $order->status_manage_by_admin ?? 'N/A' }}
                        </span>
                    </div>
                    @empty
                    <p class="text-muted mb-0">No recent orders</p>
                    @endforelse
                </div>
            </div>
        </div> -->

        <div class="col-4">
            @include('admin.dashboard.pie_chart')
        </div>


        <!-- <div class="col-xxl-3 col-md-4 col-sm-6 order-2 order-md-3">
            @include('admin.dashboard.sales_by')
        </div> -->



        {{-- revenue overview graph --}}
        <div class="col-4">
            @include('admin.dashboard.revenue_graph')
        </div>


        {{-- subscription overview graph --}}
        <div class="col-4">
            @include('admin.dashboard.subscription_graph')
        </div>



        <div class="col-12">
            <div class="card p-3 overflow-y-auto">
                <div>
                    <table id="myTable">
                        <thead class="position-sticky" style="background-color: var(--secondary-color); top: -17px">
                            <tr>
                                <th class="text-start">ID</th>
                                <th>Action Type</th>
                                <th>Description</th>
                                <th>Performed By</th>
                                <th>Performed On Type</th>
                                <th>Performed On Id</th>
                                <th>IP</th>
                                <th style="min-width: 10rem">User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < 10; $i++) <tr>
                                <td class="text-start">001</td>
                                <td>
                                    <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                        style="border-radius: 50%" height="35" width="35" class="object-fit-cover"
                                        alt="">
                                    John Doe
                                </td>
                                <td><i class="ti ti-mail text-success"></i> Johndoe123@gmail.com</td>
                                <td>4/4/2025</td>
                                <td><span class="active_status">Active</span></td>
                                <td>
                                    <button class="bg-transparent p-0 border-0 mx-2"><i
                                            class="fa-regular fa-eye"></i></button>
                                </td>
                                </tr>
                                @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var swiper = new Swiper(".swiper", {
            loop: true,
            speed: 1000,
            autoplay: {
                delay: 3000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });

    });

    // var options = {
    //     series: [{
    //         data: [0, 40, 35, 70, 60, 80, 50]
    //     }],
    //     chart: {
    //         type: 'area',
    //         height: 135,
    //         sparkline: {
    //             enabled: true
    //         }
    //     },
    //     stroke: {
    //         curve: 'smooth',
    //         width: 2,
    //         colors: ['#00e396']
    //     },
    //     fill: {
    //         colors: ['rgba(0,227,150,0.6162114504004728)'],
    //         type: 'gradient',
    //         gradient: {
    //             shadeIntensity: 1,
    //             opacityFrom: 0.4,
    //             opacityTo: 0,
    //             stops: [0, 90, 100]
    //         }
    //     },
    //     tooltip: {
    //         enabled: true,
    //         enabledOnSeries: undefined,
    //         shared: true,
    //         followCursor: true,
    //         intersect: false,
    //         inverseOrder: false,
    //         custom: undefined,
    //         hideEmptySeries: true,
    //         fillSeriesColor: false,
    //         theme: true,
    //         style: {
    //             fontSize: '12px',
    //             fontFamily: undefined
    //         },
    //         onDatasetHover: {
    //             highlightDataSeries: true,
    //         },
    //         x: {
    //             show: true,
    //             format: 'dd MMM',
    //             formatter: undefined,
    //         },
    //         y: {
    //             formatter: undefined,
    //             // title: {
    //             //     formatter: (seriesName) => seriesName,
    //             // },
    //         },
    //         z: {
    //             formatter: undefined,
    //             title: 'Size: '
    //         },
    //         marker: {
    //             show: true,
    //         },
    //         // items: {
    //         //     display: flex,
    //         // },
    //         fixed: {
    //             enabled: true,
    //             position: 'topRight',
    //             offsetX: 0,
    //             offsetY: 0,
    //         },
    //     }
    // };

    // var chart = new ApexCharts(document.querySelector("#salesChartRevenue"), options);
    // chart.render();


    

      
   
    // var options = {
    //     series: [85],
    //     chart: {
    //         height: 400,
    //         type: 'radialBar',
    //     },

    //     plotOptions: {
    //         radialBar: {
    //             startAngle: -135,
    //             endAngle: 135,
    //             hollow: {
    //                 margin: 0,
    //                 size: '60%',
    //                 background: 'transparent',
    //             },
    //             track: {
    //                 background: 'transparent',
    //                 strokeWidth: '100%',
    //             },
    //             dataLabels: {
    //                 show: true,
    //                 name: {
    //                     offsetY: 20,
    //                     show: true,
    //                     color: '#A3A9BD',
    //                     fontSize: '14px',
    //                     text: 'Completed Task'
    //                 },
    //                 value: {
    //                     offsetY: -10,
    //                     color: '#fff',
    //                     fontSize: '28px',
    //                     show: true,
    //                     formatter: function(val) {
    //                         return val + "%";
    //                     }
    //                 }
    //             },
    //         }
    //     },

    //     // ✅ Make it segmented like bars
    //     stroke: {
    //         dashArray: 12
    //     },

    //     fill: {
    //         type: 'gradient',
    //         gradient: {
    //             shade: 'dark',
    //             type: 'horizontal',
    //             gradientToColors: ['#7F6CFF'],
    //             stops: [0, 100]
    //         }
    //     },

    //     colors: ['#3D3D66'],
    //     labels: ['Completed Task']
    // };

    // var chart = new ApexCharts(document.querySelector("#taskGaugeChart"), options);
    // chart.render();

    // DAY - Subscription Chart


</script>

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
        window.location.href = "{{ route('admin.index') }}?id=" + id;
    }

    // Apply Filters
    $('#applyFilters').click(function() {
        refreshDataTable();
    });

    // Clear Filters
    $('#clearFilters').click(function() {
        $('#user_name_filter').val('');
        $('#email_filter').val('');
        $('#status_filter').val('');
        refreshDataTable();
    });

    function refreshDataTable() {
        if (window.orderTables && window.orderTables.all) {
            window.orderTables.all.ajax.reload(null, false);
        }
    }



    function initDataTable(planId = '') {
        console.log('Initializing DataTable for planId:', planId);
        const tableId = '#myTable';
        const $table = $(tableId);

        if (!$table.length) {
            console.error('Table not found with selector:', tableId);
            return null;
        }

        try {
            const table = $table.DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                dom: '<"top"f>rt<"bottom"lip><"clear">', // expose filter (f) and move others
                ajax: {
                    url: "{{ route('logs.index') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        console.error('Server response:', xhr.responseText);

                        if (xhr.status === 401) {
                            window.location.href = "{{ route('login') }}";
                        } else if (xhr.status === 403) {
                            toastr.error('You do not have permission to view this data');
                        } else {
                            toastr.error('Error loading data: ' + error);
                        }
                    }
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'action_type', name: 'action_type' },
                    { 
                        data: 'description', 
                        name: 'description', 
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex align-items-center text-nowrap">
                                    <div class="me-1 rounded-1 d-flex align-items-center justify-content-center" style="background-color: rgba(85, 255, 78, 0.4); height: 20px; width: 20px">
                                        <i style="color: #A6FF00" class="ti ti-file-description fs-6"></i>
                                    </div>
                                    <span>${data}</span>
                                </div>
                            `;
                        }
                    },   
                    { 
                        data: 'performed_by', name: 'performed_by', 
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex align-items-center text-nowrap px-2 py-1 rounded-2" style= "border: 1px solid #00F2FF">
                                    <span style="color: #00F2FF">${data}</span>
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'performed_on_type', name: 'performed_on_type' ,
                        render: function(data, type, row) {
                            return `
                                <img src="https://cdn-icons-png.flaticon.com/128/3641/3641988.png" style="width: 25px" alt="">
                                <span>${data}</span>
                            `;
                        }
                    },
                    { data: 'performed_on', name: 'performed_on' },
                    { data: 'ip', name: 'ip' },
                    { data: 'user_agent', name: 'user_agent' },
                    // { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                columnDefs: [
                    { width: '5%', targets: 0 },  // ID
                    { width: '5%', targets: 1 },  // Action Type
                    { width: '30%', targets: 2 },  // Description 
                    { width: '10%', targets: 3 },  // Performed By
                    { width: '10%', targets: 4 },  // Performed On Type
                    { width: '10%', targets: 5 },  // Performed On Id
                    { width: '15%', targets: 6 },  // Extra Data
                    { width: '10%', targets: 7 }   // User Agent
                ],
                order: [[0, 'desc']],
                drawCallback: function(settings) {
                    const counters = settings.json?.counters;

                    if (counters) {
                        $('#total_counter').text(counters.total);
                        $('#active_counter').text(counters.active);
                        $('#inactive_counter').text(counters.inactive);
                    }

                    $('[data-bs-toggle="tooltip"]').tooltip();
                    this.api().columns.adjust();
                    this.api().responsive?.recalc();
                },
                initComplete: function() {
                    console.log('Table initialization complete');
                    this.api().columns.adjust();
                    this.api().responsive?.recalc();

                    // 🔽 Append your custom button next to the search bar
                    // const button = `
                    //     <button class="m-btn fw-semibold border-0 rounded-1 ms-2 text-white"
                    //             style="padding: .4rem 1rem"
                    //             type="button"
                    //             data-bs-toggle="offcanvas"
                    //             data-bs-target="#offcanvasAddAdmin"
                    //             aria-controls="offcanvasAddAdmin">
                    //         + Add New Record
                    //     </button>
                    // `;

                    // $('.dataTables_filter').append(button);
                }
            });

            // Optional loading indicator
            table.on('processing.dt', function(e, settings, processing) {
                const wrapper = $(tableId + '_wrapper');
                if (processing) {
                    wrapper.addClass('loading');
                    if (!wrapper.find('.dt-loading').length) {
                        wrapper.append('<div class="dt-loading">Loading...</div>');
                    }
                } else {
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
            window.orderTables = {};
            // Initialize table for all Subscriptions
            window.orderTables.all = initDataTable();

            // Handle tab changes
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
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
</script>


<script>
    $('#createUserForm').on('submit', function(e) {
        e.preventDefault();

        const form = this;
        const userId = $('#user_id').val();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        if (password && password !== confirmPassword) {
            toastr.error('Passwords do not match!');
            return;
        }

        let formData = new FormData(form);
        let url = userId ?
            "{{ url('admin/') }}/" + userId // Edit URL
            :
            "{{ route('admin.users.store') }}"; // Create URL

        let method = userId ? "POST" : "POST"; // Both will use POST, but we spoof PUT for update

        if (userId) {
            formData.append('_method', 'PUT'); // Laravel expects PUT for update
        }

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                let action = userId ? 'updated' : 'created';
                toastr.success(`User ${action} successfully!`);

                // Reset and clear form
                $('#createUserForm')[0].reset();
                $('#user_id').val('');

                // Hide the offcanvas
                let offcanvasElement = document.getElementById('offcanvasAddAdmin');
                let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
                offcanvasInstance.hide();

                // Reload DataTable
                if (window.orderTables && window.orderTables.all) {
                    window.orderTables.all.ajax.reload(null, false);
                }
            },
            error: function(xhr) {
                if (xhr.responseJSON?.errors) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = Object.values(errors).map(err => err.join(', ')).join('<br>');
                    toastr.error(errorMessages);
                } else {
                    toastr.error('Something went wrong.');
                }
            }
        });
    });
</script>

@endpush