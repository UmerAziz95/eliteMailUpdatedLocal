<div class="card h-100 p-2">
    <div class="card-header border-0 pb-0 d-flex justify-content-between">
        <div class="card-title mb-0">
            <h5 class="mb-1">Earning Reports</h5>
        </div>
        <ul class="nav nav-pills mb-3 d-flex align-items-center justify-content-end" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-month-tab" data-bs-toggle="pill"
                    data-bs-target="#pills-month-revenue" type="button" role="tab" aria-controls="pills-month-revenue"
                    aria-selected="true">Month</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-week-tab" data-bs-toggle="pill" data-bs-target="#pills-week-revenue"
                    type="button" role="tab" aria-controls="pills-week-revenue" aria-selected="false">Week</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-day-tab" data-bs-toggle="pill" data-bs-target="#pills-day-revenue"
                    type="button" role="tab" aria-controls="pills-day-revenue" aria-selected="false">Day</button>
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
                <small class="">You informed of this week compared to last week</small>
            </div>

            <div class="col-12 col-md-7">

                <div class="tab-content" id="pills-tabContent">
                    <!-- Month Tab -->
                    <div class="tab-pane fade show active" id="pills-month-revenue" role="tabpanel"
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
                        <div id="monthLineChartRevenue"></div>
                    </div>

                    <!-- Week Tab -->
                    <div class="tab-pane fade" id="pills-week-revenue" role="tabpanel" aria-labelledby="pills-week-tab"
                        tabindex="0">
                        <div id="weekBarChartRevenue"></div>
                    </div>

                    <!-- Day Tab -->
                    <div class="tab-pane fade" id="pills-day-revenue" role="tabpanel" aria-labelledby="pills-day-tab"
                        tabindex="0">
                        <div id="dayBarChartRevenue"></div>
                    </div>
                </div>
            </div>
        </div>

        {{--
        <!-- Footer Section -->
        <div class="rounded p-4 mt-4" style="border: 1px solid var(--input-border);">
            <div class="row gap-4 gap-sm-0">
                <div class="col-12 col-sm-4">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="badge rounded bg-label-primary p-1">
                            <i class="ti ti-currency-dollar theme-text fs-5"></i>
                        </div>
                        <h6 class="mb-0 fw-normal" style="font-size: 12px;">Earnings</h6>
                    </div>
                    <h4 class="my-2 fs-6">$545.69</h4>
                    <div class="progress w-75" style="height:4px">
                        <div class="progress-bar" role="progressbar" style="width: 65%" aria-valuenow="65"
                            aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="badge rounded bg-label-info p-1">
                            <i class="ti ti-clock-share text-info fs-5"></i>
                        </div>
                        <h6 class="mb-0 fw-normal" style="font-size: 12px;">Profit</h6>
                    </div>
                    <h4 class="my-2 fs-6">$256.34</h4>
                    <div class="progress w-75" style="height:4px">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="badge rounded bg-label-danger p-1">
                            <i class="ti ti-brand-paypal text-danger fs-5"></i>
                        </div>
                        <h6 class="mb-0 fw-normal" style="font-size: 12px;">Expense</h6>
                    </div>
                    <h4 class="my-2 fs-6">$74.19</h4>
                    <div class="progress w-75" style="height:4px">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 65%" aria-valuenow="65"
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div> --}}
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
            height: 250,
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
        colors: [
            '#3D3D66', '#3D3D66', '#3D3D66', '#3D3D66', '#7F6CFF', '#3D3D66', '#3D3D66'
        ],
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

    var chart = new ApexCharts(document.querySelector("#dayBarChartRevenue"), options);
    chart.render();

    var options = {
        series: [{
            data: [20, 40, 35, 30, 60, 40, 45]
        }],
        chart: {
            type: 'bar',
            height: 250,
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
        colors: [
            '#3D3D66', '#3D3D66', '#3D3D66', '#3D3D66', '#7F6CFF', '#3D3D66', '#3D3D66'
        ],
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

    var chart = new ApexCharts(document.querySelector("#weekBarChartRevenue"), options);
    chart.render();


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
            enabledOnSeries: true,
            shared: true,
            followCursor: true,
            intersect: false,
            inverseOrder: true,
            custom: undefined,
            hideEmptySeries: true,
            fillSeriesColor: false,
            theme: "true",
            style: {
                fontSize: '12px',
                fontFamily: undefined,
            },
            onDatasetHover: {
                highlightDataSeries: false,
            },
            x: {
                show: true,
                format: 'dd MMM',
                formatter: undefined,
            },
            y: {
                formatter: undefined,
                // title: {
                //     formatter: (seriesName) => seriesName,
                // },
            },
            z: {
                formatter: undefined,
                title: 'Size: '
            },
            marker: {
                show: true,
            },
            // items: {
            //     display: flex,
            // },
            fixed: {
                enabled: false,
                position: 'topRight',
                offsetX: 0,
                offsetY: 0,
            },
        }
    };

    var chart = new ApexCharts(document.querySelector("#monthLineChartRevenue"), options);
      chart.render();
</script>