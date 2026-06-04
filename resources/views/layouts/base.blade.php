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

    <!-- Dynamic Favicon Assets -->
    <link rel="stylesheet" href="/iseki_pro_app/css/icon.css">
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
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" data-background-color="dark">
            <div class="sidebar-logo">
                <!-- Logo Header -->
                <div class="logo-header" data-background-color="purple">
                    <a href="{{ route('login') }}" class="logo d-flex align-items-center">
                        <img src="{{ asset('assets/img/kaiadmin/logo_light.png') }}" alt="navbar brand" class="navbar-brand" height="30" />
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
                        <li class="nav-item {{ request()->routeIs('login') ? 'active' : '' }}">
                            <a href="{{ route('login') }}">
                                <i class="fas fa-home"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
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
                                <a href="{{ route('page.login') }}" class="dropdown-toggle profile-pic">
                                    <span class="profile-username">
                                        <span class="fw-bold text-white">
                                            Login
                                        </span>
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item d-lg-none">
                                <a href="{{ route('page.login') }}" class="nav-link text-white fw-bold">
                                    Login
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
                                <div><strong class="text-primary">Code Part:</strong> <span id="modalCode"></span></div>
                                <div><strong class="text-primary">Name Part:</strong> <span id="modalName"></span></div>
                                <div><strong class="text-primary">Time:</strong> <span id="modalTime"></span></div>
                                <div><strong class="text-primary">Member:</strong> <span id="modalMember"></span></div>
                            </div>
                            <div class="col-6">
                                <div><strong class="text-primary">No Card:</strong> <span id="modalNoCard"></span></div>
                                <div><strong class="text-primary">Area:</strong> <span id="modalArea"></span></div>
                                <div><strong class="text-primary">Location:</strong> <span id="modalLocation"></span></div>
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

    <!-- Group Record Modal (shows both Scan A and Scan B) -->
    <div class="modal fade" id="groupRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div><strong>Rack:</strong> <span id="gRack" class="text-primary"></span></div>
                            <div class="mt-1"><strong>Code Part:</strong> <span id="gCode" class="text-primary"></span></div>
                            <div class="mt-1"><strong>Name Part:</strong> <span id="gName" class="text-primary"></span></div>
                        </div>
                        <div class="col-md-6">
                            <div><strong>Location:</strong> <span id="gLocation" class="text-primary"></span></div>
                            <div class="mt-1"><strong>Area:</strong> <span id="gArea" class="text-primary"></span></div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 border-end">
                            <h5 class="text-primary">Scan A</h5>
                            <div><strong>Time:</strong> <span id="gTime_A"></span></div>
                            <div><strong>Member:</strong> <span id="gMember_A"></span></div>
                            <div><strong>Count:</strong> <strong class="text-info"><span id="gCount_A"></span></strong></div>
                            <h6 class="mt-2">Photos:</h6>
                            <div id="gPhotos_A" class="row"></div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-info">Scan B</h5>
                            <div><strong>Time:</strong> <span id="gTime_B"></span></div>
                            <div><strong>Member:</strong> <span id="gMember_B"></span></div>
                            <div><strong>Count:</strong> <strong class="text-info"><span id="gCount_B"></span></strong></div>
                            <h6 class="mt-2">Photos:</h6>
                            <div id="gPhotos_B" class="row"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @yield('script')

    <script>
        var recordModalEl = document.getElementById('recordModal');
        function showRecordDetail(id) {
            $('#modalPhotos').empty();
            $.get(baseUrl + `records/${id}`, function(data) {
                $('#modalCode').text(data.code);
                $('#modalName').text(data.name);
                $('#modalRack').text(data.rack);
                $('#modalArea').text(data.area);
                $('#modalNoCard').text(data.no_card);
                $('#modalLocation').text(data.location);
                $('#modalTime').text(data.time);
                $('#modalCount').text(data.count);
                $('#modalMember').text(data.member_name);
                
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
                
                bootstrap.Modal.getOrCreateInstance(recordModalEl).show();
            });
        }

        var groupModalEl = document.getElementById('groupRecordModal');
        function showGroupDetail(data) {
            $('#gRack').text(data.Code_Rack || '-');
            $('#gLocation').text(data.Location || '-');
            $('#gArea').text(data.Area || '-');
            $('#gCode').text(data.Code_Part || '-');
            $('#gName').text(data.Name_Part || '-');

            $('#gTime_A').text(data.Time_A || '-');
            $('#gMember_A').text(data.Member_A || '-');
            $('#gCount_A').text(data.Count_A || '-');
            $('#gPhotos_A').empty();

            $('#gTime_B').text(data.Time_B || '-');
            $('#gMember_B').text(data.Member_B || '-');
            $('#gCount_B').text(data.Count_B || '-');
            $('#gPhotos_B').empty();

            if (data.Id_Record_A) {
                $.get(baseUrl + 'records/' + data.Id_Record_A, function(r) {
                    if (r.photos && r.photos.length > 0) {
                        r.photos.forEach(function(p) {
                            $('#gPhotos_A').append('<div class="col-12 mb-2"><img src="' + baseUrl + p + '" class="img-fluid rounded border"></div>');
                        });
                    } else {
                        $('#gPhotos_A').append('<p class="text-muted">No photos</p>');
                    }
                });
            } else {
                $('#gPhotos_A').append('<p class="text-muted">No record</p>');
            }

            if (data.Id_Record_B) {
                $.get(baseUrl + 'records/' + data.Id_Record_B, function(r) {
                    if (r.photos && r.photos.length > 0) {
                        r.photos.forEach(function(p) {
                            $('#gPhotos_B').append('<div class="col-12 mb-2"><img src="' + baseUrl + p + '" class="img-fluid rounded border"></div>');
                        });
                    } else {
                        $('#gPhotos_B').append('<p class="text-muted">No photos</p>');
                    }
                });
            } else {
                $('#gPhotos_B').append('<p class="text-muted">No record</p>');
            }

            bootstrap.Modal.getOrCreateInstance(groupModalEl).show();
        }

        $(document).on('click', '.view-record', function(e) {
            e.stopPropagation();
            const id = $(this).data('id');
            showRecordDetail(id);
        });

        $(document).on('click', '.view-group', function(e) {
            e.stopPropagation();
            var t = $(this).closest('table').DataTable();
            var d = t.row($(this).closest('tr')).data();
            showGroupDetail(d);
        });

        // Make the whole row clickable
        $(document).on('click', 'table.dataTable tbody tr', function(e) {
            if ($(e.target).closest('button, a, input, select, .no-click').length) return;

            var btn = $(this).find('.view-group');
            if (btn.length) {
                var t = $(this).closest('table').DataTable();
                var d = t.row(this).data();
                showGroupDetail(d);
            }
        });
    </script>
</body>

</html>