@extends('contractor.layouts.app')

@section('title', 'Orders')

{{-- 
    OPTIMIZED VERSION: This view now uses the $orderPanel variable for more efficient data access
    instead of foreach loops through $order->orderPanels. This improves performance and code clarity.
--}}

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('contractor.orders') }}" class="d-flex align-items-center justify-content-center"
            style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
    </div>

    @php
    $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
    $defaultProfilePic =
    'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <h5 class="mb-3">Order #{{ $orderPanel->order->id ?? 'N/A' }} - Panel {{ $orderPanel->id }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span> {{ $orderPanel->order->created_at ? $orderPanel->order->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="border border-{{ $orderPanel->split_status_color ?? 'secondary' }} rounded-2 py-1 px-2 text-{{ $orderPanel->split_status_color ?? 'secondary' }} bg-transparent">
            {{ ucfirst($orderPanel->status ?? 'Pending') }}
            </div>
            <button
            id="openStatusModal"
            class="btn btn-outline-success btn-sm py-1 px-2">
            Change Status
            </button>
        </div>
        
    </div>

    <!-- Configuration Content Only -->
    <div class="row">
        <div class="col-md-6">
            <div class="card p-3 mb-3">
                <h6 class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-regular fa-envelope"></i>
                    </div>
                    Email configurations
                </h6>

                @if(optional($orderPanel->order->reorderInfo)->count() > 0)
                <div class="d-flex align-items-center justify-content-between">
                    @php
                        // Calculate split total inboxes based on domains and inboxes per domain
                        $inboxesPerDomain = $orderPanel->order->reorderInfo->first()->inboxes_per_domain ?? 0;
                        $splitDomainsCount = 0;
                        
                        if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                            foreach ($orderPanel->orderPanelSplits as $split) {
                                if ($split->domains) {
                                    if (is_array($split->domains)) {
                                        $splitDomainsCount += count($split->domains);
                                    } else {
                                        $splitDomainsCount += 1;
                                    }
                                }
                            }
                        }
                        
                        $splitTotalInboxes = $splitDomainsCount * $inboxesPerDomain;
                    @endphp
                    <span>Split Total Inboxes <br> {{ $splitTotalInboxes }}</span>
                    <span>Inboxes per domain <br> {{ $inboxesPerDomain }}</span>
                </div>
                <hr>
                <div class="d-flex flex-column">
                    <span class="opacity-50">Prefix Variants</span>
                    @php
                        // Check if new prefix_variants JSON column exists and has data
                        $prefixVariants = $orderPanel->order->reorderInfo->first()->prefix_variants ?? [];
                        $inboxesPerDomain = $orderPanel->order->reorderInfo->first()->inboxes_per_domain ?? 1;
                        
                        // If new format doesn't exist, fallback to old individual fields
                        if (empty($prefixVariants)) {
                            $prefixVariants = [];
                            if ($orderPanel->order->reorderInfo->first()->prefix_variant_1) {
                                $prefixVariants['prefix_variant_1'] = $orderPanel->order->reorderInfo->first()->prefix_variant_1;
                            }
                            if ($orderPanel->order->reorderInfo->first()->prefix_variant_2) {
                                $prefixVariants['prefix_variant_2'] = $orderPanel->order->reorderInfo->first()->prefix_variant_2;
                            }
                        }
                    @endphp
                    
                    @for($i = 1; $i <= $inboxesPerDomain; $i++)
                        @php
                            $variantKey = "prefix_variant_$i";
                            $variantValue = $prefixVariants[$variantKey] ?? 'N/A';
                        @endphp
                        <span>Variant {{ $i }}: {{ $variantValue }}</span>
                    @endfor
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Profile Picture URL</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->profile_picture_link ?? 'N/A' }}</span>
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Email Persona Password</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->email_persona_password ?? 'N/A' }}</span>
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Master Inbox Email</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->master_inbox_email ?? 'N/A' }}</span>
                </div>
                @else
                <div class="text-muted">No email configuration data available</div>
                @endif
            </div>

            <div class="price-display-section" style="display:none !important;">
                    @if(isset($orderPanel->order->plan) && $orderPanel->order->plan)
                        @php
                            $totalInboxes = optional($orderPanel->order->reorderInfo->first())->total_inboxes ?? 0;
                            $originalPrice = $orderPanel->order->plan->price * $totalInboxes;
                        @endphp
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <img src="{{ $defaultImage }}" width="30" alt="Product Icon">
                            </div>
                            <div>
                                <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                <br>
                                <span>({{ $totalInboxes }} x ${{ number_format($orderPanel->order->plan->price, 2) }} <small>/{{ $orderPanel->order->plan->duration }}</small>)</span>
                            </div>
                        </div>
                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($originalPrice, 2) }}</h6>
                        <!-- <h6><span class="theme-text">Discount:</span> 0%</h6> -->
                        <h6><span class="theme-text">Total:</span> ${{ number_format($originalPrice, 2) }} <small>/{{ $orderPanel->order->plan->duration }}</small></h6>
                    @else
                        <h6><span class="theme-text">Original Price:</span> <small>Select a plan to view price</small></h6>
                        <h6><span class="theme-text">Total:</span> <small>Select a plan to view total</small></h6>
                    @endif
                </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-earth-europe"></i>
                    </div>
                    Domains & Configuration
                </h6>

                @if(optional($orderPanel->order->reorderInfo)->count() > 0)
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Hosting Platform</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->hosting_platform }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Platform Login</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->platform_login }}</span>
                </div>
                <!-- platform_password -->
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Platform Password</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->platform_password }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Domain Forwarding Destination URL</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->forwarding_url }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sending Platform</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sending_platform }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sequencer Login</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sequencer_login }}</span>
                </div>
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sending plateform Sequencer - Password </span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sequencer_password }}</span>
                </div>

                <div class="d-flex flex-column">
                    <span class="opacity-50">Domains (Split Assignment)</span>
                    
                    @php
                        // Get domains from the order panel splits collection
                        $domainsArray = [];
                        
                        if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                            // Get domains from the first split (or combine all splits if multiple)
                            foreach ($orderPanel->orderPanelSplits as $split) {
                                if ($split->domains) {
                                    if (is_array($split->domains)) {
                                        $domainsArray = array_merge($domainsArray, $split->domains);
                                    } else {
                                        $domainsArray[] = $split->domains;
                                    }
                                }
                            }
                        }
                    @endphp

                    @if(count($domainsArray) > 0)
                        @foreach ($domainsArray as $domain)
                            <span class="d-block">{{ $domain }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No domains available for this order panel</span>
                    @endif
                </div>
                @if($orderPanel->order->reorderInfo->first()->hosting_platform == 'namecheap')
                <div class="d-flex flex-column mb-3 mt-3">
                    <span class="opacity-50">Backup Codes</span>
                    @php
                    $backupCodes = explode(',', $orderPanel->order->reorderInfo->first()->backup_codes);
                    @endphp
                    @foreach($backupCodes as $backupCode)
                    <span>{{ trim($backupCode) }}</span>
                    @endforeach
                </div>
                @endif
                @else
                <div class="text-muted">No configuration information available</div>
                @endif
            </div>
        </div>
    </div>

</section>
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
                    Mark Status
                </h6>



                <form id="cancelSubscriptionForm" action="{{ route('contractor.order.panel.status.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="order_panel_id" id="order_panel_id_to_update">
                    <div class="mb-3">
                        <div class="">

                            <div class="mb-3 d-none" id="reason_wrapper">
                                <p class="note">
                                    Would you mind sharing the reason
                                    for the rejection?
                                </p>
                                <label for="cancellation_reason">Reason *</label>
                                <textarea id="cancellation_reason" name="reason" class="form-control"
                                    rows="5"></textarea>
                            </div>
                        </div>
                        <label class="form-label">Select Status *</label>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($splitStatuses as $status => $badge)
                            <div class="form-check me-3">
                                <input class="form-check-input marked_status" type="radio" name="marked_status"
                                    value="{{ $status }}" id="status_{{ $loop->index }}" required>
                                <label class="form-check-label text-{{ $badge }}" for="status_{{ $loop->index }}">
                                    {{ ucfirst($status) }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>



                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button"
                            class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                            data-bs-dismiss="modal">No</button>
                        <button type="submit"
                            class="border border-danger py-1 px-3 w-100 bg-transparent text-danger rounded-2">Yes,
                            I'm sure</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    $('#openStatusModal').on('click', function() {
        // Use the order panel ID directly since we have it from the controller
        $('#order_panel_id_to_update').val('{{ $orderPanel->id }}');
        $('#cancel_subscription').modal('show');
    });

    // Handle status change to show/hide reason field
    $('.marked_status').on('change', function() {
        const selectedStatus = $(this).val();
        if (selectedStatus === 'rejected') {
            $('#reason_wrapper').removeClass('d-none');
            $('#cancellation_reason').prop('required', true);
        } else {
            $('#reason_wrapper').addClass('d-none');
            $('#cancellation_reason').prop('required', false);
        }
    });

    // Handle form submission with SweetAlert
    $('#cancelSubscriptionForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const selectedStatus = $('input[name="marked_status"]:checked').val();
        
        if (!selectedStatus) {
            Swal.fire({
                icon: 'warning',
                title: 'Status Required',
                text: 'Please select a status before submitting.'
            });
            return;
        }

        // Show loading alert
        Swal.fire({
            title: 'Updating Status...',
            text: 'Please wait while we update the panel status.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit the form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.close();
                $('#cancel_subscription').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Panel status updated successfully.',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    // Optionally reload the page or update the UI
                    window.location.reload();
                });
            },
            error: function(xhr) {
                Swal.close();
                
                let errorMessage = 'Failed to update panel status.';
                
                if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message || errorMessage;
                    
                    // Show debug info if available
                    if (xhr.responseJSON.debug) {
                        console.log('Debug info:', xhr.responseJSON.debug);
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            }
        });
    });
</script>
@endpush
