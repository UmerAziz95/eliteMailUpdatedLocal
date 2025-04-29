<div class="table-responsive">
    <table class="display w-100" id="{{ isset($plan_id) ? 'myTable-'.$plan_id : 'myTable' }}">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                @if(!isset($plan_id))
                <th>Plan</th>
                @endif
                <th>Customer Email</th>
                <th>Customer Name</th> 
                <th>Domain URL</th>
                <th>Status</th>
                <th>Total Inboxes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<!-- Cancel Subscription Modal -->
<div class="modal fade" id="cancel_subscription" tabindex="-1" aria-labelledby="cancel_subscriptionLabel" aria-hidden="true">
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
                    Cancel Subscription
                </h6>

                <p class="note">
                    We are sad to hear you're cancelling. Would you mind sharing the reason
                    for the cancellation? We strive to always improve and would appreciate your
                    feedback.
                </p>

                <form id="cancelSubscriptionForm" action="{{ route('admin.subscription.cancel.process') }}" method="POST">
                    @csrf
                    <input type="hidden" name="chargebee_subscription_id" id="subscription_id_to_cancel">
                    <div class="mb-3">
                        <label for="cancellation_reason">Reason *</label>
                        <textarea id="cancellation_reason" name="reason" class="form-control" rows="8" required></textarea>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_accounts" id="remove_accounts">
                        <label class="form-check-label" for="remove_accounts">
                            I would like to have these email accounts removed and the domains
                            released immediately. I will not be using these inboxes any longer.
                        </label>
                    </div>

                    <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                        <button type="button" class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                            data-bs-dismiss="modal">No, I changed my mind</button>
                        <button type="submit"
                            class="border border-danger py-1 px-3 w-100 bg-transparent text-danger rounded-2">Yes,
                            I'm sure</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>