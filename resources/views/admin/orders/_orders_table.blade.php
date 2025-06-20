<div class="table-responsive">
    <table class="display w-100" id="{{ isset($plan_id) ? 'myTable-'.$plan_id : 'myTable' }}">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Name</th>
                <th>Email</th>
                @if(!isset($plan_id))
                <th>Plan</th>
                @endif
                <th>Split Counts</th>
                <th>Total Inboxes</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>


<!-- Cancel Subscription Modal -->
<div class="modal fade" id="cancel_subscription" tabindex="-1" aria-labelledby="cancel_subscriptionLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="d-flex flex-column align-items-center justify-content-start gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-cart-plus"></i>
                    </div>
                    Mark Status
                </h6>

                <form id="cancelSubscriptionForm" action="{{ route('admin.order.cancel.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="chargebee_subscription_id" id="subscription_id_to_cancel">
                    <div class="mb-3">
                        <div class="">

                            <div class="mb-3 d-none" id="reason_wrapper">
                                <p class="note">
                                    Would you mind sharing the reason
                                    for the rejection?
                                </p>
                                <label for="cancellation_reason">Reason *</label>
                                <textarea id="cancellation_reason" name="reason" class="form-control"
                                    rows="5"></textarea>
                            </div>
                        </div>
                        <label class="form-label">Select Status *</label>
                        <div class="d-flex flex-wrap gap-2">
                            @php
                            $statuses1 = \App\Models\Status::get();
                            @endphp
                            @foreach($statuses1 as $status)
                            <div class="form-check me-3">
                                <input class="form-check-input marked_status" type="radio" name="marked_status"
                                    value="{{ $status->name }}" id="status_{{ $loop->index }}" required>
                                <label class="form-check-label" for="status_{{ $loop->index }}">
                                    {{ ucfirst($status->name) }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>



                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button"
                            class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                            data-bs-dismiss="modal">No</button>
                        <button type="submit"
                            class="border border-danger py-1 px-3 w-100 bg-transparent text-danger rounded-2">Yes,
                            I'm sure</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Orders Offcanvas -->
<div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-view"
    aria-labelledby="order-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="order-viewLabel">Order splits</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div id="panelOrdersContainer">
            <!-- Dynamic content will be loaded here -->
            <div id="ordersLoadingState" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading order splits ...</span>
                </div>
                <p class="mt-2">Loading orders splits...</p>
            </div>
        </div>
    </div>
</div>