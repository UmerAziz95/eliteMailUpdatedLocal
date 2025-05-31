@extends('contractor.layouts.app')

@section('title', 'panel')

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
                <h6>Inbox Name</h6>
                <div id="chart"></div>
                <h6 class="mb-0">Orders</h6>
                <div style="background-color: #5750bf89; border: 1px solid var(--second-primary);" class="p-2 rounded-1 d-flex align-items-center justify-content-between gap-2">
                    <small>Order ID: #123</small>
                    <small>Inboxes: 100</small>
                    <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary">
                        Accept
                    </button>
                </div>
            </div>

            <div class="card p-3 d-flex flex-column gap-1">
                <h6>Inbox Name</h6>
                <div id="chart"></div>
                <h6>Orders</h6>
            </div>

            <div class="card p-3 d-flex flex-column gap-1">
                <h6>Inbox Name</h6>
                <div id="chart"></div>
                <small>Order</small>
            </div>

            <div class="card p-3 d-flex flex-column gap-1">
                <h6>Inbox Name</h6>
                <div id="chart"></div>
                <small>Order</small>
            </div>
        </div>

    </section>

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
            colors: ['#5750bf', '#2AC747', '#FDC007'], // Used and Remaining
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
