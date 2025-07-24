@extends('admin.layouts.app')

@section('title', 'Slack Settings')

@push('styles')
<style>
    .bg-label-secondary {
        background-color: #ffffff28;
        color: var(--extra-light);
        font-weight: 100;
    }

    .nav-tabs .nav-link {
        color: var(--extra-light);
        border: none
    }

    .nav-tabs .nav-link:hover {
        color: var(--white-color);
        border: none
    }

    .nav-tabs .nav-link.active {
        background-color: var(--second-primary);
        color: #fff;
        border: none;
        border-radius: 6px
    }

    .webhook-form {
        background-color: #ffffff1d;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .webhook-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .webhook-title {
        color: var(--white-color);
        font-weight: 600;
        margin: 0;
    }

    .status-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        color: var(--extra-light);
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--white-color);
        border-radius: 6px;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.15);
        border-color: var(--second-primary);
        color: var(--white-color);
        box-shadow: 0 0 0 0.2rem rgba(var(--second-primary-rgb), 0.25);
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .btn-test {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-test:hover {
        background-color: #138496;
        border-color: #138496;
        color: white;
    }

    .btn-save {
        background-color: var(--second-primary);
        border-color: var(--second-primary);
        color: white;
    }

    .btn-save:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        color: white;
    }

    .form-switch {
        padding-left: 2.5em;
    }

    .form-switch .form-check-input {
        width: 2em;
        margin-left: -2.5em;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 0.25%29'/%3e%3c/svg%3e");
        background-color: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.25);
        transition: all 0.3s ease;
    }

    .form-switch .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 1.0%29'/%3e%3c/svg%3e");
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
    }

    .form-switch .form-check-input:focus {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 0.25%29'/%3e%3c/svg%3e");
        border-color: rgba(255, 255, 255, 0.5);
        box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
    }

    .status-toggle .form-label {
        color: #6c757d;
        transition: color 0.3s ease;
    }

    .status-toggle .form-check-input:checked + .status-label {
        color: #28a745;
        font-weight: 600;
    }

    .status-indicator {
        font-size: 0.875rem;
        font-weight: 500;
        margin-left: 0.5rem;
        transition: all 0.3s ease;
    }

    .status-indicator.enabled {
        color: #28a745;
    }

    .status-indicator.disabled {
        color: #6c757d;
    }
</style>

@endpush

@section('content')
<div class="row py-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white">Slack Settings</h2>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="alert alert-info" role="alert">
                    <h5 class="alert-heading mb-1">How to Setup Slack Webhooks</h5>
                    <p class="mb-2">To receive notifications in your Slack channel, you need to create a webhook URL:</p>
                    <ol class="mb-0">
                        <li>Go to your Slack workspace</li>
                        <li>Navigate to Apps > Incoming Webhooks</li>
                        <li>Create a new webhook for your desired channel</li>
                        <li>Copy the webhook URL and paste it in the forms below</li>
                        <li>Configure which events should trigger notifications</li>
                    </ol>
                </div>

                <!-- Webhook Forms -->
                @foreach($types as $typeKey => $typeLabel)
                    <div class="webhook-form" id="form-{{ $typeKey }}">
                        <div class="webhook-header">
                            <h5 class="webhook-title">{{ $typeLabel }}</h5>
                            <div class="status-toggle">
                                <label for="status-{{ $typeKey }}" class="form-label mb-0">Enable</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input status-switch" type="checkbox" role="switch" 
                                           id="status-{{ $typeKey }}" 
                                           data-type="{{ $typeKey }}"
                                           {{ isset($settings[$typeKey]) && $settings[$typeKey] && $settings[$typeKey]->status ? 'checked' : '' }}>
                                </div>
                                <span class="status-indicator {{ isset($settings[$typeKey]) && $settings[$typeKey] && $settings[$typeKey]->status ? 'enabled' : 'disabled' }}" 
                                      id="status-text-{{ $typeKey }}">
                                    {{ isset($settings[$typeKey]) && $settings[$typeKey] && $settings[$typeKey]->status ? 'Enabled' : 'Disabled' }}
                                </span>
                            </div>
                        </div>

                        <form class="webhook-settings-form" data-type="{{ $typeKey }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $typeKey }}">
                            
                            <div class="form-group">
                                <label for="url-{{ $typeKey }}" class="form-label">Webhook URL *</label>
                                <input type="url" 
                                       class="form-control" 
                                       id="url-{{ $typeKey }}" 
                                       name="url" 
                                       placeholder="https://hooks.slack.com/services/..." 
                                       value="{{ isset($settings[$typeKey]) && $settings[$typeKey] ? $settings[$typeKey]->url : '' }}" 
                                       required>
                                <small class="form-text text-muted">Enter your Slack webhook URL for {{ strtolower($typeLabel) }} notifications</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-test btn-sm test-webhook" data-type="{{ $typeKey }}">
                                    <i class="fas fa-paper-plane me-1"></i> Test Webhook
                                </button>
                                <button type="submit" class="btn btn-save btn-sm">
                                    <i class="fas fa-save me-1"></i> Save Settings
                                </button>
                                @if(isset($settings[$typeKey]) && $settings[$typeKey])
                                    <button type="button" class="btn btn-danger btn-sm delete-webhook" data-id="{{ $settings[$typeKey]->id }}">
                                        <i class="fas fa-trash me-1"></i> Delete
                                    </button>
                                @endif
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Handle status toggle change
    $('.status-switch').on('change', function() {
        const type = $(this).data('type');
        const isChecked = $(this).is(':checked');
        const statusText = $(`#status-text-${type}`);
        
        if (isChecked) {
            statusText.text('Enabled').removeClass('disabled').addClass('enabled');
        } else {
            statusText.text('Disabled').removeClass('enabled').addClass('disabled');
        }
    });

    // Handle form submission
    $('.webhook-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = new FormData(this);
        const type = form.data('type');
        const statusCheckbox = $(`#status-${type}`);
        
        // Add status as boolean value
        formData.append('status', statusCheckbox.is(':checked') ? 'true' : 'false');
        
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        
        $.ajax({
            url: '{{ route("admin.slack.settings.save") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Update the status display without reloading
                    const isEnabled = statusCheckbox.is(':checked');
                    const statusText = $(`#status-text-${type}`);
                    
                    if (isEnabled) {
                        statusText.text('Enabled').removeClass('disabled').addClass('enabled');
                    } else {
                        statusText.text('Disabled').removeClass('enabled').addClass('disabled');
                    }
                    
                    // Only reload if it's a new setting to show delete button
                    if (!$(`button[data-id]`).length) {
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong!'
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Something went wrong!';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle test webhook
    $('.test-webhook').on('click', function() {
        const type = $(this).data('type');
        const url = $(`#url-${type}`).val();
        
        if (!url) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing URL',
                text: 'Please enter a webhook URL first!'
            });
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Testing...');
        
        $.ajax({
            url: '{{ route("admin.slack.settings.test") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                type: type,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Test Successful!',
                        text: response.message
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Test Failed!',
                        text: response.message
                    });
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Test Failed!',
                    text: 'Error occurred while testing webhook'
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle delete webhook
    $('.delete-webhook').on('click', function() {
        const id = $(this).data('id');
        const btn = $(this);
        
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/slack/settings') }}/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Deleted!',
                                response.message,
                                'success'
                            );
                            
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'Something went wrong while deleting.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});
</script>
@endpush
