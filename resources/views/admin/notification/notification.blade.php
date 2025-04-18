@extends('admin.layouts.app')

@section('title', 'Notifications')

@push('styles')
    <style>
        .notification-card {
            margin-bottom: 1rem;
        }

        .notification-header {
            font-weight: bold;
        }

        .notification-time {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .notification-unread {
            background-color: #7a7a7a37;
        }

        .notification-read {
            background-color: #7a7a7a37;
        }

        .notification-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div class="card py-3 px-4">
            <h4 class="mb-4">Notification Center</h4>

            <!-- Unread Notifications -->
            <div class="mb-4">
                <h5>Unread Notifications</h5>
                <div class="card notification-card notification-unread">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-envelope notification-icon text-primary"></i>
                        <div>
                            <div class="notification-header">New Message Received</div>
                            <div class="notification-time">2 minutes ago</div>
                            <div class="notification-content">You have a new message from John Doe.</div>
                        </div>
                    </div>
                </div>
                <div class="card notification-card notification-unread">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-exclamation-circle notification-icon text-warning"></i>
                        <div>
                            <div class="notification-header">System Alert</div>
                            <div class="notification-time">10 minutes ago</div>
                            <div class="notification-content">Your account password will expire in 3 days.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Read Notifications -->
            <div>
                <h5>Read Notifications</h5>
                <div class="card notification-card notification-read">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-check-circle notification-icon text-success"></i>
                        <div>
                            <div class="notification-header">Payment Successful</div>
                            <div class="notification-time">1 day ago</div>
                            <div class="notification-content">Your payment of $100 has been processed successfully.</div>
                        </div>
                    </div>
                </div>
                <div class="card notification-card notification-read">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-calendar-check notification-icon text-info"></i>
                        <div>
                            <div class="notification-header">Event Reminder</div>
                            <div class="notification-time">2 days ago</div>
                            <div class="notification-content">Don't forget your meeting scheduled for tomorrow at 10 AM.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card notification-card notification-read">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="avatar">
                                <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                    style="border-radius: 50%" height="40" width="40" class="object-fit-cover"
                                    alt="">
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="small mb-2">Congratulation Lettie 🎉</h6>
                            <small class="mb-1 d-block opacity-75">Won the monthly best seller gold badge</small>
                            <small class="opacity-50">1h ago</small>
                        </div>
                        <div class="flex-shrink-0 dropdown-notifications-actions">
                            <a href="javascript:void(0)" class="dropdown-notifications-read"><span
                                    class="badge badge-dot"></span></a>
                            <a href="javascript:void(0)" class="dropdown-notifications-archive"><span
                                    class="icon-base ti tabler-x"></span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script></script>
@endpush
