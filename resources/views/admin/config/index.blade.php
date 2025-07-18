@extends('admin.layouts.app')

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
        outline: 3px solid #506295;
        border-radius: 50%;
        opacity: 1
    }

   
    .bg-lighter {
        background-color: #ffffff1d;
        padding: .3rem;
        border-radius: 4px;
        color: var(--extra-light)
    }

    .timeline:not(.timeline-center) {
        padding-inline-start: .5rem;
    }
</style>
@endpush

@section('content')
<div class="row py-4">

    <div class="col-xl-8 col-lg-5">
        <ul class="nav nav-tabs border-0" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="chargebee-tab" data-bs-toggle="tab"
                    data-bs-target="#chargebee_configuration_tab-pane" type="button" role="tab" aria-controls="chargebee_configuration_tab-pane"
                    aria-selected="false"><i class="fa-solid fa-unlock"></i> Chargebee Configuration</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="plan-tab" data-bs-toggle="tab" data-bs-target="#plans-tab-pane"
                    type="button" role="tab" aria-controls="notify-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-gear"></i> Plan Configuration</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notify-tab" data-bs-toggle="tab" data-bs-target="#backup-tab-pane"
                    type="button" role="tab" aria-controls="backup-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-file"></i> System Backup</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="configuration-pane" data-bs-toggle="tab" data-bs-target="#system_configuration-pane"
                    type="button" role="tab" aria-controls="system_configuration-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-file"></i> System Configuration</button>
            </li>
        </ul>

        <div class="tab-content mt-4" id="myTabContent">
            <div class="tab-pane fade show active" id="account-tab-pane" role="tabpanel" aria-labelledby="account-tab"
                tabindex="0">
            </div>


            <div class="tab-pane fade active show" id="chargebee_configuration_tab-pane" role="tabpanel"
                aria-labelledby="chargebe_configuration-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Chargebee Configuration</h5>
                    <div class="card-body">
                        <form id="chargebeeConfigForm">
                            <div class="alert text-warning alert-dismissible"
                                style="background-color: rgba(255, 166, 0, 0.189)" role="alert">
                                <h5 class="alert-heading mb-1">Note</h5>
                                <span>Only one environment can be active at a time. Provide credentials accordingly.</span>
                                <button type="button" class="btn-close text-warning" data-bs-dismiss="alert"
                                        aria-label="Close"></button>
                            </div>

                            {{-- Environment Toggles --}}
                            <div class="row mb-4">
                                <div class="col-sm-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="liveSwitch">
                                        <label class="form-check-label" for="liveSwitch">Use Live Environment</label>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="sandboxSwitch">
                                        <label class="form-check-label" for="sandboxSwitch">Use Sandbox Environment</label>
                                    </div>
                                </div>
                            </div>

                            {{-- Live Credentials --}}
                            <div class="row gx-3">
                                <div class="col-md-6 mb-3">
                                    <label for="liveSiteKey" class="form-label">Live Site Key</label>
                                    <input type="text" id="liveSiteKey" name="liveSiteKey" class="form-control" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="liveSecretKey" class="form-label">Live Secret Key</label>
                                    <input type="text" id="liveSecretKey" name="liveSecretKey" class="form-control" disabled>
                                </div>
                            </div>

                            {{-- Sandbox Credentials --}}
                            <div class="row gx-3">
                                <div class="col-md-6 mb-3">
                                    <label for="sandboxSiteKey" class="form-label">Sandbox Site Key</label>
                                    <input type="text" id="sandboxSiteKey" name="sandboxSiteKey" class="form-control" disabled>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="sandboxSecretKey" class="form-label">Sandbox Secret Key</label>
                                    <input type="text" id="sandboxSecretKey" name="sandboxSecretKey" class="form-control" disabled>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>



           <div class="tab-pane fade" id="plans-tab-pane" role="tabpanel" aria-labelledby="plans-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Plan Configuration</h5>
                    <div class="card-body">
                        <form id="planConfigForm">
                            <div class="row gx-4">
                                {{-- Split Limit Value --}}
                                <div class="col-md-6 mb-4">
                                    <label for="splitLimitValue" class="form-label">Split Limit Value</label>
                                    <input type="number" step="0.01" id="splitLimitValue" name="splitLimitValue" class="form-control"
                                        placeholder="Enter split limit">
                                </div>

                                {{-- Chargebee Product Family ID --}}
                                <div class="col-md-6 mb-4">
                                    <label for="productFamilyId" class="form-label">Chargebee Product Family ID</label>
                                    <input type="text" id="productFamilyId" name="productFamilyId" class="form-control"
                                        placeholder="Enter Chargebee Product Family ID">
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="backup-tab-pane" role="tabpanel" aria-labelledby="backup-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Backup</h5>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Backup Title</th>
                                        <th>Occurred At</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Daily Backup - July 16</td>
                                        <td>16 Jul 2025, 03:00 AM</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#">
                                                            <i class="fa fa-download me-2 text-success"></i>Download
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger">
                                                            <i class="fa fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Weekly Backup - July 14</td>
                                        <td>14 Jul 2025, 01:15 AM</td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                                                        data-bs-toggle="dropdown" aria-expanded="false">
                                                    Actions
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="#">
                                                            <i class="fa fa-download me-2 text-success"></i>Download
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <button type="button" class="dropdown-item text-danger">
                                                            <i class="fa fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Empty state -->
                                    <!-- <tr>
                                        <td colspan="3" class="text-center text-muted">No backups found.</td>
                                    </tr> -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="system_configuration-pane" role="tabpanel"
     aria-labelledby="system_configuration-tab" tabindex="0">
    <div class="card mb-4 p-3">
        <h5 class="card-header">System Configuration</h5>
        <div class="card-body">
            <form id="systemConfigForm">
                <div class="row gx-4">
                    <div class="col-md-6 mb-3">
                        <label for="systemName" class="form-label">System Name</label>
                        <input type="text" class="form-control" id="systemName" name="systemName" placeholder="My Application">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="adminEmail" class="form-label">Admin Email</label>
                        <input type="email" class="form-control" id="adminEmail" name="adminEmail" placeholder="admin@example.com">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="supportEmail" class="form-label">Support Email</label>
                        <input type="email" class="form-control" id="supportEmail" name="supportEmail" placeholder="support@example.com">
                    </div>

                      <div class="col-md-6 mb-3">
                        <label for="logo" class="form-label">System Logo</label>
                        <input type="file" class="form-control" id="logo" name="logo">
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="maintenanceMode" name="maintenanceMode">
                            <label class="form-check-label" for="maintenanceMode">Enable Maintenance Mode</label>
                        </div>
                    </div>

                  

                    <div class="col-md-12 mb-3">
                        <label for="footerText" class="form-label">Footer Text</label>
                        <textarea class="form-control" id="footerText" name="footerText" rows="3"
                                  placeholder="© 2025 My Application. All rights reserved."></textarea>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                </div>
            </form>
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
                                        <input name="customRadioIcon" class="form-check-input rounded-1" type="radio"
                                            value="" id="customRadioHome" checked="">
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
                                        <input name="customRadioIcon" class="form-check-input rounded-1" type="radio"
                                            value="" id="customRadioOffice">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressFirstName">First Name</label>
                        <input type="text" id="modalAddressFirstName" name="modalAddressFirstName" class="form-control"
                            placeholder="John">
                        <div
                            class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressLastName">Last Name</label>
                        <input type="text" id="modalAddressLastName" name="modalAddressLastName" class="form-control"
                            placeholder="Doe">
                        <div
                            class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="modalAddressCountry">Country</label>
                        <div class="position-relative">
                            <div class="position-relative"><select id="modalAddressCountry" name="modalAddressCountry"
                                    class="select2 form-select select2-hidden-accessible" data-allow-clear="true"
                                    tabindex="-1" aria-hidden="true" data-select2-id="modalAddressCountry">
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
                                </select><span class="select2 select2-container select2-container--default" dir="ltr"
                                    data-select2-id="81" style="width: auto;"><span class="selection"><span
                                            class="select2-selection select2-selection--single" role="combobox"
                                            aria-haspopup="true" aria-expanded="false" tabindex="0"
                                            aria-disabled="false"
                                            aria-labelledby="select2-modalAddressCountry-container"><span
                                                class="select2-selection__rendered"
                                                id="select2-modalAddressCountry-container" role="textbox"
                                                aria-readonly="true"><span class="select2-selection__placeholder">Select
                                                    value</span></span><span class="select2-selection__arrow"
                                                role="presentation"><b
                                                    role="presentation"></b></span></span></span><span
                                        class="dropdown-wrapper" aria-hidden="true"></span></span></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="modalAddressAddress1">Address Line 1</label>
                        <input type="text" id="modalAddressAddress1" name="modalAddressAddress1" class="form-control"
                            placeholder="12, Business Park">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="modalAddressAddress2">Address Line 2</label>
                        <input type="text" id="modalAddressAddress2" name="modalAddressAddress2" class="form-control"
                            placeholder="Mall Road">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressLandmark">Landmark</label>
                        <input type="text" id="modalAddressLandmark" name="modalAddressLandmark" class="form-control"
                            placeholder="Nr. Hard Rock Cafe">
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
                        <input type="text" id="modalAddressZipCode" name="modalAddressZipCode" class="form-control"
                            placeholder="99950">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input rounded-1" id="billingAddress">
                            <label for="billingAddress" class="form-switch-label">Use as a billing address?</label>
                        </div>
                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" class="m-btn py-2 px-4 border-0 rounded-2">Submit</button>
                        <button type="reset" class="cancel-btn py-2 px-4 border-0 rounded-2" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
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
                                class="form-control phone-number-mask" placeholder="" value="{{ Auth::user()->phone }}">
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
                <button type="button" class="cancel-btn py-2 px-4 rounded-2 border-0"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="m-btn py-2 px-4 rounded-2 border-0" id="cropButton">Crop & Upload</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const liveSwitch = document.getElementById("liveSwitch");
        const sandboxSwitch = document.getElementById("sandboxSwitch");

        const liveSiteKey = document.getElementById("liveSiteKey");
        const liveSecretKey = document.getElementById("liveSecretKey");

        const sandboxSiteKey = document.getElementById("sandboxSiteKey");
        const sandboxSecretKey = document.getElementById("sandboxSecretKey");

        function updateFields() {
            if (liveSwitch.checked) {
                sandboxSwitch.checked = false;
                liveSiteKey.disabled = false;
                liveSecretKey.disabled = false;
                sandboxSiteKey.disabled = true;
                sandboxSecretKey.disabled = true;
            } else if (sandboxSwitch.checked) {
                liveSwitch.checked = false;
                sandboxSiteKey.disabled = false;
                sandboxSecretKey.disabled = false;
                liveSiteKey.disabled = true;
                liveSecretKey.disabled = true;
            } else {
                liveSiteKey.disabled = true;
                liveSecretKey.disabled = true;
                sandboxSiteKey.disabled = true;
                sandboxSecretKey.disabled = true;
            }
        }

        liveSwitch.addEventListener("change", updateFields);
        sandboxSwitch.addEventListener("change", updateFields);

        updateFields(); // run once on load
    });
</script>
@endpush