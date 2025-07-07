@extends('admin.layouts.app')

@section('title', 'Orders-Queue')

@push('styles')
    <style>
        input,
        .form-control,
        .form-label {
            font-size: 12px
        }

        small {
            font-size: 11px
        }

        .total {
            color: var(--second-primary);
        }


        .used {
            color: #43C95C;
        }

        .remain {
            color: orange
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .table>:not(caption)>*>* {
            border-bottom-width: 0 !important
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Loading state styling */
        #loadingState {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 2rem;
        }

        /* Fix offcanvas backdrop issues */
        .offcanvas-backdrop {
            transition: opacity 0.15s linear !important;
        }

        .offcanvas-backdrop.fade {
            opacity: 0;
        }

        .offcanvas-backdrop.show {
            opacity: 0.5;
        }

        /* Ensure body doesn't keep backdrop classes */
        body:not(.offcanvas-open) {
            overflow: visible !important;
            padding-right: 0 !important;
        }

        /* Fix any remaining backdrop elements */
        .modal-backdrop,
        .offcanvas-backdrop.fade:not(.show) {
            display: none !important;
        }

        .flip-card {
            position: relative;
            width: 15px;
            height: 15px;
            perspective: 1000px;
            font-family: "Space Grotesk";
        }

        .flip-inner {
            position: absolute;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 0.6s ease-in-out;
        }

        .flip-front,
        .flip-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            background: linear-gradient(to bottom, #eee 50%, #ccc 50%);
            border-radius: 2px;
            font-size: 12px;
            font-weight: bold;
            color: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            /* border: 1px solid #aaa; */
        }

        .flip-front {
            z-index: 2;
        }

        .flip-back {
            transform: rotateX(180deg);
        }

        .card-draft {
            background-color: rgba(0, 225, 255, 0.037);
        }
    </style>
@endpush

@section('content')
    <section class="py-3">

        <!-- Advanced Search Filter UI -->
        <div class="card p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#filter_1"
                role="button" aria-expanded="false" aria-controls="filter_1">
                <div>
                    <div class="d-flex gap-2 align-items-center">
                        <h6 class="text-uppercase fs-6 mb-0">Filters</h6>
                        <img src="https://static.vecteezy.com/system/resources/previews/052/011/341/non_2x/3d-white-down-pointing-backhand-index-illustration-png.png"
                            width="30" alt="">
                    </div>
                    <small>Click here to open advance search for orders</small>
                </div>
            </div>

            <div class="row collapse" id="filter_1">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Order ID</label>
                            <input type="text" name="order_id" class="form-control" placeholder="Enter order ID">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="unallocated">Unallocated</option>
                                <option value="allocated">Allocated</option>
                                <option value="in-progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Min Inboxes</label>
                            <input type="number" name="min_inboxes" class="form-control" placeholder="e.g. 10">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Max Inboxes</label>
                            <input type="number" name="max_inboxes" class="form-control" placeholder="e.g. 100">
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" id="resetFilters"
                                class="btn btn-outline-secondary btn-sm me-2 px-3">Reset</button>
                            <button type="submit" id="submitBtn"
                                class="btn btn-primary btn-sm border-0 px-3">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <ul class="nav nav-pills mb-3 border-0" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 text-capitalize text-white active" id="in-queue-tab" data-bs-toggle="tab"
                    data-bs-target="#in-queue-tab-pane" type="button" role="tab" aria-controls="in-queue-tab-pane"
                    aria-selected="true">in-queue</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 text-capitalize text-white" id="in-draft-tab" data-bs-toggle="tab"
                    data-bs-target="#in-draft-tab-pane" type="button" role="tab" aria-controls="in-draft-tab-pane"
                    aria-selected="false">in-draft</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="in-queue-tab-pane" role="tabpanel" aria-labelledby="in-queue-tab"
                tabindex="0">
                <div class="mb-4"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">


                    @for ($i = 0; $i < 10; $i++)
                        <div class="card p-3 overflow-hidden" style="border-bottom: 4px solid orange">
                            <div style="position: relative; z-index: 9;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <h6 class="mb-0">#92</h6>
                                        <span class="text-warning small">
                                            <i class="fa-solid fa-spinner text-warning"></i>
                                            Pending
                                        </span>
                                    </div>

                                    <div id="flip-timer-{{ $i }}" class="flip-timer"
                                        style="display: flex; gap: 4px;">
                                    </div>

                                </div>

                                <div class="d-flex flex-column gap-0">
                                    <h6 class="mb-0">
                                        Total Inboxes : <span class="text-white number ">5000</span>
                                    </h6>
                                </div>

                                <div class="my-4">
                                    <div class="content-line d-flex align-items-center justify-content-between">
                                        <div class="d-flex flex-column">
                                            <small>Inboxes/Domain</small>
                                            <small class="small">100</small>
                                        </div>
                                        <div class="d-flex flex-column align-items-end">
                                            <small>Total Domains</small>
                                            <small class="small">5000</small>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center mt-1">
                                        <span
                                            style="height: 11px; width: 11px; border-radius: 50px; border: 3px solid #fff; background-color: var(--second-primary)"></span>
                                        <span style="height: 1px; width: 100%; background-color: orange;"></span>
                                        <span
                                            style="height: 11px; width: 11px; border-radius: 50px; border: 3px solid #fff; background-color: var(--second-primary)"></span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-1">
                                        <img src="https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg"
                                            width="40" height="40" class="object-fit-cover"
                                            style="border-radius: 50px" alt="">
                                        <div class="d-flex flex-column gap-0">
                                            <h6 class="mb-0">Hamza Ashfaq</h6>
                                            <small>7/3/2025</small>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-center"
                                        style="height: 30px; width: 30px; border-radius: 50px; background-color: var(--second-primary); cursor: pointer;"
                                        onclick="viewOrderSplits(${order.order_id})" data-bs-toggle="offcanvas"
                                        data-bs-target="#order-splits-view">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor


                    <!-- Load More Button -->
                    <div id="loadMoreContainer" class="text-center mt-4" style="display: none;">
                        <button id="loadMoreBtn" class="btn btn-lg btn-primary px-4 me-2 border-0 animate-gradient">
                            <span id="loadMoreText">Load More</span>
                            <span id="loadMoreSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                                style="display: none;">
                                <span class="visually-hidden">Loading...</span>
                            </span>
                        </button>
                        <div id="paginationInfo" class="mt-2 text-light small">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span
                                id="totalOrders">0</span> orders
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="in-draft-tab-pane" role="tabpanel" aria-labelledby="in-draft-tab"
                tabindex="0">
                <div class="mb-4"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">


                    @for ($i = 0; $i < 10; $i++)
                        <div class="card p-3 overflow-hidden" style="border-bottom: 4px solid rgb(0, 221, 255)">
                            <div style="position: relative; z-index: 9;">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <h6 class="mb-0">#92</h6>
                                        <span class="text-info small">
                                            <i class="fa-solid fa-spinner text-info"></i>
                                            Draft
                                        </span>
                                    </div>

                                    <div id="flip-timer-{{ $i }}" class="flip-timer"
                                        style="display: flex; gap: 4px;">
                                    </div>

                                </div>

                                <div class="d-flex flex-column gap-0">
                                    <h6 class="mb-0">
                                        Total Inboxes : <span class="text-white number ">5000</span>
                                    </h6>
                                </div>

                                <div class="my-4">
                                    <div class="content-line d-flex align-items-center justify-content-between">
                                        <div class="d-flex flex-column">
                                            <small>Inboxes/Domain</small>
                                            <small class="small">100</small>
                                        </div>
                                        <div class="d-flex flex-column align-items-end">
                                            <small>Total Domains</small>
                                            <small class="small">5000</small>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center mt-1">
                                        <span
                                            style="height: 11px; width: 11px; border-radius: 50px; border: 3px solid #fff; background-color: var(--second-primary)"></span>
                                        <span style="height: 1px; width: 100%; background-color: rgb(0, 242, 255);"></span>
                                        <span
                                            style="height: 11px; width: 11px; border-radius: 50px; border: 3px solid #fff; background-color: var(--second-primary)"></span>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-1">
                                        <img src="https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg"
                                            width="40" height="40" class="object-fit-cover"
                                            style="border-radius: 50px" alt="">
                                        <div class="d-flex flex-column gap-0">
                                            <h6 class="mb-0">Hamza Ashfaq</h6>
                                            <small>7/3/2025</small>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-center"
                                        style="height: 30px; width: 30px; border-radius: 50px; background-color: var(--second-primary); cursor: pointer;"
                                        onclick="viewOrderSplits(${order.order_id})" data-bs-toggle="offcanvas"
                                        data-bs-target="#order-splits-view">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endfor


                    <!-- Load More Button -->
                    <div id="loadMoreContainer" class="text-center mt-4" style="display: none;">
                        <button id="loadMoreBtn" class="btn btn-lg btn-primary px-4 me-2 border-0 animate-gradient">
                            <span id="loadMoreText">Load More</span>
                            <span id="loadMoreSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                                style="display: none;">
                                <span class="visually-hidden">Loading...</span>
                            </span>
                        </button>
                        <div id="paginationInfo" class="mt-2 text-light small">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span
                                id="totalOrders">0</span> orders
                        </div>
                    </div>
                </div>
            </div>
        </div>





    </section>

    <!-- Order Details Offcanvas -->
    <div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-splits-view"
        aria-labelledby="order-splits-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="order-splits-viewLabel">Order Details</h5>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div id="orderSplitsContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="splitsLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading order details...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function createFlipCard(initial) {
            const card = document.createElement('div');
            card.className = 'flip-card';
            card.innerHTML = `
        <div class="flip-inner">
          <div class="flip-front">${initial}</div>
          <div class="flip-back">${initial}</div>
        </div>
      `;
            return card;
        }

        function updateFlipCard(card, newVal) {
            const inner = card.querySelector('.flip-inner');
            const front = card.querySelector('.flip-front');
            const back = card.querySelector('.flip-back');

            if (front.textContent === newVal) return;

            back.textContent = newVal;
            inner.style.transform = 'rotateX(180deg)';

            setTimeout(() => {
                front.textContent = newVal;
                inner.style.transition = 'none';
                inner.style.transform = 'rotateX(0deg)';
                setTimeout(() => {
                    inner.style.transition = 'transform 0.6s ease-in-out';
                }, 20);
            }, 600);
        }

        function startTimer(containerId, durationSeconds) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const digitElements = [];

            const formatTime = (s) => {
                const h = Math.floor(s / 3600).toString().padStart(2, '0');
                const m = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
                const sec = (s % 60).toString().padStart(2, '0');
                return h + m + sec;
            };

            const initial = formatTime(durationSeconds);
            for (let i = 0; i < initial.length; i++) {
                const card = createFlipCard(initial[i]);
                container.appendChild(card);
                digitElements.push(card);

                if (i === 1 || i === 3) {
                    const colon = document.createElement('div');
                    colon.textContent = ':';
                    colon.style.cssText = 'font-size: 20px; line-height: 10px; color: white;';
                    container.appendChild(colon);
                }
            }

            let current = durationSeconds;

            function update() {
                if (current < 0) return clearInterval(timer);

                if (current <= 3600) {
                    container.classList.add('time-danger');
                } else {
                    container.classList.remove('time-danger');
                }

                const timeStr = formatTime(current);
                for (let i = 0; i < 6; i++) {
                    updateFlipCard(digitElements[i], timeStr[i]);
                }
                current++;
            }

            update();
            const timer = setInterval(update, 1000);
        }

        // Init all timers after DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            // Example: run 10 timers with different or same durations
            for (let i = 0; i < 10; i++) {
                const timerId = `flip-timer-${i}`;
                const duration = 1 * 60 * 60; // or set different duration if needed
                startTimer(timerId, duration);
            }
        });
    </script>
@endpush
