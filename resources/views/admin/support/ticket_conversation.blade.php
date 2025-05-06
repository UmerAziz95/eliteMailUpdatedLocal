@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<!-- Quill Editor CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill-image-uploader@1.2.3/dist/quill.imageUploader.min.css" rel="stylesheet">

<style>
    #editor {
        height: 300px;
        background-color: transparent;
    }

    /* Custom toolbar button styles */
    .ql-toolbar button svg {
        fill: #ffffff !important;
        /* white icons */
    }

    .ql-toolbar {
        background-color: #b3b3b3;
        /* dark toolbar background */
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<section class="py-3 d-flex align-items-start justify-content-between gap-3">
    <div class="card p-3 w-100">
        <div>
            <label for="editor" class="form-label">Reply</label>

            <!-- Custom toolbar -->
            <div id="toolbar">
                <span class="ql-formats">
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-link"></button>
                    <button class="ql-image"></button>
                </span>
            </div>

            <!-- Quill Editor -->
            <div id="editor"></div>

            <!-- Hidden input to store HTML content -->
            <input type="hidden" name="description" id="description">

            <hr>
        </div>
    </div>

    <div class="card p-3 w-50">
        <h6>Ticket Info</h6>
        <div class="d-flex align-items-center justify-content-between pb-2">
            <small class="opacity-50">Status</small>
            <small>Answered</small>
        </div>

        <div class="d-flex align-items-center justify-content-between pb-2">
            <small class="opacity-50">Status</small>
            <small>Answered</small>
        </div>

        <div class="d-flex align-items-center justify-content-between pb-2">
            <small class="opacity-50">Last Update</small>
            <small>Answered</small>
        </div>

        <button class="m-btn py-2 px-4 border-0 mt-3">RESOLVE TICKET</button>
    </div>
</section>
@endsection

@push('scripts')
<!-- Quill Core JS -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<!-- Quill ImageUploader -->
<script src="https://cdn.jsdelivr.net/npm/quill-image-uploader@1.2.3/dist/quill.imageUploader.min.js"></script>

<script>
    // Register imageUploader module
    Quill.register("modules/imageUploader", window.ImageUploader);

    // Initialize the Quill editor
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: '#toolbar'
            },
            imageUploader: {
                upload: file => {
                    return new Promise((resolve, reject) => {
                        const formData = new FormData();
                        formData.append('image', file);

                        fetch("{{ route('admin.quill.image.upload') }}", {
                            method: "POST",
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.url) {
                                resolve(result.url);
                            } else {
                                reject("Upload failed");
                            }
                        })
                        .catch(error => {
                            reject("Upload error: " + error.message);
                        });
                    });
                }
            }
        }
    });

    // Save Quill HTML content to hidden input before submitting
    document.querySelector('form')?.addEventListener('submit', function () {
        document.getElementById('description').value = quill.root.innerHTML;
    });
</script>
@endpush