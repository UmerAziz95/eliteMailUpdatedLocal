@extends('layouts.app')

@section('title', 'Roles & Permissions')

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

        .avatar {
            --bs-avatar-size: 2.5rem;
            position: relative;
            width: var(--bs-avatar-size);
            height: var(--bs-avatar-size);
            cursor: pointer;
        }

        .avatar-group .avatar {
            margin-inline-start: -0.8rem;
            transition: all .25s ease;
        }

        a {
            color: var(--second-primary);
            text-decoration: none;
            font-size: 14.5px
        }

        .avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid var(--secondary-color);
        }

        .avatar .avatar-initial {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-color);
            color: var(--bs-white);
            font-weight: 500;
            inset: 0;
            text-transform: uppercase;
            border-radius: 50%;
        }

        /* h5 {
                                font-size: 18px
                            } */

        .form-check {
            padding-left: 0 !important
        }

        .form-check-input {
            background-color: transparent;
            border-radius: 2px !important;
            margin-top: .35rem
        }

        .form-check-input:checked {
            background-color: var(--second-primary);
        }


        .message {
            padding: 8px;
            margin: 5px 0;
            border-radius: 5px;
            width: fit-content;
        }

        .user-message {
            background: #d1e7fd;
            text-align: right;
            color: #000
        }

        .support-message {
            background: #f1f1f1;
            text-align: left;
            color: #000;
        }

        .input-container {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }

        input[type="text"] {
            flex: 1;
            padding: 8px;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div>
            <h5>Roles List</h5>
            <p>A role provides access to predefined menus and features so that an administrator can have access based on
                their assigned role.</p>
        </div>

        <div class="row gy-4">
            <!-- Administrator Role -->
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="fw-normal mb-0">Total 4 tickets</p>

                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h5 class="mb-1">Administrator</h5>
                                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                            </div>
                            <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manager Role -->
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="fw-normal mb-0">Pending 7 tickets</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h5 class="mb-1">Manager</h5>
                                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                            </div>
                            <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Role -->
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="fw-normal mb-0">Completed 5 tickets</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h5 class="mb-1">Users</h5>
                                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                            </div>
                            <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('modules.support.listing')


        <!-- Modal -->
        <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body p-5 position-relative">
                        <button type="button" class="p-0 border-0 bg-transparent position-absolute"
                            style="top: 20px; right: 20px" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>

                        <div class="chat-container overflow-y-auto" style="max-height: 70vh;">
                            <h3>Support Chat</h3>
                            <div class="chat-box" id="chatBox">
                                <div>
                                    <div class="message user-message">Hi, I'm having an issue with my account.</div>
                                </div>
                                <div class="d-flex align-items-center justify-content-end">
                                    <div class="message support-message">Hello! Can you please describe the issue?</div>
                                </div>
                            </div>
                            <div class="input-container position-sticky bottom-0" style="background-color: var(--secondary-color)">
                                <input type="text" id="messageInput" placeholder="Type a message...">

                                <!-- Hidden File Input -->
                                <input type="file" id="fileInput" style="display: none;">

                                <!-- File Upload Icon -->
                                <label for="fileInput" class="file-icon">
                                    <i class="fa-solid fa-paperclip"></i>
                                </label>

                                <button onclick="sendMessage()" class="m-btn py-2 px-4 rounded-2 border-0">Send</button>
                            </div>

                            <style>
                                .file-icon {
                                    cursor: pointer;
                                    font-size: 18px;
                                    margin: 0 10px;
                                }
                            </style>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        $(document).ready(function() {
            var table = $('#myTable').DataTable();

            $(".dt-search").append(
                '<button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem"><i class="fa-solid fa-plus"></i> Support Department</button>'
            );
        });

        function sendMessage() {
            const messageInput = document.getElementById("messageInput");
            const chatBox = document.getElementById("chatBox");
            const fileInput = document.getElementById("fileInput");

            if (messageInput.value.trim() !== "") {
                const userMessage = document.createElement("div");
                userMessage.classList.add("message", "user-message");
                userMessage.innerText = messageInput.value;
                chatBox.appendChild(userMessage);
            }

            if (fileInput.files.length > 0) {
                const fileMessage = document.createElement("div");
                fileMessage.classList.add("message", "user-message");
                fileMessage.innerHTML = `File uploaded: <strong>${fileInput.files[0].name}</strong>`;
                chatBox.appendChild(fileMessage);
            }

            messageInput.value = "";
            fileInput.value = "";
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
@endpush
