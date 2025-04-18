@extends('admin.layouts.app')

@section('title', 'Profile')

@push('styles')
    <style>
        .user-profile-img {
            border: 5px solid var(--secondary-color)
        }

        li span {
            font-size: 14px;
            opacity: .8
        }

        p {
            font-size: 14px
        }

        i {
            font-size: 20px !important;
            opacity: .8
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
    </style>
@endpush

@section('content')
    <section class="py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="user-profile-header-banner">
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/pages/profile-banner.png"
                            width="100%" alt="Banner image" class="rounded-top">
                    </div>
                    <div class="d-flex flex-column flex-lg-row align-items-stretch text-sm-start text-center mb-3">
                        <div class="flex-shrink-0 mx-sm-0 mx-auto" style="margin-top: -2rem">
                            <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                style="inline-size: 160px" alt="user image"
                                class="d-block h-auto ms-0 ms-sm-3 rounded user-profile-img">
                        </div>
                        <div class="flex-grow-1 mt-3">
                            <div
                                class="d-flex align-items-md-end align-items-sm-start align-items-center justify-content-md-between justify-content-start mx-3 flex-md-row flex-column gap-4">
                                <div class="user-profile-info">
                                    <h4 class="mb-2">{{Auth::user()->name}}</h4>
                                    <ul class="list-inline mb-0 d-flex align-items-center flex-wrap justify-content-sm-start justify-content-center gap-4 my-2">
                                        {{-- <li class="opacity-75 d-flex gap-2 align-items-center">
                                            <i class="ti ti-palette fs-5"></i><span class="fw-semibold">UX
                                                Designer</span>
                                        </li>
                                        <li class="opacity-75 d-flex gap-2 align-items-center"><i
                                                class="ti ti-map-pin fs-5"></i>
                                            <span class="fw-semibold">Vatican City</span>
                                        </li> --}}
                                        <li class="opacity-75 d-flex gap-2 align-items-center">
                                            <i class="ti ti-calendar fs-5"></i>
                                            <span class="fw-semibold">Joined {{ \Carbon\Carbon::parse(Auth::user()->created_at)->format('d M Y') }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <a href="javascript:void(0)"
                                    class="m-btn rounded-2 border-0 py-2 px-4 text-decoration-none fs-6">
                                    <i class="ti ti-user-check fs-6 me-2"></i>Connected </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-xl-4 col-lg-5 col-md-5">
                <!-- About User -->
                <div class="card mb-4">
                    <div class="card-body">
                        <p class="card-text text-uppercase opacity-75 fw-lighter">About</p>
                        <ul class="list-unstyled my-3 py-1">
                            <li class="d-flex align-items-center mb-4"><i class="ti ti-user"></i><span
                                    class="fw-semibold mx-2">Full Name:</span> <span>{{Auth::user()->name}}</span></li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-check"></i>
                                <span class="fw-semibold mx-2">Status:</span> <span>{{Auth::user()->status == '1' ? 'Active' : 'In-Active'}}</span>
                            </li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-crown"></i>
                                <span class="fw-semibold mx-2">Role:</span>
                                <span>{{Auth::user()->role->name}}</span>
                            </li>
                            {{-- <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-flag"></i>
                                <span class="fw-semibold mx-2">Country:</span> <span>USA</span>
                            </li>
                            <li class="d-flex align-items-center mb-2">
                                <i class="ti ti-language"></i>
                                <span class="fw-semibold mx-2">Languages:</span> <span>English</span>
                            </li> --}}
                        </ul>
                        <p class="card-text text-uppercase opacity-75 fw-lighter">Contacts</p>
                        <ul class="list-unstyled my-3 py-1">
                            <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-phone-call"></i>
                                <span class="fw-semibold mx-2">Contact:</span>
                                <span>{{Auth::user()->phone}}</span>
                            </li>
                            {{--<li class="d-flex align-items-center mb-4">
                                <i class="ti ti-brand-skype"></i><span class="fw-semibold mx-2">Skype:</span>
                                <span>john.doe</span>
                            </li>--}}
                            <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-mail"></i>
                                <span class="fw-semibold mx-2">Email:</span>
                                <span>{{Auth::user()->email}}</span>
                            </li>
                        </ul>
                        {{--<p class="card-text text-uppercase opacity-75 fw-lighter">Teams</p>
                        <ul class="list-unstyled mb-0 mt-3 pt-1">
                            <li class="d-flex flex-wrap mb-4"><span class="fw-semibold me-2">Backend
                                    Developer</span><span>(126 Members)</span></li>
                            <li class="d-flex flex-wrap"><span class="fw-semibold me-2">React Developer</span><span>(98
                                    Members)</span></li>
                        </ul>--}}
                    </div>
                </div>
                <!--/ About User -->
            </div>
            <div class="col-xl-8 col-lg-7 col-md-7">
                <!-- Activity Timeline -->
                <div class="card p-2 mb-4">
                    <div class="card-header border-0">
                        <h5 class="card-action-title mb-0">
                            <i class="ti ti-chart-bar opacity-100 me-2 fs-3"></i>Activity Timeline</h5>
                    </div>
                    <div class="card-body pt-3">
                        <ul class="timeline mb-0 list-unstyled">
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-primary"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">12 Invoices have been paid</h6>
                                        <small class="">12 min ago</small>
                                    </div>
                                    <p class="mb-2">Invoices have been paid to the company</p>
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="badge bg-lighter rounded d-flex align-items-center">
                                            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets//img/icons/misc/pdf.png"
                                                alt="img" width="15" class="me-2">
                                            <span class="mb-0">invoices.pdf</span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-success"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header d-flex align-items-center justify-content-between mb-3">
                                        <h6 class="mb-0">Client Meeting</h6>
                                        <small class="">45 min ago</small>
                                    </div>
                                    <p class="mb-2">Project meeting with john @10:15am</p>
                                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                                        <div class="d-flex flex-wrap align-items-center mb-50">
                                            <div class="avatar avatar-sm me-2">
                                                <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/1.png" width="30" alt="Avatar"
                                                    class="rounded-circle">
                                            </div>
                                            <div>
                                                <p class="mb-0 small fw-semibold">Lester McCarthy (Client)</p>
                                                <small>CEO of Pixinvent</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <!-- Activity Timeline -->

                <!-- Overview -->
                <div class="card">
                    <div class="card-body">
                        <p class="card-text text-uppercase ">Overview</p>
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center mb-4"><i class="ti ti-check"></i><span
                                    class="fw-semibold mx-2">Task
                                    Compiled:</span> <span>13.5k</span></li>
                            <li class="d-flex align-items-center mb-4"><i class="ti ti-apps"></i><span
                                    class="fw-semibold mx-2">Projects Compiled:</span> <span>146</span></li>
                            <li class="d-flex align-items-center"><i class="ti ti-users"></i><span
                                    class="fw-semibold mx-2">Connections:</span> <span>897</span></li>
                        </ul>
                    </div>
                </div>
                <!--/ Overview -->
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@endpush
