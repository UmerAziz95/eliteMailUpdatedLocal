<form action="{{ route('admin.role.store') }}" method="post" id="addRoleForm" class="row g-3">
    @csrf
    <input type="hidden" name="role_id" id="roleId">

    <style>
        /* Dark theme for Select2 */
        .select2-container .select2-selection--multiple {
            background-color: #2f3349 !important;
            border: 1px solid #444 !important;
            color: #fff !important;
        }
        
        .select2-container .select2-selection--multiple .select2-selection__choice {
            background-color: #495057 !important;
            border: 1px solid #6c757d !important;
            color: #fff !important;
        }
        
        .select2-container .select2-selection--multiple .select2-selection__choice__remove {
            color: #fff !important;
        }
        
        .select2-dropdown {
            background-color: #2f3349 !important;
            border: 1px solid #444 !important;
        }
        
        .select2-results__option {
            background-color: #2f3349 !important;
            color: #fff !important;
        }
        
        .select2-results__option--highlighted {
            background-color: #495057 !important;
            color: #fff !important;
        }
        
        .select2-search--dropdown .select2-search__field {
            background-color: #495057 !important;
            border: 1px solid #6c757d !important;
            color: #fff !important;
        }
        
        .select2-container .select2-search--inline .select2-search__field {
            background-color: transparent !important;
            color: #fff !important;
        }
    </style>

    <div class="col-12 mb-3">
        <label class="form-label" for="modalRoleName">Role Name</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Enter a role name">
        <div class="invalid-feedback"></div>
    </div>

    <div class="col-12">
        <h5 class="mb-4">Role Permissions</h5>
        <div class="table-responsive">
            <div class="mb-3">
                <label for="permissions" class="form-label">Assign Permissions</label>
                <select name="permissions[]" id="permissions" class="form-control select2" multiple
                    style="background-color: #2f3349; color: #fff; border: 1px solid #444;">
                    @foreach($permissions as $permission)
                    <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="col-12 text-center">
        <button type="submit" class="m-btn py-2 px-3 border-0 rounded-2">Submit</button>
        <button type="reset" class="cancel-btn py-2 px-3 border-0 rounded-2 ms-3" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#permissions').select2({
            dropdownAutoWidth: true,
            width: '100%',
            dropdownParent: $('#permissions').parent(), // helps if inside a modal
            theme: 'bootstrap-5'
        });

        // Apply dark theme styles after initialization
        setTimeout(function() {
            // Style the main selection container
            $('.select2-container .select2-selection--multiple').css({
                'background-color': '#2f3349',
                'border': '1px solid #444',
                'color': '#fff'
            });

            // Style selected tags
            $('.select2-container .select2-selection--multiple .select2-selection__choice').css({
                'background-color': '#495057',
                'border': '1px solid #6c757d',
                'color': '#fff'
            });

            // Style the search input
            $('.select2-container .select2-search--inline .select2-search__field').css({
                'background-color': 'transparent',
                'color': '#fff'
            });
        }, 100);

        // When dropdown opens, style options and dropdown container
        $('#permissions').on('select2:open', function() {
            setTimeout(function() {
                // Style dropdown container
                $('.select2-dropdown').css({
                    'background-color': '#2f3349',
                    'border': '1px solid #444',
                    'color': '#fff'
                });

                // Style search input in dropdown
                $('.select2-search--dropdown .select2-search__field').css({
                    'background-color': '#495057',
                    'border': '1px solid #6c757d',
                    'color': '#fff'
                });

                // Style options
                $('.select2-results__option').css({
                    'background-color': '#2f3349',
                    'color': '#fff'
                });

                // Style highlighted option
                $('.select2-results__option--highlighted').css({
                    'background-color': '#495057 !important',
                    'color': '#fff !important'
                });
            }, 10);
        });

        // Handle option hover
        $(document).on('mouseenter', '.select2-results__option', function() {
            $(this).css({
                'background-color': '#495057',
                'color': '#fff'
            });
        });

        $(document).on('mouseleave', '.select2-results__option', function() {
            if (!$(this).hasClass('select2-results__option--highlighted')) {
                $(this).css({
                    'background-color': '#2f3349',
                    'color': '#fff'
                });
            }
        });
    });
</script>