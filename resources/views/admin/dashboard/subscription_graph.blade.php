<div class="card h-100 p-3 d-flex flex-column justify-content-between">
    <div class="border-0 pb-0 d-flex justify-content-between">
        <div class="card-title mb-0 Subscriptions-title">
            <h6 class="mb-1 ">Subscriptions</h6>
        </div>
        <ul class="nav nav-pills mb-3 d-flex align-items-center justify-content-end" id="subscriptions-pills-tab"
            role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 px-2 rounded-1 active" id="subscriptions-pills-month-tab"
                    data-bs-toggle="pill" data-bs-target="#subscriptions-pills-month" type="button" role="tab"
                    aria-controls="subscriptions-pills-month" aria-selected="true">Month</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 px-2 rounded-1" id="subscriptions-pills-week-tab" data-bs-toggle="pill"
                    data-bs-target="#subscriptions-pills-week" type="button" role="tab"
                    aria-controls="subscriptions-pills-week" aria-selected="false">Week</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 px-2 rounded-1" id="subscriptions-pills-day-tab" data-bs-toggle="pill"
                    data-bs-target="#subscriptions-pills-day" type="button" role="tab"
                    aria-controls="subscriptions-pills-day" aria-selected="false">Day</button>
            </li>
        </ul>
    </div>
    <!--  -->
    <div class="">
        <div class="row align-items-center">

            <div class="col-12">

                <div class="d-flex align-items-center justify-content-between">
                    <!-- Current Period Subscription Count -->
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <h5 class="text-success" id="subscriptions_count_display">0</h5>
                        <div class="badge rounded bg-label-success" id="subscriptions_growth_badge"
                            style="display: none !important;">+0%</div>
                    </div>
                    <small id="subscriptions_comparison_text" style="display:none !important;">Compared to previous
                        period</small>
                    <div id="subscriptions_loading_indicator" class="mt-2" style="display: none;">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Subscription Totals Summary -->
                    <div class="subscriptions_totals">
                        {{-- <h6 class="theme-text">Total Subscriptions</h6> --}}
                        <div class="d-flex flex-wrap gap-3" style="display: none !important;">
                            <div class="p-2 border rounded">
                                <small class="d-block text-muted">Today</small>
                                <span id="subscriptions_day_total" class="fw-semibold">0</span>
                            </div>
                            <div class="p-2 border rounded">
                                <small class="d-block text-muted">This Week</small>
                                <span id="subscriptions_week_total" class="fw-semibold">0</span>
                            </div>
                            <div class="p-2 border rounded">
                                <small class="d-block text-muted">This Month</small>
                                <span id="subscriptions_month_total" class="fw-semibold">0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content" id="subscriptions-pills-tabContent">
                    <div class="tab-pane fade show active" id="subscriptions-pills-month" role="tabpanel"
                        aria-labelledby="subscriptions-pills-month-tab" tabindex="0">
                        <div style="margin-bottom: 1rem;">
                            <label for="subscriptions_month_selector" style="margin-right: 8px;">Select Month:</label>
                            <select class="form-select" id="subscriptions_month_selector">
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
                        <div id="subscriptions_month_chart"></div>
                    </div>
                    <div class="tab-pane fade" id="subscriptions-pills-week" role="tabpanel"
                        aria-labelledby="subscriptions-pills-week-tab" tabindex="0">
                        <div id="subscriptions_week_chart"></div>
                    </div>
                    <div class="tab-pane fade" id="subscriptions-pills-day" role="tabpanel"
                        aria-labelledby="subscriptions-pills-day-tab" tabindex="0">
                        <div id="subscriptions_day_chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    // Chart instances
    let subscriptions_day_chart, subscriptions_week_chart, subscriptions_month_chart;
    let subscriptions_current_period = 'month';

    // Initial setup when document is loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Initializing subscription charts...");

        // Initialize DOM elements first
        const countElement = document.getElementById('subscriptions_count_display');
        const growthBadge = document.getElementById('subscriptions_growth_badge');
        const comparisonText = document.getElementById('subscriptions_comparison_text');

        // Ensure DOM elements are ready
        if (!countElement || !growthBadge || !comparisonText) {
            console.error("Required DOM elements not found!");
            return;
        }

        // Set current month as default in the selector
        const now = new Date();
        const currentMonth = String(now.getMonth() + 1).padStart(2, '0');
        console.log("Current month:", currentMonth);

        const monthSelector = document.getElementById('subscriptions_month_selector');
        if (monthSelector) {
            monthSelector.value = currentMonth;
            console.log("Month selector set to:", monthSelector.value);
        } else {
            console.error("Month selector element not found!");
        }

        // Initialize charts with a small delay to ensure DOM is fully ready
        setTimeout(() => {
            // Initialize charts with default options
            initSubscriptionsCharts();

            // Set default period to month (matches active tab)
            subscriptions_current_period = 'month';

            // Load initial data for month tab with stats update
            loadSubscriptionsData('month', currentMonth, true);

            // Load all period totals separately
            loadAllSubscriptionsPeriodTotals();

            // Preload data for week and day tabs but don't update their statistics
            // This ensures data is ready when the user clicks these tabs
            console.log("Preloading week and day data...");
            setTimeout(() => {
                loadSubscriptionsData('week', '', false);
                setTimeout(() => {
                    loadSubscriptionsData('day', '', false);
                }, 300);
            }, 300);
        }, 100);

        // Add event listeners
        if (monthSelector) {
            monthSelector.addEventListener('change', function() {
                const selectedMonth = this.value;
                console.log("Month changed to:", selectedMonth);

                // Always update the current period when month is changed
                subscriptions_current_period = 'month';

                // Ensure the month tab is selected
                const monthTab = document.getElementById('subscriptions-pills-month-tab');
                if (monthTab && !monthTab.classList.contains('active')) {
                    monthTab.click();
                }

                // Load data for the selected month with stats update
                loadSubscriptionsData('month', selectedMonth, true);

                // Refresh all period totals when month changes
                loadAllSubscriptionsPeriodTotals();
            });
        }

        // Tab event listeners
        const monthTab = document.getElementById('subscriptions-pills-month-tab');
        const weekTab = document.getElementById('subscriptions-pills-week-tab');
        const dayTab = document.getElementById('subscriptions-pills-day-tab');

        if (monthTab) {
            monthTab.addEventListener('click', function() {
                console.log("Month tab clicked");
                // First set the current period
                subscriptions_current_period = 'month';
                const month = document.getElementById('subscriptions_month_selector').value;
                console.log(`Loading month data for month ${month}`);

                // Force refresh the data to ensure we get the latest stats
                showSubscriptionsLoading(true);

                // Load data with a small delay to ensure UI is updated
                setTimeout(() => {
                    // Always force update stats when tab is clicked
                    loadSubscriptionsData('month', month, true);

                    // Refresh all totals when changing tabs
                    loadAllSubscriptionsPeriodTotals();
                }, 50);
            });
        }

        if (weekTab) {
            weekTab.addEventListener('click', function() {
                console.log("Week tab clicked");
                // First set the current period
                subscriptions_current_period = 'week';
                console.log("Loading week data");

                // Force refresh the data to ensure we get the latest stats
                showSubscriptionsLoading(true);

                // Load data with a small delay to ensure UI is updated
                setTimeout(() => {
                    // Always force update stats when tab is clicked
                    loadSubscriptionsData('week', '', true);

                    // Refresh all totals when changing tabs
                    loadAllSubscriptionsPeriodTotals();
                }, 50);
            });
        }

        if (dayTab) {
            dayTab.addEventListener('click', function() {
                console.log("Day tab clicked");
                // First set the current period
                subscriptions_current_period = 'day';
                console.log("Loading day data");

                // Force refresh the data to ensure we get the latest stats
                showSubscriptionsLoading(true);

                // Load data with a small delay to ensure UI is updated
                setTimeout(() => {
                    // Always force update stats when tab is clicked
                    loadSubscriptionsData('day', '', true);

                    // Refresh all totals when changing tabs
                    loadAllSubscriptionsPeriodTotals();
                }, 50);
            });
        }
    });

    // Initialize all charts with empty data
    function initSubscriptionsCharts() {
        console.log("Initializing chart instances");

        // Day chart - bar chart
        const dayChartElement = document.querySelector("#subscriptions_day_chart");
        if (!dayChartElement) {
            console.error("Day chart container not found!");
        } else {
            subscriptions_day_chart = new ApexCharts(dayChartElement, {
                series: [{
                    name: 'Subscriptions',
                    data: Array(24).fill(0)
                }],
                chart: {
                    type: 'bar',
                    height: 250,
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
                colors: Array(24).fill('#3D3D66'),
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: Array.from({
                        length: 24
                    }, (_, i) => String(i).padStart(2, '0')),
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
                            return formatValue(value);
                        }
                    }
                }
            });
            subscriptions_day_chart.render();
            console.log("Day chart initialized");
        }

        // Week chart - bar chart
        const weekChartElement = document.querySelector("#subscriptions_week_chart");
        if (!weekChartElement) {
            console.error("Week chart container not found!");
        } else {
            subscriptions_week_chart = new ApexCharts(weekChartElement, {
                series: [{
                    name: 'Subscriptions',
                    data: Array(7).fill(0)
                }],
                chart: {
                    type: 'bar',
                    height: 250,
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
                // tooltip: {
                //     enabled: true
                // }
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
                            return formatValue(value);
                        }
                    }
                }
            });
            subscriptions_week_chart.render();
            console.log("Week chart initialized");
        }

        // Month chart - area chart
        const monthChartElement = document.querySelector("#subscriptions_month_chart");
        if (!monthChartElement) {
            console.error("Month chart container not found!");
        } else {
            subscriptions_month_chart = new ApexCharts(monthChartElement, {
                series: [{
                    name: 'Subscriptions',
                    data: Array(31).fill(0)
                }],
                chart: {
                    type: 'area',
                    height: 205,
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
                    categories: Array.from({
                        length: 31
                    }, (_, i) => String(i + 1))
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
                            return formatValue(value);
                        }
                    }
                }
            });
            subscriptions_month_chart.render();
            console.log("Month chart initialized");
        }
    }
    // Helper function to format currency values
    function formatValue(value) {
        // Convert to number first to handle different input types
        const num = parseInt(value) || 0;
        // Format with 2 decimal places
        return "No# " + num.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    // Load subscription data from the server
    function loadSubscriptionsData(type, month = '', updateStats = true) {
        console.log(`Loading ${type} data${month ? ' for month ' + month : ''}, updateStats=${updateStats}`);
        showSubscriptionsLoading(true);

        // Only update the current period type if updateStats is true
        // This ensures we respect the user's tab selection
        if (updateStats) {
            console.log(`Setting current period to ${type}`);
            subscriptions_current_period = type;
        }

        // Set default dummy data in case of errors
        const dummyData = {
            day: {
                series: Array(24).fill(0).map(() => Math.floor(Math.random() * 10)),
                categories: Array.from({
                    length: 24
                }, (_, i) => String(i).padStart(2, '0')),
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
                series: Array(31).fill(0).map(() => Math.floor(Math.random() * 10)),
                categories: Array.from({
                    length: 31
                }, (_, i) => String(i + 1)),
                total: 238,
                growth: 8.3
            }
        };

        // Add cache-busting parameter to ensure we get fresh data
        let url = `/admin/subscription-stats/?type=${type}&_=${new Date().getTime()}`;
        if (type === 'month' && month) {
            url += `&month=${month}`;
        }

        // Log which chart type we're trying to update
        console.log(`Attempting to update ${type} chart...`);

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log(`Received ${type} data:`, data);

                // Check if data is in the expected format
                if (!data.series || !Array.isArray(data.series)) {
                    console.warn(`Invalid series data format for ${type}:`, data);
                    throw new Error('Invalid data format');
                }

                // Only update statistics if updateStats is true (default behavior)
                if (updateStats) {
                    console.log(`Updating stats with total=${data.total || 0}, growth=${data.growth || 0}`);
                    // Ensure we're updating with the correct total for the period
                    updateSubscriptionsStatistics(data.total || 0, data.growth || 0);
                } else {
                    console.log(`Skipping stats update because updateStats=${updateStats}`);
                }

                updateSubscriptionsChart(type, data.series, data.categories || []);
                showSubscriptionsLoading(false);
            })
            .catch(error => {
                console.error(`Error fetching ${type} data:`, error);

                // Use dummy data on error
                const fallbackData = dummyData[type];
                console.log(`Using fallback data for ${type}:`, fallbackData);

                // Only update statistics if updateStats is true
                if (updateStats) {
                    console.log(
                        `Updating stats from fallback data with total=${fallbackData.total}, growth=${fallbackData.growth}`
                    );
                    updateSubscriptionsStatistics(fallbackData.total, fallbackData.growth);
                } else {
                    console.log(`Skipping stats update from fallback data because updateStats=${updateStats}`);
                }

                updateSubscriptionsChart(type, fallbackData.series, fallbackData.categories);
                showSubscriptionsLoading(false);
            });
    }

    // Update subscription count and growth badge
    function updateSubscriptionsStatistics(total, growth) {
        console.log(
            `Updating statistics: total=${total}, growth=${growth}, currentPeriod=${subscriptions_current_period}`);

        // Convert to numbers to ensure proper handling
        total = Number(total) || 0;
        growth = Number(growth) || 0;

        // Update period title based on current tab
        const titleElement = document.querySelector('.Subscriptions-title h5');
        if (titleElement) {
            let periodTitle = 'Subscriptions Overview';
            switch (subscriptions_current_period) {
                case 'day':
                    periodTitle = 'Today\'s Subscriptions';
                    break;
                case 'week':
                    periodTitle = 'This Week\'s Subscriptions';
                    break;
                case 'month':
                    periodTitle = 'Monthly Subscriptions';
                    break;
            }
            titleElement.textContent = periodTitle;
        }

        // Update total subscriptions count
        const countElement = document.getElementById('subscriptions_count_display');
        if (countElement) {
            countElement.textContent = total.toLocaleString(); // Format with commas for large numbers
        }

        // Format growth percentage to handle decimal places
        let formattedGrowth = growth.toFixed(1);

        // Update growth percentage badge
        const growthBadge = document.getElementById('subscriptions_growth_badge');
        if (growthBadge) {
            growthBadge.textContent = `${growth >= 0 ? '+' : ''}${formattedGrowth}%`;
            growthBadge.className = `badge rounded ${growth >= 0 ? 'bg-label-success' : 'bg-label-danger'}`;
        }

        // Update comparison text based on current period
        const comparisonText = document.getElementById('subscriptions_comparison_text');
        if (comparisonText) {
            let periodText = 'period';
            switch (subscriptions_current_period) {
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
            // comparisonText.textContent = `Compared to ${periodText}`;
        }
    }

    // Update chart data and options
    function updateSubscriptionsChart(type, series, categories) {
        console.log(`Updating ${type} chart with:`, {
            series,
            categories
        });

        if (!series || series.length === 0) {
            console.warn(`No series data for ${type} chart`);
            return;
        }

        // Check if charts are initialized
        if (!areSubscriptionsChartsInitialized()) {
            console.error('Charts not initialized yet. Attempting to initialize...');
            initSubscriptionsCharts();

            // If still not initialized, delay update
            if (!areSubscriptionsChartsInitialized()) {
                console.warn('Charts still not initialized. Delaying update...');
                setTimeout(() => updateSubscriptionsChart(type, series, categories), 300);
                return;
            }
        }

        try {
            // Don't update subscriptions_current_period here - it should be set before calling this function
            // This ensures we respect the current tab's selection

            // Prepare the chart data object
            const chartData = {
                series: [{
                    name: 'Subscriptions',
                    data: series
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
                chartData.colors = calculateSubscriptionsColors(series);
            }

            // Update the appropriate chart
            if (type === 'day') {
                console.log('Updating day chart with:', chartData);
                subscriptions_day_chart.updateOptions(chartData);
            } else if (type === 'week') {
                console.log('Updating week chart with:', chartData);
                subscriptions_week_chart.updateOptions(chartData);
            } else if (type === 'month') {
                console.log('Updating month chart with:', chartData);
                subscriptions_month_chart.updateOptions(chartData);
            }
        } catch (error) {
            console.error(`Error updating ${type} chart:`, error);
        }
    }

    // Calculate colors for bar charts - highlight highest value
    function calculateSubscriptionsColors(data) {
        console.log(`Calculating colors for ${subscriptions_current_period} data:`, data);

        // Default array size based on current period type
        let defaultSize = 7; // Default is week (7 days)
        if (subscriptions_current_period === 'day') {
            defaultSize = 24; // 24 hours in a day
        } else if (subscriptions_current_period === 'week') {
            defaultSize = 7; // 7 days in a week
        } else if (subscriptions_current_period === 'month') {
            defaultSize = 31; // Maximum days in a month
        }

        // Ensure data is valid
        if (!data || !Array.isArray(data) || data.length === 0) {
            console.warn(`Invalid data for color calculation in ${subscriptions_current_period} chart:`, data);
            return Array(defaultSize).fill('#3D3D66');
        }

        // Find the maximum value
        const maxValue = Math.max(...data);
        console.log(`Max value for ${subscriptions_current_period} chart: ${maxValue}`);

        // Create an array of colors, highlighting the maximum value(s)
        return data.map(value => value === maxValue && maxValue > 0 ? '#7F6CFF' : '#3D3D66');
    }

    // Show/hide loading spinner
    function showSubscriptionsLoading(show) {
        const indicator = document.getElementById('subscriptions_loading_indicator');
        if (indicator) {
            indicator.style.display = show ? 'block' : 'none';
        }
    }

    // Helper function to check if charts are initialized
    function areSubscriptionsChartsInitialized() {
        if (!subscriptions_day_chart) console.error("Day chart not initialized!");
        if (!subscriptions_week_chart) console.error("Week chart not initialized!");
        if (!subscriptions_month_chart) console.error("Month chart not initialized!");

        return subscriptions_day_chart && subscriptions_week_chart && subscriptions_month_chart;
    }

    // Load all period totals (day, week, month) to display in summary boxes
    function loadAllSubscriptionsPeriodTotals() {
        console.log("Loading all period totals for summary display");

        // Function to update the total counter elements
        function updateTotalElement(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = Number(value).toLocaleString();
            }
        }

        // Load day total
        fetch(`/admin/subscription-stats/?type=day&_=${new Date().getTime()}`)
            .then(response => response.json())
            .then(data => {
                console.log("Day total data:", data);
                updateTotalElement('subscriptions_day_total', data.total || 0);
            })
            .catch(error => {
                console.error("Error fetching day total:", error);
                updateTotalElement('subscriptions_day_total', 0);
            });

        // Load week total
        fetch(`/admin/subscription-stats/?type=week&_=${new Date().getTime()}`)
            .then(response => response.json())
            .then(data => {
                console.log("Week total data:", data);
                updateTotalElement('subscriptions_week_total', data.total || 0);
            })
            .catch(error => {
                console.error("Error fetching week total:", error);
                updateTotalElement('subscriptions_week_total', 0);
            });

        // Load month total
        const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');
        fetch(`/admin/subscription-stats/?type=month&month=${currentMonth}&_=${new Date().getTime()}`)
            .then(response => response.json())
            .then(data => {
                console.log("Month total data:", data);
                updateTotalElement('subscriptions_month_total', data.total || 0);
            })
            .catch(error => {
                console.error("Error fetching month total:", error);
                updateTotalElement('subscriptions_month_total', 0);
            });
    }
</script>
