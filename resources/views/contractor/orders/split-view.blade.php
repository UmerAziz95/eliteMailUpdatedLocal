@extends('contractor.layouts.app')

@section('title', 'Orders')

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
            <h5 class="mb-3">Order #{{ $order->id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span> {{ $order->created_at ? $order->created_at->format('M
                d, Y') : 'N/A' }}</h6>
        </div>
        <div
            class="border border-{{ $order->color_status2 }} rounded-2 py-1 px-2 text-{{ $order->color_status2 }} bg-transparent">
            {{ ucfirst($order->status2 ?? 'Pending') }}
        </div>
    </div>

    <!-- Removed tabs - showing only configuration content -->

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

                @if(optional($order->reorderInfo)->count() > 0)
                <div class="d-flex align-items-center justify-content-between">
                    <span>Total Inboxes <br> {{ $order->reorderInfo->first()->total_inboxes ?? '0' }}</span>
                    <span>Inboxes per domain <br> {{ $order->reorderInfo->first()->inboxes_per_domain ?? '0'
                        }}</span>
                </div>
                <hr>
                <div class="d-flex flex-column">
                    <span class="opacity-50">Prefix Variants</span>
                    @php
                        // Check if new prefix_variants JSON column exists and has data
                        $prefixVariants = $order->reorderInfo->first()->prefix_variants ?? [];
                        $inboxesPerDomain = $order->reorderInfo->first()->inboxes_per_domain ?? 1;
                        
                        // If new format doesn't exist, fallback to old individual fields
                        if (empty($prefixVariants)) {
                            $prefixVariants = [];
                            if ($order->reorderInfo->first()->prefix_variant_1) {
                                $prefixVariants['prefix_variant_1'] = $order->reorderInfo->first()->prefix_variant_1;
                            }
                            if ($order->reorderInfo->first()->prefix_variant_2) {
                                $prefixVariants['prefix_variant_2'] = $order->reorderInfo->first()->prefix_variant_2;
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
                    <span>{{ $order->reorderInfo->first()->profile_picture_link ?? 'N/A' }}</span>
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Email Persona Password</span>
                    <span>{{ $order->reorderInfo->first()->email_persona_password ?? 'N/A' }}</span>
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Master Inbox Email</span>
                    <span>{{ $order->reorderInfo->first()->master_inbox_email ?? 'N/A' }}</span>
                </div>
                @else
                <div class="text-muted">No email configuration data available</div>
                @endif
            </div>

            <div class="price-display-section" style="display:none !important;">
                    @if(isset($order->plan) && $order->plan)
                        @php
                            $totalInboxes = optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->total_inboxes : 0;
                            $originalPrice = $order->plan->price * $totalInboxes;
                        @endphp
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <img src="{{ $defaultImage }}" width="30" alt="Product Icon">
                            </div>
                            <div>
                                <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                <br>
                                <span>({{ $totalInboxes }} x ${{ number_format($order->plan->price, 2) }} <small>/{{ $order->plan->duration }})</small></span>
                            </div>
                        </div>
                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($originalPrice, 2) }}</h6>
                        <!-- <h6><span class="theme-text">Discount:</span> 0%</h6> -->
                        <h6><span class="theme-text">Total:</span> ${{ number_format($originalPrice, 2) }} <small>/{{ $order->plan->duration }}</small></h6>
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

                @if(optional($order->reorderInfo)->count() > 0)
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Hosting Platform</span>
                    <span>{{ $order->reorderInfo->first()->hosting_platform }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Platform Login</span>
                    <span>{{ $order->reorderInfo->first()->platform_login }}</span>
                </div>
                <!-- platform_password -->
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Platform Password</span>
                    <span>{{ $order->reorderInfo->first()->platform_password }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Domain Forwarding Destination URL</span>
                    <span>{{ $order->reorderInfo->first()->forwarding_url }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sending Platform</span>
                    <span>{{ $order->reorderInfo->first()->sending_platform }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sequencer Login</span>
                    <span>{{ $order->reorderInfo->first()->sequencer_login }}</span>
                </div>
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sending plateform Sequencer - Password </span>
                    <span>{{ $order->reorderInfo->first()->sequencer_password }}</span>
                </div>

                <div class="d-flex flex-column">
                    <span class="opacity-50">Domains (Split Assignment)</span>
                    @php
                        // Get domains from the order_panel_split table for current contractor
                        $domainsArray = [];
                        
                        foreach ($order->orderPanels as $orderPanel) {
                            foreach ($orderPanel->userOrderPanelAssignments as $assignment) {
                                if ($assignment->contractor_id == auth()->id() && $assignment->orderPanelSplit) {
                                    $splitDomains = $assignment->orderPanelSplit->domains;
                                    if (is_array($splitDomains)) {
                                        $domainsArray = array_merge($domainsArray, $splitDomains);
                                    } elseif ($splitDomains) {
                                        $domainsArray[] = $splitDomains;
                                    }
                                }
                            }
                        }
                        
                        // Remove duplicates if any
                        $domainsArray = array_unique($domainsArray);
                    @endphp

                    @if(count($domainsArray) > 0)
                        @foreach ($domainsArray as $domain)
                            <span class="d-block">{{ $domain }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No domains assigned to you for this order</span>
                    @endif
                </div>
                @if($order->reorderInfo->first()->hosting_platform == 'namecheap')
                <div class="d-flex flex-column mb-3 mt-3">
                    <span class="opacity-50">Backup Codes</span>
                    @php
                    $backupCodes = explode(',', $order->reorderInfo->first()->backup_codes);
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

@endsection
