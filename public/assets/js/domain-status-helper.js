/**
 * Domain Status Helper Functions for Frontend
 * Uses configuration from config/domain_statuses.php
 */

window.DomainStatusHelper = {
    /**
     * Status configuration
     */
    config: {
        'warming': {
            label: 'Warming',
            color: '#856404',
            bgColor: '#fff3cd',
            badgeClass: 'bg-warning text-dark',
            icon: 'ti-flame'
        },
        'available': {
            label: 'Available',
            color: '#155724',
            bgColor: '#d4edda',
            badgeClass: 'bg-success',
            icon: 'ti-check-circle'
        },
        'in-progress': {
            label: 'In Progress',
            color: '#004085',
            bgColor: '#cce5ff',
            badgeClass: 'bg-primary',
            icon: 'ti-loader'
        },
        'used': {
            label: 'Used',
            color: '#721c24',
            bgColor: '#f8d7da',
            badgeClass: 'bg-danger',
            icon: 'ti-circle-check'
        }
    },

    /**
     * Get status configuration
     */
    getConfig(status) {
        return this.config[status] || {
            label: status,
            color: '#6c757d',
            bgColor: '#e9ecef',
            badgeClass: 'bg-secondary',
            icon: ''
        };
    },

    /**
     * Generate status badge HTML
     */
    getBadge(status, withIcon = false) {
        const config = this.getConfig(status);
        const icon = withIcon ? `<i class="${config.icon} me-1"></i>` : '';
        return `<span class="badge ${config.badgeClass}">${icon}${config.label}</span>`;
    },

    /**
     * Get badge class for status
     */
    getBadgeClass(status) {
        const config = this.getConfig(status);
        return config.badgeClass;
    },

    /**
     * Get status label
     */
    getLabel(status) {
        const config = this.getConfig(status);
        return config.label;
    },

    /**
     * Get inline style for status
     */
    getStyle(status) {
        const config = this.getConfig(status);
        return `color: ${config.color}; background-color: ${config.bgColor};`;
    },

    /**
     * Check if transition is allowed
     */
    canTransition(fromStatus, toStatus) {
        const transitions = {
            'warming': ['available', 'used'],
            'available': ['in-progress', 'warming'],
            'in-progress': ['used', 'available'],
            'used': []
        };
        return transitions[fromStatus]?.includes(toStatus) || false;
    }
};

/**
 * Initialize status tooltips
 */
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
