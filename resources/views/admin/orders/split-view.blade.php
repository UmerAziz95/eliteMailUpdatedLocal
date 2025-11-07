@extends('admin.layouts.app')

@section('title', 'Orders')

{{-- 
    OPTIMIZED VERSION: This view now uses the $orderPanel variable for more efficient data access
    instead of foreach loops through $order->orderPanels. This improves performance and code clarity.
--}}

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
            <h5 class="mb-3">Order #{{ $orderPanel->order->id ?? 'N/A' }} - Split Id #{{ $orderPanel->id }}</h5>
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
        <li class="nav-item" role="presentation">
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
                <!-- <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Profile Picture URL</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->profile_picture_link ?? 'N/A' }}</span>
                </div> -->
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
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Customized Note</span>
                    <span class="small">{{ $orderPanel->customized_note ?? 'No customized notes added yet.' }}</span>
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
                       <small class="opacity-75">Additional information provided</small>
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
                    Email Accounts for Split Id #{{ $orderPanel->id }}
                </h6>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div>
                            <!-- @if($orderPanel->order->status_manage_by_admin === 'pending' || $orderPanel->order->status_manage_by_admin === 'in-progress') -->
                                
                            <!-- @endif -->
                            <button id="addBulkEmail" class="btn btn-primary me-2" data-bs-toggle="modal"
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
                                <a href="{{ route('admin.order.panel.email.downloadCsv', ['orderPanelId' => $orderPanel->id]) }}" 
                                   class="btn btn-outline-success me-2 btn-sm" 
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
                    <div class="email-stats d-flex align-items-center gap-3 bg- rounded p-2">
                        <div class="badge rounded-circle bg-primary p-2">
                            <i class="fa-solid fa-envelope text-white"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Email Accounts</h6>
                            <div class="d-flex align-items-center gap-2">
                                <span id="totalRowCount" class="fw-bold">0</span>
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
                    <!-- Batch-wise email display -->
                    <div id="email-batches-container">
                        <!-- Batches will be loaded dynamically via JavaScript -->
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
                    Bulk Import Emails for Split Id #{{ $orderPanel->id }}
                </h6>
                <div class="row text-muted" id="csvInstructions">
                    <p class="text-danger">Only .csv files are accepted.</p>
                    <p class="text-danger">The CSV file must include the following headers: <strong>First Name</strong>,
                        <strong>Last Name</strong>, <strong>Email address</strong>, and <strong>Password</strong>.
                    </p>
                    <p><a href="{{url('/').'/assets/samples/emails.csv'}}"><strong class="text-primary">Download
                                Sample File</strong></a></p>
                </div>
                
                <div class="alert alert-success d-none" id="fileSelectedInfo">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <span id="selectedFileName">File selected successfully</span>
                </div>
                
                <form id="BulkImportForm" action="{{ route('admin.order.panel.email.bulkImport') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_panel_id" value="{{ $orderPanel->id }}">
                    <div class="mb-3">
                        <label for="bulk_file" class="form-label">Select CSV *</label>
                        <input type="file" class="form-control" id="bulk_file" name="bulk_file" accept=".csv"
                            required>
                    </div>

                    <div class="mb-3">
                        <label for="customized_note" class="form-label">Customized Note</label>
                        <textarea class="form-control" id="customized_note" name="customized_note" rows="3" 
                                placeholder="Enter any special instructions or notes for this import..." 
                                value="{{ $orderPanel->customized_note ?? '' }}">{{ $orderPanel->customized_note ?? '' }}</textarea>
                        <small class="form-text text-muted">Optional: Add any special instructions or notes related to this email import.</small>
                    </div>

                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button"
                            class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit"
                            class="border border-success py-1 px-3 w-100 bg-transparent text-success rounded-2">Yes,
                            Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Batch-specific Import Modal -->
<div class="modal fade" id="BatchImportModal" tabindex="-1" aria-labelledby="BatchImportModalLabel"
    aria-hidden="true">
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
                    <p class="text-danger">The CSV file must include the following headers: <strong>First Name</strong>,
                        <strong>Last Name</strong>, <strong>Email address</strong>, and <strong>Password</strong>.
                    </p>
                    <p><a href="{{url('/').'/assets/samples/emails.csv'}}"><strong class="text-primary">Download
                                Sample File</strong></a></p>
                </div>
                
                <div class="alert alert-success d-none" id="batchFileSelectedInfo">
                    <i class="fa-solid fa-check-circle me-2"></i>
                    <span id="batchSelectedFileName">File selected successfully</span>
                </div>
                
                <form id="BatchImportForm" action="{{ route('admin.order.panel.email.bulkImport') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="order_panel_id" value="{{ $orderPanel->id }}">
                    <input type="hidden" name="batch_id" id="batch_id_input" value="">
                    <input type="hidden" name="expected_count" id="expected_count_input" value="">
                    
                    <div class="mb-3">
                        <label for="batch_file" class="form-label">Select CSV *</label>
                        <input type="file" class="form-control" id="batch_file" name="bulk_file" accept=".csv"
                            required>
                    </div>

                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button"
                            class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit"
                            class="border border-success py-1 px-3 w-100 bg-transparent text-success rounded-2">
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
<div class="modal fade" id="order-split-status-update" tabindex="-1" aria-labelledby="cancel_subscriptionLabel"
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
                <form id="order_split_status_modal" action="{{ route('admin.order.panel.status.process') }}" method="POST">
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
        $('#order-split-status-update').modal('show');
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
    $('#order_split_status_modal').on('submit', function(e) {
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
                $('#order-split-status-update').modal('hide');
                
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
        
        // Function to update the total email count display
        function updateTotalCount(totalEmails) {
            if (maxEmails > 0) {
                $('#totalRowCount').text(totalEmails + "/" + maxEmails);
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
            progressBar.css('width', percentage + '%')
                     .attr('aria-valuenow', percentage);
            
            progressBar.removeClass('bg-primary bg-warning bg-danger');
            if (maxEmails > 0) {
                if (percentage >= 90) {
                    progressBar.addClass('bg-danger');
                } else if (percentage >= 70) {
                    progressBar.addClass('bg-warning');
                } else {
                    progressBar.addClass('bg-primary');
                }
            } else {
                progressBar.addClass('bg-primary');
            }
        }
        
        // Function to load emails by batch
        function loadEmailBatches() {
            $.ajax({
                url: '/admin/orders/panel/{{ $orderPanel->id }}/emails/batches',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        displayEmailBatches(response);
                        updateTotalCount(response.total_emails);
                    } else {
                        showEmptyState();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading email batches:', xhr);
                    showEmptyState('Error loading email batches. Please refresh the page.');
                }
            });
        }

        // Function to display email batches
        function displayEmailBatches(response) {
            const container = $('#email-batches-container');
            container.empty();

            if (!response.batches || response.batches.length === 0) {
                showEmptyState();
                return;
            }

            // Create accordion for batches
            const accordionId = 'emailBatchesAccordion';
            let accordionHtml = `<div class="accordion" id="${accordionId}">`;

            response.batches.forEach((batch, index) => {
                const batchNumber = batch.batch_number;
                const emailCount = batch.email_count;
                const expectedCount = batch.expected_count || 200;
                const emails = batch.emails || [];
                const isFirstBatch = index === 0;
                
                // Determine badge color based on email count
                let badgeClass = 'bg-secondary';
                if (emailCount === 0) {
                    badgeClass = 'bg-danger';
                } else if (emailCount < expectedCount) {
                    badgeClass = 'bg-warning';
                } else {
                    badgeClass = 'bg-success';
                }

                accordionHtml += `
                    <div class="mb-3 border rounded">
                        <h2 class="accordion-header" id="heading${batchNumber}">
                            <button class="accordion-button ${isFirstBatch ? '' : 'collapsed'}" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse${batchNumber}" 
                                    aria-expanded="${isFirstBatch ? 'true' : 'false'}" aria-controls="collapse${batchNumber}">
                                <div class="d-flex align-items-center justify-content-between w-100 pe-3">
                                    <div>
                                        <span class="fw-bold">
                                            <i class="fa-solid fa-layer-group me-2"></i>
                                            Batch ${batchNumber}
                                        </span>
                                        <small class="text-muted ms-2">(${emailCount === 0 ? 'Empty' : emailCount} / ${expectedCount} emails)</small>
                                    </div>
                                    <span class="badge ${badgeClass} rounded-pill">
                                        ${emailCount === 0 ? 'Empty' : emailCount + ' emails'}
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse${batchNumber}" class="accordion-collapse collapse ${isFirstBatch ? 'show' : ''}" 
                             aria-labelledby="heading${batchNumber}" data-bs-parent="#${accordionId}">
                            <div class="accordion-body">
                `;

                if (emails.length > 0) {
                    // Create table for this batch
                    accordionHtml += `
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 25%">Name</th>
                                        <th style="width: 25%">Last Name</th>
                                        <th style="width: 30%">Email</th>
                                        <th style="width: 15%">Password</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    emails.forEach((email, emailIndex) => {
                        accordionHtml += `
                            <tr>
                                <td>${emailIndex + 1}</td>
                                <td>${escapeHtml(email.name || '')}</td>
                                <td>${escapeHtml(email.last_name || '')}</td>
                                <td><small>${escapeHtml(email.email || '')}</small></td>
                                <td><small class="font-monospace">${escapeHtml(email.password || '')}</small></td>
                            </tr>
                        `;
                    });

                    accordionHtml += `
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    // Empty batch - show import button
                    accordionHtml += `
                        <div class="text-center py-4">
                            <div class="alert alert-info mb-3">
                                <i class="fa-solid fa-info-circle me-2"></i>
                                <strong>Batch ${batchNumber} is empty.</strong> Expected: ${expectedCount} emails.
                            </div>
                            <button class="btn btn-primary import-batch-btn" data-batch-id="${batchNumber}" data-expected-count="${expectedCount}">
                                <i class="fa-solid fa-upload me-2"></i>
                                Import Emails for Batch ${batchNumber}
                            </button>
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
            
            // Add summary at the top
            const summaryHtml = `
                <div class="alert alert-primary mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa-solid fa-chart-bar me-2"></i>
                            <strong>Summary:</strong> ${response.total_batches} batches | ${response.total_emails} total emails
                        </div>
                        <div>
                            <span class="badge bg-primary">Space Assigned: ${response.space_assigned}</span>
                        </div>
                    </div>
                </div>
            `;

            container.html(summaryHtml + accordionHtml);
        }

        // Function to show empty state
        function showEmptyState(message = 'No email batches found. Please import emails using the CSV upload feature.') {
            const container = $('#email-batches-container');
            container.html(`
                <div class="text-center py-5">
                    <i class="fa-solid fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">${message}</h5>
                    <p class="text-muted">Click "Emails Customization" button above to import emails.</p>
                </div>
            `);
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.toString().replace(/[&<>"']/g, m => map[m]) : '';
        }

        // Load email batches on page load
        loadEmailBatches();

        // Handle batch-specific import button clicks
        $(document).on('click', '.import-batch-btn', function() {
            const batchId = $(this).data('batch-id');
            const expectedCount = $(this).data('expected-count');
            
            // Set modal content
            $('#batchNumberTitle').text('Batch ' + batchId);
            $('#batchNumberInfo').text('Batch ' + batchId);
            $('#expectedCountInfo').text(expectedCount);
            $('#batch_id_input').val(batchId);
            $('#expected_count_input').val(expectedCount);
            
            // Reset form
            $('#BatchImportForm')[0].reset();
            $('#batch_id_input').val(batchId);
            $('#expected_count_input').val(expectedCount);
            $('#batch_file').val('');
            $('#batchCsvInstructions').removeClass('d-none');
            $('#batchFileSelectedInfo').addClass('d-none');
            
            // Show modal
            $('#BatchImportModal').modal('show');
        });

        // Batch file selection handler
        $(document).on('change', '#batch_file', function() {
            const fileInput = $(this);
            const file = fileInput[0].files[0];
            const csvInstructions = $('#batchCsvInstructions');
            const fileSelectedInfo = $('#batchFileSelectedInfo');
            const selectedFileName = $('#batchSelectedFileName');
            
            fileInput.removeClass('is-invalid is-valid');
            fileInput.next('.invalid-feedback').remove();
            
            if (file) {
                if (file.type === 'text/csv' || file.type === 'application/csv' || file.name.toLowerCase().endsWith('.csv')) {
                    csvInstructions.addClass('d-none');
                    fileSelectedInfo.removeClass('d-none');
                    selectedFileName.text(`File selected: ${file.name}`);
                    fileInput.addClass('is-valid');
                } else {
                    csvInstructions.removeClass('d-none');
                    fileSelectedInfo.addClass('d-none');
                    fileInput.addClass('is-invalid');
                    if (!fileInput.next('.invalid-feedback').length) {
                        fileInput.after('<div class="invalid-feedback">Please select a valid CSV file.</div>');
                    }
                }
            } else {
                csvInstructions.removeClass('d-none');
                fileSelectedInfo.addClass('d-none');
                fileInput.removeClass('is-invalid is-valid');
            }
        });

        // Batch Import Form submission
        $(document).on('submit', '#BatchImportForm', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const form = $(this);
            const fileInput = $('#batch_file');
            const file = fileInput[0].files[0];
            const batchId = $('#batch_id_input').val();
            const expectedCount = $('#expected_count_input').val();
            
            if (!file) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No File Selected',
                    text: 'Please select a CSV file to import.',
                    confirmButtonColor: '#3085d6'
                });
                return false;
            }
            
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
            formData.append('order_panel_id', {{ $orderPanel->id }});
            formData.append('split_total_inboxes', {{ $splitTotalInboxes }});

            Swal.fire({
                title: 'Import Batch ' + batchId + '?',
                text: `This will import up to ${expectedCount} emails for Batch ${batchId}.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Import!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
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
                        timeout: 60000,
                        success: function(response) {
                            $('#BatchImportModal').modal('hide');
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || `Batch ${batchId} has been imported successfully.`,
                                confirmButtonColor: '#3085d6',
                                timer: 5000,
                                timerProgressBar: true
                            }).then(() => {
                                loadEmailBatches();
                            });
                        },
                        error: function(xhr, status, error) {
                            let errorMessage = 'An error occurred while processing the file.';
                            
                            if (status === 'timeout') {
                                errorMessage = 'File processing timed out. Please try with a smaller file.';
                            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Import Failed',
                                text: errorMessage,
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    });
                }
            });
            
            return false;
        });

        // Bulk import functionality
        $('#addBulkEmail').on('click', function() {
            $('#BulkImportModal').modal('show');
        });

        // Enhanced file selection handler
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

        // Enhanced modal management
        $('#BulkImportModal').on('show.bs.modal', function() {
            console.log('BulkImportModal opening, initializing form state');
            
            // Reset the form completely
            const form = $('#BulkImportForm')[0];
            if (form) {
                form.reset();
            }
            
            // Set the current customized note value
            $('#customized_note').val('{{ addslashes($orderPanel->customized_note ?? '') }}');
            
            // Clear file input
            $('#bulk_file').val('').trigger('change');
            
            // Remove all validation states
            $('#BulkImportForm .form-control').removeClass('is-invalid is-valid');
            $('#BulkImportForm .invalid-feedback').remove();
            
            // Reset UI messages
            $('#csvInstructions').removeClass('d-none');
            $('#fileSelectedInfo').addClass('d-none');
            
            // Ensure submit button is enabled
            $('#BulkImportForm button[type="submit"]').prop('disabled', false);
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
            
            // Remove all validation and styling
            $('#BulkImportForm .form-control').removeClass('is-invalid is-valid');
            $('#BulkImportForm .invalid-feedback').remove();
            
            // Reset UI messages
            $('#csvInstructions').removeClass('d-none');
            $('#fileSelectedInfo').addClass('d-none');
            
            // Close any lingering SweetAlert dialogs
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
        });

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
                                // Reload the email batches instead of the old table
                                loadEmailBatches();
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
    });
</script>
@endpush
