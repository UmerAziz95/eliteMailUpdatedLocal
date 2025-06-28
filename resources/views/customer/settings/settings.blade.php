@extends('customer.layouts.app')

@section('title', 'security')

@push('styles')
<style>
    .bg-label-secondary {
        background-color: #ffffff28;
        color: var(--extra-light);
        font-weight: 100;
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

    .form-check-input {
        height: 20px !important;
        width: 40px !important;
        border-radius: 50px !important;
        margin-right: 6px !important
    }

    /* Change switch color to blue when checked */
    .form-check-input:checked {
        background-color: var(--second-primary) !important;
        /* Bootstrap primary blue */
        border-color: #0d6efd !important;
    }

    /* Optional: Adjust thumb (dot) color when checked */
    .form-check-input:checked::before {
        background-color: white;
    }


    .timeline .timeline-item {
        position: relative;
        border: 0;
        border-inline-start: 1px solid var(--extra-light);
        padding-inline-start: 1.4rem;
    }

    .timeline .timeline-item .timeline-point {
        position: absolute;
        z-index: 10;
        display: block;
        background-color: var(--second-primary);
        block-size: .75rem;
        box-shadow: 0 0 0 10px var(--secondary-color);
        inline-size: .75rem;
        inset-block-start: 0;
        inset-inline-start: -0.38rem;
        outline: 3px solid #3a3b64;
        border-radius: 50%;
        opacity: 1
    }

    .bg-label-primary,
    .bg-label-warning,
    .bg-label-success {
        padding: .5rem .6rem !important;
        font-size: 10px
    }

    /* .timeline .timeline-item.timeline-item-transparent .timeline-event {
                                                                                                                    background-color: rgba(0, 0, 0, 0);
                                                                                                                    inset-block-start: -0.9rem;
                                                                                                                    padding-inline: 0;
                                                                                                                } */

    /* .timeline .timeline-item .timeline-event {
                                                                                                                    position: relative;
                                                                                                                    border-radius: 50%;
                                                                                                                    background-color: var(--secondary-color);
                                                                                                                    inline-size: 100%;
                                                                                                                    min-block-size: 4rem;
                                                                                                                    padding-block: .5rem .3375rem;
                                                                                                                    padding-inline: 0rem;
                                                                                                                } */

    .bg-lighter {
        background-color: #ffffff1d;
        padding: .3rem;
        border-radius: 4px;
        color: var(--extra-light)
    }

    .timeline:not(.timeline-center) {
        padding-inline-start: .5rem;
    }

    .cropper-container {
        width: 100%;
        max-width: 500px;
        margin: 0 auto;
    }

    #cropperModal .modal-content {
        background-color: var(--secondary-color);
        color: var(--extra-light);
    }

    .cropper-controls {
        margin-top: 15px;
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .cropper-controls button {
        padding: 5px 10px;
        border-radius: 4px;
        background: var(--second-primary);
        color: white;
        border: none;
        cursor: pointer;
    }

    .zoom-controls {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .zoom-slider {
        width: 150px;
    }

    .credit-card {
        box-shadow: rgb(0 0 0) 0px 2px 2px, rgb(0 0 0 / 72%) 0px 7px 13px -2px, #000000 0px -3px 0px inset;
        width: 33rem;
        overflow: hidden
    }

    .credit-card::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        background-image: url('https://img.freepik.com/free-vector/realistic-glossy-black-background_23-2150040274.jpg?ga=GA1.1.345486587.1750935187&semt=ais_hybrid&w=740');
        background-position: center;
        opacity: .4;
        background-size: cover;
        height: 100%;
        width: 100%;
    }
</style>
@endpush

@section('content')
<div class="row py-4">
    <!-- User Sidebar -->
    <div class="col-xl-4 col-lg-5 order-1 order-md-0">
        <!-- User Card -->
        <div class="card mb-4">
            <div class="card-body pt-12">
                <div class="user-avatar-section">
                    <div class="d-flex align-items-center flex-column">
                        <div class="position-relative">
                            <div>
                                <img class="img-fluid rounded mb-4" id="profile-image"
                                    src="{{ Auth::user()->profile_image ? asset('storage/profile_images/' . Auth::user()->profile_image) : 'https://cdn-icons-png.flaticon.com/128/3237/3237472.png' }}"
                                    height="120" width="120" alt="User avatar" style="cursor: pointer;"
                                    onclick="$('#profile-image-input').click();">
                            </div>

                            <div class="position-absolute bottom-0 end-0">
                                <label for="profile-image-input" class="btn btn-sm btn-primary rounded-circle">
                                    <i class="ti ti-pencil"></i>
                                </label>
                            </div>
                        </div>
                        <input type="file" id="profile-image-input" style="display: none;" accept="image/*">
                        <div class="user-info text-center">
                            <h5 class="text-capitalize">{{ Auth::user()->name }}</h5>
                            <span class="badge bg-label-secondary">{{ Auth::user()->role->name }}</span>
                        </div>
                    </div>
                </div>

                <!-- <div class="d-flex justify-content-around flex-wrap my-5 gap-0 gap-md-3 gap-lg-4">
                            <div class="d-flex align-items-center me-5 gap-4">
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-primary rounded p-2">
                                        <i class="ti ti-checkbox fs-4 theme-text"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">1.23k</h5>
                                    <span class="small opacity-50">Task Done</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-4">
                                <div class="avatar">
                                    <div class="avatar-initial bg-label-primary rounded p-2">
                                        <i class="ti ti-checkbox fs-4 theme-text"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-0">568</h5>
                                    <span class="small opacity-50">Project Done</span>
                                </div>
                            </div>
                        </div> -->

                <h5 class="pb-4 border-bottom mb-4">Details</h5>
                <div class="info-container">
                    <ul class="list-unstyled mb-6">
                        <li class="mb-2">
                            <span class="h6">Username:</span>
                            <span class="opacity-50 small fw-light">{{ Auth::user()->name }}</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">Email:</span>
                            <span class="opacity-50 small fw-light">{{ Auth::user()->email }}</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">Status:</span>
                            <span class="opacity-50 small fw-light">{{ Auth::user()->status == '1' ? 'Active' :
                                'In-Active' }}</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">Role:</span>
                            <span class="opacity-50 small fw-light">{{ Auth::user()->role->name }}</span>
                        </li>
                        {{-- <li class="mb-2">
                            <span class="h6">Tax id:</span>
                            <span class="opacity-50 small fw-light">Tax-8965</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">plans:</span>
                            <span class="opacity-50 small fw-light">(123) 456-7890</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">Languages:</span>
                            <span class="opacity-50 small fw-light">French</span>
                        </li>
                        <li class="mb-2">
                            <span class="h6">Country:</span>
                            <span class="opacity-50 small fw-light">England</span>
                        </li> --}}
                    </ul>

                    <div class="d-flex justify-content-center gap-2">
                        <button class="m-btn rounded-2 py-2 px-4 border-0" data-bs-target="#edit"
                            data-bs-toggle="modal">Edit</button>
                        <!-- <a href="javascript:;"
                                    class="cancel-btn py-2 px-4 rounded-2 border-0 text-decoration-none opacity-75">Suspend</a> -->
                    </div>
                </div>
            </div>
        </div>
        <!-- /User Card -->

        <!-- Plan Card -->
        <div class="card mb-4 rounded" style="border: 2px solid var(--second-primary)">
            <div class="card-body">
                @php
                $latestOrder = Auth::user()
                ->orders()
                ->where('status_manage_by_admin', '!=', 'cancelled')
                ->with(['plan', 'reorderInfo', 'subscription'])
                ->latest()
                ->first();

                // Only manipulate the date if $latestOrder and its subscription exist
                if (
                $latestOrder &&
                $latestOrder->subscription &&
                $latestOrder->subscription->next_billing_date
                ) {
                $latestOrder->subscription->next_billing_date = \Carbon\Carbon::parse(
                $latestOrder->subscription->next_billing_date,
                )->subDay();
                }
                @endphp
                <div class="d-flex justify-content-between align-items-center">
                    @if ($latestOrder && $latestOrder->plan)
                    <span class="badge bg-label-primary">{{ $latestOrder->plan->name }}</span>
                    <div class="d-flex justify-content-center">
                        <sub class="h5 pricing-currency mb-auto mt-1 theme-text">$</sub>
                        <h1 class="mb-0 theme-text">{{ number_format($latestOrder->plan->price, 2) }}</h1>
                        <small class="pricing-duration mt-auto mb-2 fw-normal">/ {{ $latestOrder->plan->duration }}
                            Per Inboxes</small>
                    </div>
                    @else
                    <span class="badge bg-label-secondary">No Active Plan</span>
                    @endif
                </div>

                @if ($latestOrder && $latestOrder->reorderInfo && $latestOrder->reorderInfo->count() > 0)
                <ul class="list-unstyled g-2 my-4">
                    <li class="mb-2 d-flex align-items-center">
                        <i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i>
                        <small>Total Inboxes: {{ $latestOrder->reorderInfo->first()->total_inboxes }}</small>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i>
                        <small>Inboxes per Domain:
                            {{ $latestOrder->reorderInfo->first()->inboxes_per_domain }}</small>
                    </li>
                    <li class="mb-2 d-flex align-items-center">
                        <i class="icon-base ti tabler-circle-filled icon-10px text-secondary me-2"></i>
                        <small>Status: <small
                                class="badge rounded-1 py-1 bg-{{ $latestOrder->status_manage_by_admin == 'completed' ? 'success' : ($latestOrder->status_manage_by_admin == 'pending' ? 'warning' : 'info') }}">
                                {{ ucfirst($latestOrder->status_manage_by_admin) }}
                            </small></small>
                    </li>
                </ul>
                @endif

                @if ($latestOrder && $latestOrder->subscription)
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="mb-0">Subscription Period: </span>
                    <small class="opacity-75 mb-0">
                        {{ \Carbon\Carbon::parse($latestOrder->subscription->last_billing_date)->format('M d, Y') }}
                        -
                        {{ $latestOrder->subscription->next_billing_date ?
                        \Carbon\Carbon::parse($latestOrder->subscription->next_billing_date)->subDay()->format('M d, Y')
                        : 'N/A' }}
                    </small>
                </div>
                <!-- remaining days and next billing date check deeply and analyzed -->
                <div class="progress mb-1 bg-label-primary rounded-1 p-0" style="height: 4px; padding: 0 !important;">
                    @php
                    $startDate = \Carbon\Carbon::parse($latestOrder->subscription->last_billing_date);
                    $endDate = $latestOrder->subscription->next_billing_date
                    ? \Carbon\Carbon::parse($latestOrder->subscription->next_billing_date)->subDay()
                    : now()->subDay();
                    $totalDays = $startDate->diffInDays($endDate);
                    $now = now();

                    // Calculate progress and days remaining correctly
                    if ($now->lt($startDate)) {
                    // Current date is before subscription period
                    $daysRemaining = $totalDays;
                    $progress = 0;
                    } elseif ($now->gt($endDate)) {
                    // Current date is after subscription period
                    $daysRemaining = 0;
                    $progress = 100;
                    } else {
                    // Current date is within subscription period
                    // Use diffInDays with false parameter to get positive number when endDate is in the future
                    $daysRemaining = $now->diffInDays($endDate, false);
                    $daysElapsed = $startDate->diffInDays($now);
                    $progress =
                    $totalDays > 0 ? min(100, max(0, ($daysElapsed / $totalDays) * 100)) : 0;
                    }
                    @endphp
                    <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%;"
                        aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="opacity-50">{{ $daysRemaining }} days remaining</small>
                @endif

                <div class="d-grid w-100 mt-4">
                    <a href="{{ route('customer.pricing') }}"
                        class="m-btn border-0 py-2 px-4 rounded-2 text-center">Upgrade Plan</a>
                </div>
            </div>
        </div>
        <!-- /Plan Card -->
    </div>
    <!--/ User Sidebar -->

    <div class="col-xl-8 col-lg-7">

        <ul class="nav nav-tabs border-0" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="notify-tab" data-bs-toggle="tab" data-bs-target="#notify-tab-pane"
                    type="button" role="tab" aria-controls="notify-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-bell"></i> Notifications</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-tab-pane"
                    type="button" role="tab" aria-controls="security-tab-pane" aria-selected="false"><i
                        class="fa-solid fa-unlock"></i> Security</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-tab-pane"
                    type="button" role="tab" aria-controls="activity-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-bell"></i> Activity</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="plans-tab" data-bs-toggle="tab" data-bs-target="#plans-tab-pane"
                    type="button" role="tab" aria-controls="plans-tab-pane" aria-selected="false"><i
                        class="fa-solid fa-file-invoice"></i> Plans</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing-tab-pane"
                    type="button" role="tab" aria-controls="billing-tab-pane" aria-selected="false"><i
                        class="fa-solid fa-file-invoice"></i> Billing</button>
            </li>

        </ul>

        <div class="tab-content mt-4" id="myTabContent">

            <div class="tab-pane fade" id="security-tab-pane" role="tabpanel" aria-labelledby="security-tab"
                tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Change Password</h5>
                    <div class="card-body">
                        <form id="formChangePassword">
                            <div class="alert text-warning alert-dismissible"
                                style="background-color: rgba(255, 166, 0, 0.189)" role="alert">
                                <h5 class="alert-heading mb-1">Ensure that these requirements are met</h5>
                                <span>Minimum 8 characters long, uppercase &amp; symbol</span>
                                <button type="button" class="btn-close text-warning " data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                            {{-- Old Password --}}

                            <div class="row gx-6">
                                <div class="mb-4 col-12 col-sm-6 form-password-toggle">
                                    <label class="form-label" for="oldPassword">Old Password</label>
                                    <div class="input-group input-group-merge has-validation">
                                        <input class="form-control" type="password" id="oldPassword" name="oldPassword"
                                            placeholder="············">
                                    </div>
                                    <div
                                        class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                    </div>
                                </div>

                                <div class="mb-4 col-12 col-sm-6 form-password-toggle">
                                    <label class="form-label" for="newPassword">New Password</label>
                                    <div class="input-group input-group-merge has-validation">
                                        <input class="form-control" type="password" id="newPassword" name="newPassword"
                                            placeholder="············">
                                    </div>
                                    <div
                                        class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                    </div>
                                </div>

                                <div class="mb-4 col-12 col-sm-6 form-password-toggle">
                                    <label class="form-label" for="confirmPassword">Confirm New Password</label>
                                    <div class="input-group input-group-merge has-validation">
                                        <input class="form-control" type="password" name="confirmPassword"
                                            id="confirmPassword" placeholder="············">
                                    </div>
                                    <div
                                        class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                    </div>
                                </div>
                                <div>
                                    <button type="submit" class="m-btn py-2 px-4 rounded-2 border-0">Change
                                        Password</button>
                                </div>
                            </div>

                            <input type="hidden">
                        </form>
                    </div>
                </div>

                <!-- <div class="card mb-4 p-3">
                            <div class="card-header">
                                <h5 class="mb-0">Two-steps verification</h5>
                                <span class="card-subtitle mt-0">Keep your account secure with authentication step.</span>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-1">SMS</h6>
                                <div class="mb-4">
                                    <div class="d-flex w-100 action-icons">
                                        <input id="defaultInput" class="form-control me-4" type="text"
                                            placeholder="+1(968) 945-8832">
                                        <a href="javascript:;" class="btn btn-icon btn-text-secondary waves-effect"
                                            data-bs-target="#enableOTP" data-bs-toggle="modal"><i
                                                class="icon-base ti tabler-edit icon-22px"></i></a>
                                        <a href="javascript:;" class="btn btn-icon btn-text-secondary waves-effect"><i
                                                class="icon-base ti tabler-trash icon-22px"></i></a>
                                    </div>
                                </div>
                                <p class="mb-0">
                                    Two-factor authentication adds an additional layer of security to your account by requiring
                                    more than just a password to log in.
                                    <a href="javascript:void(0);" class="text-primary">Learn more.</a>
                                </p>
                            </div>
                        </div> -->

                <!-- <div class="card p-3">
                            <h5 class="card-header">Recent Devices</h5>
                            <div class="table-responsive table-border-bottom-0">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="text-truncate">Browser</th>
                                            <th class="text-truncate">Device</th>
                                            <th class="text-truncate">Location</th>
                                            <th class="text-truncate">Recent Activities</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-truncate">
                                                <i class="ti ti-brand-windows fs-5 text-info"></i>
                                                <span class="text-heading">Chrome on Windows</span>
                                            </td>
                                            <td class="text-truncate">HP Spectre 360</td>
                                            <td class="text-truncate">Switzerland</td>
                                            <td class="text-truncate">10, July 2021 20:07</td>
                                        </tr>
                                        <tr>
                                            <td class="text-truncate">
                                                <i class="ti ti-device-mobile text-danger"></i>
                                                <span class="text-heading">Chrome on iPhone</span>
                                            </td>
                                            <td class="text-truncate">iPhone 12x</td>
                                            <td class="text-truncate">Australia</td>
                                            <td class="text-truncate">13, July 2021 10:10</td>
                                        </tr>
                                        <tr>
                                            <td class="text-truncate">
                                                <i class="ti ti-brand-android text-success"></i>
                                                <span class="text-heading">Chrome on Android</span>
                                            </td>
                                            <td class="text-truncate">Oneplus 9 Pro</td>
                                            <td class="text-truncate">Dubai</td>
                                            <td class="text-truncate">14, July 2021 15:15</td>
                                        </tr>
                                        <tr>
                                            <td class="text-truncate">
                                                <i class="ti ti-brand-apple"></i>
                                                <span class="text-heading">Chrome on MacOS</span>
                                            </td>
                                            <td class="text-truncate">Apple iMac</td>
                                            <td class="text-truncate">India</td>
                                            <td class="text-truncate">16, July 2021 16:17</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div> -->
            </div>


            <div class="tab-pane fade show active" id="notify-tab-pane" role="tabpanel" aria-labelledby="notify-tab"
                tabindex="0">
                <div class="card p-3">
                    <!-- Notifications -->
                    <div class="card-header">
                        <h5 class="mb-0">Notifications</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="display w-100" id="notificationsTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Message</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $notifications = \App\Models\Notification::where('user_id', Auth::id())
                                ->orderBy('created_at', 'desc')
                                ->get();
                                @endphp
                                @foreach ($notifications as $notification)
                                <tr>
                                    <td>{{ $notification->title }}</td>
                                    <td>{{ $notification->message }}</td>
                                    <td><span
                                            class="badge bg-label-{{ $notification->type === 'order_status_change' ? 'warning' : 'primary' }}">{{
                                            str_replace('_', ' ', ucfirst($notification->type)) }}</span>
                                    </td>
                                    <td>{{ $notification->created_at->diffForHumans() }}</td>
                                    <td>
                                        @if ($notification->is_read)
                                        <span class="badge bg-label-success">Read</span>
                                        @else
                                        <span class="badge bg-label-warning readToggle">Unread</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (!$notification->is_read)
                                        <button class="btn btn-sm btn-icon m-btn mark-as-read"
                                            data-id="{{ $notification->id }}" title="Mark as Read">
                                            {{-- <i class="ti ti-check"></i> --}}
                                            <div>
                                                <i class="fa-regular fa-envelope fs-5"></i>
                                                {{-- <i class="fa-solid fa-envelope-open-text fs-5"></i> --}}
                                            </div>
                                        </button>
                                        @endif
                                        @if ($notification->is_read)
                                        <button class="btn btn-sm btn-icon m-btn mark-as-read"
                                            data-id="{{ $notification->id }}" title="Mark as UnRead">
                                            {{-- <i class="ti ti-check"></i> --}}
                                            <div>
                                                {{-- <i class="fa-regular fa-envelope fs-5"></i> --}}
                                                <i class="fa-solid fa-envelope-open-text fs-5"></i>
                                            </div>
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- /Notifications -->
                </div>
            </div>

            <div class="tab-pane fade" id="activity-tab-pane" role="tabpanel" aria-labelledby="activity-tab"
                tabindex="0">
                <div class="card p-3">
                    <!-- Activity -->
                    <div class="card-header">
                        <h5 class="mb-0">Activity</h5>
                    </div>

                    <div class="table-responsive">
                        <table class="display w-100" id="activityTable">
                            <thead>
                                <tr>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Performed On</th>
                                    <!-- <th>Additional Data</th> -->
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $logs = \App\Models\Log::where('performed_by', Auth::id())
                                ->orderBy('created_at', 'desc')
                                ->get();
                                @endphp
                                @foreach ($logs as $log)
                                <tr>
                                    <td><span class="badge bg-label-primary">{{ str_replace('_', ' ',
                                            ucfirst($log->action_type)) }}</span>
                                    </td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ class_basename($log->performed_on_type) }} #{{ $log->performed_on_id }}
                                    </td>
                                    <!-- <td>
                                                    @if ($log->data)
    {{ json_encode($log->data) }}
@else
    -
    @endif
                                                </td> -->
                                    <td>{{ $log->created_at->diffForHumans() }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- /Activity -->
                </div>
            </div>

            <div class="tab-pane fade" id="plans-tab-pane" role="tabpanel" aria-labelledby="plans-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Current Plan</h5>
                    <div class="card-body">
                        <div class="row row-gap-4">
                            <div class="col-xl-6 order-1 order-xl-0">
                                @if ($latestOrder && $latestOrder->plan)
                                <div class="mb-4">
                                    <h6 class="mb-1">Your Current Plan is {{ $latestOrder->plan->name }}</h6>
                                    <p>{{ $latestOrder->plan->description }}</p>
                                </div>
                                @if ($latestOrder->subscription)
                                <div class="mb-4">
                                    <h6 class="mb-1">Active until
                                        {{ $latestOrder->subscription->next_billing_date ?
                                        \Carbon\Carbon::parse($latestOrder->subscription->next_billing_date)->format('M
                                        d, Y') : 'N/A' }}
                                    </h6>
                                    <p>We will send you a notification upon Subscription expiration</p>

                                    @php
                                    $startDate = \Carbon\Carbon::parse(
                                    $latestOrder->subscription->last_billing_date,
                                    );
                                    $endDate = $latestOrder->subscription->next_billing_date
                                    ? \Carbon\Carbon::parse(
                                    $latestOrder->subscription->next_billing_date,
                                    )->subDay()
                                    : now()->subDay();
                                    $totalDays = $startDate->diffInDays($endDate);
                                    $now = now();

                                    // Calculate days remaining and progress consistently
                                    if ($now->lt($startDate)) {
                                    $tabDaysRemaining = $totalDays;
                                    $tabProgress = 0;
                                    } elseif ($now->gt($endDate)) {
                                    $tabDaysRemaining = 0;
                                    $tabProgress = 100;
                                    } else {
                                    $tabDaysRemaining = $now->diffInDays($endDate, false);
                                    $daysElapsed = $startDate->diffInDays($now);
                                    $tabProgress =
                                    $totalDays > 0
                                    ? min(100, max(0, ($daysElapsed / $totalDays) * 100))
                                    : 0;
                                    }
                                    @endphp

                                    <!-- <div class="progress mb-1 bg-label-primary mt-2" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $tabProgress }}%;"
                                                     aria-valuenow="{{ $tabProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <small>{{ $tabDaysRemaining }} days remaining</small> -->
                                </div>
                                <div class="mb-xl-6">
                                    <h6 class="mb-1">
                                        <span class="me-1">${{ number_format($latestOrder->plan->price, 2) }}
                                            per {{ $latestOrder->plan->duration }}</span>
                                        @if ($latestOrder->plan->id === \App\Models\Plan::getMostlyUsed()?->id)
                                        <span class="badge bg-label-primary rounded-pill">Popular</span>
                                        @endif
                                    </h6>
                                    <p class="mb-0">{{ $latestOrder->plan->min_inbox }}
                                        {{ $latestOrder->plan->max_inbox == 0 ? '+' : '- ' .
                                        $latestOrder->plan->max_inbox }}
                                        Inboxes</p>
                                </div>
                                @endif
                                @else
                                <div class="mb-4">
                                    <h6 class="mb-1">No Active Plan</h6>
                                    <p>Subscribe to a plan to get started</p>
                                </div>
                                @endif
                            </div>
                            <div class="col-xl-6 order-0 order-xl-0">
                                @if ($latestOrder && $latestOrder->subscription)
                                @if ($latestOrder->subscription->next_billing_date)
                                <div class="alert" style="background-color: rgba(255, 166, 0, 0.176); color: orange"
                                    role="alert">
                                    <h5 class="alert-heading mb-2">Next Billing Information</h5>
                                    <span>Your next billing date is
                                        {{
                                        \Carbon\Carbon::parse($latestOrder->subscription->next_billing_date)->format('M
                                        d, Y') }}</span>
                                </div>
                                <div class="plan-statistics">
                                    @php
                                    $startDate = \Carbon\Carbon::parse(
                                    $latestOrder->subscription->last_billing_date,
                                    );
                                    $endDate = \Carbon\Carbon::parse(
                                    $latestOrder->subscription->next_billing_date,
                                    )->subDay();
                                    $totalDays = $startDate->diffInDays($endDate);
                                    $now = now();

                                    // Ensure days left is calculated correctly - make consistent with sidebar section
                                    if ($now->lt($startDate)) {
                                    // Current date is before subscription period
                                    $daysLeft = $totalDays;
                                    $progress = 0;
                                    } elseif ($now->gt($endDate)) {
                                    // If current date is after end date, no days left
                                    $daysLeft = 0;
                                    $progress = 100;
                                    } else {
                                    // Calculate days left with false parameter to ensure positive value
                                    $daysLeft = $now->diffInDays($endDate, false);
                                    $daysElapsed = $startDate->diffInDays($now);
                                    $progress =
                                    $totalDays > 0
                                    ? min(100, max(0, ($daysElapsed / $totalDays) * 100))
                                    : 0;
                                    }
                                    @endphp
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">Days</h6>
                                        <!-- <h6 class="mb-1">{{ $totalDays - $daysLeft }} of {{ $totalDays }} Days</h6> -->
                                    </div>
                                    <div class="progress mb-1 bg-label-primary" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%;"
                                            aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small>{{ $daysLeft }} days remaining</small>
                                </div>
                                @endif
                                @endif
                            </div>
                            <div class="col-12 order-2 order-xl-0 d-flex gap-2 flex-wrap">
                                <a href="{{ route('customer.pricing') }}" class="m-btn py-2 px-4 rounded-2 border-0">
                                    @if ($latestOrder && $latestOrder->subscription &&
                                    $latestOrder->subscription->status === 'active')
                                    Upgrade Plan
                                    @else
                                    View Plans
                                    @endif
                                </a>
                                @if ($latestOrder && $latestOrder->subscription && $latestOrder->subscription->status
                                === 'active')
                                <button class="cancel-btn py-2 px-4 rounded-2 border-0"
                                    onclick="CancelSubscription('{{ $latestOrder->subscription->chargebee_subscription_id }}')">
                                    Cancel Subscription
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @if ($latestOrder && $latestOrder->plan && $latestOrder->plan->features->count() > 0)
                <div class="card p-3 mb-4">
                    <div class="card-header">
                        <h5 class="card-action-title mb-0">Plan Features</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <ul class="list-unstyled mb-0">
                                    @foreach ($latestOrder->plan->features as $feature)
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success"></i>
                                        {{ $feature->title }}
                                        @if ($feature->pivot->value)
                                        : {{ $feature->pivot->value }}
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Customer Billing Address -->
                {{-- <div class="card p-3 mb-4">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h5 class="card-action-title mb-0">Billing Address
                            <span id="syn-label">
                                @if (isset(Auth::user()->billing_address_syn))
                                @if (Auth::user()->billing_address_syn)
                                <span class="badge bg-label-success ms-1">Synced with Chargebee</span>
                                @else
                                <span class="badge bg-label-danger ms-1">Not synced with Chargebee</span>
                                @endif
                                @endif
                            </span>
                        </h5>

                        <div class="card-action-element">
                            <button class="m-btn rounded-2 border-0 py-2 px-4" data-bs-target="#addRoleModal"
                                data-bs-toggle="modal"><i class="icon-base ti tabler-plus icon-14px me-1_5"></i>Edit
                                Address
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-7 col-12">
                                <div class="row mb-0 gx-2">
                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Company Name:
                                    </div>
                                    <div id="billing-company-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_company ?? 'Not set' }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Billing Email:
                                    </div>
                                    <div class="col-sm-8 opacity-50 small">{{ Auth::user()->email }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading mb-0">Billing
                                        Address:</div>
                                    <div id="billing-address-container" class="col-sm-8 opacity-50 small mb-0">
                                        <span id="billing-address1-display">{{ Auth::user()->billing_address ?? 'Not
                                            set' }}</span><br>
                                        <span id="billing-address2-display"
                                            class="{{ Auth::user()->billing_address2 ? '' : 'd-none' }}">
                                            {{ Auth::user()->billing_address2 }}
                                            <br>
                                        </span>
                                        <span id="billing-landmark-display"
                                            class="{{ Auth::user()->billing_landmark ? '' : 'd-none' }}">
                                            {{ Auth::user()->billing_landmark }}
                                            <br>
                                        </span>
                                        <span id="billing-city-display">{{ Auth::user()->billing_city ?? 'Not set'
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-5 col-12">
                                <div class="row mb-0 gx-2">
                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Country:</div>
                                    <div id="billing-country-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_country ?? 'Not set' }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">State:</div>
                                    <div id="billing-state-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_state ?? 'Not set' }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Zipcode:</div>
                                    <div id="billing-zip-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_zip ?? 'Not set' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card p-3 mb-4">
                    <div class="card-body">
                        <div class="">
                            <h5>Credit Card</h5>
                            <div id="card-details">Loading card details...</div>
                            <div class="mt-3" id="card-button-container">
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>

            <div class="tab-pane fade" id="billing-tab-pane" role="tabpanel" aria-labelledby="billing-tab" tabindex="0">

                <!-- Customer Billing Address -->
                <div class="card p-3 mb-4 border-0">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <h5 class="card-action-title mb-0">Billing Address
                            <span id="syn-label">
                                @if (isset(Auth::user()->billing_address_syn))
                                @if (Auth::user()->billing_address_syn)
                                <span class="badge bg-label-success ms-1">Synced with Chargebee</span>
                                @else
                                <span class="badge bg-label-danger ms-1">Not synced with Chargebee</span>
                                @endif
                                @endif
                            </span>
                        </h5>

                        <div class="card-action-element">
                            <button class="m-btn rounded-2 border-0 py-2 px-4" data-bs-target="#addRoleModal"
                                data-bs-toggle="modal"><i class="icon-base ti tabler-plus icon-14px me-1_5"></i>Edit
                                Address
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-7 col-12">
                                <div class="row mb-0 gx-2">
                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Company Name:
                                    </div>
                                    <div id="billing-company-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_company ?? 'Not set' }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Billing Email:
                                    </div>
                                    <div class="col-sm-8 opacity-50 small">{{ Auth::user()->email }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading mb-0">Billing
                                        Address:</div>
                                    <div id="billing-address-container" class="col-sm-8 opacity-50 small mb-0">
                                        <span id="billing-address1-display">{{ Auth::user()->billing_address ?? 'Not
                                            set' }}</span><br>
                                        <span id="billing-address2-display"
                                            class="{{ Auth::user()->billing_address2 ? '' : 'd-none' }}">
                                            {{ Auth::user()->billing_address2 }}
                                            <br>
                                        </span>
                                        <span id="billing-landmark-display"
                                            class="{{ Auth::user()->billing_landmark ? '' : 'd-none' }}">
                                            {{ Auth::user()->billing_landmark }}
                                            <br>
                                        </span>
                                        <span id="billing-city-display">{{ Auth::user()->billing_city ?? 'Not set'
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-5 col-12">
                                <div class="row mb-0 gx-2">
                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Country:</div>
                                    <div id="billing-country-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_country ?? 'Not set' }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">State:</div>
                                    <div id="billing-state-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_state ?? 'Not set' }}</div>

                                    <div class="col-sm-4 mb-sm-2 text-nowrap fw-medium text-heading">Zipcode:</div>
                                    <div id="billing-zip-display" class="col-sm-8 opacity-50 small">
                                        {{ Auth::user()->billing_zip ?? 'Not set' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- <div class="card p-3 mb-4">
                    <div class="card-body">
                        <div class="">
                            <h5>Credit Card</h5>
                            <div id="card-details">Loading card details...</div>
                            <div class="mt-3" id="card-button-container">
                            </div>
                        </div>
                    </div>
                </div> --}}

                <div>
                    {{-- <h5 class="text-white">Credit Card</h5>
                    <img src="{{asset('assets/logo/sim.jpg')}}" width="40" class="rounded-2 my-2" alt=""> --}}
                    <div id="card-details">Loading card details...</div>
                    <div class="mt-3" id="card-button-container">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" style="scrollbar-width: none" id="addRoleModal" tabindex="-1"
    aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                    data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                <div class="text-center mb-6">
                    <h4 class="address-title mb-2">
                        Edit Address
                        <span id="syn-edit-label">
                            @if (isset(Auth::user()->billing_address_syn))
                            @if (Auth::user()->billing_address_syn)
                            <span class="badge bg-label-success ms-1">Synced with Chargebee</span>
                            @else
                            <span class="badge bg-label-danger ms-1">Not synced with Chargebee</span>
                            @endif
                            @endif
                        </span>
                    </h4>
                    <p class="address-subtitle">Edit your current address</p>
                    <!--  -->
                </div>
                <form id="addNewAddressForm" class="row g-6">
                    <!-- billing_company -->
                    <div class="col-12 mb-3">
                        <label class="form-label" for="modalAddressCompany">Company Name</label>
                        <input type="text" id="modalAddressCompany" name="modalAddressCompany" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_company ?? '' }}">
                    </div>
                    <div class="col-md-12 mb-3">
                        <label>Country</label>
                        <select id="modalcountry" name="modalcountry" class="form-control" required="">
                            <option value="">Select Country</option>
                            @foreach ([
                            'Afghanistan',
                            'Albania',
                            'Algeria',
                            'Andorra',
                            'Angola',
                            'Antigua and Barbuda',
                            'Argentina',
                            'Armenia',
                            'Australia',
                            'Austria',
                            'Azerbaijan',
                            'Bahamas',
                            'Bahrain',
                            'Bangladesh',
                            'Barbados',
                            'Belarus',
                            'Belgium',
                            'Belize',
                            'Benin',
                            'Bhutan',
                            'Bolivia',
                            'Bosnia and Herzegovina',
                            'Botswana',
                            'Brazil',
                            'Brunei',
                            'Bulgaria',
                            'Burkina Faso',
                            'Burundi',
                            'Cabo Verde',
                            'Cambodia',
                            'Cameroon',
                            'Canada',
                            'Central African Republic',
                            'Chad',
                            'Chile',
                            'China',
                            'Colombia',
                            'Comoros',
                            'Congo (Congo-Brazzaville)',
                            'Costa Rica',
                            'Croatia',
                            'Cuba',
                            'Cyprus',
                            'Czech Republic',
                            'Democratic Republic of the Congo',
                            'Denmark',
                            'Djibouti',
                            'Dominica',
                            'Dominican Republic',
                            'Ecuador',
                            'Egypt',
                            'El Salvador',
                            'Equatorial Guinea',
                            'Eritrea',
                            'Estonia',
                            'Eswatini',
                            'Ethiopia',
                            'Fiji',
                            'Finland',
                            'France',
                            'Gabon',
                            'Gambia',
                            'Georgia',
                            'Germany',
                            'Ghana',
                            'Greece',
                            'Grenada',
                            'Guatemala',
                            'Guinea',
                            'Guinea-Bissau',
                            'Guyana',
                            'Haiti',
                            'Honduras',
                            'Hungary',
                            'Iceland',
                            'India',
                            'Indonesia',
                            'Iran',
                            'Iraq',
                            'Ireland',
                            'Israel',
                            'Italy',
                            'Ivory Coast',
                            'Jamaica',
                            'Japan',
                            'Jordan',
                            'Kazakhstan',
                            'Kenya',
                            'Kiribati',
                            'Kuwait',
                            'Kyrgyzstan',
                            'Laos',
                            'Latvia',
                            'Lebanon',
                            'Lesotho',
                            'Liberia',
                            'Libya',
                            'Liechtenstein',
                            'Lithuania',
                            'Luxembourg',
                            'Madagascar',
                            'Malawi',
                            'Malaysia',
                            'Maldives',
                            'Mali',
                            'Malta',
                            'Marshall Islands',
                            'Mauritania',
                            'Mauritius',
                            'Mexico',
                            'Micronesia',
                            'Moldova',
                            'Monaco',
                            'Mongolia',
                            'Montenegro',
                            'Morocco',
                            'Mozambique',
                            'Myanmar',
                            'Namibia',
                            'Nauru',
                            'Nepal',
                            'Netherlands',
                            'New Zealand',
                            'Nicaragua',
                            'Niger',
                            'Nigeria',
                            'North Korea',
                            'North Macedonia',
                            'Norway',
                            'Oman',
                            'Pakistan',
                            'Palau',
                            'Palestine',
                            'Panama',
                            'Papua New Guinea',
                            'Paraguay',
                            'Peru',
                            'Philippines',
                            'Poland',
                            'Portugal',
                            'Qatar',
                            'Romania',
                            'Russia',
                            'Rwanda',
                            'Saint Kitts and Nevis',
                            'Saint Lucia',
                            'Saint Vincent and the Grenadines',
                            'Samoa',
                            'San Marino',
                            'Sao Tome and Principe',
                            'Saudi Arabia',
                            'Senegal',
                            'Serbia',
                            'Seychelles',
                            'Sierra Leone',
                            'Singapore',
                            'Slovakia',
                            'Slovenia',
                            'Solomon Islands',
                            'Somalia',
                            'South Africa',
                            'South Korea',
                            'South Sudan',
                            'Spain',
                            'Sri Lanka',
                            'Sudan',
                            'Suriname',
                            'Sweden',
                            'Switzerland',
                            'Syria',
                            'Taiwan',
                            'Tajikistan',
                            'Tanzania',
                            'Thailand',
                            'Timor-Leste',
                            'Togo',
                            'Tonga',
                            'Trinidad and Tobago',
                            'Tunisia',
                            'Turkey',
                            'Turkmenistan',
                            'Tuvalu',
                            'Uganda',
                            'Ukraine',
                            'United Arab Emirates',
                            'United Kingdom',
                            'United States',
                            'Uruguay',
                            'Uzbekistan',
                            'Vanuatu',
                            'Vatican City',
                            'Venezuela',
                            'Vietnam',
                            'Yemen',
                            'Zambia',
                            'Zimbabwe',
                            ] as $country)
                            <option value="{{ $country }}" {{ Auth::user()->billing_country == $country ? 'selected' :
                                '' }}>
                                {{ $country }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label" for="modalAddressAddress1">Address Line 1</label>
                        <input type="text" id="modalAddressAddress1" name="modalAddressAddress1" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_address ?? '' }}">
                    </div>

                    <div class="col-12 mb-3">
                        <label class="form-label" for="modalAddressAddress2">Address Line 2</label>
                        <input type="text" id="modalAddressAddress2" name="modalAddressAddress2" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_address2 ?? '' }}">
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label" for="modalAddressLandmark">Landmark</label>
                        <input type="text" id="modalAddressLandmark" name="modalAddressLandmark" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_landmark ?? '' }}">
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label" for="modalAddressCity">City</label>
                        <input type="text" id="modalAddressCity" name="modalAddressCity" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_city ?? '' }}">
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label" for="modalAddressLandmark">State</label>
                        <input type="text" id="modalAddressState" name="modalAddressState" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_state ?? '' }}">
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <label class="form-label" for="modalAddressZipCode">Zip Code</label>
                        <input type="text" id="modalAddressZipCode" name="modalAddressZipCode" class="form-control"
                            placeholder="" value="{{ Auth::user()->billing_zip ?? '' }}">
                    </div>

                    <div class="col-12 mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input rounded-1" id="billingAddress" checked>
                            <label for="billingAddress" class="form-switch-label">Use as a billing address?</label>
                        </div>

                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" class="m-btn py-2 px-4 border-0 rounded-2">Submit</button>
                        <button type="reset" class="cancel-btn py-2 px-4 border-0 rounded-2" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" style="scrollbar-width: none" id="edit" tabindex="-1" aria-labelledby="editLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                    data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                <div class="text-center mb-6">
                    <h4 class="mb-2">Edit User Information</h4>
                    <p>Updating user details will receive a privacy audit.</p>
                </div>
                <form id="editUserForm" class="row gy-3">
                    <div class="col-12 col-md-12 mb-3">
                        <label class="form-label" for="modalEditUserFirstName">Username</label>
                        <input type="text" id="modalEditUserFirstName" name="modalEditUserFirstName"
                            class="form-control" placeholder="John" value="{{ Auth::user()->name }}">
                    </div>

                    <div class="col-12 col-md-12 mb-3">
                        <label class="form-label" for="modalEditUserEmail">Email</label>
                        <input type="text" id="modalEditUserEmail" name="modalEditUserEmail" class="form-control"
                            placeholder="example@domain.com" value="{{ Auth::user()->email }}" readonly>
                    </div>

                    <!-- <div class="col-12 col-md-12">
                            <label class="form-label" for="modalEditUserPhone">Phone Number</label>
                            <div class="input-group">
                                <input type="text" id="modalEditUserPhone" name="modalEditUserPhone"
                                    class="form-control phone-number-mask" placeholder="Enter phone number"
                                    value="{{ Auth::user()->phone }}">
                            </div>
                        </div> -->


                    {{-- bill address --}}
                    <div class="col-12 col-md-6 mb-3" style="display: none;">
                        <label class="form-label" for="modalEditUserBillingAddress">Billing Address</label>
                        <div class="input-group">
                            <input type="text" id="modalEditUserBillingAddress" name="modalEditUserBillingAddress"
                                class="form-control" placeholder="123 Main St, City, Country"
                                value="{{ Auth::user()->billing_address ?? '' }}">
                        </div>
                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" class="m-btn py-2 px-4 rounded-2 border-0 ">Submit</button>
                        <button type="reset" class="cancel-btn py-2 px-4 rounded-2 border-0" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Add Cropper Modal -->
<div class="modal fade" id="cropperModal" tabindex="-1" role="dialog" aria-labelledby="cropperModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Profile Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cropper-container">
                    <img id="cropperImage" src="" alt="Image to crop" style="max-width: 100%;">
                </div>
                <div class="cropper-controls">
                    <button type="button" class="rotate-left"><i class="ti ti-rotate-clockwise-2"></i> Rotate
                        Left</button>
                    <button type="button" class="rotate-right"><i class="ti ti-rotate"></i> Rotate Right</button>
                    <button type="button" class="flip-horizontal"><i class="ti ti-flip-horizontal"></i> Flip
                        H</button>
                    <button type="button" class="flip-vertical"><i class="ti ti-flip-vertical"></i> Flip V</button>
                    <div class="zoom-controls">
                        <button type="button" class="zoom-in"><i class="ti ti-zoom-in"></i></button>
                        <input type="range" class="zoom-slider" min="0" max="100" value="0">
                        <button type="button" class="zoom-out"><i class="ti ti-zoom-out"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn py-2 px-4 rounded-2 border-0"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="m-btn py-2 px-4 rounded-2 border-0" id="cropButton">Crop &
                    Upload</button>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Subscription Modal -->
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
                    We are sad to hear you're cancelling. Would you mind sharing the reason
                    for the cancellation? We strive to always improve and would appreciate your
                    feedback.
                </p>

                <form id="cancelSubscriptionForm" action="{{ route('customer.subscription.cancel.process') }}"
                    method="POST">
                    @csrf
                    <input type="hidden" name="chargebee_subscription_id" id="subscription_id_to_cancel">
                    <div class="mb-3">
                        <label for="cancellation_reason">Reason *</label>
                        <textarea id="cancellation_reason" name="reason" class="form-control" rows="8"
                            required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_accounts" id="remove_accounts">
                        <label class="form-check-label" for="remove_accounts">
                            I would like to have these email accounts removed and the domains
                            released immediately. I will not be using these inboxes any longer.
                        </label>
                    </div>

                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button"
                            class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                            data-bs-dismiss="modal">No, I changed my mind</button>
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
    $(document).ready(function() {
            var table = $('#myTable').DataTable();
            // Initialize DataTable for notifications
            var notificationsTable = $('#notificationsTable').DataTable({
                responseive: true,
                order: [
                    [3, 'desc']
                ], // Sort by date column descending
                pageLength: 10,
                language: {
                    emptyTable: "No notifications found"
                }
            });
            // Initialize DataTable for notifications
            var activityTable = $('#activityTable').DataTable({
                responseive: true,
                order: [
                    [3, 'desc']
                ], // Sort by date column descending
                pageLength: 10,
                language: {
                    emptyTable: "No Activity found"
                }
            });
            // Handle user edit form submission
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();

                var formData = {
                    name: $('#modalEditUserFirstName').val(),
                    email: $('#modalEditUserEmail').val(),
                    phone: $('#modalEditUserPhone').val(),
                    billing_address: $('#modalEditUserBillingAddress').val(),
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    type: 'POST',
                    url: '{{ route('admin.profile.update') }}',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            $('#edit').modal('hide');

                            // Show success message
                            toastr.success('Profile updated successfully');
                            window.location.reload();

                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                toastr.error(errors[key][0]);
                            });
                        } else if (xhr.status === 400) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error(xhr.responseJSON.message);
                        }
                        // Show error message
                        // toastr.error('Error updating profile');
                        console.log(xhr.responseText);
                    }
                });
            });

        });
        // 
        $(document).ready(function() {
            $('#formChangePassword').on('submit', function(e) {
                e.preventDefault();

                $.ajax({
                    url: "{{ route('change.password') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        oldPassword: $('#oldPassword').val(),
                        newPassword: $('#newPassword').val(),
                        confirmPassword: $('#confirmPassword').val()
                    },
                    success: function(response) {
                        toastr.success(response.message);
                        $('#formChangePassword')[0].reset();
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                toastr.error(errors[key][0]);
                            });
                        } else if (xhr.status === 400) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
            });
        });

        let cropper;
        let zoomValue = 0;

        // Initialize image cropping when file is selected
        $('#profile-image-input').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Initialize cropper
                    const image = document.getElementById('cropperImage');
                    image.src = e.target.result;

                    // Show cropper modal
                    $('#cropperModal').modal('show');

                    // Initialize Cropper.js after modal is shown
                    $('#cropperModal').on('shown.bs.modal', function() {
                        if (cropper) {
                            cropper.destroy();
                        }
                        cropper = new Cropper(image, {
                            aspectRatio: 1,
                            viewMode: 1,
                            dragMode: 'move',
                            autoCropArea: 1,
                            cropBoxResizable: true,
                            cropBoxMovable: true,
                            minCropBoxWidth: 200,
                            minCropBoxHeight: 200,
                            width: 200,
                            height: 200,
                            guides: true,
                            center: true,
                            highlight: true,
                            background: true,
                            autoCrop: true,
                            responsive: true,
                            toggleDragModeOnDblclick: true
                        });
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle image manipulation controls
        $('.rotate-left').on('click', function() {
            cropper.rotate(-90);
        });

        $('.rotate-right').on('click', function() {
            cropper.rotate(90);
        });

        $('.flip-horizontal').on('click', function() {
            cropper.scaleX(cropper.getData().scaleX === 1 ? -1 : 1);
        });

        $('.flip-vertical').on('click', function() {
            cropper.scaleY(cropper.getData().scaleY === 1 ? -1 : 1);
        });

        $('.zoom-in').on('click', function() {
            zoomValue = Math.min(zoomValue + 10, 100);
            $('.zoom-slider').val(zoomValue);
            cropper.zoom(0.1);
        });

        $('.zoom-out').on('click', function() {
            zoomValue = Math.max(zoomValue - 10, 0);
            $('.zoom-slider').val(zoomValue);
            cropper.zoom(-0.1);
        });

        $('.zoom-slider').on('input', function() {
            const newZoom = parseInt($(this).val());
            const zoomDiff = (newZoom - zoomValue) / 100;
            cropper.zoom(zoomDiff);
            zoomValue = newZoom;
        });

        // Clean up cropper when modal is hidden
        $('#cropperModal').on('hidden.bs.modal', function() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
                zoomValue = 0;
                $('.zoom-slider').val(0);
            }
        });

        // Rest of your existing cropper code...
        $('#cropButton').on('click', function() {
            if (!cropper) return;

            // Get cropped canvas
            const canvas = cropper.getCroppedCanvas({
                width: 200,
                height: 200
            });

            // Apply copper filter effect
            const ctx = canvas.getContext('2d');
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;

            for (let i = 0; i < data.length; i += 4) {
                data[i] = Math.min(255, data[i] * 1.2); // Red
                data[i + 1] = Math.min(255, data[i + 1] * 0.9); // Green
                data[i + 2] = Math.min(255, data[i + 2] * 0.7); // Blue
            }

            ctx.putImageData(imageData, 0, 0);

            // Convert to blob and upload
            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('profile_image', blob, 'profile.jpg');
                formData.append('_token', '{{ csrf_token() }}');

                $.ajax({
                    url: '{{ route('profile.update.image') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            // Update the image preview
                            $('#profile-image').attr('src', response.image_url);
                            // login-user-profile
                            $('.login-user-profile').attr('src', response.image_url);
                            toastr.success('Profile image updated successfully');
                            $('#cropperModal').modal('hide');
                            window.location.reload();
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error updating profile image');
                        console.log(xhr.responseText);
                    }
                });
            }, 'image/jpeg', 0.95);
        });
        // Handle mark as read functionality
        $('.mark-as-read').on('click', function() {
            const button = $(this);
            const notificationId = button.data('id');

            $.ajax({
                url: `/notifications/${notificationId}/mark-read`,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Update the status badge
                        button.closest('tr').find('.readToggle').removeClass('bg-label-warning')
                            .addClass('bg-label-success').text('Read');
                        // Remove the mark as read button
                        button.remove();
                        // Show success message
                        toastr.success(response.message || 'Notification marked as read');
                        window.location.reload();
                    } else {
                        toastr.error(response.message || 'Failed to mark notification as read');
                    }
                },
                error: function(xhr) {
                    console.error('Error marking notification as read:', xhr);
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error(xhr.responseJSON.message);
                    } else {
                        toastr.error('Failed to mark notification as read. Please try again.');
                    }
                }
            });
        });

        // Handle subscription cancellation
        function CancelSubscription(subscriptionId) {
            $('#subscription_id_to_cancel').val(subscriptionId);
            $('#cancel_subscription').modal('show');
        }

        // Handle form submission for subscription cancellation
        $('#cancelSubscriptionForm').on('submit', function(e) {
            e.preventDefault();

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
                        url: "{{ route('customer.subscription.cancel.process') }}",
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
                            let errorMessage =
                                'An error occurred while cancelling your subscription.';
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

        // Handle billing address form submission
        $('#addNewAddressForm').on('submit', function(e) {
            e.preventDefault();

            // Show loading state
            Swal.fire({
                title: 'Processing...',
                text: 'Updating your billing address',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: "{{ route('customer.address.update') }}",
                type: "POST",
                data: $(this).serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    // Close loading indicator
                    Swal.close();

                    if (response.success) {
                        // Close modal
                        $('#addRoleModal').modal('hide');

                        // Show success message
                        toastr.success('Billing address updated successfully');

                        // Update billing address section without reloading the page
                        // Include the sync status from the response
                        updateBillingAddress(response.billing_address_syn);
                    }
                },
                error: function(xhr) {
                    // Close loading indicator
                    Swal.close();

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        Object.values(xhr.responseJSON.errors).forEach(function(error) {
                            toastr.error(error[0]);
                        });
                    } else {
                        toastr.error('Error updating billing address');
                    }
                }
            });
        });

        function loadCardDetails() {
            $.ajax({
                url: '{{ route('customer.plans.card-details') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_id: ''
                },
                success: function(response) {
                    if (response.success) {
                        const card = response.card;
                        if (response.payment_sources && response.payment_sources.length > 0) {
                            let cardHtml = '';
                            response.payment_sources.forEach(source => {
                                if (source.type === 'card' && source.status === 'valid' && source
                                    .card) {
                                    cardHtml += `

                                        <div class="credit-card p-3 mb-4 card border-0">
                                            <div class="card-body" style="position: relative; z-index: 10;">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <h5 class="text-white">Credit Card</h5>
                                                    <h6 class="text-white"><i>VISA</i></h6>    
                                                </div>
                                                <img  src="{{asset('assets/logo/sim.jpg')}}" width="40" class="rounded-2 my-2" alt="">
                                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                                    <h1 class="text-white mb-0 number text-shadow">
                                                        **** **** **** ${source.card.last4}
                                                    </h1>
                                                    <button type="button" class="cancel-btn py-2 px-2 rounded-2 border-0" onclick="deletePaymentMethod('${source.id}')">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </div>
    
                                                <div class="d-flex align-items-center justify-content-between" >
                                                    <h6 class="text-shadow">HAMZA ASHFAQ</h6>
                                                    <span class="number small">Expires ${source.card.expiry_month}/${source.card.expiry_year}</span>    
                                                </div>    
                                            </div>
                                        </div>

                                    `;
                                }
                            });

                            if (cardHtml) {
                                $('#card-details').html(cardHtml);
                                // Show Change Card button when cards are available
                                $('#card-button-container').html(`
                                    <button type="button" class="btn btn-sm btn-primary" onclick="updatePaymentMethod()">
                                        <i class="fa-solid fa-credit-card"></i> Change Card
                                    </button>
                                `);
                            } else {
                                $('#card-details').html(
                                    '<span class="opacity-50">No valid card details available</span>');
                                // Show Add Card button when no valid cards
                                $('#card-button-container').html(`
                                    <button type="button" class="btn btn-sm btn-success" onclick="updatePaymentMethod()">
                                        <i class="fa-solid fa-plus"></i> Add Card
                                    </button>
                                `);
                            }
                        } else {
                            $('#card-details').html(
                            '<span class="opacity-50">No card details available</span>');
                            // Show Add Card button when no cards
                            $('#card-button-container').html(`
                                <button type="button" class="btn btn-sm btn-success" onclick="updatePaymentMethod()">
                                    <i class="fa-solid fa-plus"></i> Add Card
                                </button>
                            `);
                        }
                    } else {
                        $('#card-details').html(
                            '<span class="opacity-50">No card details available</span>'
                        );
                        // Show Add Card button when no cards
                        $('#card-button-container').html(`
                            <button type="button" class="btn btn-sm btn-success" onclick="updatePaymentMethod()">
                                <i class="fa-solid fa-plus"></i> Add Card
                            </button>
                        `);
                    }
                },
                error: function(xhr) {
                    $('#card-details').html(
                        '<span class="opacity-50">Failed to load card details</span>'
                    );
                }
            });
        }
        // Load card details when page loads
        if ($('#card-details').length) {
            loadCardDetails();
        }

        function deletePaymentMethod(paymentSourceId) {
            // Use SweetAlert for confirmation
            Swal.fire({
                title: 'Are you sure?',
                text: "You are about to delete this payment method.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete your payment method.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '{{ route('customer.plans.delete-payment-method') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            payment_source_id: paymentSourceId,
                            order_id: ''
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'Your payment method has been deleted successfully.',
                                    icon: 'success',
                                    confirmButtonColor: '#3085d6'
                                });
                                // Reload card details to update the UI
                                loadCardDetails();
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to delete payment method',
                                    icon: 'error',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        },
                        error: function(xhr) {
                            // Handle specific error for primary/only payment method
                            if (xhr.status === 400 && xhr.responseJSON && xhr.responseJSON.message) {
                                Swal.fire({
                                    title: 'Warning!',
                                    text: xhr.responseJSON.message,
                                    icon: 'warning',
                                    confirmButtonColor: '#3085d6'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: xhr.responseJSON?.message ||
                                        'Failed to delete payment method',
                                    icon: 'error',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        }
                    });
                }
            });
        }

        function updatePaymentMethod() {
            // Show loading state
            Swal.fire({
                title: 'Loading...',
                text: 'Please wait while we prepare the payment form.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route('customer.plans.update-payment-method') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_id: ''
                },
                success: function(response) {
                    if (response.success) {
                        // Close the loading dialog
                        Swal.close();

                        // Open the payment page in a popup window
                        const popupWidth = 500;
                        const popupHeight = 700;
                        const left = (window.innerWidth - popupWidth) / 2;
                        const top = (window.innerHeight - popupHeight) / 2;

                        const popup = window.open(
                            response.hosted_page_url,
                            'payment_method_update',
                            `width=${popupWidth},height=${popupHeight},top=${top},left=${left},resizable=yes,scrollbars=yes`
                        );

                        // Check when popup is closed
                        const checkPopup = setInterval(function() {
                            if (popup.closed) {
                                clearInterval(checkPopup);
                                // Reload card details without refreshing page
                                loadCardDetails();
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Your payment method has been updated successfully.',
                                    icon: 'success',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        }, 500);
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to initiate payment method update',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to initiate payment method update',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        }

        // Function to update billing address section without page reload
        function updateBillingAddress(isSynced) {
            // Get values from the form
            const company = $('#modalAddressCompany').val() || 'Not set';
            const address1 = $('#modalAddressAddress1').val() || 'Not set';
            const address2 = $('#modalAddressAddress2').val();
            const landmark = $('#modalAddressLandmark').val();
            const city = $('#modalAddressCity').val() || 'Not set';
            const state = $('#modalAddressState').val() || 'Not set';
            const zipCode = $('#modalAddressZipCode').val() || 'Not set';
            const country = $('#modalcountry').val() || 'Not set';

            // Update display elements
            $('#billing-company-display').text(company);
            $('#billing-address1-display').text(address1);

            // Handle optional fields
            if (address2 && address2.trim() !== '') {
                $('#billing-address2-display').text(address2).removeClass('d-none');
            } else {
                $('#billing-address2-display').addClass('d-none');
            }

            if (landmark && landmark.trim() !== '') {
                $('#billing-landmark-display').text(landmark).removeClass('d-none');
            } else {
                $('#billing-landmark-display').addClass('d-none');
            }

            $('#billing-city-display').text(city);
            $('#billing-country-display').text(country);
            $('#billing-state-display').text(state);
            $('#billing-zip-display').text(zipCode);

            // Update Chargebee sync badge if available
            if (typeof isSynced !== 'undefined') {
                // Convert to boolean to ensure consistent behavior
                isSynced = isSynced === true || isSynced === 1 || isSynced === "1" || isSynced === "true";

                if (isSynced) {
                    $('#syn-label').html('<span class="badge bg-label-success ms-1">Synced with Chargebee</span>');
                    $('#syn-edit-label').html('<span class="badge bg-label-success ms-1">Synced with Chargebee</span>');
                } else {
                    $('#syn-edit-label').html('<span class="badge bg-label-danger ms-1">Not synced with Chargebee</span>');
                    $('#syn-label').html('<span class="badge bg-label-danger ms-1">Not synced with Chargebee</span>');
                }
            }
        }
</script>
@endpush