@extends('admin.layouts.app')

@section('title', 'Panels')

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
                    <small>Click here to open advance search for a table</small>
                </div>
            </div>
            <div class="row collapse" id="filter_1">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-0">Inbox Name</label>
                        <input type="text" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-0">Min Inbox Limit</label>
                        <input type="number" class="form-control" placeholder="e.g. 10">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-0">Max Inbox Limit</label>
                        <input type="number" class="form-control" placeholder="e.g. 100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-0">Min Remaining</label>
                        <input type="number" class="form-control" placeholder="e.g. 5">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-0">Max Remaining</label>
                        <input type="number" class="form-control" placeholder="e.g. 50">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label mb-0">Order</label>
                        <select class="form-select">
                            <option selected disabled>Select order</option>
                            <option value="asc">Ascending</option>
                            <option value="desc">Descending</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm me-2 px-3">Reset</button>
                        <button type="submit" class="btn btn-primary btn-sm border-0 px-3">Search</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Cards (Sample) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
            <div class="card p-3 d-flex flex-column gap-1">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-0">Inbox Name</h6>
                    <div class="d-flex gap-2">
                        <small class="total">Total</small>
                        <small class="remain">Remaining</small>
                        <small class="used">Used</small>
                    </div>
                </div>
                <div id="chart"></div>
                <h6 class="mb-0">Orders</h6>
                <div style="background-color: #5750bf89; border: 1px solid var(--second-primary);"
                    class="p-2 rounded-1 d-flex align-items-center justify-content-between gap-2">
                    <small>5 New Orders of Inboxes</small>
                    {{-- <small>Inboxes: 100</small> --}}
                    <button style="font-size: 12px" data-bs-toggle="offcanvas" data-bs-target="#order-view"
                        aria-controls="order-view" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary">
                        View
                    </button>
                </div>
            </div>
        </div>

    </section>



    <div class="offcanvas offcanvas-end" style="width: 100%" tabindex="-1" id="order-view" aria-labelledby="order-viewLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="order-viewLabel">Orders View</h5>
            <div type="button" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fa-solid fa-xmark fs-4" style="color: var(--light-color)"></i>
            </div>
        </div>
        <div class="offcanvas-body">
            <div class="accordion accordion-flush" id="accordionFlushExample">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <div class="button p-3 collapsed d-flex align-items-center justify-content-between" type="button"
                            data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false"
                            aria-controls="flush-collapseOne">
                            <small>ID: #123</small>
                            <small>Inboxes: 100</small>
                            <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary">
                                View
                            </button>
                        </div>
                    </h2>
                    <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Order ID</th>
                                            <th scope="col">Title</th>
                                            <th scope="col">Domain</th>
                                            <th scope="col">Quantity</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th scope="row">1</th>
                                            <td>ORD-1024</td>
                                            <td>Website Development</td>
                                            <td>IT Services</td>
                                            <td>1</td>
                                            <td><span class="badge bg-success">Completed</span></td>
                                            <td>2025-06-03</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">2</th>
                                            <td>ORD-1025</td>
                                            <td>Domain Hosting</td>
                                            <td>Hosting</td>
                                            <td>3</td>
                                            <td><span class="badge bg-warning text-dark">Pending</span></td>
                                            <td>2025-06-02</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">3</th>
                                            <td>ORD-1026</td>
                                            <td>SEO Optimization</td>
                                            <td>Marketing</td>
                                            <td>1</td>
                                            <td><span class="badge bg-danger">Cancelled</span></td>
                                            <td>2025-06-01</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <div class="button p-3 collapsed d-flex align-items-center justify-content-between" type="button"
                            data-bs-toggle="collapse" data-bs-target="#flush-collapseTwo" aria-expanded="false"
                            aria-controls="flush-collapseTwo">
                            <small>ID: #123</small>
                            <small>Inboxes: 100</small>
                            <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary">
                                View
                            </button>
                        </div>
                    </h2>
                    <div id="flush-collapseTwo" class="accordion-collapse collapse"
                        data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">Placeholder content for this accordion, which is intended to
                            demonstrate the <code>.accordion-flush</code> class. This is the second item’s accordion
                            body. Let’s imagine this being filled with some actual content.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <div class="button p-3 collapsed d-flex align-items-center justify-content-between" type="button"
                            data-bs-toggle="collapse" data-bs-target="#flush-collapseThree" aria-expanded="false"
                            aria-controls="flush-collapseThree">
                            <small>ID: #123</small>
                            <small>Inboxes: 100</small>
                            <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary">
                                View
                            </button>
                        </div>
                    </h2>
                    <div id="flush-collapseThree" class="accordion-collapse collapse"
                        data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">Placeholder content for this accordion, which is intended to
                            demonstrate the <code>.accordion-flush</code> class. This is the third item’s accordion
                            body. Nothing more exciting happening here in terms of content, but just filling up the
                            space to make it look, at least at first glance, a bit more representative of how this
                            would look in a real-world application.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const total = 1200;
        const used = 800;
        const remaining = total - used;

        const options = {
            series: [
                total,
                Math.round((used / total) * 100),
                Math.round((remaining / total) * 100)
            ],
            chart: {
                height: 220,
                type: 'radialBar',
            },
            plotOptions: {
                radialBar: {
                    offsetY: 0,
                    startAngle: 0,
                    endAngle: 270,
                    hollow: {
                        size: '40%',
                    },
                    dataLabels: {
                        name: {
                            show: true
                        },
                        value: {
                            show: false
                        }
                    }
                }
            },
            colors: ['#5750bf', '#2AC747', '#FDC007'],
            labels: [
                `Total: ${total}`,
                `Used: ${used}`,
                `Remaining: ${remaining}`
            ],
            legend: {
                show: true,
                position: 'bottom',
                formatter: function(seriesName, opts) {
                    const rawValue = seriesName === 'Used' ? used : remaining;
                    return `${seriesName}: ${rawValue}`;
                },
                labels: {
                    useSeriesColors: true
                },
                itemMargin: {
                    vertical: 5
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();
    </script>
@endpush
