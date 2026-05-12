<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Stockopname System</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="{{ asset('assets/img/kaiadmin/favicon.png') }}" type="image/x-icon" />

    <!-- Fonts and icons (all served locally - no internet required) -->
    <style>
        @font-face {
            font-family: 'Public Sans';
            font-style: normal;
            font-weight: 300;
            font-display: swap;
            src: url('{{ asset('assets/fonts/public-sans/public-sans-300.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Public Sans';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('assets/fonts/public-sans/public-sans-400.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Public Sans';
            font-style: normal;
            font-weight: 500;
            font-display: swap;
            src: url('{{ asset('assets/fonts/public-sans/public-sans-500.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Public Sans';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: url('{{ asset('assets/fonts/public-sans/public-sans-600.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Public Sans';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('assets/fonts/public-sans/public-sans-700.ttf') }}') format('truetype');
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.min.css') }}" />
    <script>
        var baseUrl = "{{ asset('') }}";
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/plugins.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/kaiadmin.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}" />

    <!-- Dynamic Favicon -->
    <script src="/iseki_pro_app/js/dynamic-favicon.js"></script>
    <script>document.addEventListener("DOMContentLoaded", function() { setDynamicFavicon("shelves", "Stockopname"); });</script>

    @yield('style')
    <style>
        table.dataTable tbody tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        table.dataTable tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05) !important;
        }
    </style>
</head>

<body>
    @if(!request()->routeIs('login'))
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <!-- Logo Header -->
                <div class="logo-header" data-background-color="purple">
                    <a href="{{ route('login') }}" class="logo d-flex align-items-center">
                        <img src="{{ asset('assets/img/kaiadmin/logo_light.png') }}" alt="navbar brand" class="navbar-brand" height="30" />
                        <span class="text-white fw-bold ms-2 d-lg-none" style="font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                            @if(Auth::guard('member')->check())
                                {{ Auth::guard('member')->user()->nama }}
                            @elseif(Auth::guard('admin')->check())
                                {{ Auth::guard('admin')->user()->name }}
                            @endif
                        </span>
                    </a>
                    <div class="nav-toggle">
                        <button class="btn btn-toggle toggle-sidebar">
                            <i class="gg-menu-right"></i>
                        </button>
                        <button class="btn btn-toggle sidenav-toggler">
                            <i class="gg-menu-left"></i>
                        </button>
                    </div>
                    <button class="topbar-toggler more">
                        <i class="gg-more-vertical-alt"></i>
                    </button>
                </div>
                <!-- End Logo Header -->
            </div>
            <div class="sidebar-wrapper scrollbar scrollbar-inner">
                <div class="sidebar-content">
                    <ul class="nav nav-primary">
                        @if(Auth::guard('member')->check())
                        <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <a href="{{ route('dashboard') }}">
                                <i class="fas fa-home"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('record.create') ? 'active' : '' }}">
                            <a href="{{ route('record.create') }}">
                                <i class="fas fa-qrcode"></i>
                                <p>Scan Record</p>
                            </a>
                        </li>
                        @endif

                        @if(Auth::guard('admin')->check())
                        <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-chart-bar"></i>
                                <p>Admin Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users-cog"></i>
                                <p>User Management</p>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="purple">
                        <a href="{{ route('login') }}" class="logo d-flex align-items-center">
                            <img src="{{ asset('assets/img/kaiadmin/logo_light.png') }}" alt="navbar brand" class="navbar-brand" height="30" />
                            <span class="text-white fw-bold ms-2 d-lg-none" style="font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">
                                @if(Auth::guard('member')->check())
                                    {{ Auth::guard('member')->user()->nama }}
                                @elseif(Auth::guard('admin')->check())
                                    {{ Auth::guard('admin')->user()->name }}
                                @endif
                            </span>
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                    <!-- End Logo Header -->
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-expand-lg border-bottom" data-background-color="purple">
                    <div class="container-fluid">
                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#" aria-expanded="false">
                                    <span class="profile-username">
                                        <span class="fw-bold text-white">
                                            @if(Auth::guard('member')->check())
                                                {{ Auth::guard('member')->user()->nama }}
                                            @elseif(Auth::guard('admin')->check())
                                                {{ Auth::guard('admin')->user()->name }}
                                            @endif
                                        </span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">Logout</button>
                                            </form>
                                        </li>
                                    </div>
                                </ul>
                            </li>
                            <li class="nav-item d-lg-none">
                                <div class="nav-link text-white fw-bold">
                                    <i class="fas fa-user"></i>
                                    @if(Auth::guard('member')->check())
                                        {{ Auth::guard('member')->user()->nama }}
                                    @elseif(Auth::guard('admin')->check())
                                        {{ Auth::guard('admin')->user()->name }}
                                    @endif
                                </div>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a class="nav-link text-white fw-bold" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar Header -->
            </div>

            @yield('content')

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <div class="copyright">
                        <script>
                            document.write(new Date().getFullYear());
                        </script>, Iseki <span class="text-primary">Stockopname</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    @else
        @yield('content')
    @endif

    <!--   Core JS Files   -->
    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

    <!-- jQuery Scrollbar -->
    <script src="{{ asset('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>

    <!-- Datatables -->
    <script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>

    <!-- Kaiadmin JS -->
    <script src="{{ asset('assets/js/kaiadmin.min.js') }}"></script>

    <!-- Record Detail Modal -->
    <div class="modal fade" id="recordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="recordDetailContent">
                        <div class="row">
                            <div class="col-6">
                                <div><strong class="text-primary">Rack:</strong> <span id="modalRack"></span></div>
                                <div><strong class="text-primary">No Sequence:</strong> <span id="modalSeq"></span></div>
                                <div><strong class="text-primary">Code Part:</strong> <span id="modalCode"></span></div>
                                <div><strong class="text-primary">Name Part:</strong> <span id="modalName"></span></div>
                            </div>
                            <div class="col-6">
                                <div><strong class="text-primary">No Card:</strong> <span id="modalNoCard"></span></div>
                                <div><strong class="text-primary">Area:</strong> <span id="modalArea"></span></div>
                                <div><strong class="text-primary">Location:</strong> <span id="modalLocation"></span></div>
                                <div><strong class="text-primary">Time:</strong> <span id="modalTime"></span></div>
                                <div><strong class="text-primary">Count:</strong> <strong class="text-info"><span id="modalCount"></span></strong></div>
                            </div>
                        </div>
                        <hr>
                        <h6>Photos:</h6>
                        <div id="modalPhotos" class="row">
                            <!-- Photos will be injected here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @yield('script')

    <script>
        function showRecordDetail(id) {
            $('#modalPhotos').empty();
            $.get(baseUrl + `records/${id}`, function(data) {
                $('#modalCode').text(data.code);
                $('#modalName').text(data.name);
                $('#modalRack').text(data.rack);
                $('#modalSeq').text(data.no_sequence);
                $('#modalArea').text(data.area);
                $('#modalNoCard').text(data.no_card);
                $('#modalLocation').text(data.location);
                $('#modalCount').text(data.count);
                $('#modalTime').text(data.time);
                
                $('#modalPhotos').empty();
                if (data.photos && data.photos.length > 0) {
                    data.photos.forEach(path => {
                        $('#modalPhotos').append(`
                            <div class="col-md-6 mb-3">
                                <img src="${baseUrl}${path}" class="img-fluid rounded border" alt="Record Photo">
                            </div>
                        `);
                    });
                } else {
                    $('#modalPhotos').append('<div class="col-12"><p class="text-muted">No photos available</p></div>');
                }
                
                $('#recordModal').modal('show');
            });
        }

        $(document).on('click', '.view-record', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            showRecordDetail(id);
        });

        // Make the whole row clickable
        $(document).on('click', 'table.dataTable tbody tr', function(e) {
            if ($(e.target).closest('button, a, input, select, .no-click').length) return;
            
            const btn = $(this).find('.view-record');
            if (btn.length) {
                const id = btn.data('id');
                showRecordDetail(id);
            }
        });
    </script>
</body>

</html>