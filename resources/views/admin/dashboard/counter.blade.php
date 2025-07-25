<div class="row h-100 ">
    <!-- Total Customers -->
    <div class="col-xl-6 col-sm-6 pb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start h-100">
                    <div class="d-flex flex-column justify-content-between h-100">
                        <h6 class="mb-1 fw-semibold">Customers</h6>
                        <h4 class="mb-0">{{ $totalCustomers ?? 0 }}</h4>
                    </div>
                    <div class="icon rounded p-2">
                        <i class="fa-solid fa-users fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Contractors -->
    <div class="col-xl-6 col-sm-6 pb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start h-100">
                    <div class="d-flex flex-column justify-content-between h-100">
                        <h6 class="mb-1 fw-semibold">Contractors</h6>
                        <h4 class="mb-0">{{ $totalContractors ?? 0 }}</h4>
                    </div>
                    <div class="icon rounded rounded p-2">
                        <i class="fa-solid fa-user-tie fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Inboxes Sold -->
    <div class="col-xl-6 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start h-100">
                    <div class="d-flex justify-content-between flex-column h-100">
                        <h6 class="mb-1 fw-semibold">Inboxes Sold</h6>
                        <h4 class="mb-0">{{ $totalInboxesSold ?? 0 }}</h4>
                    </div>
                    <div class="icon rounded rounded p-2">
                        <i class="fa-solid fa-inbox fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Inboxes Completed -->
    <div class="col-xl-6 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start h-100">
                    <div class="d-flex justify-content-between flex-column h-100">
                        <h6 class="mb-1 fw-semibold">Inboxes Completed</h6>
                        <h4 class="mb-0">{{ $completedOrdersTotalInboxes ?? 0 }}</h4>
                    </div>
                    <div class="icon rounded rounded p-2">
                        <i class="fa-solid fa-inbox fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>