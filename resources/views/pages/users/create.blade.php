@extends('layout.app')

@section('title', ' | App Settings')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Add Sticker</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">Add Sticker</li>
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
                    <h3 class="card-title">Add Sticker Details</h3>

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
                    <form method="post" action="{{ route('admin.sticker.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <!-- Sticker Name -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Sticker Name</label>
                                    <input type="text" class="form-control" id="sticker_name"
                                           value="{{ $settings->sticker_name ?? '' }}" name="sticker_name" style="width: 100%;">
                                </div>

                                <!-- Sticker Image Upload -->
                                <div class="form-group">
                                    <label>Sticker Image (Only .png Files allowed)</label>
                                    <input type="file" class="form-control" id="sticker_image" name="sticker_image" style="width: 100%;">
                                </div>

                                <!-- Image preview section -->
                                <div class="form-group">
                                    <img id="image_preview"
                                         src="{{ isset($settings->sticker_image) ? asset($settings->sticker_image) : '' }}"
                                         alt="No Image Selected"
                                         style="max-width: 200px; display: {{ isset($settings->sticker_image) ? 'block' : 'none' }};" />
                                </div>
                            </div>
                            <!-- /.col -->

                            <!-- Sticker Type and Cost -->
                            <div class="col-md-6">
                                <!-- Sticker Type -->
                                <div class="form-group">
                                    <label>Sticker Type</label>
                                    <select class="form-control" id="sticker_type" name="sticker_type" style="width: 100%;">
                                        <option disabled>Please Select Type</option>
                                        <option value="free" {{ (isset($settings) && $settings->sticker_type == 'free') ? 'selected' : '' }}>Free</option>
                                        <option value="premium" {{ (isset($settings) && $settings->sticker_type == 'premium') ? 'selected' : '' }}>Paid</option>
                                        <option value="point_redeemed" {{ (isset($settings) && $settings->sticker_type == 'point_redeemed') ? 'selected' : '' }}>Points Redeemable</option>
                                    </select>
                                </div>

                                <!-- Cost Field -->
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input class="form-control" type="number" step="0.01" id="price" value="{{ $settings->price ?? '' }}" name="price"
                                           style="width: 100%;"
                                           {{ isset($settings) && $settings->sticker_type == 'free' ? 'readonly value=0' : '' }}>
                                </div>

                                <!-- Status Field -->
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" id="status" name="status"
                                           style="width: 100%;">
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
        document.getElementById('sticker_type').addEventListener('change', function() {
            const priceInput = document.getElementById('price');
            if (this.value === 'free') {
                priceInput.value = 0;
                priceInput.setAttribute('readonly', true);
            } else {
                priceInput.removeAttribute('readonly');
                priceInput.value = "{{ $settings->price ?? '' }}"; // Reset to saved value or empty if not set
            }
        });

        // Ensure initial page load state
        window.onload = function() {
            const stickerType = document.getElementById('sticker_type').value;
            const priceInput = document.getElementById('price');
            if (stickerType === 'free') {
                priceInput.value = 0;
                priceInput.setAttribute('readonly', true);
            }
        };

    document.getElementById('sticker_image').addEventListener('change', function(event) {
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
