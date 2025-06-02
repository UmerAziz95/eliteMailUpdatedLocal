@extends('contractor.layouts.app')

@section('title', 'panel')

@push('styles')
    <style>
        
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem;">
            @for ($i = 0; $i < 40; $i++)
            <div class="card p-3 d-flex flex-column gap-1">
                <small>Inbox Name</small>
                <small>Inbox Limit</small>
                <small>Remaining</small>
                <small>Name</small>
            </div>
            @endfor
        </div>        
    </section>
@endsection

@push('scripts')
    <script>
       
    </script>
@endpush
