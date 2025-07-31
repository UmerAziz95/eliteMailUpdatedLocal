@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
<section class="py-3 overflow-hidden">
    @php
    $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
    $defaultProfilePic =
    'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <div style="cursor: pointer; display: inline-flex; align-items: center; margin-bottom: 10px;">
                <a href="{{ url('/admin/domain_health_dashboard') }}"
                    style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #2f3349; border-radius: 50%; text-decoration: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </a>
            </div>
            <h5 class="mb-3">Order #{{ $order->id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span>
                {{ $order->created_at ? $order->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <a href="{{ route('admin.domains.health.report', [$order->id]) }}"
            class="inline-block px-4 py-2 border-success text-white bg-green-600 hover:bg-green-700 border border-green-700 rounded-lg shadow-md transition-all duration-200 ease-in-out">
            🚑 Generate Health Report(CSV)
        </a>
    </div>
    @if (session('error'))
    <div class="bg-red-100 text-red-700 p-3 rounded mb-4 shadow">
        {{ session('error') }}
    </div>
    @endif

    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
        <li class="nav-item" role="presentation" style="display: block;">
            <button class="nav-link fs-6 px-5" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-tab-pane"
                type="button" role="tab" aria-controls="email-tab-pane" aria-selected="false">Domains</button>
        </li>
    </ul>
    <div class="tab-content mt-3" id="myTabContent">
        <div class="tab-pane fade active show" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab"
            tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-earth-europe"></i>
                        </div>
                        Domains
                    </h6>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="email-stats d-flex align-items-center gap-3 bg- rounded p-2">
                            <div class="badge rounded-circle bg-primary p-2">
                                <i class="fa-solid fa-envelope text-white"></i>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="email-configuration" class="display w-100">
                            <thead>
                                <tr>
                                    <th>Sr No</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Summary</th>
                                    <th>Dns Status</th>
                                    <th>Black Listed</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                    @push('scripts')
                    <script>
                        $(document).ready(function() {
                            let emailTable = $('#email-configuration').DataTable({
                                responsive: true,
                                paging: true,
                                pageLength: 10,
                                searching: true,
                                info: true,
                                serverSide: true,
                                processing: true,
                                dom: 'frtip',
                                autoWidth: false,
                                columnDefs: [
                                    { width: '33%', targets: 1 },
                                    { width: '33%', targets: 2 },
                                    { width: '33%', targets: 3 },
                                ],
                                responsive: {
                                    details: {
                                        display: $.fn.dataTable.Responsive.display.modal({
                                            header: function(row) {
                                                return 'Domain Health Details';
                                            }
                                        }),
                                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                                    }
                                },
                                ajax: {
                                    url: '/admin/domains/listings/{{ $order->id }}',
                                    type: 'GET',
                                    dataSrc: function(json) {
                                        return json.data || [];
                                    }
                                },
                                columns: [
                                    {
                                        data: null,
                                        render: function(data, type, row, meta) {
                                            // Sr No: starting from 1 + current page start
                                            return meta.row + 1 + meta.settings._iDisplayStart;
                                        },
                                        title: 'Sr No'
                                    },
                                    { data: 'domain', render: function(data) { return data || ''; } },
                                    { data: 'status', render: function(data) { return data || ''; } },
                                    { data: 'summary', render: function(data) { return data || ''; } },
                                    { data: 'dns_status', render: function(data) { return data || ''; } },
                                    { data: 'blacklist_listed', render: function(data) { return data || ''; } },
                                ]
                            });
                        });
                    </script>
                    @endpush
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Subscription Modal (unchanged) -->
    <div class="modal fade" id="cancel_subscription" tabindex="-1" aria-labelledby="cancel_subscriptionLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="d-flex flex-column align-items-center justify-content-start gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-cart-plus"></i>
                        </div>
                        Cancel Subscription
                    </h6>
                    <p class="note">
                        We are sad to to hear you're cancelling. Would you mind sharing the reason
                        for the cancelation? We strive to always improve and would appreciate your
                        feedback.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection