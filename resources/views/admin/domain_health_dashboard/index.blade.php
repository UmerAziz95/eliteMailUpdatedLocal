@extends('admin.layouts.app')

@section('title', 'Domain Health Dashboard')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<style>
    .avatar {
        position: relative;
        block-size: 2.5rem;
        cursor: pointer;
        inline-size: 2.5rem;
    }

    .avatar .avatar-initial {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--second-primary);
        font-size: 1.5rem;
        font-weight: 500;
        inset: 0;
        text-transform: uppercase;
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
</style>
<style>
    .dt-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 1rem;
        border-radius: 4px;
    }

    .loading {
        position: relative;
        pointer-events: none;
        opacity: 0.6;
    }
</style>
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

    .accordion {
        --bs-accordion-bg: transparent !important;
    }

    .accordion-button:focus {
        box-shadow: none !important
    }

    .button.collapsed {
        background-color: var(--slide-bg) !important;
        color: var(--light-color)
    }

    .button {
        background-color: var(--second-primary);
        color: var(--light-color);
        transition: all ease .4s
    }

    .accordion-body {
        color: var(--light-color)
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

    /* Ensure offcanvas doesn't interfere with page interaction */
    .offcanvas.hiding,
    .offcanvas:not(.show) {
        pointer-events: none;
    }

    /* Force cleanup of backdrop opacity */
    .offcanvas-backdrop.fade {
        opacity: 0 !important;
        transition: opacity 0.15s linear;
    }

    /* Ensure page remains interactive */
    body:not(.offcanvas-open):not(.modal-open) {
        overflow: visible !important;
        padding-right: 0 !important;
    }

    /* Hide any orphaned backdrop elements */
    div[class*="backdrop"]:empty {
        display: none !important;
    }

    /* Flip card styling for timer */
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
    }

    .flip-front {
        z-index: 2;
    }

    .flip-back {
        transform: rotateX(180deg);
    }

    /* Flip timer container styles */
    .flip-timer {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        font-family: "Space Grotesk", "Courier New", monospace;
        font-size: 12px;
        padding: 4px 8px;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(2px);
    }

    .flip-timer.positive {
        background: transparent;
        color: #28a745;
    }

    .flip-timer.positive .flip-front,
    .flip-timer.positive .flip-back {
        color: #155724;
        border-color: rgba(40, 167, 69, 0.2);
    }

    .flip-timer.negative {
        background: transparent;
        color: #dc3545;
    }

    .flip-timer.negative .flip-front,
    .flip-timer.negative .flip-back {
        color: #dc3545;
        background-color: rgba(255, 0, 0, 0.16);
        border-color: rgb(220, 53, 70);
    }

    .flip-timer.completed {
        background: rgba(108, 117, 125, 0.1);
        border-color: rgba(108, 117, 125, 0.3);
        color: #6c757d;
    }

    .flip-timer.completed .flip-front,
    .flip-timer.completed .flip-back {
        background: linear-gradient(to bottom, #e2e6ea 50%, #dae0e5 50%);
        color: #495057;
        border-color: rgba(108, 117, 125, 0.2);
    }

    .flip-timer.paused {
        background: rgba(255, 193, 7, 0.1);
        border-color: rgba(255, 193, 7, 0.3);
        color: #856404;
    }

    .flip-timer.paused .flip-front,
    .flip-timer.paused .flip-back {
        background: linear-gradient(to bottom, #fff3cd 50%, #ffeaa7 50%);
        color: #856404;
        border-color: rgba(255, 193, 7, 0.2);
    }

    .flip-timer.cancelled {
        background: rgba(108, 117, 125, 0.1);
        border-color: rgba(108, 117, 125, 0.3);
        color: #6c757d;
    }

    .flip-timer.cancelled .flip-front,
    .flip-timer.cancelled .flip-back {
        background: linear-gradient(to bottom, #e2e6ea 50%, #dae0e5 50%);
        color: #495057;
        border-color: rgba(108, 117, 125, 0.2);
    }

    /* Timer badge styling */
    .timer-badge {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        font-size: 11px;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        letter-spacing: 0.5px;
        min-width: 70px;
        justify-content: center;
        margin-left: 8px;
    }

    /* Timer states */
    .timer-badge.positive {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-color: rgba(40, 167, 69, 0.3);
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
    }

    .timer-badge.negative {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        color: white;
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        animation: pulse-red 2s infinite;
    }

    .timer-badge.completed {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        border-color: rgba(108, 117, 125, 0.3);
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
    }

    /* Pulse animation for overdue timers */
    @keyframes pulse-red {
        0% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        50% {
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
            transform: scale(1.02);
        }

        100% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
    }

    /* Pulse animation for paused timers */
    @keyframes pulse-yellow {
        0% {
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }

        50% {
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
            transform: scale(1.02);
        }

        100% {
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
    }

    /* Additional timer badge states */
    .timer-badge.paused {
        background: linear-gradient(135deg, #ffc107, #ffb300) !important;
        color: #212529 !important;
        border-color: rgba(255, 193, 7, 0.3) !important;
        box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2) !important;
        animation: pulse-yellow 2s infinite !important;
    }

    .timer-badge.cancelled {
        background: linear-gradient(135deg, #6c757d, #495057) !important;
        color: white !important;
        border-color: rgba(108, 117, 125, 0.3) !important;
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2) !important;
    }

    /* Timer icon styling */
    .timer-icon {
        font-size: 10px;
        margin-right: 2px;
    }

    /* Hover effects */
    .timer-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }


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

    /* Domain badge styling */
    .domain-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: 1px solid rgba(102, 126, 234, 0.3);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        margin: 0.125rem;
        display: inline-block;
        transition: all 0.3s ease;
    }

    .domain-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Order card styling */
    .order-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .order-card:hover {
        /* transform: translateY(-2px); */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Enhanced stat boxes hover effects */
    .order-card .col-6>div {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .order-card .col-6>div:hover {
        /* transform: translateY(-1px); */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    /* Icon animations */
    .order-card i {
        transition: all 0.3s ease;
    }

    .order-card:hover i {
        transform: scale(1.1);
    }

    /* Status badge enhancement */
    .order-card .badge {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Button enhancement */
    .order-card button {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .order-card button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    /* Split content animations */
    .collapse {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .collapse:not(.show) {
        opacity: 0;
        transform: translateY(-10px);
    }

    .collapse.show {
        opacity: 1;
        transform: translateY(0);
        animation: splitFadeIn 0.4s ease-out;
    }

    .collapse.collapsing {
        opacity: 0.5;
        transform: translateY(-5px);
    }

    /* Split fade-in animation */
    @keyframes splitFadeIn {
        0% {
            opacity: 0;
            transform: translateY(-15px) scale(0.98);
        }

        50% {
            opacity: 0.7;
            transform: translateY(-5px) scale(0.99);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Domain badge animations */
    @keyframes domainFadeIn {
        0% {
            opacity: 0;
            transform: translateY(-10px) scale(0.8);
        }

        50% {
            opacity: 0.7;
            transform: translateY(-2px) scale(0.95);
        }

        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Toast animations */
    @keyframes toastSlideIn {
        0% {
            opacity: 0;
            transform: translateX(100%) scale(0.8);
        }

        100% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    /* Chevron rotation animation */
    .transition-transform {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Enhanced hover effects for domain badges */
    .domain-badge {
        will-change: transform, box-shadow;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .domain-badge:hover {
        transform: translateY(-3px) scale(1.08) !important;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25) !important;
        filter: brightness(1.1);
    }

    /* Split container animations */
    .split-container {
        transition: all 0.3s ease;
    }

    .split-container.expanding {
        animation: splitExpand 0.4s ease-out;
    }

    @keyframes splitExpand {
        0% {
            transform: scale(0.98);
        }

        50% {
            transform: scale(1.02);
        }

        100% {
            transform: scale(1);
        }
    }

    /* Timer badge styling */
    .timer-badge {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        font-size: 11px;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        letter-spacing: 0.5px;
        min-width: 70px;
        justify-content: center;
        margin-left: 8px;
    }

    /* Timer states */
    .timer-badge.positive {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-color: rgba(40, 167, 69, 0.3);
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
    }

    .timer-badge.negative {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        color: white;
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        animation: pulse-red 2s infinite;
    }

    .timer-badge.completed {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        border-color: rgba(108, 117, 125, 0.3);
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
    }

    /* Pulse animation for overdue timers */
    @keyframes pulse-red {
        0% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        50% {
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
            transform: scale(1.02);
        }

        100% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
    }

    /* Timer icon styling */
    .timer-icon {
        font-size: 10px;
        margin-right: 2px;
    }

    /* Hover effects */
    .timer-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }


    .anim_card {
        background-color: var(--secondary-color);
        color: var(--light-color);
        border: 1px solid #99999962;
        border-radius: 8px;
        position: relative;
        opacity: 1;
    }

    .anim_card .order_detail {
        width: 100%;
        height: 14rem;
        overflow: hidden;
        border: 1px solid #86868654
    }

    .anim_card .order_detail .card_content {
        width: 100%;
        transition: .5s;
    }

    .card_content {
        transform: translateX(30%);
    }

    .anim_card:hover .order_detail .card_content {
        opacity: .9;
        transform: translateX(0%);
    }

    .anim_card .flip_details {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--second-primary);
        border-radius: 10px;
        transition: transform 0.5s ease, box-shadow 0.5s ease;
        transform-origin: left;
        transform: perspective(2000px) rotateY(0deg);
        z-index: 2;
    }

    .anim_card .flip_details::after {
        content: "";
        position: absolute;
        top: 0;
        right: -5px;
        width: 0px;
        height: 100%;
        background: rgba(255, 255, 255, 0.602);
        border-radius: 0 5px 5px 0;
        transition: width 0.3s ease;
    }

    .anim_card:hover .flip_details {
        transform: perspective(2000px) rotateY(-91deg);
        box-shadow: rgba(255, 255, 255, 0.4) 0px 2px 4px,
            rgba(255, 255, 255, 0.3) 0px 7px 13px -3px,
            rgba(255, 255, 255, 0.2) 0px -3px 0px inset;
        pointer-events: none
    }

    .anim_card:hover .flip_details::after {
        width: 102px;
        background-color: #9a9a9a81;
        pointer-events: none;
    }

    .anim_card .flip_details .center {
        padding: 20px;
        background-color: var(--secondary-color);
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }

    /* Change Status Modal Styles */
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .modal-header .btn-close {
        filter: invert(1);
    }

    .form-select,
    .form-control {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-select:focus,
    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* Status badge styles */
    .badge.bg-primary {
        background-color: #0d6efd !important;
    }

    .badge.bg-success {
        background-color: #198754 !important;
    }

    .badge.bg-warning {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    .badge.bg-danger {
        background-color: #dc3545 !important;
    }

    .badge.bg-secondary {
        background-color: #6c757d !important;
    }

    /* Notification styles */
    .alert {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
</style>
@endpush

@section('content')

<section class="py-3">


    <ul class="nav nav-tabs border-0 mb-4 d-flex align-items-center justify-content-center" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link text-uppercase active" id="list-tab" data-bs-toggle="tab"
                data-bs-target="#list-tab-pane" type="button" role="tab" aria-controls="list-tab-pane"
                aria-selected="true">list view</button>
        </li>

        {{-- <li class="nav-item" role="presentation">
            <button class="nav-link text-uppercase" id="grid-tab" data-bs-toggle="tab" data-bs-target="#grid-tab-pane"
                type="button" role="tab" aria-controls="grid-tab-pane" aria-selected="false">grid view</button>
        </li> --}}
    </ul>


    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="list-tab-pane" role="tabpanel" aria-labelledby="list-tab"
            tabindex="0">
            <div class="row gy-3 mb-4">
                <div class="counters col-lg-6"
                    style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important">
                    <div class="card p-3 counter_1">
                        <div>
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Total Orders</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($totalOrders) }}</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ti ti-user-search"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/14385/14385008.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-regular fa-file-lines fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 counter_2">
                        <div>
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Pending</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($pendingOrders) }}</h4>
                                        <p class="text-danger mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-success">
                                        <i class="ti ti-user-check"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/18873/18873804.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-solid fa-spinner fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 counter_1">
                        <div>
                            <!-- {{-- //card body --}} -->
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Complete</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($inProgressOrders) }}</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti ti-user-plus"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/6416/6416374.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-solid fa-check-double fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 counter_2">
                        <div>
                            <!-- {{-- //card body --}} -->
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">In-Progress</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($inProgressOrders) }}</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti ti-user-plus"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/10282/10282642.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-solid fa-bars-progress fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 counter_1">
                        <div>
                            <!-- {{-- //card body --}} -->
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Cancelled</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($cancelledOrders) }}</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti ti-user-plus"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/19005/19005362.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-brands fa-empire fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 counter_1">
                        <div>
                            <!-- {{-- //card body --}} -->
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Rejected</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($rejectOrders) }}</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-danger">
                                        <i class="ti ti-user-plus"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/15332/15332434.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-solid fa-book-skull fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 counter_2">
                        <div>
                            <!-- {{-- //card body --}} -->
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Draft</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-3">{{ number_format($draftOrders) }}</h4>
                                        <p class="text-warning mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    {{-- <span class="avatar-initial rounded bg-label-warning">
                                        <i class="ti ti-edit"></i>
                                    </span> --}}
                                    {{-- <img src="https://cdn-icons-gif.flaticon.com/10690/10690672.gif" width="40"
                                        style="border-radius: 50px" alt=""> --}}
                                    <i class="fa-solid fa-ban fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="p-3 h-100 filter">
                        <div class="d-flex align-items-center justify-content-between">
                            <h6 class="mb-2 text-white">Filters</h6>
                        </div>

                        <div class="d-flex align-items-start gap-4">
                            <div class="row gy-3">
                                <div class="col-md-6 col-lg-4">
                                    <label for="orderIdFilter" class="form-label mb-0">Order ID</label>
                                    <input type="text" id="orderIdFilter" class="form-control"
                                        placeholder="Search by ID">
                                </div>

                                <div class="col-md-6 col-lg-4">
                                    <label for="emailFilter" class="form-label mb-0">Email</label>
                                    <input type="text" id="emailFilter" class="form-control"
                                        placeholder="Search by email">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="domainFilter" class="form-label mb-0">Domain URL</label>
                                    <input type="text" id="domainFilter" class="form-control"
                                        placeholder="Search by domain">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="totalInboxesFilter" class="form-label mb-0">Total Inboxes</label>
                                    <input type="number" id="totalInboxesFilter" class="form-control"
                                        placeholder="Search by total inboxes" min="1">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="startDate" class="form-label mb-0">Start Date</label>
                                    <input type="date" id="startDate" class="form-control">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label for="endDate" class="form-label mb-0">End Date</label>
                                    <input type="date" id="endDate" class="form-control">
                                </div>
                                <div class="d-flex align-items-center justify-content-end">
                                    <button id="applyFilters"
                                        class="btn btn-primary btn-sm me-2 px-4 border-0">Filter</button>
                                    <button id="clearFilters" class="btn btn-secondary btn-sm px-4">Clear</button>
                                </div>
                            </div>

                            {{-- <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                                style="border-radius: 50%" class="d-none d-sm-block" alt=""> --}}
                        </div>

                    </div>
                </div>
            </div>

            <div class="card py-3 px-4">
                <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist">
                    <div class="dropdown">
                        <button class="btn btn-primary shadow dropdown-toggle" style="width: fit-content" type="button"
                            id="plansDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Select Plan
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="plansDropdown">
                            <li>
                                <a class="dropdown-item active text-capitalize" id="all-tab" data-bs-toggle="tab"
                                    href="#all-tab-pane" role="tab" aria-controls="all-tab-pane"
                                    aria-selected="true">All
                                    Orders</a>
                            </li>
                            @foreach ($plans as $plan)
                            <li>
                                <a class="dropdown-item text-capitalize" id="plan-{{ $plan->id }}-tab"
                                    data-bs-toggle="tab" href="#plan-{{ $plan->id }}-tab-pane" role="tab"
                                    aria-controls="plan-{{ $plan->id }}-tab-pane" aria-selected="false">{{ $plan->name
                                    }}</a>
                            </li>
                            @endforeach
                        </ul>
                    </div>

                </ul>

                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                        tabindex="0">
                        @include('admin.domain_health_dashboard._orders_table')
                    </div>
                    @foreach ($plans as $plan)
                    <div class="tab-pane fade" id="plan-{{ $plan->id }}-tab-pane" role="tabpanel"
                        aria-labelledby="plan-{{ $plan->id }}-tab" tabindex="0">
                        @include('admin.domain_health_dashboard._orders_table', ['plan_id' => $plan->id])
                    </div>
                    @endforeach
                </div>
            </div>


            <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin"
                aria-labelledby="offcanvasAddAdminLabel" aria-modal="true" role="dialog">
                <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
                    <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">View Detail</h5>
                    <button class="border-0 bg-transparent" type="button" data-bs-dismiss="offcanvas"
                        aria-label="Close"><i class="fa-solid fa-xmark fs-5"></i></button>
                </div>
                <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">

                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="grid-tab-pane" role="tabpanel" aria-labelledby="grid-tab" tabindex="0">
            <section>
                <!-- Advanced Search Filter UI -->
                <div class="card p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse"
                        href="#filter_1" role="button" aria-expanded="false" aria-controls="filter_1">
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
                                    <input type="text" name="order_id" class="form-control"
                                        placeholder="Enter order ID">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label mb-0">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="completed" selected>Completed</option>
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
                <!-- Grid Cards (Dynamic) -->
                <div id="ordersContainer"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
                    <!-- Loading state -->
                    <div id="loadingState"
                        style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading orders...</p>
                    </div>
                </div>

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

            </section>


        </div>
    </div>
    <!-- Order Details Offcanvas -->
    <div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-splits-view"
        aria-labelledby="order-splits-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="order-splits-viewLabel">Details Order </h5>
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
    </div>
    </div>
    </div>
    </div>

    <!-- Change Status Modal -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Change Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Order ID: <span id="modalOrderId"
                                class="fw-bold text-primary"></span></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Status: <span id="modalCurrentStatus"
                                class="badge"></span></label>
                    </div>
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Select New Status</label>
                        <select class="form-select" id="newStatus" required>
                            <option value="">-- Select Status --</option>
                            <!-- <option value="pending">Pending</option> -->
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="reject">Rejected</option>
                            <!-- <option value="in-progress">In Progress</option> -->
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="statusReason" class="form-label">Reason for Status Change (Optional)</label>
                        <textarea class="form-control" id="statusReason" rows="3"
                            placeholder="Enter reason for status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStatusChange"
                        onclick="updateOrderStatus()">
                        <i class="fas fa-save me-1"></i>
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')

<script>
    // Debug AJAX calls
        $(document).ajaxSend(function(event, jqXHR, settings) {
            console.log('AJAX Request:', {
                url: settings.url,
                type: settings.type,
                data: settings.data,
                headers: jqXHR.headers
            });
        });

        function viewOrder(id) {
            window.location.href = `{{ url('/admin/orders/${id}/view') }}`;
        }

        // Timer calculation functions with pause and cancelled support
        function calculateOrderTimer(createdAt, status, completedAt = null, timerStartedAt = null, timerPausedAt = null, totalPausedSeconds = 0) {
            
            const now = new Date();

            const startTime = timerStartedAt ? new Date(timerStartedAt) : new Date(createdAt);
            const twelveHours = 12 * 60 * 60 * 1000;

            // ⏸ If paused OR cancelled OR rejected, treat as paused
            if ((timerPausedAt && status !== 'completed') || status === 'cancelled' || status === 'reject') {
                const pausedTime = timerPausedAt ? new Date(timerPausedAt) : now;

                const timeElapsedBeforePause = pausedTime - startTime;
                const effectiveTimeAtPause = Math.max(0, timeElapsedBeforePause - (totalPausedSeconds * 1000));
                const timeDiffAtPause = effectiveTimeAtPause - twelveHours;

                const label = (status === 'cancelled' || status === 'reject') ? '' : '';
                const timerClass = (status === 'cancelled' || status === 'reject') ? status : 'paused';

                if (timeDiffAtPause > 0) {
                    // Was overdue - for paused, show as paused regardless of being overdue
                    return {
                        display: '-' + formatTimeDuration(timeDiffAtPause) + label,
                        isNegative: true,
                        isCompleted: false,
                        isPaused: true,
                        class: timerClass // Only use primary state class for paused/cancelled
                    };
                } else {
                    // Still had time left - for paused, show as paused regardless of time left
                    return {
                        display: formatTimeDuration(-timeDiffAtPause) + label,
                        isNegative: false,
                        isCompleted: false,
                        isPaused: true,
                        class: timerClass // Only use primary state class for paused/cancelled
                    };
                }
            }

            // ✅ Completed (with timestamp)
            if (status === 'completed' && completedAt) {
                const completionDate = new Date(completedAt);
                const totalElapsedTime = completionDate - startTime;
                const effectiveWorkingTime = Math.max(0, totalElapsedTime - (totalPausedSeconds * 1000));
                const isOverdue = effectiveWorkingTime > twelveHours;

                return {
                    display: formatTimeDuration(effectiveWorkingTime),
                    isNegative: isOverdue,
                    isCompleted: true,
                    class: 'completed'
                };
            }

            // ✅ Completed (no timestamp)
            if (status === 'completed') {
                return {
                    display: 'Completed',
                    isNegative: false,
                    isCompleted: true,
                    class: 'completed'
                };
            }

            // ⏱ Active countdown or overtime
            const totalElapsedTime = now - startTime;
            const effectiveElapsedTime = Math.max(0, totalElapsedTime - (totalPausedSeconds * 1000));
            const effectiveDeadline = new Date(startTime.getTime() + twelveHours + (totalPausedSeconds * 1000));
            const timeDiff = now - effectiveDeadline;

            if (timeDiff > 0) {
                // Overtime
                return {
                    display: '-' + formatTimeDuration(timeDiff),
                    isNegative: true,
                    isCompleted: false,
                    class: 'negative'
                };
            } else {
                // Still in time
                return {
                    display: formatTimeDuration(-timeDiff),
                    isNegative: false,
                    isCompleted: false,
                    class: 'positive'
                };
            }
        }

        // Format time duration in countdown format (HH:MM:SS)
        function formatTimeDuration(milliseconds) {
            const totalSeconds = Math.floor(Math.abs(milliseconds) / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            // Format with leading zeros for proper countdown display
            const hoursStr = hours.toString().padStart(2, '0');
            const minutesStr = minutes.toString().padStart(2, '0');
            const secondsStr = seconds.toString().padStart(2, '0');

            return `${hoursStr}:${minutesStr}:${secondsStr}`;
        }

        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            return date.toLocaleDateString('en-US', options);
        }

        // Create timer badge HTML with pause and cancelled support
        function createTimerBadge(timerData) {
            const timer = calculateOrderTimer(
                timerData.created_at, 
                timerData.status, 
                timerData.completed_at, 
                timerData.timer_started_at,
                timerData.timer_paused_at,
                timerData.total_paused_seconds
            );

            // Determine the icon class based on status and timer
            let iconClass = '';
            if (timerData.status === 'cancelled') {
                iconClass = 'fas fa-exclamation-triangle'; // warning icon
            } else if (timerData.status === 'reject') {
                iconClass = 'fas fa-ban'; // ban icon for rejected
            } else if (timer.isCompleted) {
                iconClass = 'fas fa-check';
            } else if (timer.isPaused) {
                iconClass = 'fas fa-pause';
            } else {
                iconClass = timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock';
            }

            // Create tooltip text
            let tooltip = '';
            if (timerData.status === 'cancelled') {
                tooltip = `Order was cancelled on ${formatDate(timerData.completed_at || timerData.timer_paused_at || timerData.created_at)}`;
            } else if (timerData.status === 'reject') {
                tooltip = `Order was rejected on ${formatDate(timerData.completed_at || timerData.timer_paused_at || timerData.created_at)}`;
            } else if (timer.isCompleted) {
                tooltip = timerData.completed_at 
                    ? `Order completed on ${formatDate(timerData.completed_at)}` 
                    : 'Order is completed';
            } else if (timer.isPaused) {
                tooltip = `Timer is paused at ${timer.display.replace(' (Paused)', '')}. Paused on ${formatDate(timerData.timer_paused_at)}`;
            } else if (timer.isNegative) {
                tooltip = `Order is overdue by ${timer.display.substring(1)} (overtime). Created on ${formatDate(timerData.created_at)}`;
            } else {
                tooltip = `Time remaining: ${timer.display} (12-hour countdown). Order created on ${formatDate(timerData.created_at)}`;
            }

            return `
                <span class="timer-badge ${timer.class}" 
                      data-order-id="${timerData.order_id}" 
                      data-created-at="${timerData.created_at}" 
                      data-status="${timerData.status}" 
                      data-completed-at="${timerData.completed_at || ''}"
                      data-timer-started-at="${timerData.timer_started_at || ''}"
                      data-timer-paused-at="${timerData.timer_paused_at || ''}"
                      data-total-paused-seconds="${timerData.total_paused_seconds || 0}"
                      title="${tooltip}">
                    <i class="${iconClass} timer-icon"></i>
                    ${timer.display}
                </span>
            `;
        }

        function initDataTable(planId = '') {
            var tableId = planId ? `#myTable-${planId}` : '#myTable';
            var $table = $(tableId);
            if (!$table.length) {
                console.error('Table not found with selector:', tableId);
                return null;
            }
           
            try {
                var table = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    autoWidth: false,
                    dom: '<"top"f>rt<"bottom"lip><"clear">',
                    columnDefs: [{
                            width: '10%',
                            targets: 0
                        }, // ID 
                        {
                            width: '15%',
                            targets: 1
                        }, // Date
                        {
                            width: '15%',
                            targets: 2
                        }, // Name
                        {
                            width: '15%',
                            targets: 3
                        }, // Email
                        ...(planId ? [] : [{
                            width: '15%',
                            targets: 4
                        }]), // Plan (only for All Orders) 
                        {
                            width: '20%',
                            targets: planId ? 4 : 5
                        }, // Domain URL
                        {
                            width: '15%',
                            targets: planId ? 5 : 6
                        }, // Total Inboxes 
                        {
                            width: '15%',
                            targets: planId ? 6 : 7
                        }, // Status
                        {
                            width: '10%',
                            targets: planId ? 7 : 8
                        } // Actions
                    ],
                    ajax: {
                        url: "{{ route('admin.domains.orders.card.data') }}",
                        type: "GET",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        data: function(d) {
                            d.draw = d.draw || 1;
                            d.length = d.length || 10;
                            d.start = d.start || 0;
                            d.search = d.search || {
                                value: '',
                                regex: false
                            };
                            d.plan_id = planId;

                            // Add filter parameters
                            d.orderId = $('#orderIdFilter').val();
                            d.name = $('#nameFilter').val();
                            
                            d.email = $('#emailFilter').val();
                            d.domain = $('#domainFilter').val();
                            d.totalInboxes = $('#totalInboxesFilter').val();
                            d.startDate = $('#startDate').val();
                            d.endDate = $('#endDate').val();

                            return d;
                        },
                        dataSrc: function(json) {
                            return json.data;
                        },
                        error: function(xhr, error, thrown) {
                            console.error('DataTables error:', error);
                            console.error('Server response:', xhr.responseText);
                            console.error('Status:', xhr.status);
                            console.error('Full XHR:', xhr);

                            if (xhr.status === 401) {
                                window.location.href = "{{ route('login') }}";
                            } else if (xhr.status === 403) {
                                toastr.error('You do not have permission to view this data');
                            } else {
                                toastr.error('Error loading orders data: ' + error);
                            }
                        }
                    },
                    columns: [{
                            data: 'id',
                            name: 'orders.id'
                        },
                        {
                            data: 'created_at',
                            name: 'orders.created_at',
                            render: function(data, type, row) {
                                return `
                                <div class="d-flex align-items-center gap-1 text-nowrap">
                                    <i class="ti ti-calendar-month fs-5"></i>
                                    <span>${data}</span>
                                </div>
                            `;
                            }
                        },

                        {
                            data: 'name',
                            name: 'name',
                            render: function(data, type, row) {
                                return `
                                <div class="d-flex gap-1 align-items-center">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 25px" alt="">
                                    </div>
                                    <span class="text-nowrap">${data}</span>    
                                </div>
                            `;
                            }
                        },
                        {
                            data: 'email',
                            name: 'email',
                            render: function(data, type, row) {
                                return `
                                    <div class="d-flex align-items-center gap-1">
                                        <i style= "color: #00BBFF"; class="ti ti-mail fs-6"></i>
                                        <span style= "color: #00BBFF";>${data}</span>    
                                    </div>
                                `;
                            }
                        },
                        ...(planId ? [] : [{
                            data: 'plan_name',
                            name: 'plans.name',
                            render: function(data, type, row) {
                                return `
                                <div class="d-flex gap-1 align-items-center ">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/7756/7756169.png" style="width: 18px" alt="">
                                    </div>
                                    <span>${data}</span>    
                                </div>
                            `;
                            }
                        }]),
                        // add contractor_name
                        {
                            data: 'contractor_name',
                            name: 'orders.contractor_name',
                            render: function(data, type, row) {
                                return `
                                <div class="d-flex align-items-center gap-1">
                                    <i class="ti ti-user fs-6"></i>
                                    <span>${data}</span>
                                </div>
                            `;
                            }
                        },
                        {
                            data: 'split_counts',
                            name: 'split_counts',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'total_inboxes',
                            name: 'total_inboxes'
                        },
                        {
                            data: 'dns_issues_count',
                            name: 'dns_issues_count'
                        },
                        {
                            data: 'blacklisted_count',
                            name: 'blacklisted_count'
                        },
                       
                        {
                            data: 'status',
                            name: 'orders.status'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    drawCallback: function(settings) {
                        if (settings.json && settings.json.error) {
                            toastr.error(settings.json.message || 'Error loading data');
                        }
                        $('[data-bs-toggle="tooltip"]').tooltip();

                        // Only adjust columns
                        this.api().columns.adjust();
                    },
                    initComplete: function(settings, json) {
                        this.api().columns.adjust();
                    }
                });

                // Handle processing state visually
                table.on('processing.dt', function(e, settings, processing) {
                    const wrapper = $(tableId + '_wrapper');
                    if (processing) {
                        wrapper.addClass('loading');
                        wrapper.append('<div class="dt-loading">Loading...</div>');
                    } else {
                        wrapper.removeClass('loading');
                        wrapper.find('.dt-loading').remove();
                    }
                });

                return table;
            } catch (error) {
                console.error('Error initializing DataTable:', error);
                toastr.error('Error initializing table. Please refresh the page.');
                return null;
            }
        }

        $(document).ready(function() {
            try {

                window.orderTables = {};

                // Initialize table for all orders
                window.orderTables.all = initDataTable();

                // Initialize tables for each plan
                @foreach ($plans as $plan)
                    window.orderTables['plan{{ $plan->id }}'] = initDataTable('{{ $plan->id }}');
                @endforeach

                // Handle tab changes
                $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    const tabId = $(e.target).attr('id');

                    // Clear DataTables events before reapplying
                    Object.values(window.orderTables).forEach(function(table) {
                        if (table) {
                            table.off('preXhr.dt');
                        }
                    });

                    // Force recalculation of column widths for visible tables
                    setTimeout(function() {
                        Object.values(window.orderTables).forEach(function(table) {
                            if (table && $(table.table().node()).is(':visible')) {
                                try {
                                    // Add filter parameters before redraw
                                    table.on('preXhr.dt', function(e, settings, data) {
                                        data.orderId = $('#orderIdFilter').val();
                                        data.name = $('#nameFilter').val();
                                        
                                        data.email = $('#emailFilter').val();
                                        data.domain = $('#domainFilter').val();
                                        data.totalInboxes = $('#totalInboxesFilter')
                                            .val();
                                        data.startDate = $('#startDate').val();
                                        data.endDate = $('#endDate').val();
                                    });

                                    table.columns.adjust();
                                    if (table.responsive && typeof table.responsive
                                        .recalc === 'function') {
                                        table.responsive.recalc();
                                    }
                                    table.draw();
                                } catch (error) {
                                    console.error('Error adjusting table:', error);
                                }
                            }
                        });
                    }, 100); // Increased timeout to ensure DOM is ready
                });

                // Initial column adjustment for the active tab
                setTimeout(function() {
                    try {
                        const activeTable = $('.tab-pane.active .table').DataTable();
                        if (activeTable) {
                            activeTable.columns.adjust();
                            if (activeTable.responsive && typeof activeTable.responsive.recalc ===
                                'function') {
                                activeTable.responsive.recalc();
                            }
                        }
                    } catch (error) {
                        console.error('Error in initial column adjustment:', error);
                    }
                }, 100);

                // Add global error handler for AJAX requests
                $(document).ajaxError(function(event, xhr, settings, error) {
                    console.error('AJAX Error:', error);
                    if (xhr.status === 401) {
                        window.location.href = "{{ route('login') }}";
                    } else if (xhr.status === 403) {
                        toastr.error('You do not have permission to perform this action');
                    }
                });

                // Filter functionality
              

                // Apply filters button click handler
                $('#applyFilters').on('click', function() {
                   
                    applyFiltersListView();
                });

                // Clear filters
                $('#clearFilters').on('click', function() {
                    $('#orderIdFilter, #nameFilter, #emailFilter, #domainFilter, #totalInboxesFilter').val('');
                    
                    $('#startDate, #endDate').val('');
                    applyFiltersListView()
                   
                });


                  function applyFiltersListView() {
                    // Clear previous event handlers
                    Object.values(window.orderTables).forEach(function(table) {
                        table.off('preXhr.dt');
                    });

                    Object.values(window.orderTables).forEach(function(table) {
                        if ($(table.table().node()).is(':visible')) {
                            // Add filter parameters
                            table.on('preXhr.dt', function(e, settings, data) {
                                data.orderId = $('#orderIdFilter').val();
                                data.name = $('#nameFilter').val();
                                
                                
                                data.email = $('#emailFilter').val();
                                data.domain = $('#domainFilter').val();
                                data.totalInboxes = $('#totalInboxesFilter').val();
                                data.startDate = $('#startDate').val();
                                data.endDate = $('#endDate').val();
                            });

                            table.draw();
                        }
                    });
                }

            } catch (error) {
                console.error('Error in document ready:', error);
            }
        });



        $(document).on('change', '.status-dropdown', function() {
            let selectedStatus = $(this).val();
            let orderId = $(this).data('id');

            $.ajax({
                url: '/admin/update-order-status',
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    order_id: orderId,
                    status_manage_by_admin: selectedStatus
                },
                success: function(response) {
                    // Reload the correct table instead of re-initializing it
                    if (window.orderTables && window.orderTables.all) {
                        window.orderTables.all.ajax.reload(null,
                            false); // false to stay on the current page
                    }

                    alert('Status updated successfully!');
                },
                error: function(xhr) {
                    if (window.orderTables && window.orderTables.all) {
                        window.orderTables.all.ajax.reload(null,
                            false); // false to stay on the current page
                    }
                    alert('Something went wrong!');
                    console.error(xhr.responseText);
                }
            });
        });


        //open the modal for cancel subscription
        $(document).on('click', '.markStatus', function() {
            const chargebee_subscription_id = $(this).data('id');
            const status = $(this).data('status');
            const reason = $(this).data('reason');

            // Set subscription ID in the hidden input
            $('#subscription_id_to_cancel').val(chargebee_subscription_id);

            // Uncheck all first to reset previous state
            $('input[name="marked_status"]').prop('checked', false);

            // Check the radio button that matches the status
            $('input[name="marked_status"][value="' + status + '"]').prop('checked', true);

            // Show or hide reason field depending on status
            if (status === 'Reject') {
                $('#reason_wrapper').removeClass('d-none');
                $('#cancellation_reason').attr('required', true);
                $('#cancellation_reason').val(reason);
            } else {
                $('#reason_wrapper').addClass('d-none');
                $('#cancellation_reason').removeAttr('required');
                $('#cancellation_reason').val('');
            }

            // Show the modal
            $('#cancel_subscription').modal('show');
        });


        //handle the reason field on status change
        $('.marked_status').on('change', function() {
            const selected = $(this).val();
            if (selected === 'reject') {
                $('#reason_wrapper').removeClass('d-none');
                $('#cancellation_reason').attr('required', true);
            } else {
                $('#reason_wrapper').addClass('d-none');
                $('#cancellation_reason').val('');
                $('#cancellation_reason').removeAttr('required');
            }
        });


        // Handle form submission
        $('#cancelSubscriptionForm').on('submit', function(e) {
            e.preventDefault();

            const selectedStatus = $('input[name="marked_status"]:checked').val();
            const reason = $('#cancellation_reason').val().trim();


            // If status is required
            if (!selectedStatus) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a status.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // If Reject is selected but no reason
            if (selectedStatus === 'Reject' && !reason) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'The reason field is required for rejection.',
                    confirmButtonColor: '#3085d6'
                });
                return;
            }

            // Gather form data manually
            const formData = new FormData(this);
            formData.append('marked_status', selectedStatus);

            // Confirm dialog
            Swal.fire({
                title: 'Are you sure?',
                text: "",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: Object.fromEntries(formData),
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Please wait a while...',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            $('#cancel_subscription').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Status has been updated successfully.',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                $('#cancellation_reason').val('');
                                window.location.reload();
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred while updating status.';
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



       
</script>

//card view
<script>
    let orders = [];
    let currentFilters = {};
    let currentPage = 1;
    let hasMorePages = false;
    let totalOrders = 0;
    let isLoading = false;

    // Load orders data
    async function loadOrders(filters = {}, page = 1, append = false) {
        try {
            if (isLoading) return; // Prevent concurrent requests
            isLoading = true;
            
            if (!append) {
                showLoading();
                orders = []; // Reset orders array for new search
            }
            
            // Show loading state for Load More button
            if (append) {
                showLoadMoreSpinner(true);
            }
            
            const params = new URLSearchParams({
                ...filters,
                page: page,
                per_page: 12
            });
            const url = `/admin/domains/orders/card/data?${params}`;
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                throw new Error(`Failed to fetch orders: ${response.status} ${response.statusText}`);
            }
              
            const data = await response.json();
            
            const newOrders = data.data || [];
            
            if (append) {
                orders = orders.concat(newOrders);
            } else {
                orders = newOrders;
            }
            
            // Update pagination state
            const pagination = data.pagination || {};
            currentPage = pagination.current_page || 1;
            hasMorePages = pagination.has_more_pages || false;
            totalOrders = pagination.total || 0;
            
            renderOrders(append);
            updatePaginationInfo();
            updateLoadMoreButton();
            
        } catch (error) {
            console.error('Error loading orders:', error);
            if (!append) {
                showError(`Failed to load orders: ${error.message}`);
            }
        } finally {
            isLoading = false;
            if (append) {
                showLoadMoreSpinner(false);
            }

            // submit enabled
            document.getElementById('submitBtn').disabled = false;
        }
    }
    
    // Show loading state        
    function showLoading() {
        const container = document.getElementById('ordersContainer');
        const loadingElement = document.getElementById('loadingState');
        
        if (container && loadingElement) {
            // Keep the grid display but show only loading element
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
            container.style.gap = '1rem';
            
            // Clear any existing content except loading
            container.innerHTML = '';
            container.appendChild(loadingElement);
            loadingElement.style.display = 'flex';
        }
    }
    
    // Hide loading state
    function hideLoading() {
        const loadingElement = document.getElementById('loadingState');
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }        
    
    // Show error message
    function showError(message) {
        hideLoading();
        const container = document.getElementById('ordersContainer');
        if (!container) {
            console.error('ordersContainer element not found');
            return;
        }
        
        // Keep grid layout but show error spanning full width
        container.style.display = 'grid';
        container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
        container.style.gap = '1rem';
        
        container.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                <h5>Error</h5>
                <p class="mb-3">${message}</p>
                <button class="btn btn-primary" onclick="loadOrders(currentFilters)">Retry</button>
            </div>
        `;
    }
    
    // Render orders
    function renderOrders(append = false) {
        if (!append) {
            hideLoading();
        }
        
        const container = document.getElementById('ordersContainer');
        if (!container) {
            console.error('ordersContainer element not found');
            return;
        }
          
        if (orders.length === 0 && !append) {
            // Keep grid layout but show empty state spanning full width
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
            container.style.gap = '1rem';
            
            container.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                    <i class="fas fa-inbox text-white mb-3" style="font-size: 3rem;"></i>
                    <h5>No Orders Found</h5>
                    <p class="mb-3">No orders match your current filters.</p>
                    <button class="btn btn-outline-primary" onclick="resetFilters()">Clear Filters</button>
                </div>
            `;
            return;
        }
        // Reset container to grid layout for orders
        container.style.display = 'grid';
        container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
        container.style.gap = '1rem';

        if (append) {
            // Only add new orders for pagination
            const currentOrdersCount = container.children.length;
            const newOrders = orders.slice(currentOrdersCount);
           
            container.insertAdjacentHTML('beforeend', newOrdersHtml);
            
            // Start timers for new orders
            
        } 
    }
   
    // Update pagination info display
    function updatePaginationInfo() {
        const showingFromEl = document.getElementById('showingFrom');
        const showingToEl = document.getElementById('showingTo');
        const totalOrdersEl = document.getElementById('totalOrders');
        
        if (showingFromEl && showingToEl && totalOrdersEl) {
            const from = orders.length > 0 ? 1 : 0;
            const to = orders.length;
            
            showingFromEl.textContent = from;
            showingToEl.textContent = to;
            totalOrdersEl.textContent = totalOrders;
        }
    }

    // Update Load More button visibility and state
    function updateLoadMoreButton() {
        const loadMoreContainer = document.getElementById('loadMoreContainer');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (loadMoreContainer && loadMoreBtn) {
            if (hasMorePages && orders.length > 0) {
                loadMoreContainer.style.display = 'block';
                loadMoreBtn.disabled = false;
            } else {
                loadMoreContainer.style.display = 'none';
            }
        }
    }

    // Show/hide loading spinner on Load More button
    function showLoadMoreSpinner(show) {
        const loadMoreText = document.getElementById('loadMoreText');
        const loadMoreSpinner = document.getElementById('loadMoreSpinner');
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        
        if (loadMoreText && loadMoreSpinner && loadMoreBtn) {
            if (show) {
                loadMoreText.textContent = 'Loading...';
                loadMoreSpinner.style.display = 'inline-block';
                loadMoreBtn.disabled = true;
            } else {
                loadMoreText.textContent = 'Load More';
                loadMoreSpinner.style.display = 'none';
                loadMoreBtn.disabled = false;
            }
        }
    }

    // Load More button click handler
    function loadMoreOrders() {
        if (hasMorePages && !isLoading) {
            loadOrders(currentFilters, currentPage + 1, true);
        }
    }

    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'completed': return 'bg-success';
            case 'unallocated': return 'bg-warning text-dark';
            case 'allocated': return 'bg-info';
            case 'rejected': return 'bg-danger';
            case 'in-progress': return 'bg-primary';
            default: return 'bg-secondary';
        }
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
        } catch (error) {
            return 'Invalid Date';
        }
    }

 

   
        function parseUTCDateTime(dateStr) {
            const [datePart, timePart] = dateStr.split(" ");
            const [year, month, day] = datePart.split("-").map(Number);
            const [hour, minute, second] = timePart.split(":").map(Number);
            return new Date(Date.UTC(year, month - 1, day, hour, minute, second));
        }

  
 
    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'completed': return 'bg-success';
            case 'unallocated': return 'bg-warning text-dark';
            case 'allocated': return 'bg-info';
            case 'rejected': return 'bg-danger';
            case 'in-progress': return 'bg-primary';
            default: return 'bg-secondary';
        }
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: '2-digit'
            });
        } catch (error) {
            return 'Invalid Date';
        }
    }

    // Handle filter form submission
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const filters = {};
        
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '') {
                filters[key] = value.trim();
            }
        }
        
        currentFilters = filters;
        currentPage = 1;
        hasMorePages = false;
        totalOrders = 0;
        loadOrders(filters);
    });
      
    // Reset filters
    function resetFilters() {
        document.getElementById('filterForm').reset();
        currentFilters = {};
        currentPage = 1;
        hasMorePages = false;
        totalOrders = 0;
        loadOrders();
    }
    
    // Reset filters button
    document.getElementById('resetFilters').addEventListener('click', resetFilters);

    // Global cleanup function for offcanvas issues
    function cleanupOffcanvasBackdrop() {
        // Remove any remaining backdrop elements
        const backdrops = document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop, .fade');
        backdrops.forEach(backdrop => {
            if (backdrop.classList.contains('offcanvas-backdrop') || backdrop.classList.contains('modal-backdrop')) {
                backdrop.remove();
            }
        });
        
        // Reset body styles
        document.body.classList.remove('offcanvas-open', 'modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Add global event listener for offcanvas cleanup
    document.addEventListener('click', function(e) {
        // If clicking outside offcanvas or on close button, ensure cleanup
        if (e.target.matches('[data-bs-dismiss="offcanvas"]') || 
            e.target.closest('[data-bs-dismiss="offcanvas"]')) {
            setTimeout(cleanupOffcanvasBackdrop, 300);
        }
    });

    // Cleanup on page focus (in case of any lingering issues)
    window.addEventListener('focus', cleanupOffcanvasBackdrop);

   
    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        // Clean up any existing backdrop issues on page load
        cleanupOffcanvasBackdrop();
        
        // Add Load More button event handler
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', loadMoreOrders);
        }
        
        // Load orders immediately
        loadOrders();
        
       
    });
   
</script>

@endpush