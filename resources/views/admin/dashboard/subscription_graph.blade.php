<div class="card h-100 p-2">
    <div class="card-header border-0 pb-0 d-flex justify-content-between">
        <div class="card-title mb-0">
            <h5 class="mb-1">Subscriptions Overview</h5>
        </div>
        <ul class="nav nav-pills mb-3 d-flex align-items-center justify-content-end" id="pills-tab"
                    role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pills-month-tab" data-bs-toggle="pill"
                            data-bs-target="#pills-month" type="button" role="tab" aria-controls="pills-month"
                            aria-selected="true">Month</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-week-tab" data-bs-toggle="pill" data-bs-target="#pills-week"
                            type="button" role="tab" aria-controls="pills-week" aria-selected="false">Week</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pills-day-tab" data-bs-toggle="pill" data-bs-target="#pills-day"
                            type="button" role="tab" aria-controls="pills-day" aria-selected="false">Day</button>
                    </li>
                </ul>
    </div>

    <div class="card-body pt-0">
        <div class="row align-items-center g-md-8">
            <div class="col-12 col-md-5 d-flex flex-column">
                <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                    <h1 class="mb-2">$468</h1>
                    <div class="badge rounded bg-label-success">+4.2%</div>
                </div>
                <small>You informed of this week compared to last week</small>
            </div>

            <div class="col-12 col-md-7">

                <div class="tab-content" id="pills-tabContent">
                    <div class="tab-pane fade show active" id="pills-month" role="tabpanel"
                        aria-labelledby="pills-month-tab" tabindex="0">
                        <div style="margin-bottom: 1rem;">
                            <label for="monthSelector" style="margin-right: 8px;">Select Month:</label>
                            <select class="form-select" id="monthSelector">
                                <option value="jan">January</option>
                                <option value="feb">February</option>
                                <option value="mar">March</option>
                                <option value="apr">April</option>
                                <option value="may">May</option>
                                <option value="jun">June</option>
                                <option value="jul">July</option>
                                <option value="aug">August</option>
                                <option value="sep">September</option>
                                <option value="oct">October</option>
                                <option value="nov">November</option>
                                <option value="dec">December</option>
                            </select>
                        </div>
                        <div id="monthLineChartSubscription"></div>
                    </div>
                    <div class="tab-pane fade" id="pills-week" role="tabpanel" aria-labelledby="pills-week-tab"
                        tabindex="0">
                        <div id="weekBarChartSubscription"></div>
                    </div>
                    <div class="tab-pane fade" id="pills-day" role="tabpanel" aria-labelledby="pills-day-tab"
                        tabindex="0">
                        <div id="dayBarChartSubscription"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    var options = {
    series: [{
        data: [20, 40, 35, 30, 60, 40, 45]
    }],
    chart: {
        type: 'bar',
        height: 180,
        toolbar: {
            show: false
        },
    },
    plotOptions: {
        bar: {
            borderRadius: 3,
            columnWidth: '40%',
            distributed: true
        }
    },
    colors: ['#3D3D66', '#3D3D66', '#3D3D66', '#3D3D66', '#7F6CFF', '#3D3D66', '#3D3D66'],
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'],
        labels: {
            style: {
                colors: '#A3A9BD',
                fontSize: '12px'
            }
        },
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        }
    },
    yaxis: {
        show: false
    },
    grid: {
        show: false
    },
    tooltip: {
        enabled: false
    }
};

var chart = new ApexCharts(document.querySelector("#dayBarChartSubscription"), options);
chart.render();

// WEEK - Subscription Chart
var options = {
    series: [{
        data: [20, 40, 35, 30, 60, 40, 45]
    }],
    chart: {
        type: 'bar',
        height: 180,
        toolbar: {
            show: false
        },
    },
    plotOptions: {
        bar: {
            borderRadius: 3,
            columnWidth: '40%',
            distributed: true
        }
    },
    colors: ['#3D3D66', '#3D3D66', '#3D3D66', '#3D3D66', '#7F6CFF', '#3D3D66', '#3D3D66'],
    dataLabels: {
        enabled: false
    },
    xaxis: {
        categories: ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'],
        labels: {
            style: {
                colors: '#A3A9BD',
                fontSize: '12px'
            }
        },
        axisBorder: {
            show: false
        },
        axisTicks: {
            show: false
        }
    },
    yaxis: {
        show: true,
    },
    grid: {
        show: false
    },
    tooltip: {
        enabled: true
    }
};
var chart = new ApexCharts(document.querySelector("#weekBarChartSubscription"), options);
chart.render();

// MONTH - Subscription Chart
var options = {
    series: [{
        data: [0, 40, 35, 70, 60, 80, 50]
    }],
    chart: {
        type: 'area',
        height: 180,
        sparkline: {
            enabled: true
        }
    },
    stroke: {
        curve: 'smooth',
        width: 2,
        colors: ['#00e396']
    },
    fill: {
        colors: ['rgba(0,227,150,0.6162114504004728)'],
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0,
            stops: [0, 90, 100]
        }
    },
    tooltip: {
        enabled: true,
        shared: true,
        followCursor: true,
        intersect: false,
        hideEmptySeries: true,
        theme: true,
        x: {
            show: true,
            format: 'dd MMM',
        },
        marker: {
            show: true,
        },
    }
};
var chart = new ApexCharts(document.querySelector("#monthLineChartSubscription"), options);
chart.render();
</script>