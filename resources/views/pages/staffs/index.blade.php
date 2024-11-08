@extends('layout.app')

@section('title', ' | Staffs List')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Staffs List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">Staffs List</li>
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
                            <h3 class="card-title">All Staffs List</h3>
                            <a class="btn btn-success" style="float:right" href="{{ route('admin.staff.create')}}">Add Staff</a>
                        </div>
                        <!-- /.card-header -->
                        <div class="card-body">
                            @include('includes.flash_message')
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($staffs) == 0)
                                        <tr>
                                            <td colspan="7" style="text-align: center; font-weight:bold">
                                                No Users Found!
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($staffs as $staff)
                                            <tr>
                                                <td>{{ $staff->full_name }}</td>
                                                <td>

                                                    {{ $staff->email }}
                                                </td>
                                                <td>{{$staff->user_type}}
                                                    </td>
                                                <td>{{$staff->phone_number}}
                                                </td>
                                                <td>
                                                    @if ($staff->is_active == 1)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">Inactive</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.staff.status',$staff->id)}}"
                                                        class="btn @if ($staff->is_active == 0) btn-danger @else btn-success @endif">
                                                        @if ($staff->is_active == 0)
                                                            Activate
                                                        @else
                                                            Deactivate
                                                        @endif
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>User Type</th>
                                        <th>Phone</th>
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
