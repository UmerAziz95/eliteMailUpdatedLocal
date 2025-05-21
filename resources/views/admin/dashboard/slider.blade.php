<div class="swiper w-100 h-100 swiper-container">
    <div class="swiper-wrapper w-100">
        @forelse($recentOrders ?? [] as $order)
        <div class="swiper-slide d-flex align-items-start p-4 justify-content-between">
            <div class="w-100">
                <!-- Order Header with Status Badge -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
                        <i class="ti ti-shopping-cart text-light fs-5"></i>
                        #{{ $order->id ?? 'N/A' }}
                    </h6>
                    <div>
                        @php
                            $statusColor = 'bg-label-info';
                            switch(strtolower($order->status_manage_by_admin ?? 'pending')) {
                                case 'active':
                                case 'paid':
                                    $statusColor = 'bg-label-success';
                                    break;
                                case 'pending':
                                    $statusColor = 'bg-label-warning';
                                    break;
                                case 'cancelled':
                                case 'failed':
                                    $statusColor = 'bg-label-danger';
                                    break;
                                default:
                                    $statusColor = 'bg-label-info';
                            }
                        @endphp
                        <!-- <span class="badge {{ $statusColor }} rounded-pill px-3">{{ ucfirst($order->status ?? 'Pending') }}</span> -->
                    </div>
                </div>
                
                <!-- Plan Name with Animation -->
                <div class="plan-badge mb-4">
                    <span class="badge bg-primary bg-opacity-10 text-light fw-semibold">{{ $order->plan->name }}</span>
                    <small class="d-block mt-2 text-muted">
                        <i class="ti ti-calendar me-1"></i> {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}
                    </small>
                </div>
                
                <div class="mt-4">
                    <!-- Customer Information -->
                    <div class="customer-info d-flex align-items-center mb-4 gap-2">
                        <div class="avatar bg-primary bg-opacity-10 p-2 rounded-circle">
                            <i class="ti ti-user text-light fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-0">{{ $order->user->name ?? 'N/A' }}</h6>
                            <small class="text-muted">{{ $order->user->email ?? '' }}</small>
                        </div>
                    </div>
                    
                    <!-- Order Details -->
                    <div class="row gy-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="badge rounded bg-primary bg-opacity-10 p-2">
                                    <i class="ti ti-mail text-light"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Total Inboxes</small>
                                    <span class="fw-semibold">{{ $order->plan->max_inbox ?? 0 }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="badge rounded bg-info bg-opacity-10 p-2">
                                    <i class="ti ti-credit-card text-info"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Amount</small>
                                    <span class="fw-semibold">{{ number_format($order->amount ?? 0, 2) }} {{ $order->currency ?? 'USD' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="badge rounded bg-success bg-opacity-10 p-2">
                                    <i class="ti ti-calendar-stats text-success"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Created</small>
                                    <span class="fw-semibold">{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->diffForHumans() : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">
                            <div class="d-flex align-items-center gap-2">
                                <div class="badge rounded bg-warning bg-opacity-10 p-2">
                                    <i class="ti ti-refresh text-warning"></i>
                                </div>
                                <div>
                                    <small class="d-block text-muted">Status</small>
                                    <span class="fw-semibold text-capitalize">{{ $order->status ?? 'Pending' }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="position-relative d-none d-sm-block">
                @php
                    $imageNumber = $loop->index % 3 + 1;
                @endphp
                <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-{{ $imageNumber }}.png"
                    width="160" class="slide-image" alt="Order Image">
                <!-- <div class="position-absolute bottom-0 end-0 mb-3 me-3">
                    <span class="badge bg-primary rounded-pill">#{{ $loop->iteration }}</span>
                </div> -->
            </div>
        </div>
        @empty
        <div class="swiper-slide d-flex align-items-center justify-content-center p-4">
            <div class="text-center">
                <div class="mb-3">
                    <i class="ti ti-shopping-cart-off text-muted" style="font-size: 3rem;"></i>
                </div>
                <h6 class="text-muted">No recent orders found</h6>
                <p class="small text-muted mb-0">New orders will appear here when created</p>
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
    <div class="swiper-pagination"></div>
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
    
    .swiper-slide:hover {
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    }
    
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
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .badge {
        transition: all 0.3s ease;
    }
    
    .badge:hover {
        transform: translateY(-2px);
    }
    
    .avatar {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
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