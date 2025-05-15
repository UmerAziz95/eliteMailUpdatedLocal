<div class="card h-100">
    <div class="d-flex flex-column flex-sm-row align-items-start">
        <div class="d-flex flex-sm-column justify-content-between w-100 border-0 px-3 pt-3 pb-0">
            <div>
                <h6 class="mb-2 ">Average Daily Sales</h6>
                <p class="mb-3 small">Total Sales This Month</p>
                <h4 class="mb-0">$28,450</h4>
            </div>
            <ul class="mt-sm-4 px-3">
                <li class="custom-dot">Total Orders</li>
                <li class="custom-dot">Total Orders</li>
                <li class="custom-dot">Total Orders</li>
                <li class="custom-dot">Total Orders</li>
                <li class="custom-dot">Total Orders</li>
                <li class="custom-dot">Total Orders</li>
                <li class="custom-dot">Total Orders</li>
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
    var options = {
        series: [44, 4, 41, 4, 17, 4, 15, 4, 10, 4, 20, 4, 30, 4],
        chart: {
            width: 330,
            type: 'donut',
            dropShadow: {
                enabled: true,
                color: 'var(--second-primary)',
                top: -1,
                left: 3,
                blur: 5,
                opacity: 1
            }
        },
        stroke: {
            width: 0,
        },
        // plotOptions: {
        //     pie: {
        //         donut: {
        //             labels: {
        //                 show: true,
        //                 total: {
        //                     showAlways: true,
        //                     show: true
        //                 }
        //             }
        //         }
        //     }
        // },
        labels: ["Comedy", "Action", "SciFi", "Drama", "Horror"],
        dataLabels: {
            dropShadow: {
                blur: 2,
                opacity: 1
            }
        },
        fill: {
            type: 'pattern',
            opacity: 1,
            pattern: {
                enabled: false,
                style: ['verticalLines', 'squares', 'horizontalLines', 'circles','slantedLines', 'circles', 'horizontalLines', 'slantedLines', 'verticalLines', 'squares', 'horizontalLines', 'circles', 'slantedLines', 'circles', 'horizontalLines'],
            },
        },
        colors: [
            '#7367ef',  // base purple
            'transparent',  // base purple
            '#7367ef',  // lighter tint
            'transparent',  // lighter tint
            '#7367ef',  // dark shade
            'transparent',  // dark shade
            '#7367ef',  // pastel
            'transparent',  // pastel
            '#7367ef',  // bold deep
            'transparent',   // bold deep
            '#7367ef',  // bold deep
            'transparent',  // bold deep
        ],
        states: {
            hover: {
                filter: 'none'
            }
        },
        theme: {
            palette: 'palette2'
        },
        // title: {
        //     text: "Favourite Movie Type"
        // },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 300
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