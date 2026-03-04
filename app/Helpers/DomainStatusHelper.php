<?php

if (!function_exists('get_domain_status_config')) {
    /**
     * Get domain status configuration
     *
     * @param string|null $status
     * @param string|null $property
     * @return mixed
     */
    function get_domain_status_config($status = null, $property = null)
    {
        $config = config('domain_statuses.statuses', []);
        
        if ($status === null) {
            return $config;
        }
        
        if (!isset($config[$status])) {
            return null;
        }
        
        if ($property !== null) {
            return $config[$status][$property] ?? null;
        }
        
        return $config[$status];
    }
}

if (!function_exists('get_domain_status_badge')) {
    /**
     * Get HTML badge for domain status
     *
     * @param string $status
     * @param bool $withIcon
     * @return string
     */
    function get_domain_status_badge($status, $withIcon = false)
    {
        $config = get_domain_status_config($status);
        
        if (!$config) {
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
        }
        
        $icon = $withIcon && isset($config['icon']) ? '<i class="' . $config['icon'] . ' me-1"></i>' : '';
        $badgeClass = $config['badge_class'] ?? 'secondary';
        $label = $config['label'] ?? ucfirst($status);
        
        return '<span class="badge bg-' . $badgeClass . '">' . $icon . $label . '</span>';
    }
}

if (!function_exists('get_domain_status_style')) {
    /**
     * Get inline styles for domain status
     *
     * @param string $status
     * @return string
     */
    function get_domain_status_style($status)
    {
        $config = get_domain_status_config($status);
        
        if (!$config) {
            return '';
        }
        
        $color = $config['color'] ?? '#000';
        $bgColor = $config['bg_color'] ?? '#fff';
        
        return "color: {$color};";
    }
}

if (!function_exists('get_domain_status_options')) {
    /**
     * Get domain status options for dropdowns
     *
     * @return array
     */
    function get_domain_status_options()
    {
        $statuses = config('domain_statuses.statuses', []);
        $options = [];
        
        foreach ($statuses as $key => $config) {
            $options[$key] = $config['label'] ?? ucfirst($key);
        }
        
        return $options;
    }
}

if (!function_exists('can_transition_domain_status')) {
    /**
     * Check if status transition is allowed
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    function can_transition_domain_status($from, $to)
    {
        $transitions = config('domain_statuses.transitions', []);
        
        if (!isset($transitions[$from])) {
            return false;
        }
        
        return in_array($to, $transitions[$from]);
    }
}
