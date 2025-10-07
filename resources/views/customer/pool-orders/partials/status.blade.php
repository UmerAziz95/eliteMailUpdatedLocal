<span class="badge bg-{{ $order->status_color }}">
    {{ $order->status_label }}
</span>
<br>
<small class="badge bg-{{ $order->admin_status_color }} mt-1">
    {{ $order->admin_status_label }}
</small>
