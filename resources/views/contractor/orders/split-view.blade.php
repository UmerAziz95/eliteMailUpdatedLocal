@extends('contractor.layouts.app')

@section('title', 'Orders')

@push('styles')
<style>
    .accordion {
        --bs-accordion-bg: transparent !important;
    }

    .accordion-button:focus {
        box-shadow: none !important
    }

    .button.collapsed {
        background-color: var(--slide-bg) !important;
        color: var(--light-color);
        cursor: pointer;
    }

    .button {
        background-color: var(--second-primary);
        color: var(--light-color);
        transition: all ease .4s;
        cursor: pointer;
    }

    .accordion-body {
        color: var(--light-color)
    }

    .transition-transform {
        transition: transform 0.3s ease;
    }
</style>
@endpush

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ url()->previous() }}" class="d-flex align-items-center justify-content-center"
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
        <!-- <div class="d-flex align-items-center gap-2">
            <div class="border border-{{ $orderPanel->split_status_color ?? 'secondary' }} rounded-2 py-1 px-2 text-{{ $orderPanel->split_status_color ?? 'secondary' }} bg-transparent">
            {{ ucfirst($orderPanel->status ?? 'Pending') }}
            </div>
            <button
            id="openStatusModal"
            class="btn btn-outline-success btn-sm py-1 px-2">
            Change Status
            </button>
        </div> -->
        
    </div>
    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5 active" id="configuration-tab" data-bs-toggle="tab"
                data-bs-target="#configuration-tab-pane" type="button" role="tab" aria-controls="configuration-tab-pane"
                aria-selected="true">Configuration</button>
        </li>
        <li class="nav-item" role="presentation ">
            <button class="nav-link fs-6 px-5" id="other-tab" data-bs-toggle="tab" data-bs-target="#other-tab-pane"
                type="button" role="tab" aria-controls="other-tab-pane" aria-selected="false">Emails</button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="myTabContent">
        <div class="tab-pane fade show active" id="configuration-tab-pane" role="tabpanel"
            aria-labelledby="configuration-tab" tabindex="0">
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
                    <span>Split Total Inboxes <br> ({{ $splitDomainsCount }} domains * {{ $inboxesPerDomain }} inboxes per domain) = {{ $splitTotalInboxes }}</span>
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
                    <span class="opacity-50">Profile Picture URLs</span>
                    @if($orderPanel->order->reorderInfo->first()->prefix_variants_details)
                        @foreach($orderPanel->order->reorderInfo->first()->prefix_variants_details as $key => $variant)
                            <div class="mt-1">
                                <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                @if(!empty($variant['profile_link']))
                                    <a href="{{ $variant['profile_link'] }}" target="_blank">{{ $variant['profile_link'] }}</a>
                                @else
                                    <span>N/A</span>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <span>N/A</span>
                    @endif
                </div>
                <!-- <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Email Persona Password</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->email_persona_password ?? 'N/A' }}</span>
                </div> -->
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
                    <span class="opacity-50">Cold email platform - Login</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sequencer_login }}</span>
                </div>
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Cold email platform - Password </span>
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
                    <span class="opacity-50">Additional Notes</span>
                     <span>{{ $orderPanel->order->reorderInfo->first()->additional_info ?? '' }}</span>
                </div>
                @endif
                @else
                <div class="text-muted">No configuration information available</div>
                    @endif
                </div>
            </div>
        </div>
        
    </div>               
    <div class="tab-pane fade" id="other-tab-pane" role="tabpanel" aria-labelledby="other-tab" tabindex="0">
        <div class="col-12">
            <!-- Custom Note Alert -->
            @if($orderPanel->customized_note)
            <div class="position-relative overflow-hidden rounded-4 border-0 shadow-sm mb-3" 
                style="background: linear-gradient(135deg, #1d2239 0%, #2a2f48 100%);">
                
                <!-- Decorative Background Pattern -->
                <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10">
                  <div class="position-absolute" style="top: -20px; right: -20px; width: 80px; height: 80px; background: linear-gradient(45deg, #4f46e5, #7c3aed); border-radius: 50%; opacity: 0.3;"></div>
                  <div class="position-absolute" style="bottom: -10px; left: -10px; width: 60px; height: 60px; background: linear-gradient(45deg, #06b6d4, #3b82f6); border-radius: 50%; opacity: 0.2;"></div>
                </div>
                
                <!-- Content Container -->
                <div class="position-relative p-4">
                  <!-- Header with Icon -->
                  <div class="d-flex align-items-center mb-3">
                    <div class="me-3 d-flex align-items-center justify-content-center" 
                        style="width: 45px; height: 45px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 12px; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);">
                       <i class="fa-solid fa-sticky-note text-white fs-5"></i>
                    </div>
                    <div>
                       <h6 class="mb-0 fw-bold text-white">Customized Note</h6>
                       <small class="text-light opacity-75">Additional information provided</small>
                    </div>
                  </div>
                  
                  <!-- Note Content -->
                  <div class="p-4 rounded-3 border-0 position-relative overflow-hidden" 
                     style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.12) 0%, rgba(124, 58, 237, 0.08) 100%); border-left: 4px solid #4f46e5 !important;">
                    <!-- Quote Icon -->
                    <div class="position-absolute top-0 start-0 mt-2 ms-3">
                       <i class="fas fa-quote-left text-info opacity-25 fs-4"></i>
                    </div>
                    
                    <!-- Note Text -->
                    <div class="ms-4">
                       <p class="mb-0 text-white fw-medium" 
                         style="line-height: 1.7; font-size: 15px; text-indent: 1rem;">
                         {{ $orderPanel->customized_note }}
                       </p>
                    </div>
                    
                    <!-- Bottom Quote Icon -->
                    <div class="position-absolute bottom-0 end-0 mb-2 me-3">
                       <i class="fas fa-quote-right text-info opacity-25 fs-4"></i>
                    </div>
                  </div>
                  
                  <!-- Bottom Accent Line -->
                  <div class="mt-3 mx-auto rounded-pill" 
                     style="width: 60px; height: 3px; background: linear-gradient(90deg, #4f46e5, #7c3aed);"></div>
                </div>
            </div>
            @endif
            <div class="card p-3">
                <h6 class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    Email Accounts for Panel #{{ $orderPanel->id }}
                </h6>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <!-- @if($orderPanel->order->status_manage_by_admin === 'pending' || $orderPanel->order->status_manage_by_admin === 'in-progress') -->
                            
                            <!-- @endif -->
                            <button id="addBulkEmail" class="btn btn-primary me-2 btn-sm d-none" data-bs-toggle="modal"
                                data-bs-target="#BulkImportModal">
                                <i class="fa-solid fa-plus me-1"></i> Emails Customization
                            </button>
                            @php
                                // Get the uploaded file path from order panel splits
                                $uploadedFilePath = null;
                                if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                                    foreach ($orderPanel->orderPanelSplits as $split) {
                                        if ($split->uploaded_file_path) {
                                            $uploadedFilePath = $split->uploaded_file_path;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            
                            @if($uploadedFilePath)
                                <a href="{{ route('contractor.order.panel.email.downloadCsv', ['orderPanelId' => $orderPanel->id]) }}" 
                                   class="btn btn-outline-success me-2 btn-sm d-none" 
                                   title="Download uploaded CSV file">
                                    <i class="fa-solid fa-download me-1"></i> Download CSV
                                </a>
                            @endif
                            
                            <!-- <button id="addNewBtn" class="btn btn-primary me-2">
                                <i class="fa-solid fa-plus me-1"></i> Add Email
                            </button>
                            <button id="saveAllBtn" class="btn btn-success">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save All
                            </button> -->
                        </div>
                    </div>
                    <div class="email-stats d-flex align-items-center gap-2 bg- rounded p-2 d-none">
                        <div class="badge rounded-circle bg-primary p-2">
                            <i class="fa-solid fa-envelope text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 small">Email Accounts</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span id="totalRowCount" class="fw-bold small">0</span>
                                <div class="progress" style="width: 100px; height: 6px;">
                                    <div class="progress-bar bg-primary" id="emailProgressBar" role="progressbar"
                                        style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <div id="email-batches-container">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-3 text-muted">Loading email batches...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

</section>

<!-- Bulk Import Modal -->
<div class="modal fade" id="BulkImportModal" tabindex="-1" aria-labelledby="BulkImportModalLabel"
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
                    Bulk Import Emails for Panel #{{ $orderPanel->id }}
                </h6>
                <div class="row text-muted" id="csvInstructions">
                    <p class="text-danger">Only .csv files are accepted.</p>
                    <p class="text-danger">The CSV file must include the following headers: <strong>First Name</strong>,
                        <strong>Last Name</strong>, <strong>Email</strong>, and <strong>Password</strong>.
                    </p>
                    <p><a href="{{url('/').'/assets/samples/emails.csv'}}"><strong class="text-primary">Download
                                Sample File</strong></a></p>
                </div>
                
                <div class="alert alert-success d-none" id="fileSelectedInfo">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <span id="selectedFileName">File selected successfully</span>
                </div>

                <form id="BulkImportForm" action="{{ route('contractor.order.panel.email.bulkImport') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="bulk_file" class="form-label">Select CSV *</label>
                        <input type="file" class="form-control" id="bulk_file" name="bulk_file" accept=".csv"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="customized_note" class="form-label">Customized Note (Optional)</label>
                        <textarea class="form-control" id="customized_note" name="customized_note" rows="3" 
                                  placeholder="Add any customized notes or instructions for this import..." maxlength="1000"></textarea>
                        <div class="form-text" style="display:none;">
                            <span id="note-char-count">0</span> / 1000 characters
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

<!-- Batch-specific Import Modal -->
<div class="modal fade" id="BatchImportModal" tabindex="-1" aria-labelledby="BatchImportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="BatchImportModalLabel">
                    <i class="fa-solid fa-layer-group me-2"></i>
                    Import Emails for <span id="batchNumberTitle"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    You are importing emails for <strong id="batchNumberInfo"></strong>. 
                    Expected: <strong id="expectedCountInfo"></strong> emails.
                </div>

                <div class="row text-muted" id="batchCsvInstructions">
                    <p class="text-danger">Only .csv files are accepted.</p>
                    <p class="text-danger">The CSV file must include the following headers: <strong>First Name</strong>, <strong>Last Name</strong>, <strong>Email address</strong>, and <strong>Password</strong>.</p>
                    <p><a href="{{ url('/').'/assets/samples/emails.csv' }}"><strong class="text-primary">Download Sample File</strong></a></p>
                </div>

                <div class="alert alert-success d-none" id="batchFileSelectedInfo">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <span id="batchSelectedFileName">File selected successfully</span>
                </div>

                <form id="BatchImportForm" action="{{ route('contractor.order.panel.email.bulkImport') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_panel_id" value="{{ $orderPanel->id }}">
                    <input type="hidden" name="batch_id" id="batch_id_input" value="">
                    <input type="hidden" name="expected_count" id="expected_count_input" value="">

                    <div class="mb-3">
                        <label for="batch_file" class="form-label">Select CSV *</label>
                        <input type="file" class="form-control" id="batch_file" name="bulk_file" accept=".csv" required>
                    </div>

                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button" class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="border border-success py-1 px-3 w-100 bg-transparent text-success rounded-2">
                            <i class="fa-solid fa-upload me-1"></i>
                            Import for Batch
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
</div>

<!-- Status Change Modal -->
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
    // Open status modal with proper event handling
    $('#openStatusModal').on('click', function(e) {
        console.log("ok");
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Opening status modal');
        
        // Set the order panel ID
        const orderPanelId = '{{ $orderPanel->id }}';
        $('#order_panel_id_to_update').val(orderPanelId);
        
        // Reset form state
        $('#cancelSubscriptionForm')[0].reset();
        $('#reason_wrapper').addClass('d-none');
        $('#cancellation_reason').prop('required', false);
        
        // Remove any validation states
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Show the modal
        $('#cancel_subscription').modal('show');
        
        console.log('Modal should be visible now, order panel ID set to:', orderPanelId);
    });

    // Handle status change to show/hide reason field with better event handling
    $(document).on('change', '.marked_status', function() {
        const selectedStatus = $(this).val();
        console.log('Status changed to:', selectedStatus);
        
        if (selectedStatus === 'rejected') {
            $('#reason_wrapper').removeClass('d-none');
            $('#cancellation_reason').prop('required', true);
            // Focus on the reason field after a short delay to ensure it's visible
            setTimeout(() => {
                $('#cancellation_reason').focus();
            }, 100);
        } else {
            $('#reason_wrapper').addClass('d-none');
            $('#cancellation_reason').prop('required', false);
            $('#cancellation_reason').val(''); // Clear the reason when not needed
        }
    });

    // Handle form submission with SweetAlert
    $('#cancelSubscriptionForm').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Form submission triggered');
        
        const form = $(this);
        const formData = new FormData(this);
        const selectedStatus = $('input[name="marked_status"]:checked').val();
        const orderPanelId = $('#order_panel_id_to_update').val();
        
        console.log('Selected status:', selectedStatus);
        console.log('Order panel ID:', orderPanelId);
        
        // Validation checks
        if (!selectedStatus) {
            Swal.fire({
                icon: 'warning',
                title: 'Status Required',
                text: 'Please select a status before submitting.'
            });
            return false;
        }
        
        if (!orderPanelId) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Information',
                text: 'Order panel ID is missing. Please refresh the page and try again.'
            });
            return false;
        }
        
        // Check if reason is required and provided
        if (selectedStatus === 'rejected') {
            const reason = $('#cancellation_reason').val()?.trim();
            if (!reason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reason Required',
                    text: 'Please provide a reason for rejection.'
                });
                $('#cancellation_reason').focus();
                return false;
            }
        }

        // Show loading alert
        Swal.fire({
            title: 'Updating Status...',
            text: 'Please wait while we update the panel status.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit the form via AJAX
        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            timeout: 30000, // 30 second timeout
            success: function(response) {
                console.log('Success response:', response);
                Swal.close();
                $('#cancel_subscription').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Panel status updated successfully.',
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true
                }).then(() => {
                    // Reload the page to reflect changes
                    window.location.reload();
                });
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseText,
                    xhr: xhr
                });
                
                Swal.close();
                
                let errorMessage = 'Failed to update panel status.';
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message || errorMessage;
                    
                    // Show validation errors if present
                    if (xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        errorMessage = errors.join('<br>');
                    }
                    
                    // Show debug info if available
                    if (xhr.responseJSON.debug) {
                        console.log('Debug info:', xhr.responseJSON.debug);
                    }
                } else if (xhr.status === 0) {
                    errorMessage = 'Network error. Please check your connection and try again.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Server endpoint not found. Please contact support.';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    html: errorMessage,
                    confirmButtonText: 'OK',
                    allowOutsideClick: true
                });
            }
        });
        
        return false;
    });

    // Additional fallback handler for the submit button
    // $(document).on('click', '#cancelSubscriptionForm button[type="submit"]', function(e) {
    //     console.log('Submit button clicked directly');
        
    //     // Trigger form submission if it hasn't been triggered yet
    //     const form = $('#cancelSubscriptionForm');
    //     if (form.length) {
    //         form.trigger('submit');
    //     }
    // });
    
    // Debug modal events
    $('#cancel_subscription').on('shown.bs.modal', function() {
        console.log('Modal is now visible');
    });
    
    $('#cancel_subscription').on('hidden.bs.modal', function() {
        console.log('Modal is now hidden');
        // Reset form when modal is closed
        $('#cancelSubscriptionForm')[0].reset();
        $('#reason_wrapper').addClass('d-none');
        $('#cancellation_reason').prop('required', false);
    });

    // Email Management JavaScript
    $(document).ready(function() {
        // Get the split total inboxes from order configuration
        @php
            $inboxesPerDomain = optional($orderPanel->order->reorderInfo->first())->inboxes_per_domain ?? 0;
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
        
        const splitTotalInboxes = {{ $splitTotalInboxes }};
        const maxEmails = splitTotalInboxes || 0; // If splitTotalInboxes is 0, allow unlimited emails
        
        // If batch UI is present (no #email-configuration table), load batches and exit early
        if (!$('#email-configuration').length) {
            function updateTotalCount(totalEmails) {
                if (maxEmails > 0) {
                    $('#totalRowCount').text(totalEmails + "/" + splitTotalInboxes);
                } else {
                    $('#totalRowCount').text(totalEmails);
                }
                updateProgressBar(totalEmails);
            }
            function updateProgressBar(totalEmails) {
                let percentage = 0;
                if (maxEmails > 0) {
                    percentage = Math.min((totalEmails / maxEmails) * 100, 100);
                } else {
                    percentage = Math.min((totalEmails / 100) * 100, 100);
                }
                const progressBar = $('#emailProgressBar');
                progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);
                progressBar.removeClass('bg-primary bg-warning bg-danger');
                if (maxEmails > 0) {
                    if (percentage >= 90) progressBar.addClass('bg-danger');
                    else if (percentage >= 70) progressBar.addClass('bg-warning');
                    else progressBar.addClass('bg-primary');
                } else { progressBar.addClass('bg-primary'); }
            }
            function showEmptyState(message = 'No email batches found. Please import emails using the CSV upload feature.') {
                $('#email-batches-container').html(`
                    <div class=\"text-center py-5\">
                        <i class=\"fa-solid fa-inbox fa-4x text-muted mb-3\"></i>
                        <h5 class=\"text-muted\">${message}</h5>
                        <p class=\"text-muted\">Click \"Emails Customization\" to import emails.</p>
                    </div>
                `);
            }
            function escapeHtml(text) {
                const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                return text ? text.toString().replace(/[&<>"']/g, m => map[m]) : '';
            }

            // Toggle batch accordions and rotate icon (global function)
            window.toggleBatchAccordion = function(targetId, buttonElement, event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                const target = document.getElementById(targetId);
                if (!target) {
                    console.error('Target not found:', targetId);
                    return;
                }

                const batchNumber = targetId.replace('collapse', '');
                const arrowIcon = document.getElementById(`batch-accordion-icon-${batchNumber}`);
                const isExpanded = target.classList.contains('show');

                if (isExpanded) {
                    // Close this accordion
                    $(target).collapse('hide');
                    buttonElement.setAttribute('aria-expanded', 'false');
                    buttonElement.classList.add('collapsed');
                    if (arrowIcon) {
                        arrowIcon.style.transform = 'rotate(0deg)';
                    }
                } else {
                    // Close other accordions first
                    document.querySelectorAll('#emailBatchesAccordion .accordion-collapse.show').forEach(openItem => {
                        $(openItem).collapse('hide');
                        const btn = openItem.previousElementSibling?.querySelector('.button');
                        if (btn) {
                            btn.setAttribute('aria-expanded', 'false');
                            btn.classList.add('collapsed');
                        }
                        const collapseId = openItem.id;
                        const batchNum = collapseId.replace('collapse', '');
                        const icon = document.getElementById(`batch-accordion-icon-${batchNum}`);
                        if (icon) {
                            icon.style.transform = 'rotate(0deg)';
                        }
                    });

                    // Open this accordion
                    $(target).collapse('show');
                    buttonElement.setAttribute('aria-expanded', 'true');
                    buttonElement.classList.remove('collapsed');
                    if (arrowIcon) {
                        arrowIcon.style.transform = 'rotate(180deg)';
                    }
                }
            };

            function displayEmailBatches(response) {
                const container = $('#email-batches-container');
                container.empty();

                if (!response.batches || response.batches.length === 0) {
                    showEmptyState();
                    return;
                }

                // Create accordion for batches
                const accordionId = 'emailBatchesAccordion';
                let accordionHtml = `<div class="accordion accordion-flush" id="${accordionId}">`;

                response.batches.forEach((batch, index) => {
                    const batchNumber = batch.batch_number;
                    const emailCount = batch.email_count;
                    const expectedCount = batch.expected_count || 200;
                    const emails = batch.emails || [];
                    const isFirstBatch = index === 0;
                    
                    // Determine badge color and status based on email count
                    let badgeClass = 'bg-secondary';
                    let statusIcon = 'fa-circle-question';
                    let statusText = 'Unknown';
                    let cardBorder = 'border-secondary';
                    
                    if (emailCount === 0) {
                        badgeClass = 'bg-info';
                        statusIcon = 'fa-circle-dot';
                        statusText = 'Default';
                        cardBorder = 'border-info';
                    } else if (emailCount < expectedCount) {
                        badgeClass = 'bg-warning text-dark';
                        statusIcon = 'fa-circle-exclamation';
                        statusText = 'Partial';
                        cardBorder = 'border-warning';
                    } else {
                        badgeClass = 'bg-success';
                        statusIcon = 'fa-circle-check';
                        statusText = 'Customized';
                        cardBorder = 'border-success';
                    }

                    accordionHtml += `
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading${batchNumber}">
                                <div class="button p-3 ${isFirstBatch ? '' : 'collapsed'} d-flex align-items-center justify-content-between" 
                                     type="button"
                                     aria-expanded="${isFirstBatch ? 'true' : 'false'}"
                                     aria-controls="collapse${batchNumber}"
                                     onclick="toggleBatchAccordion('collapse${batchNumber}', this, event)">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="d-flex align-items-center justify-content-center" 
                                             style="width: 35px; height: 35px; background: rgba(255, 255, 255, 0.15); border-radius: 8px; backdrop-filter: blur(10px);">
                                            <i class="fa-solid fa-layer-group text-white" style="font-size: 0.85rem;"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-0 text-white" style="font-size: 0.9rem;">Batch #${batchNumber}</div>
                                            <small class="d-flex align-items-center gap-1" style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.7);">
                                                <i class="fa-solid fa-envelope" style="font-size: 0.7rem;"></i>
                                                ${emailCount} / ${expectedCount} emails
                                            </small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge ${badgeClass} rounded-pill px-2 py-1 d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                            <i class="fa-solid ${statusIcon}" style="font-size: 0.7rem;"></i>
                                            ${statusText}
                                        </span>
                                        <i class="fas fa-chevron-down transition-transform" id="batch-accordion-icon-${batchNumber}" style="font-size: 12px; transform: rotate(${isFirstBatch ? '180' : '0'}deg);"></i>
                                    </div>
                                </div>
                            </h2>
                            <div id="collapse${batchNumber}" class="accordion-collapse collapse ${isFirstBatch ? 'show' : ''}" 
                                 aria-labelledby="heading${batchNumber}" data-bs-parent="#${accordionId}">
                                <div class="accordion-body" style="padding: 0.5rem;">
                    `;

                    if (emails.length > 0) {
                        // Create table for this batch
                        accordionHtml += `
                            <div class="table-responsive" style="border-radius: 8px; overflow: hidden; max-height: 500px; overflow-y: auto;">
                                <table class="table table-hover mb-0" style="background: transparent;">
                                    <thead style="background: linear-gradient(135deg, #1d2239 0%, #2a2f48 100%); color: white; position: sticky; top: 0; z-index: 10;">
                                        <tr>
                                            <th style="width: 5%; padding: 0.5rem; font-size: 0.8rem;">#</th>
                                            <th style="width: 25%; padding: 0.5rem; font-size: 0.8rem;">First Name</th>
                                            <th style="width: 25%; padding: 0.5rem; font-size: 0.8rem;">Last Name</th>
                                            <th style="width: 30%; padding: 0.5rem; font-size: 0.8rem;">Email Address</th>
                                            <th style="width: 15%; padding: 0.5rem; font-size: 0.8rem;">Password</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

                        emails.forEach((email, emailIndex) => {
                            accordionHtml += `
                                <tr style="border-bottom: 1px solid #e0e2e5;">
                                    <td style="padding: 0.5rem;">
                                        <span class="badge rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.75rem; background: rgba(79, 70, 229, 0.1); color: var(--second-primary);">
                                            ${emailIndex + 1}
                                        </span>
                                    </td>
                                    <td style="padding: 0.5rem; font-size: 0.85rem;">${escapeHtml(email.name || '')}</td>
                                    <td style="padding: 0.5rem; font-size: 0.85rem;">${escapeHtml(email.last_name || '')}</td>
                                    <td style="padding: 0.5rem;">
                                        <span class="badge px-2 py-1" style="font-weight: normal; font-size: 0.75rem; background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                            <i class="fa-solid fa-envelope me-1" style="font-size: 0.65rem;"></i>
                                            ${escapeHtml(email.email || '')}
                                        </span>
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <code class="px-2 py-1 rounded" style="background: rgba(214, 51, 132, 0.1); color: #d63384; font-size: 0.75rem;">
                                            ${escapeHtml(email.password || '')}
                                        </code>
                                    </td>
                                </tr>
                            `;
                        });

                        accordionHtml += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-2 py-2 border-top" style="background: rgba(234, 179, 8, 0.15); border-top-color: rgba(234, 179, 8, 0.3) !important;">
                                <button class="btn btn-warning import-batch-btn px-3 py-1 shadow-sm" 
                                        data-batch-id="${batchNumber}" 
                                        data-expected-count="${expectedCount}" 
                                        data-overwrite="true"
                                        style="border-radius: 6px; font-weight: 500; font-size: 0.8rem; background: linear-gradient(135deg, #eab308 0%, #f59e0b 100%); border: none; color: white;">
                                    <i class="fa-solid fa-sync-alt me-1" style="font-size: 0.75rem;"></i>
                                    Overwrite Batch ${batchNumber}
                                </button>
                            </div>
                        `;
                    } else {
                        // Empty batch - show import button
                        accordionHtml += `
                            <div class="py-4 px-3" style="background: transparent;">
                                <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 180px;">
                                    <!-- Icon Container -->
                                    <div class="mb-3 d-flex align-items-center justify-content-center" 
                                         style="width: 70px; height: 70px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; border: 2px dashed rgba(59, 130, 246, 0.3);">
                                        <i class="fa-solid fa-inbox" style="font-size: 1.8rem; color: #3b82f6;"></i>
                                    </div>
                                    
                                    <!-- Message -->
                                    <div class="text-center mb-3">
                                        <h6 class="mb-1 fw-bold" style="color: #e5e7eb;">Batch ${batchNumber} is Empty</h6>
                                        <p class="mb-0 small" style="color: #9ca3af;">
                                            <i class="fa-solid fa-circle-info me-1" style="font-size: 0.75rem;"></i>
                                            Expected ${expectedCount} email accounts
                                        </p>
                                    </div>
                                    
                                    <!-- Import Button -->
                                    <button class="btn import-batch-btn px-4 py-2 shadow-sm" 
                                            data-batch-id="${batchNumber}" 
                                            data-expected-count="${expectedCount}"
                                            style="border-radius: 8px; font-weight: 500; font-size: 0.85rem; background: linear-gradient(135deg, var(--second-primary) 0%, #4f46e5 100%); border: none; color: white; transition: all 0.3s ease;">
                                        <i class="fa-solid fa-upload me-2" style="font-size: 0.8rem;"></i>
                                        Import Emails for Batch ${batchNumber}
                                    </button>
                                </div>
                            </div>
                        `;
                    }

                    accordionHtml += `
                                </div>
                            </div>
                        </div>
                    `;
                });

                accordionHtml += '</div>';
                
                // Add summary at the top with progress bar
                const totalEmails = response.total_emails || 0;
                const spaceAssigned = response.space_assigned || 0;
                const completionPercentage = spaceAssigned > 0 ? Math.round((totalEmails / spaceAssigned) * 100) : 0;
                const remaining = spaceAssigned - totalEmails;

                const summaryHtml = `
                    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1d2239 0%, #2a2f48 100%); border-radius: 12px; overflow: hidden;">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-3">
                                        <div class="d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px; background: rgba(79, 70, 229, 0.2); border-radius: 12px;">
                                            <i class="fa-solid fa-chart-pie text-white" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div class="text-start">
                                            <h6 class="mb-0 text-white fw-bold">Summary</h6>
                                            <small style="color: rgba(255, 255, 255, 0.7);">Email Distribution</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="row g-3">
                                        <div class="col-4">
                                            <div class="text-center">
                                                <div class="badge bg-info bg-opacity-25 text-white px-3 py-2 w-100" style="font-size: 0.85rem;">
                                                    <i class="fa-solid fa-layer-group me-1"></i>
                                                    <div class="fw-bold fs-5 mt-1">${response.total_batches || 0}</div>
                                                    <small class="opacity-75">Total Batches</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-center">
                                                <div class="badge bg-success bg-opacity-25 text-white px-3 py-2 w-100" style="font-size: 0.85rem;">
                                                    <i class="fa-solid fa-envelope-circle-check me-1"></i>
                                                    <div class="fw-bold fs-5 mt-1">${totalEmails}</div>
                                                    <small class="opacity-75">Emails Added</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-center">
                                                <div class="badge bg-primary bg-opacity-25 text-white px-3 py-2 w-100" style="font-size: 0.85rem;">
                                                    <i class="fa-solid fa-database me-1"></i>
                                                    <div class="fw-bold fs-5 mt-1">${spaceAssigned}</div>
                                                    <small class="opacity-75">Space Assigned</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-white opacity-75">
                                                <i class="fa-solid fa-chart-line me-1"></i>
                                                Progress: ${completionPercentage}% Complete
                                            </small>
                                            <small class="text-white opacity-75">
                                                <i class="fa-solid fa-hourglass-half me-1"></i>
                                                ${remaining} Remaining
                                            </small>
                                        </div>
                                        <div class="progress" style="height: 8px; border-radius: 10px; background: rgba(255, 255, 255, 0.1);">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: ${completionPercentage}%; background: linear-gradient(90deg, #10b981 0%, #3b82f6 50%, #8b5cf6 100%); border-radius: 10px; transition: width 0.6s ease;" 
                                                 aria-valuenow="${completionPercentage}" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                container.html(summaryHtml + accordionHtml);
            }
            function loadEmailBatches() {
                $.ajax({
                    url: '/contractor/orders/panel/{{ $orderPanel->id }}/emails/batches',
                    method: 'GET',
                    success: function(resp){ if(resp.success){ displayEmailBatches(resp); updateTotalCount(resp.total_emails); } else { showEmptyState(resp.message||undefined);} },
                    error: function(){ showEmptyState('Error loading email batches. Please refresh the page.'); }
                });
            }
            // Batch-specific import button
            $(document).on('click', '.import-batch-btn', function(){
                const batchId=$(this).data('batch-id'); const expected=$(this).data('expected-count');
                const isOverwrite=$(this).data('overwrite')===true;
                
                if (isOverwrite) {
                    $('#batchNumberTitle').text('Overwrite Batch ' + batchId);
                    $('#BatchImportModalLabel h5').text('Overwrite Emails for Batch ' + batchId);
                } else {
                    $('#batchNumberTitle').text('Batch ' + batchId);
                    $('#BatchImportModalLabel h5').text('Import Emails for Batch ' + batchId);
                }
                
                $('#batchNumberInfo').text('Batch ' + batchId);
                $('#expectedCountInfo').text(expected);
                $('#batch_id_input').val(batchId);
                $('#expected_count_input').val(expected);
                
                if (isOverwrite) {
                    $('#BatchImportForm').attr('data-overwrite', 'true');
                } else {
                    $('#BatchImportForm').removeAttr('data-overwrite');
                }
                
                const form=$('#BatchImportForm')[0]; if(form) form.reset();
                $('#batch_id_input').val(batchId);
                $('#expected_count_input').val(expected);
                $('#batch_file').val(''); $('#batchCsvInstructions').removeClass('d-none'); $('#batchFileSelectedInfo').addClass('d-none');
                $('#BatchImportModal').modal('show');
            });
            // Batch import submit
            $(document).on('submit', '#BatchImportForm', function(e){
                e.preventDefault();
                const file=$('#batch_file')[0].files[0]; if(!file||(!file.type.includes('csv')&&!file.name.toLowerCase().endsWith('.csv'))){ Swal.fire({icon:'warning', title:'Invalid or Missing File'}); return false; }
                const fd=new FormData(this); fd.append('order_panel_id', {{ $orderPanel->id }}); fd.append('split_total_inboxes', {{ $splitTotalInboxes }});
                const batchId=$('#batch_id_input').val(); const expected=$('#expected_count_input').val();
                const isOverwrite=$('#BatchImportForm').attr('data-overwrite')==='true';
                if(isOverwrite) fd.append('overwrite', '1');
                
                const confirmTitle = isOverwrite ? 'Overwrite Batch '+batchId+'?' : 'Import Batch '+batchId+'?';
                const confirmText = isOverwrite ? `This will REPLACE all existing ${expected} emails in Batch ${batchId} with new data.` : `This will import exactly ${expected} emails.`;
                const confirmBtn = isOverwrite ? 'Yes, Overwrite!' : 'Yes, Import!';
                const confirmColor = isOverwrite ? '#f39c12' : '#3085d6';
                const icon = isOverwrite ? 'warning' : 'question';
                
                Swal.fire({title:confirmTitle, text:confirmText, icon:icon, showCancelButton:true, confirmButtonColor:confirmColor, cancelButtonColor:'#d33', confirmButtonText:confirmBtn}).then((r)=>{
                    if(!r.isConfirmed) return; Swal.fire({title:'Processing...', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading()});
                    
                    const successTitle = isOverwrite ? 'Overwritten Successfully!' : 'Imported Successfully!';
                    
                    $.ajax({url: $(e.target).attr('action'), method:'POST', data:fd, contentType:false, processData:false, headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}, success:function(resp){ $('#BatchImportModal').modal('hide'); Swal.fire({icon:'success', title:successTitle, text: resp.message || (isOverwrite ? `Batch ${batchId} overwritten.` : `Batch ${batchId} imported.`)}); loadEmailBatches(); }, error:function(xhr, st){ let msg='An error occurred while processing the file.'; if(st==='timeout') msg='File processing timed out.'; else if(xhr.responseJSON&&xhr.responseJSON.message) msg=xhr.responseJSON.message; Swal.fire({icon:'error', title:'Import Failed', text: msg}); }});
                });
                return false;
            });
            // Bulk import submit (next available batches)
            $(document).on('submit', '#BulkImportForm', function(e){
                e.preventDefault(); const fd=new FormData(this); fd.append('order_panel_id', {{ $orderPanel->id }}); fd.append('split_total_inboxes', {{ $splitTotalInboxes }});
                Swal.fire({title:'Processing...', allowOutsideClick:false, showConfirmButton:false, didOpen:()=>Swal.showLoading()});
                $.ajax({url: $(this).attr('action'), method:'POST', data:fd, contentType:false, processData:false, headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')}, success:function(resp){ $('#BulkImportModal').modal('hide'); Swal.fire({icon:'success', title:'Success!', text: resp.message || 'File imported successfully.'}); loadEmailBatches(); }, error:function(xhr, st){ let msg='An error occurred while processing the file.'; if(st==='timeout') msg='File processing timed out.'; else if(xhr.responseJSON&&xhr.responseJSON.message) msg=xhr.responseJSON.message; else if(xhr.responseText&&xhr.responseText.includes('validation')) msg='File validation failed. Please check your CSV format.'; Swal.fire({icon:'error', title:'Import Failed', text: msg}); }});
                return false;
            });
            // Initial load for batches UI
            loadEmailBatches();
            return; // Skip DataTable-related code below
        }
        
        // Function declarations first
        function updateRowCount(table) {
            const rowCount = table.rows().count();
            if (maxEmails > 0) {
                $('#totalRowCount').text(rowCount + "/" + splitTotalInboxes);
            } else {
                $('#totalRowCount').text(rowCount + " (Unlimited)");
            }
            updateProgressBar(table);
        }

        function updateAddButtonState(table) {
            const rowCount = table.rows().count();
            const addButton = $('#addNewBtn');
            
            if (maxEmails > 0 && rowCount >= maxEmails) {
                addButton.prop('disabled', true);
                addButton.attr('title', `Maximum limit of ${maxEmails} emails reached`);
            } else {
                addButton.prop('disabled', false);
                addButton.removeAttr('title');
            }
        }

        function updateProgressBar(table) {
            const rowCount = table.rows().count();
            let percentage = 0;
            
            if (maxEmails > 0) {
                percentage = Math.min((rowCount / maxEmails) * 100, 100);
            } else {
                // For unlimited inboxes, use a different logic
                percentage = Math.min((rowCount / 100) * 100, 100);
            }
            
            const progressBar = $('#emailProgressBar');
            progressBar.css('width', percentage + '%')
                     .attr('aria-valuenow', percentage);
            
            progressBar.removeClass('bg-primary bg-warning bg-danger');
            if (maxEmails > 0) {
                // For limited inboxes
                if (percentage >= 90) {
                    progressBar.addClass('bg-danger');
                } else if (percentage >= 70) {
                    progressBar.addClass('bg-warning');
                } else {
                    progressBar.addClass('bg-primary');
                }
            } else {
                // For unlimited inboxes, always show primary color
                progressBar.addClass('bg-primary');
            }
        }

        // Initialize DataTable with non-blocking configuration
        let emailTable = $('#email-configuration').DataTable({
            responsive: true,
            paging: false,
            searching: false,
            info: false,
            dom: 'frtip',
            autoWidth: false,
            deferRender: true, // Improve performance and reduce blocking
            columnDefs: [
            { width: '25%', targets: 0 }, // First Name column
            { width: '25%', targets: 1 }, // Last Name column
            { width: '25%', targets: 2 }, // Email column
            { width: '25%', targets: 3 }, // Password column
            { visible: false, targets: 4 }  // Hide Action column
            ],
            responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                header: function(row) {
                    return 'Email Details';
                }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll()
            }
            },
            ajax: {
            url: '/contractor/orders/panel/{{ $orderPanel->id }}/emails',
            dataSrc: function(json) {
                return json.data || [];
            }
            },
            columns: [
            { 
                data: 'name',
                render: function(data, type, row) {
                return `<input type="text" class="form-control name" value="${data || ''}" placeholder="Enter first name">`;
                }
            },
            { 
                data: 'last_name',
                render: function(data, type, row) {
                return `<input type="text" class="form-control last_name" value="${data || ''}" placeholder="Enter last name">`;
                }
            },
            { 
                data: 'email',
                render: function(data, type, row) {
                return `<input type="email" class="form-control email" value="${data || ''}" placeholder="Enter email">`;
                }
            },
            { 
                data: 'password',
                render: function(data, type, row) {
                return `<input type="password" class="form-control password" value="${data || ''}" placeholder="Enter password">`;
                }
            },
            {
                data: 'id',
                render: function(data, type, row) {
                return `<button class="bg-transparent p-0 border-0 deleteEmailBtn" data-id="${data || ''}"><i class="fa-regular fa-trash-can text-danger"></i></button>`;
                }
            }
            ],
            drawCallback: function(settings) {
            updateRowCount(this.api());
            updateAddButtonState(this.api());
            
            // Ensure tab remains functional after draw
            $('#other-tab').removeClass('disabled').prop('disabled', false);
            },
            initComplete: function() {
            // Ensure tab is functional after initialization
            $('#other-tab').removeClass('disabled').prop('disabled', false);
            console.log('Email DataTable initialized - tab should be fully functional');
            }
        });

        // Event listeners with tab functionality preservation
        emailTable.on('draw', function() {
            updateRowCount(emailTable);
            updateAddButtonState(emailTable);
            updateProgressBar(emailTable);
            
            // Ensure tab remains functional
            $('#other-tab').removeClass('disabled').prop('disabled', false);
        });

        // Remove processing-related tab blocking
        emailTable.on('processing.dt', function(e, settings, processing) {
            if (processing) {
                console.log('DataTable processing started - keeping tab functional');
            } else {
                console.log('DataTable processing completed - ensuring tab remains functional');
            }
            // Always keep tab functional regardless of processing state
            $('#other-tab').removeClass('disabled').prop('disabled', false);
        });

        // Add new row button click handler
        $('#addNewBtn').click(function() {
            const rowCount = emailTable.rows().count();
            if (maxEmails > 0 && rowCount >= maxEmails) {
                toastr.error(`You can only add up to ${maxEmails} email accounts as per your panel configuration.`);
                return;
            }

            emailTable.row.add({
                name: '',
                last_name: '',
                email: '',
                password: '',
                id: ''
            }).draw(false);
        });

        // Save all button click handler
        $('#saveAllBtn').click(function() {
            const emailsToSave = [];
            let isValid = true;

            $(emailTable.rows().nodes()).each(function() {
                const row = $(this);
                const nameField = row.find('.name');
                const lastNameField = row.find('.last_name');
                const emailField = row.find('.email');
                const passwordField = row.find('.password');

                // Reset validation classes
                nameField.removeClass('is-invalid');
                lastNameField.removeClass('is-invalid');
                emailField.removeClass('is-invalid');
                passwordField.removeClass('is-invalid');

                const name = nameField.val()?.trim();
                const lastName = lastNameField.val()?.trim();
                const email = emailField.val()?.trim();
                const password = passwordField.val()?.trim();

                // Validate fields
                if (!name) {
                    nameField.addClass('is-invalid');
                    isValid = false;
                }
                if (!lastName) {
                    lastNameField.addClass('is-invalid');
                    isValid = false;
                }
                if (!email) {
                    emailField.addClass('is-invalid');
                    isValid = false;
                }
                if (!password) {
                    passwordField.addClass('is-invalid');
                    isValid = false;
                }

                if (name && lastName && email && password) {
                    emailsToSave.push({ name, last_name: lastName, email, password });
                }
            });

            if (!isValid) {
                toastr.error('Please fill in all required fields');
                return;
            }

            // Send as JSON to avoid max_input_vars limit
            $.ajax({
                url: '/contractor/orders/panel/emails',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({
                    order_panel_id: {{ $orderPanel->id }},
                    emails: emailsToSave
                }),
                success: function(response) {
                    toastr.success('Emails saved successfully');
                    emailTable.ajax.reload();
                },
                error: function(xhr) {
                    console.error('Error response:', xhr.responseText);
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Loop through each error and mark fields as invalid
                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            // Handle array fields like emails.0.email
                            if (key.includes('emails.')) {
                                const parts = key.split('.');
                                const index = parseInt(parts[1]); // Get the row index as integer
                                const field = parts[2]; // Get the field name (email, password, etc.)
                                const errorMsg = xhr.responseJSON.errors[key][0]; // Get the first error message
                                
                                // Find the input field at the specific row
                                const row = $(emailTable.rows().nodes()).eq(index);
                                const input = row.find(`.${field}`);
                                
                                if (input.length) {
                                    input.addClass('is-invalid');
                                    
                                    // Add tooltip or display error message
                                    input.attr('title', errorMsg);
                                    
                                    // Optionally create/update feedback element
                                    let feedback = input.next('.invalid-feedback');
                                    if (!feedback.length) {
                                        input.after(`<div class="invalid-feedback">${errorMsg}</div>`);
                                    } else {
                                        feedback.text(errorMsg);
                                    }
                                }
                            }
                        });
                        toastr.error('Please correct the errors in the form');
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Error saving emails');
                    }
                }
            });
        });

        // Delete button click handler
        $('#email-configuration tbody').on('click', '.deleteEmailBtn', function() {
            const button = $(this);
            const row = button.closest('tr');
            const id = button.data('id');
            
            if (id) {
                // Delete existing record
                $.ajax({
                    url: `/contractor/orders/panel/emails/${id}`,
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function() {
                        toastr.success('Email deleted successfully');
                        // Remove just the deleted row instead of reloading the entire table
                        emailTable.row(row).remove().draw(false);
                        updateRowCount(emailTable);
                        updateAddButtonState(emailTable);
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Error deleting email');
                    }
                });
            } else {
                // Remove unsaved row and redraw the table
                emailTable.row(row).remove().draw(false);
                updateRowCount(emailTable);
                updateAddButtonState(emailTable);
            }
        });

        // Bulk import functionality with improved event handling
        $('#addBulkEmail').on('click', function() {
            $('#BulkImportModal').modal('show');
        });

        // Use delegated event handling for better reliability
        $(document).on('submit', '#BulkImportForm', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Bulk import form submitted');
            
            const form = $(this);
            const fileInput = $('#bulk_file');
            const file = fileInput[0].files[0];
            
            // Validate file selection
            if (!file) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a CSV file to import.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            
            // Validate file type
            if (!file.type.includes('csv') && !file.name.toLowerCase().endsWith('.csv')) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid File Type',
                    text: 'Please select a valid CSV file.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }

            const formData = new FormData(this);
            const order_panel_id = {{ $orderPanel->id }};
            const split_total_inboxes = {{ $splitTotalInboxes }};

            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you want to import this CSV file?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Import!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Add additional form data
                    formData.append('order_panel_id', order_panel_id);
                    formData.append('split_total_inboxes', split_total_inboxes);

                    // Show processing dialog
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Please wait while we process your file...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: form.attr('action'),
                        method: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        timeout: 60000, // 60 second timeout for file processing
                        success: function(response) {
                            console.log('Import success:', response);
                            
                            // Close the modal first
                            $('#BulkImportModal').modal('hide');
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'File has been imported successfully.',
                                confirmButtonColor: '#3085d6',
                                timer: 5000,
                                timerProgressBar: true
                            }).then(() => {
                                // Reload the email table
                                if (typeof emailTable !== 'undefined') {
                                    emailTable.ajax.reload();
                                }
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Import error:', {
                                status: status,
                                error: error,
                                response: xhr.responseText
                            });
                            
                            let errorMessage = 'An error occurred while processing the file.';
                            
                            if (status === 'timeout') {
                                errorMessage = 'File processing timed out. Please try with a smaller file.';
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                // Check for specific error patterns
                                if (xhr.responseText.includes('max_input_vars')) {
                                    errorMessage = 'File too large. Please try with a smaller file or contact administrator.';
                                } else if (xhr.responseText.includes('validation')) {
                                    errorMessage = 'File validation failed. Please check your CSV format.';
                                }
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Import Failed',
                                text: errorMessage,
                                confirmButtonColor: '#3085d6',
                                allowOutsideClick: true
                            });
                        }
                    });
                } else {
                    console.log('Import cancelled by user');
                }
            });
            
            return false;
        });

        // Document ready function with validation
        $(document).ready(function() {
            console.log('Document ready, checking form elements...');
            
            // Validate required elements exist
            const requiredElements = [
                '#openStatusModal',
                '#cancel_subscription',
                '#cancelSubscriptionForm',
                '#order_panel_id_to_update'
            ];
            
            requiredElements.forEach(selector => {
                const element = $(selector);
                if (element.length === 0) {
                    console.error(`Required element not found: ${selector}`);
                } else {
                    console.log(`Found element: ${selector}`);
                }
            });
            
            // Check if CSRF token exists
            const csrfToken = $('meta[name="csrf-token"]').attr('content');
            if (!csrfToken) {
                console.error('CSRF token not found in meta tag');
            } else {
                console.log('CSRF token found');
            }
            
            console.log('Form validation complete');
        });

        // Enhanced modal event handlers
        $('#BulkImportModal').on('show.bs.modal', function() {
            console.log('BulkImportModal opening, initializing form state');
            
            // Reset the form completely
            const form = $('#BulkImportForm')[0];
            if (form) {
                form.reset();
            }
            
            // Clear file input
            $('#bulk_file').val('').trigger('change');
            
            // Clear customized note and reset character count
            $('#customized_note').val('');
            $('#note-char-count').text('0').css('color', '#6c757d');
            
            // Remove all validation states
            $('#BulkImportForm .form-control').removeClass('is-invalid is-valid');
            $('#BulkImportForm .invalid-feedback').remove();
            
            // Reset UI messages
            $('#csvInstructions').removeClass('d-none');
            $('#fileSelectedInfo').addClass('d-none');
            
            // Ensure submit button is enabled
            $('#BulkImportForm button[type="submit"]').prop('disabled', false);
            
            console.log('BulkImportModal initialization complete');
        });

        $('#BulkImportModal').on('shown.bs.modal', function() {
            // Focus on file input when modal is fully shown
            $('#bulk_file').focus();
        });

        $('#BulkImportModal').on('hidden.bs.modal', function() {
            console.log('BulkImportModal closed, performing cleanup');
            
            // Complete form reset
            const form = $('#BulkImportForm')[0];
            if (form) {
                form.reset();
            }
            
            // Clear file input and trigger change event
            $('#bulk_file').val('').trigger('change');
            
            // Clear customized note and reset character count
            $('#customized_note').val('');
            $('#note-char-count').text('0').css('color', '#6c757d');
            
            // Remove all validation and styling
            $('#BulkImportForm .form-control').removeClass('is-invalid is-valid');
            $('#BulkImportForm .invalid-feedback').remove();
            $('#BulkImportForm .form-control').removeAttr('style');
            
            // Reset UI messages
            $('#csvInstructions').removeClass('d-none');
            $('#fileSelectedInfo').addClass('d-none');
            
            // Close any lingering SweetAlert dialogs
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            
            // Re-enable submit button
            $('#BulkImportForm button[type="submit"]').prop('disabled', false);
            
            console.log('BulkImportModal cleanup complete');
        });

        // Enhanced file selection handler with better event delegation
        $(document).on('change', '#bulk_file', function() {
            console.log('File input changed');
            
            const fileInput = $(this);
            const file = fileInput[0].files[0];
            const csvInstructions = $('#csvInstructions');
            const fileSelectedInfo = $('#fileSelectedInfo');
            const selectedFileName = $('#selectedFileName');
            
            // Clear previous validation states
            fileInput.removeClass('is-invalid is-valid');
            fileInput.next('.invalid-feedback').remove();
            
            if (file) {
                console.log('File selected:', file.name, 'Type:', file.type);
                
                // Check if it's a CSV file
                if (file.type === 'text/csv' || file.type === 'application/csv' || file.name.toLowerCase().endsWith('.csv')) {
                    // Valid CSV file selected
                    csvInstructions.addClass('d-none');
                    fileSelectedInfo.removeClass('d-none');
                    selectedFileName.text(`File selected: ${file.name}`);
                    fileInput.addClass('is-valid');
                    
                    console.log('Valid CSV file selected');
                } else {
                    // Invalid file type
                    csvInstructions.removeClass('d-none');
                    fileSelectedInfo.addClass('d-none');
                    fileInput.addClass('is-invalid');
                    
                    // Add error feedback
                    if (!fileInput.next('.invalid-feedback').length) {
                        fileInput.after('<div class="invalid-feedback">Please select a valid CSV file.</div>');
                    }
                    
                    console.log('Invalid file type selected');
                }
            } else {
                // No file selected
                csvInstructions.removeClass('d-none');
                fileSelectedInfo.addClass('d-none');
                fileInput.removeClass('is-invalid is-valid');
                
                console.log('No file selected');
            }
        });

        // Character count for customized note
        $(document).on('input', '#customized_note', function() {
            const currentLength = $(this).val().length;
            const maxLength = 1000;
            $('#note-char-count').text(currentLength);
            
            // Change color based on usage
            const charCountElement = $('#note-char-count');
            if (currentLength > maxLength * 0.9) {
                charCountElement.css('color', '#dc3545'); // Red when near limit
            } else if (currentLength > maxLength * 0.7) {
                charCountElement.css('color', '#ffc107'); // Yellow when getting close
            } else {
                charCountElement.css('color', '#6c757d'); // Default gray
            }
        });

        // Ensure tabs remain functional during email loading
        $(document).ready(function() {
            // Remove any potential disabled state from tabs
            $('#other-tab').removeClass('disabled').prop('disabled', false);
            
            // Ensure tab switching works during DataTable loading
            $('#other-tab').on('click', function(e) {
                // Don't prevent tab switching even during loading
                console.log('Emails tab clicked - ensuring it remains functional');
                
                // Force enable the tab if it was disabled
                $(this).removeClass('disabled').prop('disabled', false);
                
                // Ensure the tab content is shown
                setTimeout(() => {
                    $('#other-tab-pane').addClass('show active');
                    $('#configuration-tab-pane').removeClass('show active');
                }, 10);
            });
            
            // Remove any loading-related tab disabling
            $(document).on('processing.dt', function(e, settings, processing) {
                // Keep tabs functional during processing
                $('#other-tab').removeClass('disabled').prop('disabled', false);
            });
            
            // Ensure tab remains enabled after DataTable operations
            if (typeof emailTable !== 'undefined') {
                emailTable.on('draw.dt', function() {
                    $('#other-tab').removeClass('disabled').prop('disabled', false);
                });
                
                emailTable.on('xhr.dt', function() {
                    $('#other-tab').removeClass('disabled').prop('disabled', false);
                });
            }
        });
    });
</script>
@endpush
