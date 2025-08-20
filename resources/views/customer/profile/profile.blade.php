@extends('customer.layouts.app')

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

        .profile-card {
            position: relative;
            overflow: hidden;
            color: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: rgba(0, 0, 0, 0.83) 0px 2px 4px, rgba(0, 0, 0, 0.728) 0px 7px 13px -3px, rgba(0, 0, 0, 0.949) 0px -3px 0px inset;
        }

        /* Shine effect using ::before */
        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -75%;
            width: 50%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.216) 50%, rgba(255, 255, 255, 0.05) 100%);
            filter: blur(100px);
            transform: skewX(-25deg);
            animation: profileShine 2s infinite;
        }

        /* Shine animation keyframes */
        @keyframes profileShine {
            0% {
                left: -75%;
            }

            100% {
                left: 125%;
            }
        }
    </style>
@endpush

@section('content')
    <section class="py-4">
        {{-- <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="user-profile-header-banner">
                    <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/pages/profile-banner.png"
                        width="100%" alt="Banner image" class="rounded-top">
                </div>

                </div>
            </div>
        </div> --}}

        <div class="row mt-4 justify-content-center">
            <div class="col-xl-6 col-lg-7 col-md-8">
                <!-- About User -->
                <div class="card mb-4 profile-card p-3">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <div class="d-flex flex-column align-items-center">
                            @php
                                $user = Auth::user();
                                if (!empty($user->name)) {
                                    $initials = collect(explode(' ', $user->name))
                                        ->filter()
                                        ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                } else {
                                    $initials = strtoupper(substr($user->email, 0, 2));
                                }
                            @endphp

                            <div class="flex-shrink-0 mx-sm-0 mx-auto">
                                @if ($user->profile_image)
                                    <img src="{{ asset('storage/profile_images/' . $user->profile_image) }}"
                                        style="inline-size: 160px; border-radius: 50%" alt="user image"
                                        class="d-block h-auto user-profile-img">
                                    {{-- <i class="fa-regular fa-user fs-2"></i> --}}
                                @else
                                    <div class="d-flex justify-content-center align-items-center text-white fw-bold"
                                        style="width: 160px; height: 160px; font-size: 48px; border-radius: 50%; background-color: #007bff;">
                                        {{ $initials }}
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex flex-column align-items-center">
                                <div class="user-profile-info">
                                    <h4 class="my-2 text-capitalize text-nowrap">{{ Auth::user()->name }}</h4>
                                    <ul class="list-inline">

                                        <li class="opacity-75 d-flex gap-2 align-items-center">
                                            <i class="ti ti-calendar fs-5"></i>
                                            <span class="fw-semibold">Joined
                                                {{ \Carbon\Carbon::parse(Auth::user()->created_at)->format('d M Y') }}</span>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </div>

                        <ul class="mb-0">
                            <li class="d-flex align-items-center mb-4"><i class="ti ti-user"></i>
                            <span class="fw-semibold mx-2">First Name:</span>
                                <span
                                    class="opacity-50 text-capitalize">{{ Auth::user()->name }}</span></li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-check"></i>
                                <span class="fw-semibold mx-2">Status:</span>
                                <span class="opacity-50 text-capitalize">{{ Auth::user()->status == '1' ? 'Active' : 'In-Active' }}</span>
                            </li>
                            <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-crown"></i>
                                <span class="fw-semibold mx-2">Role:</span>
                                <span class="opacity-50 text-capitalize">{{ Auth::user()->role->name }}</span>
                            </li>

                            <!-- <li class="d-flex align-items-center mb-4">
                                <i class="ti ti-phone-call"></i>
                                <span class="fw-semibold mx-2">Contact:</span>
                                <span class="opacity-50 text-capitalize">{{ Auth::user()->phone ?? 'N/A' }}</span>
                            </li> -->

                            <li class="d-flex align-items-center">
                                <i class="ti ti-mail"></i>
                                <span class="fw-semibold mx-2">Email:</span>
                                <span class="opacity-50 text-capitalize">{{ Auth::user()->email }}</span>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
            <div class="col-xl-8 col-lg-7 col-md-7 d-none">
                <!-- Activity Timeline -->
                {{-- <div class="card p-2 mb-4 overflow-y-auto" style="max-height: 37rem">
                <div class="card-header border-0">
                    <h5 class="card-action-title mb-0">
                        <i class="ti ti-chart-bar opacity-100 me-2 fs-3"></i>Activity Timeline
                    </h5>
                </div>
                <div class="card-body pt-3">
                    <ul class="timeline mb-0 list-unstyled">
                        @forelse(Auth::user()->logs()->latest()->take(5)->get() as $log)
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                                <div class="timeline-header d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0">{{ $log->description }}</h6>
                                    <small class="">{{ $log->created_at->diffForHumans() }}</small>
                                </div>
                                <p class="mb-2">{{ $log->action_type }}</p>
                                @if ($log->data)
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-lighter rounded d-flex align-items-center">
                                        @if (isset($log->data['file']))
                                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/icons/misc/pdf.png"
                                            alt="img" width="15" class="me-2">
                                        <span class="mb-0">{{ $log->data['file'] }}</span>
                                        @else
                                        <span class="mb-0">{{ json_encode($log->data) }}</span>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </li>
                        @empty
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                                <div class="timeline-header">
                                    <h6 class="mb-0">No activity logs found</h6>
                                </div>
                            </div>
                        </li>
                        @endforelse
                    </ul>
                </div>
            </div> --}}
                <!-- Activity Timeline -->

                <!-- Overview -->
                <!-- <div class="card">
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
                        </div> -->
                <!--/ Overview -->
            </div>
        </div>
    </section>
@endsection

@push('scripts')
@endpush
