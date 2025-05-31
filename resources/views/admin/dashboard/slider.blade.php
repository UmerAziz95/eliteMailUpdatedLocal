<div class="swiper w-100 h-100 swiper-container">
    <div class="swiper-wrapper w-100">
        @forelse($recentOrders ?? [] as $order)
        <div class="swiper-slide d-flex align-items-start px-4 py-3 justify-content-between">
            <div class="w-100">
                <div class="d-flex align-items-center justify-content-between gap-4">
                    <!-- Order Header with Status Badge -->
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-1" style="text-shadow: 0 1px 2px rgba(0,0,0,0.25); animation: fadeIn 0.5s ease;">
                        <i class="ti ti-shopping-cart fs-5"></i>
                        <span class="opacity-75">#{{ $order->id ?? 'N/A' }}</span>
                    </h6>
                    <div>
                        @php
                            $statusName = strtolower($order->status_manage_by_admin ?? 'pending');
                            $status = \App\Models\Status::where('name', $statusName)->first();
                            $statusClass = $status ? $status->badge : 'info';
                            $statusColor = 'bg-label-' . $statusClass;
                        @endphp
                        <!-- <span class="badge {{ $statusColor }} rounded-pill px-3">{{ ucfirst($order->status ?? 'Pending') }}</span> -->
                    </div>
                </div>
                
                <!-- Plan Name with Animation -->
                <small class="fw-semibold py-1 px-2 rounded-1 text-white" style="background-color: var(--second-primary)">{{ $order->plan->name ?? '' }}</small>
                <div class="plan-badge">
                    <small class="d-block opacity-50" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">
                        <i class="ti ti-calendar me-1 opacity-50"></i> {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}
                    </small>
                </div>
                </div>
                
                <div class="mt-3">
                    <!-- Customer Information -->
                    <div class="customer-info d-flex align-items-center mb-3 gap-2" style="animation: fadeInRight 0.6s ease;">
                        <div class="d-flex align-items-center justify-content-center" style="background-color: var(--second-primary); height: 40px; width: 40px; border-radius: 50px;">
                            <i class="ti ti-user fs-4" style="filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));"></i>
                        </div>
                        <div class="d-flex flex-column gap-0">
                            <h6 class="fw-semibold mb-0" style="text-shadow: 0 1px 1px rgba(0,0,0,0.2);">{{ $order->user->name ?? 'N/A' }}</h6>
                            <small class="opacity-50" style="font-weight: 500;">{{ $order->user->email ?? '' }}</small>
                        </div>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="row gy-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 content-item" style="animation: fadeInUp 0.4s ease forwards; animation-delay: 0.1s; opacity: 0; transform: translateY(10px);">
                                <div class="badge icon rounded bg-primary bg-opacity-20 p-2" style="box-shadow: 0 3px 6px rgba(0,0,0,0.1); transform-origin: center; transition: all 0.3s ease;">
                                    <i class="ti ti-mail fs-6" style="filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));"></i>
                                </div>
                                <div class="d-flex flex-column gap-0">
                                    <small class="d-block opacity-50" style="font-weight: 500;">Total Inboxes</small>
                                    <span class="fw-semibold" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">{{  optional($order->reorderInfo->first())->total_inboxes ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 content-item" style="animation: fadeInUp 0.4s ease forwards; animation-delay: 0.2s; opacity: 0; transform: translateY(10px);">
                                <div class="badge rounded bg-info icon bg-opacity-20 p-2" style="box-shadow: 0 3px 6px rgba(0,0,0,0.1); transform-origin: center; transition: all 0.3s ease;">
                                    <i class="ti ti-credit-card fs-6" style="filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));"></i>
                                </div>
                                <div class="d-flex flex-column gap-0">
                                    <small class="d-block opacity-50" style="font-weight: 500;">Amount</small>
                                    <span class="fw-semibold" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">{{ number_format($order->amount ?? 0, 2) }} {{ $order->currency ?? 'USD' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 content-item" style="animation: fadeInUp 0.4s ease forwards; animation-delay: 0.3s; opacity: 0; transform: translateY(10px);">
                                <div class="badge rounded bg-success icon bg-opacity-20 p-2" style="box-shadow: 0 3px 6px rgba(0,0,0,0.1); transform-origin: center; transition: all 0.3s ease;">
                                    <i class="ti ti-calendar-stats fs-6" style="filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));"></i>
                                </div>
                                <div class="d-flex flex-column gap-0">
                                    <small class="d-block opacity-50" style="font-weight: 500;">Created</small>
                                    <span class="fw-semibold" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->diffForHumans() : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2 content-item" style="animation: fadeInUp 0.4s ease forwards; animation-delay: 0.4s; opacity: 0; transform: translateY(10px);">
                                <div class="badge rounded bg-warning icon bg-opacity-20 p-2" style="box-shadow: 0 3px 6px rgba(0,0,0,0.1); transform-origin: center; transition: all 0.3s ease;">
                                    <i class="ti ti-refresh fs-6" style="filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));"></i>
                                </div>
                                <div class="d-flex flex-column gap-0">
                                    <small class="d-block opacity-50" style="font-weight: 500;">Status</small>
                                    @php
                                        $statusName = $order->status_manage_by_admin ?? 'pending';
                                        $statusName = strtolower($statusName);
                                        $status = \App\Models\Status::where('name', $statusName)->first();
                                        $statusClass = $status ? $status->badge : 'secondary';
                                    @endphp
                                    <span style="padding: .1rem .7rem" class="text-{{ $statusClass }} border border-{{ $statusClass }} rounded-2 bg-transparent">
                                        {{ ucfirst($statusName) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="position-relative d-none d-sm-block" style="animation: fadeInRight 0.7s ease;">
                @php
                    $imageNumber = $loop->index % 3 + 1;
                @endphp
                {{-- <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-{{ $imageNumber }}.png"
                    width="160" class="slide-image" alt="Order Image" style="filter: drop-shadow(0 5px 15px rgba(0,0,0,0.15)); animation: float 3s ease-in-out infinite;"> --}}
                <!-- <div class="position-absolute top-0 start-0 mt-2 ms-2" style="animation: pulse 2s infinite;">
                    <span class="badge bg-primary bg-opacity-25 rounded-pill px-2 py-1" style="backdrop-filter: blur(4px); border: 1px solid rgba(255,255,255,0.2);">#{{ $loop->iteration }}</span>
                </div> -->
            </div>
        </div>
        @empty
        <div class="swiper-slide d-flex align-items-center justify-content-center p-4">
            <div class="text-center" style="animation: fadeIn 0.6s ease;">
                <div class="mb-3" style="animation: bounce 2s infinite alternate;">
                    <i class="ti ti-shopping-cart-off" style="font-size: 3rem; filter: drop-shadow(0 3px 5px rgba(0,0,0,0.2));"></i>
                </div>
                <h6 class=" style="text-shadow: 0 1px 2px rgba(0,0,0,0.2);">No recent orders found</h6>
                <p class="small opacity-50 mb-0" style="text-shadow: 0 1px 1px rgba(0,0,0,0.15);">New orders will appear here when created</p>
            </div>
        </div>
        @endforelse
        
        <!-- <div class="swiper-slide d-flex align-items-start p-4 justify-content-between">
            <div class="w-100">
                <h6 class="mb-0 fw-bold">Websites Analyticss</h6>
                <small>Total 28.5% conversation rate</small>
                <div class="mt-5">
                    <h6 class="fw-bold">Spending</h6>
                    <div class="row gy-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-2.png"
                width="160" class="d-none d-sm-block" alt="Slide 1">
        </div>
        
        <div class="swiper-slide d-flex align-items-start p-4 justify-content-between">
            <div class="w-100">
                <h6 class="mb-0 fw-bold">Websites Analyticss</h6>
                <small>Total 28.5% conversation rate</small>
                <div class="mt-5">
                    <h6 class="fw-bold">Spending</h6>
                    <div class="row gy-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <small class="py-1 px-2 rounded-1 slider_span_bg">268</small>
                                <span class="mb-0">Session</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-3.png"
                width="160" class="d-none d-sm-block" alt="Slide 1">
        </div> -->
    </div>
    {{-- <div class="swiper-pagination"></div> --}}
</div>

<style>
    /* Enhanced Slider Styles */
    .swiper {
        position: relative;
        width: 100%;
        overflow: hidden;
    }
    
    .swiper-slide {
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        overflow: hidden;
        width: 100% !important; /* Ensure full width */
        flex-shrink: 0; /* Prevent slides from shrinking */
        height: auto !important; /* Maintain height */
    }
    
    /* .swiper-slide:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    } */
    
    .swiper-pagination-bullet-active {
        background-color: var(--bs-primary) !important;
    }
    
    .slide-image {
        transition: transform 0.5s ease;
    }
    
    .swiper-slide:hover .slide-image {
        transform: scale(1.05) translateY(-5px);
    }
    
    .plan-badge {
        position: relative;
        animation: fadeIn 0.5s ease;
    }

    small {
        color: var(--light-color)
    }

    h6, span {
        color: var(--light-color)
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Content Animations - Without changing slider functionality */
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes fadeInRight {
        from { opacity: 0; transform: translateX(20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }
    
    @keyframes bounce {
        from { transform: translateY(0); }
        to { transform: translateY(-5px); }
    }
    
    @keyframes glowText {
        0% { text-shadow: 0 0 5px rgba(255,255,255,0.3); }
        50% { text-shadow: 0 0 20px rgba(255,255,255,0.5), 0 0 30px rgba(255,255,255,0.2); }
        100% { text-shadow: 0 0 5px rgba(255,255,255,0.3); }
    }
    
    /* Enhanced content styles */
    /* .swiper-slide {
        background: linear-gradient(270deg, rgba(76, 60, 255, 0.45) 0%,rgb(115, 103, 251) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }
     */
    .badge.rounded:hover {
        transform: scale(1.15);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .content-item:hover .badge.rounded {
        transform: scale(1.1) rotate(5deg);
    }
    
     opacity-50 {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .text-info {

    }
    
    /* .customer-info:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 8px;
    } */
    
    .plan-badge .badge:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
    }
    
    .fw-semibold {
        letter-spacing: 0.3px;
    }
    
    /* Status Badge Colors */
    .bg-label-success {
        background-color: rgba(40, 199, 111, 0.16) !important;
        color: #28c76f !important;
    }
    
    .bg-label-warning {
        background-color: rgba(255, 159, 67, 0.16) !important;
        color: #ff9f43 !important;
    }
    
    .bg-label-danger {
        background-color: rgba(234, 84, 85, 0.16) !important;
        color: #ea5455 !important;
    }
    
    .bg-label-info {
        background-color: rgba(0, 207, 232, 0.16) !important;
        color: #00cfe8 !important;
    }
    
    .bg-label-primary {
        background-color: rgba(115, 103, 239, 0.16) !important;
        color: #7367ef !important;
    }
    
    .text-info {
        color: orange !important;
        border: 1px solid orange !important;
        font-size: 10px !important;
    }

    /* Swiper Navigation Styles */
    .swiper-button-next,
    .swiper-button-prev {
        width: 35px !important;
        height: 35px !important;
        background-color: var(--bs-primary);
        border-radius: 50%;
        color: white !important;
        opacity: 0.8;
        transition: all 0.3s ease;
    }
    
    .swiper-button-next:hover,
    .swiper-button-prev:hover {
        opacity: 1;
        transform: scale(1.05);
    }
    
    .swiper-button-next:after,
    .swiper-button-prev:after {
        font-size: 14px !important;
        font-weight: bold;
    }
    
    /* Hide navigation on mobile but show on hover */
    @media (max-width: 768px) {
        .swiper-button-next,
        .swiper-button-prev {
            opacity: 0;
        }
        
        .swiper:hover .swiper-button-next,
        .swiper:hover .swiper-button-prev {
            opacity: 0.8;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there are slides to initialize Swiper
        const slidesCount = document.querySelectorAll('.swiper-slide').length;
        
        if (slidesCount > 0) {
            // Initialize swiper with better animation and controls
            const swiper = new Swiper('.swiper', {
                // Config to fix blank slides issue
                observer: true,
                observeParents: true,
                resizeObserver: true,
                updateOnWindowResize: true,
                
                // Prevent browser from caching transitions
                watchSlidesProgress: true,
                watchSlidesVisibility: true,
                
                // Only enable loop if we have multiple slides
                loop: slidesCount > 1,
                // Use slide effect instead of fade to prevent blank slides
                effect: 'slide',
                slidesPerView: 1,
                // Critical for loop mode to work properly
                loopAdditionalSlides: 2,
                // No space between slides
                spaceBetween: 0,
                // Proper slide sizing
                autoHeight: true,
                
                // Enable autoplay only if we have multiple slides
                autoplay: slidesCount > 1 ? {
                    delay: 5000,
                    disableOnInteraction: false,
                } : false,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                    // Only show pagination dots if we have multiple slides
                    dynamicBullets: slidesCount > 3,
                },
                // Use smoother transitions
                speed: 600,
                // Enable navigation
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                    // Hide navigation if only one slide
                    hideOnClick: slidesCount <= 1
                }
            });
            
            // Add event listeners to pause on hover if desired
            const swiperContainer = document.querySelector('.swiper');
            if (swiperContainer && slidesCount > 1) {
                swiperContainer.addEventListener('mouseenter', function() {
                    swiper.autoplay.stop();
                });
                
                swiperContainer.addEventListener('mouseleave', function() {
                    swiper.autoplay.start();
                });
            }
        }
    });
</script>