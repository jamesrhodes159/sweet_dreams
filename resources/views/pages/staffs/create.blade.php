@extends('layout.app')

@section('title', ' | App Settings')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add Staff</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">Add Staff</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- SELECT2 EXAMPLE -->
            <div class="card card-default">
                <div class="card-header">
                    <h3 class="card-title">Add Staff Details</h3>

                    {{-- <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div> --}}
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <form method="post" action="{{ route('admin.staff.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Sticker Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Staff Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name"
                                        style="width: 100%;">
                                </div>
                                <div class="form-group">
                                    <label>Staff Email</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        style="width: 100%;">
                                </div>

                                <!-- Sticker Image Upload -->
                                <div class="form-group">
                                    <label>Staff Image</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image"
                                        style="width: 100%;">
                                </div>

                                <!-- Image preview section -->
                                <div class="form-group">
                                    <img id="image_preview"
                                        src="{{ isset($settings->profile_image) ? asset($settings->profile_image) : '' }}"
                                        alt="No Image Selected"
                                        style="max-width: 200px; display: {{ isset($settings->profile_image) ? 'block' : 'none' }};" />
                                </div>
                            </div>
                            <!-- /.col -->

                            <!-- Sticker Type and Cost -->
                            <div class="col-md-6">
                                <!-- Sticker Type -->
                                <div class="form-group">
                                    <label>Type </label>
                                    <select class="form-control" id="user_type" name="user_type" style="width: 100%;">
                                        <option disabled>Please Select Type</option>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>

                                <!-- Cost Field -->
                                <div class="form-group">
                                    <label>Password</label>
                                    <input class="form-control" type="password" id="password" name="password"
                                        style="width: 100%;">
                                </div>

                                <!-- Status Field -->
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" id="is_active" name="is_active" style="width: 100%;">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->
                </div>

                <!-- Card Footer -->
                <div class="card-footer">
                    <input type="submit" value="Save" id="save" name="save" class="btn btn-success">
                </div>
                </form>
            </div>
            <!-- /.card -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->


    <script>
        document.getElementById('profile_image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('image_preview');

            // Check if a file is selected and it's an image
            if (file && file.type.match('image.*')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
@stop
