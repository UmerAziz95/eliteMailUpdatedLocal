@extends('layouts.app')

@section('title', 'Contact_us')

@push('styles')
    <style>
        .avatar-group .avatar:hover {
            z-index: 30;
            transition: all .25s ease;
        }

        .pull-up:hover {
            z-index: 30;
            border-radius: 50%;
            box-shadow: var(--box-shadow);
            transform: translateY(-4px) scale(1.02);
        }

        .form-check-input:checked {
            background-color: var(--second-primary);
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div class="card py-3 px-4">
            <div class="table-responsive">
                <table id="contactTable" class="display w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 30; $i++)
                        <tr>
                            <td>
                                <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                    style="border-radius: 50%" height="35" width="35" class="object-fit-cover"
                                    alt="">
                                John Doe
                            </td>
                            <td>johndoe@example.com</td>
                            <td>Lorem ipsum dolor sit amet...</td>
                            <td>25 Mar 2025</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="border-0 bg-transparent ">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="border-0 bg-transparent">
                                        <i class="fa-solid fa-trash text-danger"></i> 
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                    style="border-radius: 50%" height="35" width="35" class="object-fit-cover"
                                    alt="">
                                John Doe
                            </td>
                            <td>janesmith@example.com</td>
                            <td>Consectetur adipiscing elit...</td>
                            <td>24 Mar 2025</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <button class="border-0 bg-transparent ">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    <button class="border-0 bg-transparent">
                                        <i class="fa-solid fa-trash text-danger"></i> 
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('#contactTable').DataTable();
        });
    </script>
@endpush
