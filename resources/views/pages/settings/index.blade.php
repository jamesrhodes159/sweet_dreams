@extends('layout.app')

@section('title', ' | App Settings')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>App Settings</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">App Settings</li>
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
                    <h3 class="card-title">App Settings Details</h3>

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
                    @include('includes.flash_message')

                    <form method="post" action="{{ route('admin.settings.store') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reward Points Per Comment</label>
                                    <input class="form-control" id="points_per_comment"
                                        value="{{ $settings->points_per_comment ?? '' }}" name="points_per_comment"
                                        style="width: 100%;">
                                </div>
                                <div class="form-group">
                                    <label>Reward Points Per Review</label>
                                    <input class="form-control" id="points_per_review"
                                        value="{{ $settings->points_per_review ?? '' }}" name="points_per_review"
                                        style="width: 100%;">
                                </div>
                            </div>
                            <!-- /.col -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Reward Points Per Post</label>
                                    <input class="form-control" id="points_per_post"
                                        value="{{ $settings->points_per_post ?? '' }}" name="points_per_post"
                                        style="width: 100%;">
                                </div>
                                <div class="form-group">
                                    <label>Reward Points Per Message Send</label>
                                    <input class="form-control" id="points_per_chat_message"
                                        value="{{ $settings->points_per_chat_message ?? '' }}"
                                        name="points_per_chat_message" style="width: 100%;">
                                </div>
                            </div>
                            <!-- /.col -->
                        </div>
                        <!-- /.row -->
                </div>
                <!-- /.card-body -->
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



@stop
