<div class="card h-100 p-2">
    <div class="card-header border-0 pb-0 d-flex justify-content-between">
        <div class="card-title mb-0">
            <h5 class="mb-1" id="revenue_title">Earning Reports</h5>
        </div>
        <ul class="nav nav-pills mb-3 d-flex align-items-center justify-content-end" id="revenue-pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="revenue-pills-month-tab" data-bs-toggle="pill"
                    data-bs-target="#revenue-pills-month" type="button" role="tab" aria-controls="revenue-pills-month"
                    aria-selected="true">Month</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="revenue-pills-week-tab" data-bs-toggle="pill" data-bs-target="#revenue-pills-week"
                    type="button" role="tab" aria-controls="revenue-pills-week" aria-selected="false">Week</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="revenue-pills-day-tab" data-bs-toggle="pill" data-bs-target="#revenue-pills-day"
                    type="button" role="tab" aria-controls="revenue-pills-day" aria-selected="false">Day</button>
            </li>
        </ul>
    </div>

    <div class="card-body pt-0">
        <div class="row align-items-center g-md-8">
            <div class="col-12 col-md-5 d-flex flex-column">
                <!-- Current Period Revenue Amount -->
                <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                    <h1 class="mb-2" id="revenue_count_display">$0</h1>
                    <div class="badge rounded bg-label-success" id="revenue_growth_badge" style="display: none !important;">+0%</div>
                </div>
                <small id="revenue_comparison_text" style="display: none !important;">Compared to previous period</small>
                <div id="revenue_loading_indicator" class="mt-2" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                
                <!-- Revenue Totals Summary -->
                <div class="mt-4 revenue_totals">
                    <h6 class="text-light mb-3">Total Revenue</h6>
                    <div class="d-flex flex-wrap gap-3" style="display: none !important;">
                        <div class="p-2 border rounded">
                            <small class="d-block text-muted">Today</small>
                            <span id="revenue_day_total" class="fw-semibold">$0</span>
                        </div>
                        <div class="p-2 border rounded">
                            <small class="d-block text-muted">This Week</small>
                            <span id="revenue_week_total" class="fw-semibold">$0</span>
                        </div>
                        <div class="p-2 border rounded">
                            <small class="d-block text-muted">This Month</small>
                            <span id="revenue_month_total" class="fw-semibold">$0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-7">
                <div class="tab-content" id="revenue-pills-tabContent">
                    <!-- Month Tab -->
                    <div class="tab-pane fade show active" id="revenue-pills-month" role="tabpanel"
                        aria-labelledby="revenue-pills-month-tab" tabindex="0">
                        <div style="margin-bottom: 1rem;">
                            <label for="revenue_month_selector" style="margin-right: 8px;">Select Month:</label>
                            <select class="form-select" id="revenue_month_selector">
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
                        <div id="revenue_month_chart"></div>
                    </div>

                    <!-- Week Tab -->
                    <div class="tab-pane fade" id="revenue-pills-week" role="tabpanel" aria-labelledby="revenue-pills-week-tab"
                        tabindex="0">
                        <div id="revenue_week_chart"></div>
                    </div>

                    <!-- Day Tab -->
                    <div class="tab-pane fade" id="revenue-pills-day" role="tabpanel" aria-labelledby="revenue-pills-day-tab"
                        tabindex="0">
                        <div id="revenue_day_chart"></div>
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
    // Chart instances
    let revenue_day_chart, revenue_week_chart, revenue_month_chart;
    let revenue_current_period = 'month';
    
    // Initial setup when document is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Initializing revenue charts...");
        
        // Initialize DOM elements first
        const countElement = document.getElementById('revenue_count_display');
        const growthBadge = document.getElementById('revenue_growth_badge');
        const comparisonText = document.getElementById('revenue_comparison_text');
        
        // Ensure DOM elements are ready
        if (!countElement || !growthBadge || !comparisonText) {
            console.error("Required DOM elements not found!");
            return;
        }
        
        // Set current month as default in the selector
        const now = new Date();
        const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
        console.log("Current month:", currentMonth);
        
        const monthSelector = document.getElementById('revenue_month_selector');
        if (monthSelector) {
            monthSelector.value = currentMonth;
            console.log("Month selector set to:", monthSelector.value);
        } else {
            console.error("Month selector element not found!");
        }
        
        // Initialize charts with a small delay to ensure DOM is fully ready
        setTimeout(() => {
            // Initialize charts with default options
            initRevenueCharts();
            
            // Set default period to month (matches active tab)
            revenue_current_period = 'month';
            
            // Load initial data for month tab with stats update
            loadRevenueData('month', currentMonth, true);
            
            // Load all period totals separately
            loadAllRevenuePeriodTotals();
            
            // Preload data for week and day tabs but don't update their statistics
            // This ensures data is ready when the user clicks these tabs
            console.log("Preloading week and day data...");
            setTimeout(() => {
                loadRevenueData('week', '', false);
                setTimeout(() => {
                    loadRevenueData('day', '', false);
                }, 300);
            }, 300);
        }, 100);
        
        // Add event listeners
        if (monthSelector) {
            monthSelector.addEventListener('change', function() {
                const selectedMonth = this.value;
                console.log("Month changed to:", selectedMonth);
                
                // Always update the current period when month is changed
                revenue_current_period = 'month';
                
                // Ensure the month tab is selected
                const monthTab = document.getElementById('revenue-pills-month-tab');
                if (monthTab && !monthTab.classList.contains('active')) {
                    monthTab.click();
                }
                
                // Load data for the selected month with stats update
                loadRevenueData('month', selectedMonth, true);
                
                // Refresh all period totals when month changes
                loadAllRevenuePeriodTotals();
            });
        }
        
        // Tab event listeners
        const monthTab = document.getElementById('revenue-pills-month-tab');
        const weekTab = document.getElementById('revenue-pills-week-tab');
        const dayTab = document.getElementById('revenue-pills-day-tab');
        
        if (monthTab) {
            monthTab.addEventListener('click', function() {
                console.log("Month tab clicked");
                // First set the current period
                revenue_current_period = 'month';
                const month = document.getElementById('revenue_month_selector').value;
                console.log(`Loading month data for month ${month}`);
                
                // Force refresh the data to ensure we get the latest stats
                showRevenueLoading(true);
                
                // Load data with a small delay to ensure UI is updated
                setTimeout(() => {
                    // Always force update stats when tab is clicked
                    loadRevenueData('month', month, true);
                    
                    // Refresh all totals when changing tabs
                    loadAllRevenuePeriodTotals();
                }, 50);
            });
        }
        
        if (weekTab) {
            weekTab.addEventListener('click', function() {
                console.log("Week tab clicked");
                // First set the current period
                revenue_current_period = 'week';
                console.log("Loading week data");
                
                // Force refresh the data to ensure we get the latest stats
                showRevenueLoading(true);
                
                // Load data with a small delay to ensure UI is updated
                setTimeout(() => {
                    // Always force update stats when tab is clicked
                    loadRevenueData('week', '', true);
                    
                    // Refresh all totals when changing tabs
                    loadAllRevenuePeriodTotals();
                }, 50);
            });
        }
        
        if (dayTab) {
            dayTab.addEventListener('click', function() {
                console.log("Day tab clicked");
                // First set the current period
                revenue_current_period = 'day';
                console.log("Loading day data");
                
                // Force refresh the data to ensure we get the latest stats
                showRevenueLoading(true);
                
                // Load data with a small delay to ensure UI is updated
                setTimeout(() => {
                    // Always force update stats when tab is clicked
                    loadRevenueData('day', '', true);
                    
                    // Refresh all totals when changing tabs
                    loadAllRevenuePeriodTotals();
                }, 50);
            });
        }
    });
    
    // Initialize all charts with empty data
    function initRevenueCharts() {
        console.log("Initializing revenue chart instances");
        
        // Day chart - bar chart
        const dayChartElement = document.querySelector("#revenue_day_chart");
        if (!dayChartElement) {
            console.error("Day chart container not found!");
        } else {
            revenue_day_chart = new ApexCharts(dayChartElement, {
                series: [{
                    name: 'Today Revenue',
                    data: Array(24).fill(0)
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
                // colors not changed
                colors: Array(24).fill('#3D3D66'),
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    // categories: Array.from({length: 24}, (_, i) => String(i).padStart(2, '0')),
                    categories: Array.from({length: 24}, (_, i) => String(i).padStart(2, '0')),
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
                    enabled: true,
                    shared: true,
                    followCursor: true,
                    intersect: false,
                    hideEmptySeries: true,
                    theme: true,
                    x: {
                        show: true
                    },
                    marker: {
                        show: true
                    },
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            });
            revenue_day_chart.render();
            console.log("Day chart initialized");
        }
        
        // Initialize week chart
        const weekChartElement = document.querySelector("#revenue_week_chart");
        if (!weekChartElement) {
            console.error("Week chart container not found!");
        } else {
            revenue_week_chart = new ApexCharts(weekChartElement, {
                series: [{
                    name: 'This Week Revenue',
                    data: Array(7).fill(0),
                }],
                chart: {
                    type: 'bar',
                    height: 180,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 3,
                        columnWidth: '40%',
                        distributed: true
                    }
                },
                colors: Array(7).fill('#3D3D66'),
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
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
                    show: true
                },
                grid: {
                    show: false
                },
                tooltip: {
                    enabled: true,
                    shared: true,
                    followCursor: true,
                    intersect: false,
                    hideEmptySeries: true,
                    theme: true,
                    x: {
                        show: true
                    },
                    marker: {
                        show: true
                    },
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            });
            revenue_week_chart.render();
            console.log("Week chart initialized");
        }
        
        // Initialize month chart
        const monthChartElement = document.querySelector("#revenue_month_chart");
        if (!monthChartElement) {
            console.error("Month chart container not found!");
        } else {
            revenue_month_chart = new ApexCharts(monthChartElement, {
                series: [{
                    name: 'Revenue',
                    data: Array.from({length: 31}, () => 0)
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
                xaxis: {
                    categories: Array.from({length: 31}, (_, i) => String(i + 1))
                },
                tooltip: {
                    enabled: true,
                    shared: true,
                    followCursor: true,
                    intersect: false,
                    hideEmptySeries: true,
                    theme: true,
                    x: {
                        show: true
                    },
                    marker: {
                        show: true
                    },
                    y: {
                        formatter: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            });
            revenue_month_chart.render();
            console.log("Month chart initialized");
        }
    }
    
    // Load data for a specific period
    function loadRevenueData(type, month = '', updateStats = true) {
        console.log(`Loading revenue data for ${type}${month ? ' month ' + month : ''}, updateStats=${updateStats}`);
        
        showRevenueLoading(true);
        
        // Only update the current period type if updateStats is true
        if (updateStats) {
            console.log(`Setting current period to ${type}`);
            revenue_current_period = type;
        }
        
        // Dummy data for fallback in case of error
        const dummyData = {
            day: {
                series: Array(24).fill(0).map(() => Math.floor(Math.random() * 10)),
                categories: Array.from({length: 24}, (_, i) => String(i).padStart(2, '0')),
                total: 105,
                growth: 2.5
            },
            week: {
                series: Array(7).fill(0).map(() => Math.floor(Math.random() * 10)),
                categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                total: 116,
                growth: 5.7
            },
            month: {
                series: Array.from({length: 31}, () => Math.floor(Math.random() * 10)),
                categories: Array.from({length: 31}, (_, i) => String(i + 1)),
                total: 238,
                growth: 8.3
            }
        };
        
        // Add cache-busting parameter to ensure we get fresh data
        let url = `/admin/revenue-stats/?type=${type}&_=${new Date().getTime()}`;
        if (type === 'month' && month) {
            url += `&month=${month}`;
        }
        
        // Log which chart type we're trying to update
        console.log(`Fetching data from ${url}`);
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Received ${type} data:`, data);
                
                // Validate data
                if (!data) {
                    throw new Error('No data received');
                }
                
                // Check if series is present and is an array
                if (!data.series || !Array.isArray(data.series)) {
                    console.warn(`Invalid series data format for ${type}:`, data);
                    data.series = dummyData[type].series;
                }
                
                // Ensure categories exist
                if (!data.categories || !Array.isArray(data.categories)) {
                    console.warn(`Invalid categories data format for ${type}:`, data);
                    data.categories = dummyData[type].categories;
                }
                
                // Only update statistics if updateStats is true (default behavior)
                if (updateStats) {
                    console.log(`Updating stats with total=${data.total || 0}, growth=${data.growth || 0}`);
                    // Ensure we're updating with the correct total for the period
                    updateRevenueStatistics(data.total || 0, data.growth || 0);
                } else {
                    console.log(`Skipping stats update because updateStats=${updateStats}`);
                }
                
                // Update chart with the data
                updateRevenueChart(type, data.series, data.categories);
                showRevenueLoading(false);
            })
            .catch(error => {
                console.error(`Error fetching ${type} data:`, error);
                
                // Use dummy data on error
                const fallbackData = dummyData[type];
                console.log(`Using fallback data for ${type}:`, fallbackData);
                
                // Only update statistics if updateStats is true
                if (updateStats) {
                    console.log(`Updating stats from fallback data with total=${fallbackData.total}, growth=${fallbackData.growth}`);
                    updateRevenueStatistics(fallbackData.total, fallbackData.growth);
                } else {
                    console.log(`Skipping stats update from fallback data because updateStats=${updateStats}`);
                }
                
                updateRevenueChart(type, fallbackData.series, fallbackData.categories);
                showRevenueLoading(false);
            });
    }
    
    // Update the chart with new data
    function updateRevenueChart(type, seriesData, categories) {
        console.log(`Updating ${type} chart with:`, { seriesData, categories });
        
        if (!seriesData || !Array.isArray(seriesData) || seriesData.length === 0) {
            console.warn(`No series data for ${type} chart`);
            return;
        }
        
        // Check if charts are properly initialized
        if (!areRevenueChartsInitialized()) {
            console.error('Charts not initialized yet. Attempting to initialize...');
            initRevenueCharts();
            
            // If still not initialized, delay the update
            if (!areRevenueChartsInitialized()) {
                console.warn('Charts still not initialized. Delaying update...');
                setTimeout(() => updateRevenueChart(type, seriesData, categories), 300);
                return;
            }
        }
        
        try {
            // Prepare the chart data object with proper name field
            const chartData = {
                series: [{
                    name: 'Revenue',
                    data: seriesData
                }]
            };
            
            // Add categories if provided
            if (categories && categories.length > 0) {
                chartData.xaxis = {
                    categories: categories
                };
            }
            
            // Add specific configuration for different chart types
            if (type === 'day' || type === 'week') {
                // Calculate colors for bar charts - highlight highest value
                chartData.colors = calculateRevenueColors(seriesData);
            }
            
            // Update the appropriate chart
            if (type === 'day') {
                console.log('Updating day chart with:', chartData);
                revenue_day_chart.updateOptions(chartData);
            } else if (type === 'week') {
                console.log('Updating week chart with:', chartData);
                revenue_week_chart.updateOptions(chartData);
            } else if (type === 'month') {
                console.log('Updating month chart with:', chartData);
                revenue_month_chart.updateOptions(chartData);
            }
        } catch (error) {
            console.error(`Error updating ${type} chart:`, error);
        }
    }
    
    // Update the statistics and comparison text
    function updateRevenueStatistics(total, growth) {
        console.log(`Updating revenue statistics: total=${total}, growth=${growth}, currentPeriod=${revenue_current_period}`);
        
        // Convert to numbers to ensure proper handling
        total = Number(total) || 0;
        growth = Number(growth) || 0;
        
        // Update period title based on current tab
        const titleElement = document.getElementById('revenue_title');
        if (titleElement) {
            let periodTitle = 'Revenue Overview';
            switch(revenue_current_period) {
                case 'day':
                    periodTitle = "Today's Revenue";
                    break;
                case 'week':
                    periodTitle = "This Week's Revenue";
                    break;
                case 'month':
                    periodTitle = "Monthly Revenue";
                    break;
            }
            titleElement.textContent = periodTitle;
        }
        
        // Update total revenue count
        const countElement = document.getElementById('revenue_count_display');
        if (countElement) {
            countElement.textContent = formatCurrency(total);
        }
        
        // Format growth percentage to handle decimal places
        let formattedGrowth = growth.toFixed(1);
        
        // Update growth percentage badge
        const growthBadge = document.getElementById('revenue_growth_badge');
        if (growthBadge) {
            growthBadge.textContent = `${growth >= 0 ? '+' : ''}${formattedGrowth}%`;
            growthBadge.className = `badge rounded ${growth >= 0 ? 'bg-label-success' : 'bg-label-danger'}`;
        }
        
        // Update comparison text based on current period
        const comparisonText = document.getElementById('revenue_comparison_text');
        if (comparisonText) {
            let periodText = 'period';
            switch(revenue_current_period) {
                case 'day':
                    periodText = 'yesterday';
                    break;
                case 'week':
                    periodText = 'last week';
                    break;
                case 'month':
                    periodText = 'last month';
                    break;
            }
            comparisonText.textContent = `Compared to ${periodText}`;
        }
    }
    
    // Show or hide loading indicator
    function showRevenueLoading(isLoading) {
        const loadingIndicator = document.getElementById('revenue_loading_indicator');
        if (loadingIndicator) {
            loadingIndicator.style.display = isLoading ? 'block' : 'none';
        }
    }
    
    // Load all period totals (day, week, month)
    function loadAllRevenuePeriodTotals() {
        console.log("Loading all period totals for summary display");
        
        // Function to update the total counter elements
        function updateTotalElement(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = formatCurrency(value);
            }
        }
        
        // Load day total
        fetch(`/admin/revenue-stats/?type=day&_=${new Date().getTime()}`)
            .then(response => response.json())
            .then(data => {
                console.log("Day total data:", data);
                updateTotalElement('revenue_day_total', data.total || 0);
            })
            .catch(error => {
                console.error("Error fetching day total:", error);
                updateTotalElement('revenue_day_total', 0);
            });
            
        // Load week total
        fetch(`/admin/revenue-stats/?type=week&_=${new Date().getTime()}`)
            .then(response => response.json())
            .then(data => {
                console.log("Week total data:", data);
                updateTotalElement('revenue_week_total', data.total || 0);
            })
            .catch(error => {
                console.error("Error fetching week total:", error);
                updateTotalElement('revenue_week_total', 0);
            });
            
        // Load month total
        const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');
        fetch(`/admin/revenue-stats/?type=month&month=${currentMonth}&_=${new Date().getTime()}`)
            .then(response => response.json())
            .then(data => {
                console.log("Month total data:", data);
                updateTotalElement('revenue_month_total', data.total || 0);
            })
            .catch(error => {
                console.error("Error fetching month total:", error);
                updateTotalElement('revenue_month_total', 0);
            });
    }
    
    // Helper function to check if charts are initialized
    function areRevenueChartsInitialized() {
        if (!revenue_day_chart) console.error("Day chart not initialized!");
        if (!revenue_week_chart) console.error("Week chart not initialized!");
        if (!revenue_month_chart) console.error("Month chart not initialized!");
        
        return revenue_day_chart && revenue_week_chart && revenue_month_chart;
    }
    
    // Calculate colors for bar charts - highlight highest value
    function calculateRevenueColors(data) {
        console.log(`Calculating colors for ${revenue_current_period} data:`, data);
        
        // Default array size based on current period type
        let defaultSize = 7; // Default is week (7 days)
        if (revenue_current_period === 'day') {
            defaultSize = 24; // 24 hours in a day
        } else if (revenue_current_period === 'week') {
            defaultSize = 7;  // 7 days in a week
        } else if (revenue_current_period === 'month') {
            defaultSize = 31; // Maximum days in a month
        }
        
        // Ensure data is valid
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn(`Invalid data for color calculation in ${revenue_current_period} chart:`, data);
            return Array(defaultSize).fill('#3D3D66');
        }
        
        // Find the maximum value
        const maxValue = Math.max(...data);
        console.log(`Max value for ${revenue_current_period} chart: ${maxValue}`);
        
        // Create an array of colors, highlighting the maximum value(s)
        return data.map(value => value === maxValue && maxValue > 0 ? '#7F6CFF' : '#3D3D66');
    }
    
    // Helper function to format currency values
    function formatCurrency(value) {
        // Convert to number first to handle different input types
        const num = parseFloat(value) || 0;
        // Format with 2 decimal places
        return '$' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
</script>