@extends('admin.layouts.app')

@section('title', 'My-Task')
@push('styles')
    <style>
        .glass-box {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.55rem .5rem;
        }

        .nav-link {
            font-size: 13px;
            color: #fff
        }
    </style>
@endpush

@section('content')
    <section class="py-3">

        <ul class="nav nav-pills mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-tab-pane"
                    type="button" role="tab" aria-controls="home-tab-pane" aria-selected="true">Home</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-tab-pane"
                    type="button" role="tab" aria-controls="profile-tab-pane" aria-selected="false">Profile</button>
            </li>
        </ul>
        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="home-tab-pane" role="tabpanel" aria-labelledby="home-tab"
                tabindex="0">
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    @for ($i = 0; $i < 10; $i++)
                        <div class="card p-3 rounded-4 border-0 shadow">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <div class="text-white-50 small mb-1">#4</div>
                                    <span class="badge px-2 py-1 rounded bg-info bg-opacity-25 text-info">Draft</span>
                                </div>
                                <button class="btn btn-sm border-0"
                                    style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;">
                                    <i class="fas fa-arrow-right text-white"></i>
                                </button>
                            </div>

                            <!-- Stats -->
                            <div class="mb-4">
                                <div class="glass-box mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-white-50">Total Inboxes</span>
                                        <span class="fw-bold text-white">300</span>
                                    </div>
                                </div>
                                <div class="glass-box">
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-white-50">Splits</span>
                                        <span class="fw-bold text-white">0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Domain Info -->
                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <div class="glass-box text-center">
                                        <small class="text-white-50 d-block mb-1">Inboxes / Domain</small>
                                        <span class="fw-semibold text-white">1</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="glass-box text-center">
                                        <small class="text-white-50 d-block mb-1">Total Domains</small>
                                        <span class="fw-semibold text-white">300</span>
                                    </div>
                                </div>
                            </div>

                            <!-- User -->
                            <div class="d-flex align-items-center mt-auto">
                                <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="User"
                                    class="rounded-circle border border-info" width="42" height="42">
                                <div class="ms-3">
                                    <p class="mb-0 fw-semibold text-white">Customer User</p>
                                    <small class="text-white-50">07/11/2025</small>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div class="tab-pane fade" id="profile-tab-pane" role="tabpanel" aria-labelledby="profile-tab" tabindex="0">

            </div>
        </div>
    </section>
@endsection

@push('scripts')
@endpush
