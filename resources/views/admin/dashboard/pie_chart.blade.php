<div class="card h-100">
    <div class="d-flex flex-column flex-sm-row align-items-start">
        <div class="d-flex flex-sm-column justify-content-between w-100 border-0 px-3 pb-0">
            <div class="mt-lg-4 mb-lg-6 mb-2">
                <h5 class="mb-0">Total Tickets</h5>
                <p class="mb-0" id="totalTicketCount">{{ ($newTickets ?? 0) + ($inProgressTickets ?? 0) + ($resolvedTickets ?? 0) }}</p>
                
                <div id="tickets_loading_indicator" class="mt-2" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            
            
           <ul class="p-0 m-0">
                <li class="d-flex gap-3 align-items-start mb-2">
                    <div class="badge rounded bg-label-primary mt-1">
                        <i class="ti ti-ticket theme-text fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-nowrap">Open Tickets</h6>
                        <p class="small opacity-75" id="openTicketsCount">{{ $newTickets ?? 0 }}</p>
                    </div>
                </li>
                
                <li class="d-flex gap-3 align-items-start mb-2">
                    <div class="badge rounded bg-label-info mt-1">
                        <i class="ti ti-clock fs-4 text-info"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-nowrap">In Progress</h6>
                        <p class="small opacity-75" id="inProgressTicketsCount">{{ $inProgressTickets ?? 0 }}</p>
                    </div>
                </li>
                <li class="d-flex gap-3 align-items-start pb-1">
                    <div class="badge rounded bg-label-success mt-1">
                        <i class="ti ti-check fs-4 text-success"></i>
                    </div>
                    <div>
                        <h6 class="mb-0 text-nowrap">Closed</h6>
                        <p class="small opacity-75" id="closedTicketsCount">{{ $resolvedTickets ?? 0 }}</p>
                    </div>
                </li>
            </ul>
        </div>
        <div class="">
            <ul class="nav nav-pills mb-3 d-flex align-items-center mt-2" id="tickets-pills-tab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="tickets-pills-month-tab" data-bs-toggle="pill"
                            data-bs-target="#tickets-pills-month" type="button" role="tab" aria-controls="tickets-pills-month"
                            aria-selected="true">Month</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tickets-pills-week-tab" data-bs-toggle="pill" data-bs-target="#tickets-pills-week"
                            type="button" role="tab" aria-controls="tickets-pills-week" aria-selected="false">Week</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tickets-pills-today-tab" data-bs-toggle="pill" data-bs-target="#tickets-pills-today"
                            type="button" role="tab" aria-controls="tickets-pills-today" aria-selected="false">Today</button>
                    </li>
                </ul>
            <div class="tab-content" id="tickets-pills-tabContent">
                <!-- Month Tab -->
                <div class="tab-pane fade show active" id="tickets-pills-month" role="tabpanel"
                    aria-labelledby="tickets-pills-month-tab" tabindex="0">
                    <div style="margin-bottom: 1rem;">
                        <label for="tickets_month_selector" style="margin-right: 8px;">Select Month:</label>
                        <select class="form-select" id="tickets_month_selector">
                            <option value="01">January</option>
                            <option value="02">February</option>
                            <option value="03">March</option>
                            <option value="04">April</option>
                            <option value="05">May</option>
                            <option value="06">June</option>
                            <option value="07">July</option>
                            <option value="08">August</option>
                            <option value="09">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div id="pieChartMonth"></div>
                </div>

                <!-- Week Tab -->
                <div class="tab-pane fade" id="tickets-pills-week" role="tabpanel" aria-labelledby="tickets-pills-week-tab"
                    tabindex="0">
                    <div id="pieChartWeek"></div>
                </div>

                <!-- Today Tab -->
                <div class="tab-pane fade" id="tickets-pills-today" role="tabpanel" aria-labelledby="tickets-pills-today-tab"
                    tabindex="0">
                    <div id="pieChartToday"></div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    // Chart instances and period state
    let ticketChartMonth, ticketChartWeek, ticketChartToday;
    let currentPeriod = 'month';
    
    // Default ticket data (used for initial render and fallback)
    const initialTicketData = {
        open: {{ $newTickets ?? 0 }},
        inProgress: {{ $inProgressTickets ?? 0 }},
        closed: {{ $resolvedTickets ?? 0 }}
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        // Set current month as default in the selector
        const now = new Date();
        const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
        
        const monthSelector = document.getElementById('tickets_month_selector');
        if (monthSelector) {
            monthSelector.value = currentMonth;
        }
        
        // Initialize the charts
        initTicketCharts();
        
        // Add event listeners
        setupEventListeners();
        
        // Load initial data for month tab
        loadTicketData('month', currentMonth);
        
        // Preload data for week and day tabs
        setTimeout(() => {
            loadTicketData('week');
            setTimeout(() => {
                loadTicketData('today');
            }, 300);
        }, 300);
    });
    
    // Initialize the pie charts
    function initTicketCharts() {
        // Create common chart options
        const getChartOptions = () => {
            return {
                series: [
                    initialTicketData.open,
                    initialTicketData.inProgress,
                    initialTicketData.closed
                ],
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
        };

        // Initialize month chart
        ticketChartMonth = new ApexCharts(document.querySelector("#pieChartMonth"), getChartOptions());
        ticketChartMonth.render();
        
        // Initialize week chart
        ticketChartWeek = new ApexCharts(document.querySelector("#pieChartWeek"), getChartOptions());
        ticketChartWeek.render();
        
        // Initialize today chart
        ticketChartToday = new ApexCharts(document.querySelector("#pieChartToday"), getChartOptions());
        ticketChartToday.render();
    }
    
    // Set up event listeners for period buttons and tabs
    function setupEventListeners() {
        // Month selector change event
        const monthSelector = document.getElementById('tickets_month_selector');
        if (monthSelector) {
            monthSelector.addEventListener('change', function() {
                const selectedMonth = this.value;
                
                // Ensure the month tab is selected
                const monthTab = document.getElementById('tickets-pills-month-tab');
                if (monthTab && !monthTab.classList.contains('active')) {
                    monthTab.click();
                }
                
                // Load data for the selected month
                loadTicketData('month', selectedMonth);
            });
        }
        
        // Tab click events
        const monthTab = document.getElementById('tickets-pills-month-tab');
        const weekTab = document.getElementById('tickets-pills-week-tab');
        const todayTab = document.getElementById('tickets-pills-today-tab');
        
        if (monthTab) {
            monthTab.addEventListener('click', function() {
                currentPeriod = 'month';
                const month = document.getElementById('tickets_month_selector').value;
                
                // Force refresh the data
                showTicketLoading(true);
                
                setTimeout(() => {
                    loadTicketData('month', month);
                }, 50);
            });
        }
        
        if (weekTab) {
            weekTab.addEventListener('click', function() {
                currentPeriod = 'week';
                
                // Force refresh the data
                showTicketLoading(true);
                
                setTimeout(() => {
                    loadTicketData('week');
                }, 50);
            });
        }
        
        if (todayTab) {
            todayTab.addEventListener('click', function() {
                currentPeriod = 'today';
                
                // Force refresh the data
                showTicketLoading(true);
                
                setTimeout(() => {
                    loadTicketData('today');
                }, 50);
            });
        }
    }
    
    // Show or hide loading indicator
    function showTicketLoading(isLoading) {
        const loadingIndicator = document.getElementById('tickets_loading_indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = isLoading ? 'block' : 'none';
        }
    }
    
    // Load ticket data for the specified period
    function loadTicketData(period, month = '') {
        showTicketLoading(true);
        
        // Prepare the URL with cache-busting
        let url = `/admin/ticket-stats/?period=${period}&_=${new Date().getTime()}`;
        
        // Add month parameter if provided
        if (period === 'month' && month) {
            url += `&month=${month}`;
        }
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Received ${period} ticket data:`, data);
                updateTicketStats(data, period);
                showTicketLoading(false);
            })
            .catch(error => {
                console.error(`Error fetching ${period} ticket data:`, error);
                
                // Use initial data as fallback
                updateTicketStats({
                    open: initialTicketData.open,
                    inProgress: initialTicketData.inProgress,
                    closed: initialTicketData.closed,
                    total: initialTicketData.open + initialTicketData.inProgress + initialTicketData.closed
                }, period);
                
                showTicketLoading(false);
            });
    }
    
    // Update ticket statistics and chart
    function updateTicketStats(data, period) {
        // Default values in case data is missing
        const openTickets = data.open || 0;
        const inProgressTickets = data.inProgress || 0;
        const closedTickets = data.closed || 0;
        const totalTickets = data.total || (openTickets + inProgressTickets + closedTickets);
        
        // Only update counts in UI if this is the currently active period
        if (period === currentPeriod) {
            document.getElementById('totalTicketCount').textContent = totalTickets;
            document.getElementById('openTicketsCount').textContent = openTickets;
            document.getElementById('inProgressTicketsCount').textContent = inProgressTickets;
            document.getElementById('closedTicketsCount').textContent = closedTickets;
        }
        
        // Update the appropriate chart based on period
        let chartToUpdate;
        switch(period) {
            case 'month':
                chartToUpdate = ticketChartMonth;
                break;
            case 'week':
                chartToUpdate = ticketChartWeek;
                break;
            case 'today':
                chartToUpdate = ticketChartToday;
                break;
            default:
                chartToUpdate = ticketChartMonth;
        }
        
        // Update the chart
        if (openTickets === 0 && inProgressTickets === 0 && closedTickets === 0) {
            // If all values are 0, use placeholder values so chart doesn't show empty
            chartToUpdate.updateSeries([0, 0, 0]);
        } else {
            chartToUpdate.updateSeries([openTickets, inProgressTickets, closedTickets]);
        }
    }
</script>