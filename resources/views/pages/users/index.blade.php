@extends('layout.app')

@section('title', ' | Users List')

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Users List</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item active">Users List</li>
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
                            <h3 class="card-title">All Users List</h3>
                            {{-- <a class="btn btn-success" style="float:right" href="{{ route('admin.user.create')}}">Add User</a> --}}
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
                                        <th>Legal Name</th>
                                        <th>Driver License</th>
                                        <th>Status</th>
                                        <th>Verified</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($users->isEmpty())
                                        <tr>
                                            <td colspan="9" style="text-align: center; font-weight:bold">
                                                No Users Found!
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $user->full_name }}</td>
                                                <td>

                                                    {{ $user->email }}
                                                </td>
                                                <td>{{$user->user_type}}
                                                    </td>
                                                <td>{{$user->phone_number}}
                                                </td>
                                                <td>{{$user->legal_name}}
                                                </td>
                                                <td>@if($user->driver_license != null)
                                                    <img src="{{ url('storage/' . $user->driver_license) }}" alt="Drive License" style="max-width: 200px; height: 75px;">
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($user->is_blocked == 0)
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-danger">Blocked</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($user->is_verified == 0)
                                                        <span class="badge bg-success">Verified</span>
                                                    @else
                                                        <span class="badge bg-danger">Unverified</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.user.status',$user->id)}}"
                                                        class="btn @if ($user->is_blocked == 0) btn-danger @else btn-success @endif">
                                                        @if ($user->is_blocked == 0)
                                                            Block
                                                        @else
                                                            Unblock
                                                        @endif
                                                    </a>
                                                    <a href="{{ route('admin.user.verification',$user->id)}}"
                                                        class="btn @if ($user->is_verified == 0) btn-danger @else btn-success @endif">
                                                        @if ($user->is_verified == 0)
                                                            Remove Verification
                                                        @else
                                                            Accept Verification
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
                                        <th>Legal Name</th>
                                        <th>Driver License</th>
                                        <th>Status</th>
                                        <th>Verified</th>
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
