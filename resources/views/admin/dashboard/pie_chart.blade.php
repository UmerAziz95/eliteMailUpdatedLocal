<div class="card h-100">
    <div class="d-flex flex-column flex-sm-row align-items-start">
        <div class="d-flex flex-sm-column justify-content-between w-100 border-0 px-3 pb-0">
            <div class="mt-lg-4 mb-lg-6 mb-2">
                <h5 class="mb-0">Total Tickets</h5>
                <p class="mb-0">0</p>
            </div>
            
            
           <ul class="p-0 m-0">
                <li class="d-flex gap-3 align-items-start mb-2">
                    <div class="badge rounded bg-label-primary mt-1">
                        <i class="ti ti-ticket theme-text fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-nowrap">Open Tickets</h6>
                        <p class="small opacity-75">{{ $newTickets ?? 0 }}</p>
                    </div>
                </li>
                <li class="d-flex gap-3 align-items-start mb-2">
                    <div class="badge rounded bg-label-info mt-1">
                        <i class="ti ti-clock fs-4 text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-nowrap">In Progress</h6>
                        <p class="small opacity-75">{{ $inProgressTickets ?? 0 }}</p>
                    </div>
                </li>
                <li class="d-flex gap-3 align-items-start pb-1">
                    <div class="badge rounded bg-label-success mt-1">
                        <i class="ti ti-check fs-4 text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-nowrap">Closed</h6>
                        <p class="small opacity-75">{{ $resolvedTickets ?? 0 }}</p>
                    </div>
                </li>
            </ul>
        </div>
        <div class="">
            {{-- <div id="salesChart"></div> --}}
            <div id="pieChart"></div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    const options = {
        series: [1,2,3],
        chart: {
            type: 'pie',
            height: 400,
            dropShadow: {
                enabled: true,
                color: '#000',
                top: -1,
                left: 3,
                blur: 5,
                opacity: 0.1
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 350
                }
            }
        },
        labels: ["Open", "In-Progress", "Closed"],
        colors: ['#7367ef', '#00CFE8', '#28C76F'],
        legend: {
            position: 'bottom',
            fontSize: '14px'
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return opts.w.config.series[opts.seriesIndex];
            },
            style: {
                fontSize: '14px'
            },
            dropShadow: {
                enabled: false
            }
        },
        stroke: {
            width: 0, // Removing white lines between slices
        },
        states: {
            hover: {
                filter: {
                    type: 'darken',
                    value: 0.15
                }
            }
        },
        plotOptions: {
            pie: {
                expandOnClick: false,
                donut: {
                    size: '10%'
                },
                offsetX: 0,
                offsetY: 0,
                customScale: 0.95,
                startAngle: 0,
                endAngle: 360,
                hover: {
                    enabled: true,
                    offsetX: 0,
                    offsetY: 0,
                    size: '35%' // Increased for more dramatic separation on hover
                }
            }
        },
        fill: {
            type: 'gradient'
        },
        tooltip: {
            enabled: true,
            theme: 'dark',
            style: {
                fontSize: '14px'
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 250
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#pieChart"), options);
    chart.render();
</script>