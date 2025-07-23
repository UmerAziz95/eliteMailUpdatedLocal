@extends('admin.layouts.app')

@section('title', 'My-Task')
@push('styles')
<style>
    .nav-link {
        color: #fff;
        font-size: 13px
    }
</style>
@endpush

@section('content')
    <section class="py-3">
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home"
                    type="button" role="tab" aria-controls="pills-home" aria-selected="true">Home</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-profile-tab" data-bs-toggle="pill" data-bs-target="#pills-profile"
                    type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Profile</button>
            </li>
        </ul>
        
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab"
                tabindex="0">
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    @for ($i = 0; $i < 10; $i++)
                        <div class="card p-3">
                            <!-- Header -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-semibold">#4</span>
                                <span class="badge bg-info text-dark fw-semibold">Draft</span>
                            </div>

                            <!-- Stats -->
                            <div class="row mb-3">
                                <div class="col-6">
                                    <p class="mb-1 small">Total Inboxes</p>
                                    <h4 class="fw-bold mb-0">300</h4>
                                </div>
                                <div class="col-6 text-end">
                                    <p class="mb-1 small">Splits</p>
                                    <h4 class="fw-bold mb-0">0</h4>
                                </div>
                            </div>

                            <!-- Inboxes & Domains -->
                            <div class="row mb-3">
                                <div class="col">
                                    <div style="background-color: rgba(0, 0, 0, 0.398); border: 1px solid #464646;"
                                        class="rounded py-2 px-3 d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-1 small">Inboxes/Domain</p>
                                            <h6 class="fw-semibold mb-0">1</h6>
                                        </div>
                                        <div class="text-end">
                                            <p class="mb-1 small">Total Domains</p>
                                            <h6 class="fw-semibold mb-0">300</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer with User -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center gap-2">
                                    <div>
                                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="User"
                                            style="border-radius: 50px" width="40" height="40">
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-semibold">Customer User</p>
                                        <small>07/11/2025</small>
                                    </div>
                                </div>
                                <div>
                                    <button class="btn btn-primary btn-sm d-flex align-items-center justify-content-center">
                                        <i class="fas fa-arrow-right text-white"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="tab-pane fade" id="pills-profile" role="tabpanel" aria-labelledby="pills-profile-tab"
                tabindex="0">...</div>
        </div>
    </section>
@endsection

@push('scripts')
@endpush
