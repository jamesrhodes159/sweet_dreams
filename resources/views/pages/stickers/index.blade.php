@extends('layout.app')

@section('title', ' | Stickers List')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Stickers List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">Stickers List</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>


    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">All Stickers List</h3>
                            <a class="btn btn-success" style="float:right" href="{{ route('admin.sticker.create')}}">Add Sticker</a>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            @include('includes.flash_message')
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Sticker Name</th>
                                        <th>Sticker</th>
                                        <th>Sticker Type</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($stickers->isEmpty())
                                        <tr>
                                            <td colspan="6" style="text-align: center; font-weight:bold">
                                                No Stickers Found!
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($stickers as $sticker)
                                            <tr>
                                                <td>{{ $sticker->sticker_name }}</td>
                                                <td>
                                                    <img src="{{ url('storage/' . $sticker->sticker_image) }}" alt="Sticker Image" style="max-width: 100px; height: auto;">
                                                    {{-- {{ $sticker->sticker_image }} --}}
                                                </td>
                                                <td>@if ($sticker->sticker_type == 'free')
                                                    Free
                                                    @elseif ($sticker->sticker_type == 'premium')
                                                    Premium
                                                    @else
                                                    Points Redeemable
                                                @endif</td>
                                                <td>
                                                    @if ($sticker->sticker_type != 'point_redeemed')
                                                        $
                                                        @else
                                                        Pts
                                                    @endif
                                                    {{ number_format($sticker->price, 2) }}
                                                </td>
                                                <td>
                                                    @if ($sticker->is_active == 1)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">In Active</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.sticker.status',$sticker->id)}}"
                                                        class="btn @if ($sticker->is_active == 1) btn-danger @else btn-success @endif">
                                                        @if ($sticker->is_active == 1)
                                                            Disable It
                                                        @else
                                                            Enable It
                                                        @endif
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Sticker Name</th>
                                        <th>Sticker</th>
                                        <th>Sticker Type</th>
                                        <th>Cost</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

@stop
