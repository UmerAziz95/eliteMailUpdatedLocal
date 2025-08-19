@extends('admin.layouts.app')

@section('title', 'Error Log Details')

@push('styles')
<style>
    .error-severity {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 600;
    }
    .severity-error { background-color: #f8d7da; color: #721c24; }
    .severity-warning { background-color: #fff3cd; color: #856404; }
    .severity-info { background-color: #d1ecf1; color: #0c5460; }
    .severity-debug { background-color: #e2e3e5; color: #383d41; }
    
    .code-block {
        /* background-color: #f8f9fa; */
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 1rem;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    
    .info-card {
        border-left: 4px solid #007bff;
        /* background-color: #f8f9fa; */
    }
    
    .error-card {
        border-left: 4px solid #dc3545;
        /* background-color: #fff5f5; */
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Error Log Details #{{ $errorLog->id }}</h4>
                <div>
                    <a href="{{ route('admin.error-logs.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Error Logs
                    </a>
                    <form method="POST" action="{{ route('admin.error-logs.destroy', $errorLog) }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this error log?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fa fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="card mb-4 error-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-exclamation-triangle text-danger"></i>
                        Exception Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>Exception Class:</strong></td>
                                    <td><code>{{ $errorLog->exception_class }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Severity:</strong></td>
                                    <td>
                                        <span class="error-severity severity-{{ $errorLog->severity }}">
                                            {{ ucfirst($errorLog->severity) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Date/Time:</strong></td>
                                    <td>{{ $errorLog->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>File:</strong></td>
                                    <td><code>{{ $errorLog->file }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Line:</strong></td>
                                    <td><code>{{ $errorLog->line }}</code></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>URL:</strong></td>
                                    <td>{{ $errorLog->url ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>HTTP Method:</strong></td>
                                    <td>{{ $errorLog->method ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>User:</strong></td>
                                    <td>{{ $errorLog->user ? $errorLog->user->name : 'Guest' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>IP Address:</strong></td>
                                    <td>{{ $errorLog->ip_address ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>User Agent:</strong></td>
                                    <td style="word-break: break-all;">{{ Str::limit($errorLog->user_agent, 50) ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div>
                        <h6><strong>Error Message:</strong></h6>
                        <div class="code-block">{{ $errorLog->message }}</div>
                    </div>
                </div>
            </div>

            <!-- Stack Trace -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-code text-primary"></i>
                        Stack Trace
                    </h5>
                </div>
                <div class="card-body">
                    <div class="code-block">{{ $errorLog->trace }}</div>
                </div>
            </div>

            <!-- Request Data -->
            @if($errorLog->request_data && count($errorLog->request_data) > 0)
            <div class="card mb-4 info-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-database text-info"></i>
                        Request Data
                    </h5>
                </div>
                <div class="card-body">
                    <div class="code-block">{{ json_encode($errorLog->request_data, JSON_PRETTY_PRINT) }}</div>
                </div>
            </div>
            @endif

            <!-- User Agent Details -->
            @if($errorLog->user_agent)
            <div class="card mb-4 info-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-globe text-info"></i>
                        User Agent Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="code-block">{{ $errorLog->user_agent }}</div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-cogs text-secondary"></i>
                        Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard()">
                            <i class="fa fa-copy"></i> Copy Error Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    function copyToClipboard() {
        const errorDetails = `
Error Log #{{ $errorLog->id }}
Exception: {{ $errorLog->exception_class }}
Message: {{ $errorLog->message }}
File: {{ $errorLog->file }}:{{ $errorLog->line }}
Date: {{ $errorLog->created_at->format('Y-m-d H:i:s') }}
URL: {{ $errorLog->url ?: 'N/A' }}
User: {{ $errorLog->user ? $errorLog->user->name : 'Guest' }}

Stack Trace:
{{ $errorLog->trace }}
        `.trim();
        
        navigator.clipboard.writeText(errorDetails).then(function() {
            alert('Error details copied to clipboard!');
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = errorDetails;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            alert('Error details copied to clipboard!');
        });
    }
    
    function markAsResolved() {
        // This could be extended to update the error log status
        if (confirm('Mark this error as resolved? This will help track which errors have been addressed.')) {
            // You could implement a resolution tracking system here
            alert('Feature coming soon: Resolution tracking system');
        }
    }
</script>
@endpush
