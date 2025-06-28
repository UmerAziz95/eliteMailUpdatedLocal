@extends('contractor.layouts.app')

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

        .bg-label-warning, .bg-label-secondary, .bg-label-primary {
            padding: .5rem;
            font-size: 10px;
            font-weight: 400 
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
                           <div>
                                    <img class="img-fluid rounded mb-4" id="profile-image" src="{{ Auth::user()->profile_image ? asset('storage/profile_images/' . Auth::user()->profile_image) : 'https://cdn-icons-png.flaticon.com/128/3237/3237472.png' }}" height="120" width="120" alt="User avatar" style="cursor: pointer;" onclick="$('#profile-image-input').click();">
                                </div>
                            <input type="file" id="profile-image-input" style="display: none;" accept="image/*">
                            <div class="user-info text-center">
                                <h5>{{ Auth::user()->name }}</h5>
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
                                <span class="h6">Full Name:</span>
                                <span class="opacity-50 small fw-light">{{ Auth::user()->name }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">Email:</span>
                                <span class="opacity-50 small fw-light">{{ Auth::user()->email }}</span>
                            </li>
                            <li class="mb-2">
                                <span class="h6">Status:</span>
                                <span
                                    class="opacity-50 small fw-light">{{ Auth::user()->status == '1' ? 'Active' : 'In-Active' }}</span>
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
        </div>
        <!--/ User Sidebar -->

        <div class="col-xl-8 col-lg-5">
            <ul class="nav nav-tabs border-0" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="notify-tab" data-bs-toggle="tab" data-bs-target="#notify-tab-pane"
                            type="button" role="tab" aria-controls="notify-tab-pane" aria-selected="false"><i
                                class="fa-regular fa-bell"></i> Notifications</button>
                </li>
                <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity-tab-pane"
                            type="button" role="tab" aria-controls="activity-tab-pane" aria-selected="false"><i
                                class="fa-regular fa-bell"></i> Activity</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security-tab-pane"
                        type="button" role="tab" aria-controls="security-tab-pane" aria-selected="false"><i
                            class="fa-solid fa-unlock"></i> Security</button>
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
                                            <input class="form-control" type="password" id="oldPassword"
                                                name="oldPassword" placeholder="············">
                                        </div>
                                        <div
                                            class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                                        </div>
                                    </div>

                                    <div class="mb-4 col-12 col-sm-6 form-password-toggle">
                                        <label class="form-label" for="newPassword">New Password</label>
                                        <div class="input-group input-group-merge has-validation">
                                            <input class="form-control" type="password" id="newPassword"
                                                name="newPassword" placeholder="············">
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
                                    @foreach($notifications as $notification)
                                        <tr>
                                            <td>{{ $notification->title }}</td>
                                            <td>{{ $notification->message }}</td>
                                            <td><span class="badge bg-label-{{ $notification->type === 'order_status_change' ? 'warning' : 'info' }}">{{ str_replace('_', ' ', ucfirst($notification->type)) }}</span></td>
                                            <td>{{ $notification->created_at->diffForHumans() }}</td>
                                            <td>
                                                @if($notification->is_read)
                                                    <span class="badge bg-label-success">Read</span>
                                                @else
                                                    <span class="badge bg-label-warning readToggle">Unread</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if(!$notification->is_read)
                                                    <button class="btn btn-sm btn-icon m-btn mark-as-read" data-id="{{ $notification->id }}" title="Mark as Read">
                                                        <i class="ti ti-check"></i>
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
                                    @foreach($logs as $log)
                                        <tr>
                                            <td><span class="badge bg-label-primary">{{ str_replace('_', ' ', ucfirst($log->action_type)) }}</span></td>
                                            <td>{{ $log->description }}</td>
                                            <td>{{ class_basename($log->performed_on_type) }} #{{ $log->performed_on_id }}</td>
                                            <!-- <td>
                                                @if($log->data)
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
                        <h4 class="address-title mb-2">Edit Address</h4>
                        <p class="address-subtitle">Edit your current address</p>
                    </div>
                    <form id="addNewAddressForm" class="row g-6">
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md mb-md-0 mb-4">
                                    <div class="form-check custom-option custom-option-icon checked">
                                        <label class="form-check-label custom-option-content" for="customRadioHome">
                                            <span class="custom-option-body">
                                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.2"
                                                        d="M16.625 23.625V16.625H11.375V23.625H4.37501V12.6328C4.37437 12.5113 4.39937 12.391 4.44837 12.2798C4.49737 12.1686 4.56928 12.069 4.65939 11.9875L13.4094 4.03592C13.5689 3.88911 13.7778 3.80762 13.9945 3.80762C14.2113 3.80762 14.4202 3.88911 14.5797 4.03592L23.3406 11.9875C23.4287 12.0706 23.4992 12.1706 23.548 12.2814C23.5969 12.3922 23.6231 12.5117 23.625 12.6328V23.625H16.625Z">
                                                    </path>
                                                    <path
                                                        d="M23.625 23.625V12.6328C23.623 12.5117 23.5969 12.3922 23.548 12.2814C23.4992 12.1706 23.4287 12.0706 23.3406 11.9875L14.5797 4.03592C14.4202 3.88911 14.2113 3.80762 13.9945 3.80762C13.7777 3.80762 13.5689 3.88911 13.4094 4.03592L4.65937 11.9875C4.56926 12.069 4.49736 12.1686 4.44836 12.2798C4.39936 12.391 4.37436 12.5113 4.375 12.6328V23.625M1.75 23.625H26.25M16.625 23.625V17.5C16.625 17.2679 16.5328 17.0454 16.3687 16.8813C16.2046 16.7172 15.9821 16.625 15.75 16.625H12.25C12.0179 16.625 11.7954 16.7172 11.6313 16.8813C11.4672 17.0454 11.375 17.2679 11.375 17.5V23.625"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    </path>
                                                </svg>
                                                <span class="custom-option-title">Home</span>
                                                <small> Delivery time (9am – 9pm) </small>
                                            </span>
                                            <input name="customRadioIcon" class="form-check-input rounded-1"
                                                type="radio" value="" id="customRadioHome" checked="">
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md mb-md-0 mb-4">
                                    <div class="form-check custom-option custom-option-icon">
                                        <label class="form-check-label custom-option-content" for="customRadioOffice">
                                            <span class="custom-option-body">
                                                <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                                    xmlns="http://www.w3.org/2000/svg">
                                                    <path opacity="0.2"
                                                        d="M15.75 23.625V4.375C15.75 4.14294 15.6578 3.92038 15.4937 3.75628C15.3296 3.59219 15.1071 3.5 14.875 3.5H4.375C4.14294 3.5 3.92038 3.59219 3.75628 3.75628C3.59219 3.92038 3.5 4.14294 3.5 4.375V23.625">
                                                    </path>
                                                    <path
                                                        d="M1.75 23.625H26.25M15.75 23.625V4.375C15.75 4.14294 15.6578 3.92038 15.4937 3.75628C15.3296 3.59219 15.1071 3.5 14.875 3.5H4.375C4.14294 3.5 3.92038 3.59219 3.75628 3.75628C3.59219 3.92038 3.5 4.14294 3.5 4.375V23.625M24.5 23.625V11.375C24.5 11.1429 24.4078 10.9204 24.2437 10.7563C24.0796 10.5922 23.8571 10.5 23.625 10.5H15.75M7 7.875H10.5M8.75 14.875H12.25M7 19.25H10.5M19.25 19.25H21M19.25 14.875H21"
                                                        stroke-opacity="0.9" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round"></path>
                                                </svg>
                                                <span class="custom-option-title"> Office </span>
                                                <small> Delivery time (9am – 5pm) </small>
                                            </span>
                                            <input name="customRadioIcon" class="form-check-input rounded-1"
                                                type="radio" value="" id="customRadioOffice">
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="modalAddressFirstName">First Name</label>
                            <input type="text" id="modalAddressFirstName" name="modalAddressFirstName"
                                class="form-control" placeholder="John">
                            <div
                                class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="modalAddressLastName">Last Name</label>
                            <input type="text" id="modalAddressLastName" name="modalAddressLastName"
                                class="form-control" placeholder="Doe">
                            <div
                                class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="modalAddressCountry">Country</label>
                            <div class="position-relative">
                                <div class="position-relative"><select id="modalAddressCountry"
                                        name="modalAddressCountry" class="select2 form-select select2-hidden-accessible"
                                        data-allow-clear="true" tabindex="-1" aria-hidden="true"
                                        data-select2-id="modalAddressCountry">
                                        <option value="" data-select2-id="82">Select</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Bangladesh">Bangladesh</option>
                                        <option value="Belarus">Belarus</option>
                                        <option value="Brazil">Brazil</option>
                                        <option value="Canada">Canada</option>
                                        <option value="China">China</option>
                                        <option value="France">France</option>
                                        <option value="Germany">Germany</option>
                                        <option value="India">India</option>
                                        <option value="Indonesia">Indonesia</option>
                                        <option value="Israel">Israel</option>
                                        <option value="Italy">Italy</option>
                                        <option value="Japan">Japan</option>
                                        <option value="Korea">Korea, Republic of</option>
                                        <option value="Mexico">Mexico</option>
                                        <option value="Philippines">Philippines</option>
                                        <option value="Russia">Russian Federation</option>
                                        <option value="South Africa">South Africa</option>
                                        <option value="Thailand">Thailand</option>
                                        <option value="Turkey">Turkey</option>
                                        <option value="Ukraine">Ukraine</option>
                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="United States">United States</option>
                                    </select><span class="select2 select2-container select2-container--default"
                                        dir="ltr" data-select2-id="81" style="width: auto;"><span
                                            class="selection"><span class="select2-selection select2-selection--single"
                                                role="combobox" aria-haspopup="true" aria-expanded="false"
                                                tabindex="0" aria-disabled="false"
                                                aria-labelledby="select2-modalAddressCountry-container"><span
                                                    class="select2-selection__rendered"
                                                    id="select2-modalAddressCountry-container" role="textbox"
                                                    aria-readonly="true"><span
                                                        class="select2-selection__placeholder">Select
                                                        value</span></span><span class="select2-selection__arrow"
                                                    role="presentation"><b
                                                        role="presentation"></b></span></span></span><span
                                            class="dropdown-wrapper" aria-hidden="true"></span></span></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="modalAddressAddress1">Address Line 1</label>
                            <input type="text" id="modalAddressAddress1" name="modalAddressAddress1"
                                class="form-control" placeholder="12, Business Park">
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="modalAddressAddress2">Address Line 2</label>
                            <input type="text" id="modalAddressAddress2" name="modalAddressAddress2"
                                class="form-control" placeholder="Mall Road">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="modalAddressLandmark">Landmark</label>
                            <input type="text" id="modalAddressLandmark" name="modalAddressLandmark"
                                class="form-control" placeholder="Nr. Hard Rock Cafe">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="modalAddressCity">City</label>
                            <input type="text" id="modalAddressCity" name="modalAddressCity" class="form-control"
                                placeholder="Los Angeles">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="modalAddressLandmark">State</label>
                            <input type="text" id="modalAddressState" name="modalAddressState" class="form-control"
                                placeholder="California">
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label" for="modalAddressZipCode">Zip Code</label>
                            <input type="text" id="modalAddressZipCode" name="modalAddressZipCode"
                                class="form-control" placeholder="99950">
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="checkbox" class="form-check-input rounded-1" id="billingAddress">
                                <label for="billingAddress" class="form-switch-label">Use as a billing address?</label>
                            </div>
                        </div>

                        <div class="col-12 text-center">
                            <button type="submit" class="m-btn py-2 px-4 border-0 rounded-2">Submit</button>
                            <button type="reset" class="cancel-btn py-2 px-4 border-0 rounded-2"
                                data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>

                        <input type="hidden">
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
                        <div class="col-12 col-md-12">
                            <label class="form-label" for="modalEditUserFirstName">Full Name</label>
                            <input type="text" id="modalEditUserFirstName" name="modalEditUserFirstName"
                                class="form-control" placeholder="John" value="{{ Auth::user()->name }}">
                        </div>
                        <div class="col-12 col-md-12">
                            <label class="form-label" for="modalEditUserEmail">Email</label>
                            <input type="text" id="modalEditUserEmail" name="modalEditUserEmail" class="form-control"
                                placeholder="example@domain.com" value="{{ Auth::user()->email }}" readonly>
                        </div>



                        <div class="col-12 col-md-12">
                            <label class="form-label" for="modalEditUserPhone">Phone Number</label>
                            <div class="input-group">
                                <input type="text" id="modalEditUserPhone" name="modalEditUserPhone"
                                    class="form-control phone-number-mask" placeholder="202 555 0111"
                                    value="{{ Auth::user()->phone }}">
                            </div>
                        </div>




                        {{-- bill address --}}
                        <div class="col-12 col-md-6" style="display: none;">
                            <label class="form-label" for="modalEditUserBillingAddress">Billing Address</label>
                            <div class="input-group">
                                <input type="text" id="modalEditUserBillingAddress" name="modalEditUserBillingAddress"
                                    class="form-control" placeholder="123 Main St, City, Country"
                                    value="{{ Auth::user()->billing_address ?? '' }}">
                            </div>
                        </div>

                        <div class="col-12 text-center">
                            <button type="submit" class="m-btn py-2 px-4 rounded-2 border-0 ">Submit</button>
                            <button type="reset" class="cancel-btn py-2 px-4 rounded-2 border-0"
                                data-bs-dismiss="modal" aria-label="Close">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Cropper Modal -->
    <div class="modal fade" id="cropperModal" tabindex="-1" role="dialog" aria-labelledby="cropperModalLabel" aria-hidden="true">
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
                        <button type="button" class="rotate-left"><i class="ti ti-rotate-clockwise-2"></i> Rotate Left</button>
                        <button type="button" class="rotate-right"><i class="ti ti-rotate"></i> Rotate Right</button>
                        <button type="button" class="flip-horizontal"><i class="ti ti-flip-horizontal"></i> Flip H</button>
                        <button type="button" class="flip-vertical"><i class="ti ti-flip-vertical"></i> Flip V</button>
                        <div class="zoom-controls">
                            <button type="button" class="zoom-in"><i class="ti ti-zoom-in"></i></button>
                            <input type="range" class="zoom-slider" min="0" max="100" value="0">
                            <button type="button" class="zoom-out"><i class="ti ti-zoom-out"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="cancel-btn py-2 px-4 rounded-2 border-0" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="m-btn py-2 px-4 rounded-2 border-0" id="cropButton">Crop & Upload</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = $('#myTable').DataTable();

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

                            // Reload page to reflect changes
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        // Show error message
                        toastr.error('Error updating profile');
                        console.log(xhr.responseText);
                    }
                });
            });

        });
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

        // Handle crop and upload
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

        // Initialize DataTable for notifications
        var notificationsTable = $('#notificationsTable').DataTable({
            responseive: true,
            order: [[3, 'desc']],  // Sort by date column descending
            pageLength: 10,
            language: {
                emptyTable: "No notifications found"
            }
        });
        // Initialize DataTable for notifications
        var activityTable = $('#activityTable').DataTable({
            responseive: true,
            order: [[3, 'desc']],  // Sort by date column descending
            pageLength: 10,
            language: {
                emptyTable: "No Activity found"
            }
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
                        button.closest('tr').find('.readToggle').removeClass('bg-label-warning').addClass('bg-label-success').text('Read');
                        // Remove the mark as read button
                        button.remove();
                        // Show success message
                        toastr.success(response.message || 'Notification marked as read');
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
    </script>
@endpush
