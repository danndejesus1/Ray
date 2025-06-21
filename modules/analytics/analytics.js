/**
 * CarGo Business Analytics Module
 * Provides comprehensive analytics and reporting for the car rental business
 */

class AnalyticsManager {
    constructor() {
        this.data = {
            bookings: [],
            users: [],
            vehicles: [],
            payments: [],
            revenue: []
        };
        this.charts = {};
        this.init();
    }

    init() {
        this.loadAnalyticsData();
        this.bindEvents();
    }

    /**
     * Load analytics data from API
     * @returns {Promise<Object>} Analytics data
     */
    async loadAnalyticsData() {
        try {
            const response = await this.apiCall('/analytics/dashboard', {
                headers: { 'Authorization': `Bearer ${AuthManager.sessionToken}` }
            });

            if (response.success) {
                this.data = response.data;
                return this.data;
            } else {
                // Load demo data for development
                this.loadDemoData();
                return this.data;
            }
        } catch (error) {
            console.error('Analytics data loading error:', error);
            this.loadDemoData();
            return this.data;
        }
    }

    /**
     * Load demo analytics data
     */
    loadDemoData() {
        const now = new Date();
        const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const thisMonth = new Date(now.getFullYear(), now.getMonth(), 1);

        this.data = {
            totalBookings: {
                weekly: 24,
                monthly: 98,
                yearly: 1247
            },
            activeUsers: 156,
            availableVehicles: 45,
            revenue: {
                quarterly: 875000,
                annually: 3500000
            },
            bookingTrends: {
                weekly: [5, 8, 12, 6, 9, 15, 11],
                monthly: [78, 85, 92, 88, 95, 102, 98, 105, 89, 94, 98, 87],
                yearly: [1150, 1247, 1189, 1234]
            },
            vehicleTypeBookings: {
                'Sedan': 45,
                'SUV': 32,
                'Van': 18,
                'Hatchback': 15,
                'Pickup': 8
            },
            popularVehicles: [
                { name: 'Toyota Vios', bookings: 45, revenue: 67500 },
                { name: 'Honda CRV', bookings: 32, revenue: 80000 },
                { name: 'Mitsubishi Adventure', bookings: 28, revenue: 56000 }
            ],
            userSegmentation: {
                'Tourists': 65,
                'Business': 25,
                'Local': 10
            },
            revenueBreakdown: {
                'Vehicle Rental': 70,
                'Driver Service': 20,
                'Insurance': 7,
                'Other': 3
            }
        };
    }

    /**
     * Get dashboard summary statistics
     * @returns {Object} Dashboard stats
     */
    getDashboardStats() {
        return {
            totalBookings: this.data.totalBookings,
            activeUsers: this.data.activeUsers,
            availableVehicles: this.data.availableVehicles,
            revenue: this.data.revenue
        };
    }

    /**
     * Get booking trends data
     * @param {string} period - Time period (weekly, monthly, yearly)
     * @returns {Array} Booking trends data
     */
    getBookingTrends(period = 'monthly') {
        return this.data.bookingTrends[period] || [];
    }

    /**
     * Get vehicle type popularity
     * @returns {Object} Vehicle type booking data
     */
    getVehicleTypeAnalytics() {
        return this.data.vehicleTypeBookings || {};
    }

    /**
     * Get popular vehicles
     * @returns {Array} Popular vehicles data
     */
    getPopularVehicles() {
        return this.data.popularVehicles || [];
    }

    /**
     * Get user segmentation data
     * @returns {Object} User segmentation
     */
    getUserSegmentation() {
        return this.data.userSegmentation || {};
    }

    /**
     * Get revenue breakdown
     * @returns {Object} Revenue breakdown by category
     */
    getRevenueBreakdown() {
        return this.data.revenueBreakdown || {};
    }

    /**
     * Render dashboard with analytics
     */
    renderDashboard() {
        this.renderDashboardStats();
        this.renderBookingTrendsChart();
        this.renderVehicleTypeChart();
        this.renderRevenueChart();
        this.renderPopularVehiclesTable();
    }

    /**
     * Render dashboard statistics cards
     */
    renderDashboardStats() {
        const statsContainer = document.getElementById('dashboard-stats');
        if (!statsContainer) return;

        const stats = this.getDashboardStats();
        
        statsContainer.innerHTML = `
            <div class="dashboard-grid">
                <div class="stat-card">
                    <div class="stat-value">${stats.totalBookings.monthly || 0}</div>
                    <div class="stat-label">Monthly Bookings</div>
                    <div class="text-sm text-gray-500 mt-1">
                        +${stats.totalBookings.weekly || 0} this week
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">${stats.activeUsers || 0}</div>
                    <div class="stat-label">Active Users</div>
                    <div class="text-sm text-green-600 mt-1">↗ +12% from last month</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">${stats.availableVehicles || 0}</div>
                    <div class="stat-label">Available Vehicles</div>
                    <div class="text-sm text-gray-500 mt-1">Metro Manila</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value">${CONFIG.LOCATION.CURRENCY_SYMBOL}${(stats.revenue.quarterly || 0).toLocaleString()}</div>
                    <div class="stat-label">Quarterly Revenue</div>
                    <div class="text-sm text-green-600 mt-1">↗ +18% from last quarter</div>
                </div>
            </div>
        `;
    }

    /**
     * Render booking trends chart
     */
    renderBookingTrendsChart() {
        const chartContainer = document.getElementById('booking-trends-chart');
        if (!chartContainer) return;

        const monthlyData = this.getBookingTrends('monthly');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        // Simple HTML/CSS chart for now (can be replaced with Chart.js or similar)
        const maxValue = Math.max(...monthlyData);
        
        chartContainer.innerHTML = `
            <div class="card">
                <h3 class="text-lg font-semibold mb-4">Booking Trends (Monthly)</h3>
                <div class="chart-container">
                    <div class="flex justify-between items-end h-64 px-4">
                        ${monthlyData.map((value, index) => `
                            <div class="flex flex-col items-center">
                                <div class="bg-blue-500 rounded-t" 
                                     style="height: ${(value / maxValue) * 200}px; width: 20px;"
                                     title="${months[index]}: ${value} bookings"></div>
                                <div class="text-xs text-gray-600 mt-2">${months[index]}</div>
                                <div class="text-xs font-semibold">${value}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render vehicle type popularity chart
     */
    renderVehicleTypeChart() {
        const chartContainer = document.getElementById('vehicle-type-chart');
        if (!chartContainer) return;

        const vehicleData = this.getVehicleTypeAnalytics();
        const total = Object.values(vehicleData).reduce((sum, value) => sum + value, 0);
        
        chartContainer.innerHTML = `
            <div class="card">
                <h3 class="text-lg font-semibold mb-4">Popular Vehicle Types</h3>
                <div class="space-y-3">
                    ${Object.entries(vehicleData).map(([type, bookings]) => {
                        const percentage = ((bookings / total) * 100).toFixed(1);
                        return `
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium">${type}</span>
                                <div class="flex items-center space-x-2">
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: ${percentage}%"></div>
                                    </div>
                                    <span class="text-sm text-gray-600">${bookings}</span>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            </div>
        `;
    }

    /**
     * Render revenue breakdown chart
     */
    renderRevenueChart() {
        const chartContainer = document.getElementById('revenue-chart');
        if (!chartContainer) return;

        const revenueData = this.getRevenueBreakdown();
        const colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'];
        
        chartContainer.innerHTML = `
            <div class="card">
                <h3 class="text-lg font-semibold mb-4">Revenue Breakdown</h3>
                <div class="space-y-3">
                    ${Object.entries(revenueData).map(([category, percentage], index) => `
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 rounded-full" style="background-color: ${colors[index % colors.length]}"></div>
                                <span class="text-sm font-medium">${category}</span>
                            </div>
                            <span class="text-sm font-semibold">${percentage}%</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    /**
     * Render popular vehicles table
     */
    renderPopularVehiclesTable() {
        const tableContainer = document.getElementById('popular-vehicles-table');
        if (!tableContainer) return;

        const vehicles = this.getPopularVehicles();
        
        tableContainer.innerHTML = `
            <div class="card">
                <h3 class="text-lg font-semibold mb-4">Most Popular Vehicles</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2">Vehicle</th>
                                <th class="text-right py-2">Bookings</th>
                                <th class="text-right py-2">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${vehicles.map(vehicle => `
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 font-medium">${vehicle.name}</td>
                                    <td class="py-2 text-right">${vehicle.bookings}</td>
                                    <td class="py-2 text-right text-green-600">
                                        ${CONFIG.LOCATION.CURRENCY_SYMBOL}${vehicle.revenue.toLocaleString()}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    /**
     * Generate analytics report
     * @param {string} period - Report period
     * @param {string} format - Report format (html, json, csv)
     * @returns {Object} Generated report
     */
    generateReport(period = 'monthly', format = 'html') {
        const reportData = {
            period,
            generatedAt: new Date().toISOString(),
            summary: this.getDashboardStats(),
            bookingTrends: this.getBookingTrends(period),
            vehicleAnalytics: this.getVehicleTypeAnalytics(),
            revenueBreakdown: this.getRevenueBreakdown(),
            popularVehicles: this.getPopularVehicles()
        };

        switch (format) {
            case 'json':
                return JSON.stringify(reportData, null, 2);
            case 'csv':
                return this.convertToCSV(reportData);
            case 'html':
            default:
                return this.generateHTMLReport(reportData);
        }
    }

    /**
     * Generate HTML report
     * @param {Object} data - Report data
     * @returns {string} HTML report
     */
    generateHTMLReport(data) {
        return `
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>CarGo Analytics Report - ${data.period}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
                    .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; }
                    .stat-value { font-size: 2rem; font-weight: bold; color: #3B82F6; }
                    .stat-label { color: #6b7280; margin-top: 5px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                    th { background-color: #f8f9fa; }
                    .no-print { display: none; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>CarGo Analytics Report</h1>
                    <p>Period: ${data.period.charAt(0).toUpperCase() + data.period.slice(1)} | Generated: ${new Date(data.generatedAt).toLocaleString()}</p>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">${data.summary.totalBookings?.monthly || 0}</div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${data.summary.activeUsers || 0}</div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${data.summary.availableVehicles || 0}</div>
                        <div class="stat-label">Available Vehicles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">${CONFIG.LOCATION.CURRENCY_SYMBOL}${(data.summary.revenue?.quarterly || 0).toLocaleString()}</div>
                        <div class="stat-label">Revenue</div>
                    </div>
                </div>
                
                <h2>Popular Vehicles</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Bookings</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.popularVehicles.map(vehicle => `
                            <tr>
                                <td>${vehicle.name}</td>
                                <td>${vehicle.bookings}</td>
                                <td>${CONFIG.LOCATION.CURRENCY_SYMBOL}${vehicle.revenue.toLocaleString()}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
                
                <script>
                    window.print();
                </script>
            </body>
            </html>
        `;
    }

    /**
     * Convert data to CSV format
     * @param {Object} data - Report data
     * @returns {string} CSV data
     */
    convertToCSV(data) {
        let csv = 'CarGo Analytics Report\\n';
        csv += `Period,${data.period}\\n`;
        csv += `Generated,${new Date(data.generatedAt).toLocaleString()}\\n\\n`;
        
        // Summary stats
        csv += 'Summary Statistics\\n';
        csv += 'Metric,Value\\n';
        csv += `Total Bookings,${data.summary.totalBookings?.monthly || 0}\\n`;
        csv += `Active Users,${data.summary.activeUsers || 0}\\n`;
        csv += `Available Vehicles,${data.summary.availableVehicles || 0}\\n`;
        csv += `Revenue,${data.summary.revenue?.quarterly || 0}\\n\\n`;
        
        // Popular vehicles
        csv += 'Popular Vehicles\\n';
        csv += 'Vehicle,Bookings,Revenue\\n';
        data.popularVehicles.forEach(vehicle => {
            csv += `${vehicle.name},${vehicle.bookings},${vehicle.revenue}\\n`;
        });
        
        return csv;
    }

    /**
     * Export report
     * @param {string} format - Export format
     * @param {string} period - Report period
     */
    exportReport(format = 'html', period = 'monthly') {
        const report = this.generateReport(period, format);
        const filename = `cargo-analytics-${period}-${new Date().toISOString().split('T')[0]}.${format}`;
        
        if (format === 'html') {
            const blob = new Blob([report], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } else {
            const blob = new Blob([report], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    }

    /**
     * Bind analytics-related events
     */
    bindEvents() {
        // Refresh data button
        const refreshButton = document.getElementById('refresh-analytics');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => {
                this.loadAnalyticsData().then(() => {
                    this.renderDashboard();
                });
            });
        }

        // Export buttons
        const exportButtons = document.querySelectorAll('[data-export]');
        exportButtons.forEach(button => {
            button.addEventListener('click', () => {
                const format = button.dataset.export;
                const period = button.dataset.period || 'monthly';
                this.exportReport(format, period);
            });
        });

        // Period selector
        const periodSelector = document.getElementById('analytics-period');
        if (periodSelector) {
            periodSelector.addEventListener('change', (e) => {
                const period = e.target.value;
                this.renderBookingTrendsChart(period);
            });
        }
    }

    /**
     * Make API call
     * @param {string} endpoint - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Object>} API response
     */
    async apiCall(endpoint, options = {}) {
        // Use AuthManager's API call method for consistency
        if (AuthManager && AuthManager.apiCall) {
            return AuthManager.apiCall(endpoint, options);
        }
        
        // Fallback implementation
        try {
            const url = `${CONFIG.API.BASE_URL}${endpoint}`;
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });
            return await response.json();
        } catch (error) {
            console.error('Analytics API call error:', error);
            return { success: false, message: error.message };
        }
    }
}

// Initialize analytics manager
const analyticsManager = new AnalyticsManager();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AnalyticsManager;
} else {
    window.AnalyticsManager = analyticsManager;
}
