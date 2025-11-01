@props(['status' => 'warming', 'withIcon' => false, 'size' => 'md'])

@php
    $config = get_domain_status_config($status);
    $badgeClass = $config['badge_class'] ?? 'secondary';
    $label = $config['label'] ?? ucfirst($status);
    $icon = $config['icon'] ?? '';
    $description = $config['description'] ?? '';
    
    $sizeClasses = [
        'sm' => 'badge-sm',
        'md' => '',
        'lg' => 'badge-lg'
    ];
    $sizeClass = $sizeClasses[$size] ?? '';
@endphp

<span 
    class="badge bg-{{ $badgeClass }} {{ $sizeClass }}" 
    data-bs-toggle="tooltip" 
    data-bs-placement="top" 
    title="{{ $description }}"
>
    @if($withIcon && $icon)
        <i class="{{ $icon }} me-1"></i>
    @endif
    {{ $label }}
</span>
