<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>Sweet Dreams @yield('title')</title>

    <meta name="csrf_token" content="{{ csrf_token() }}" />

    @include('layout.styles')

    <script>
        var BASE_URL = '{{ url("/") }}';
    </script>

</head>
<body class="hold-transition sidebar-mini layout-fixed">

    <div class="wrapper">
          <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="{{ url('assets')}}/dist/img/Logo.png" alt="Sweet Dreams" height="60" width="60">
  </div>

        @include('layout.header')
        @include('layout.sidebar')

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            @yield('content')
        </div>
        <footer class="main-footer">
            <strong>Copyright &copy; {{ date('Y')}} <a href="#">Sweet Dreams</a>.</strong>
            All rights reserved.
            <div class="float-right d-none d-sm-inline-block">
              <b>Version</b> 1.0.0
            </div>
          </footer>
    </div>

    @include('layout.footer')
    @yield('scripts')
</body>
</html>
