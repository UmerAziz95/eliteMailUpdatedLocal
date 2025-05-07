@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<style>

</style>
@endpush

@section('content')
<section class="py-3">
    <div id="editor"></div>
    askjdlksajd
</section>
@endsection

@push('scripts')
<script>
    const quill = new Quill('#editor', {
    theme: 'snow'
  });
</script>
@endpush