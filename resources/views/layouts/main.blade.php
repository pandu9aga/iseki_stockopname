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
    @if(!request()->routeIs('page.login'))
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
                        <li class="nav-item {{ request()->routeIs('dual-check.create') ? 'active' : '' }}">
                            <a href="{{ route('dual-check.create') }}">
                                <i class="fas fa-qrcode"></i>
                                <p>Dual Check Record</p>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('dual-check.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('dual-check.dashboard') }}">
                                <i class="fas fa-check-double"></i>
                                <p>Dual Check Data</p>
                            </a>
                        </li>
                        @endif

                        @if(Auth::guard('admin')->check())
                        <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-table"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.missing.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.missing.index') }}">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>Missing</p>
                            </a>
                        </li>
                        <li class="nav-item {{ request()->routeIs('admin.base-data.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.base-data.index') }}">
                                <i class="fas fa-database"></i>
                                <p>Base Data</p>
                            </a>
                        </li>
                        @if(Auth::guard('admin')->check() && Auth::guard('admin')->user()->name === 'saiful')
                        <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}">
                                <i class="fas fa-users-cog"></i>
                                <p>User Management</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item {{ request()->routeIs('admin.dual-check') ? 'active' : '' }}">
                            <a href="{{ route('admin.dual-check') }}">
                                <i class="fas fa-check-double"></i>
                                <p>Dual Check</p>
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
    <div class="wrapper" style="padding: 10px;">
        @yield('content')
    </div>
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

    <!-- Dynamic Dual Check Record Modal (shows N records) -->
    <div class="modal fade" id="dualRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dual Check Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="dualSharedFields" class="row mb-2"></div>
                    <hr>
                    <div id="dualRecordsContainer"></div>
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

        var dualModalEl = document.getElementById('dualRecordModal');
        function showDualGroupDetail(data) {
            var html = '<div class="col-md-6"><strong>Rack:</strong> <span class="text-primary">' + (data.Code_Rack || '-') + '</span></div>';
            html += '<div class="col-md-6"><strong>Code Part:</strong> <span class="text-primary">' + (data.Code_Part || '-') + '</span></div>';
            html += '<div class="col-md-6"><strong>Name Part:</strong> <span class="text-primary">' + (data.Name_Part || '-') + '</span></div>';
            html += '<div class="col-md-6"><strong>Area:</strong> <span class="text-primary">' + (data.Area || '-') + '</span></div>';
            html += '<div class="col-md-12"><strong>Location:</strong> <span class="text-primary">' + (data.Location || '-') + '</span></div>';
            $('#dualSharedFields').html(html);
            $('#dualRecordsContainer').html('<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading records...</p></div>');

            bootstrap.Modal.getOrCreateInstance(dualModalEl).show();

            var ids = data.record_ids || [];
            var total = ids.length;
            var results = [];
            var done = 0;

            function renderAll() {
                $('#dualRecordsContainer').empty();
                for (var i = 0; i < total; i++) {
                    var r = results[i];
                    if (!r) continue;
                    var card = '<div class="mb-3 p-3 border rounded">';
                    card += '<div class="row">';
                    card += '<div class="col-6"><small class="text-muted">Time:</small><br><strong>' + (r.time || '-') + '</strong></div>';
                    card += '<div class="col-6"><small class="text-muted">Member:</small><br><strong>' + (r.member_name || '-') + '</strong></div>';
                    card += '<div class="col-6 mt-1"><small class="text-muted">Count:</small><br><strong class="text-info">' + (r.count || '-') + '</strong></div>';
                    card += '<div class="col-6 mt-1"><small class="text-muted">No Card:</small><br><strong>' + (r.no_card || '-') + '</strong></div>';
                    card += '</div>';
                    if (r.photos && r.photos.length > 0) {
                        card += '<hr><div class="row">';
                        r.photos.forEach(function(p) {
                            card += '<div class="col-6 mb-2"><img src="' + baseUrl + p + '" class="img-fluid rounded border"></div>';
                        });
                        card += '</div>';
                    }
                    card += '</div>';
                    $('#dualRecordsContainer').append(card);
                }
            }

            if (total === 0) {
                $('#dualRecordsContainer').html('<p class="text-muted">No records found.</p>');
                return;
            }

            ids.forEach(function(id, idx) {
                $.get(baseUrl + 'records/' + id, function(r) {
                    results[idx] = r;
                    done++;
                    if (done === total) renderAll();
                }).fail(function() {
                    done++;
                    if (done === total) renderAll();
                });
            });
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

        $(document).on('click', '.view-dual-group', function(e) {
            e.stopPropagation();
            var t = $(this).closest('table').DataTable();
            var d = t.row($(this).closest('tr')).data();
            showDualGroupDetail(d);
        });

        // Make the whole row clickable
        $(document).on('click', 'table.dataTable tbody tr', function(e) {
            if ($(e.target).closest('button, a, input, select, .no-click').length) return;

            var btn = $(this).find('.view-group');
            var dualBtn = $(this).find('.view-dual-group');
            if (btn.length) {
                var t = $(this).closest('table').DataTable();
                var d = t.row(this).data();
                showGroupDetail(d);
            } else if (dualBtn.length) {
                var t = $(this).closest('table').DataTable();
                var d = t.row(this).data();
                showDualGroupDetail(d);
            }
        });
    </script>
</body>

</html>