<div class="table-responsive">
    <table class="w-100" id="{{ isset($plan_id) ? 'myTable-'.$plan_id : 'myTable' }}">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                @if(!isset($plan_id))
                <th>Plan</th>
                @endif
                <th>Domain URL</th>
                <th>Total Inboxes</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
