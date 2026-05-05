@extends('layouts.main')

@section('style')
<style>
    #reader {
        width: 100%;
        margin: 0 auto;
    }
    .preview-images img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        margin: 5px;
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Scan Record</h4>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div id="reader-container" style="display: none;">
                            <div id="reader"></div>
                            <button type="button" class="btn btn-danger w-100 mt-2" id="stopScan">Stop Camera</button>
                        </div>
                        <button type="button" class="btn btn-primary w-100 mb-3" id="startScan">Start Scan</button>
                        <hr>
                        <form id="recordForm" action="{{ route('record.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label>NIK</label>
                                    <input type="text" value="{{ Auth::guard('member')->user()->nik }}" class="form-control" readonly>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Member Name</label>
                                    <input type="text" value="{{ Auth::guard('member')->user()->nama }}" class="form-control" readonly>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Code Rack</label>
                                    <input type="text" name="Code_Rack" id="Code_Rack" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>No Sequence</label>
                                    <input type="text" name="No_Sequence" id="No_Sequence" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Code Part</label>
                                    <input type="text" name="Code_Part" id="Code_Part" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Name Part</label>
                                    <input type="text" name="Name_Part" id="Name_Part" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Area</label>
                                    <input type="text" name="Area" id="Area" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>No Card</label>
                                    <input type="text" name="No_Card" id="No_Card" class="form-control" readonly required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label>Location</label>
                                    <input type="text" name="Location" id="Location" class="form-control" readonly required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label>Count Record <span class="text-danger">*</span></label>
                                    <input type="number" name="Count_Record" id="Count_Record" class="form-control" required placeholder="Enter actual count">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label>Count Record Validation <span class="text-danger">*</span></label>
                                    <input type="number" id="Count_Record_Validation" class="form-control" required placeholder="Re-enter count to confirm">
                                    <div id="count-validation-msg" class="invalid-feedback">Count does not match. Please re-enter.</div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label>Photos (Multiple)</label>
                                    <input type="file" name="photos[]" id="photos" class="form-control" multiple accept="image/*" capture="environment">
                                    <div class="preview-images mt-2"></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info w-100 mt-3" id="submitBtn">Submit Record</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('assets/js/plugin/html5-qrcode.min.js') }}"></script>
<script>
    let html5QrcodeScanner = null;

    function onScanSuccess(decodedText, decodedResult) {
        // Format: Code_Part|Name_Part|Code_Rack|Area|No_Card|Location
        const parts = decodedText.split('|');
        if (parts.length >= 6) {
            document.getElementById('Code_Part').value = parts[0];
            document.getElementById('Name_Part').value = parts[1];
            
            // Split Rack by ; to get No_Sequence
            const rackParts = parts[2].split(';');
            document.getElementById('Code_Rack').value = rackParts[0] || '';
            document.getElementById('No_Sequence').value = rackParts[1] || '';
            
            document.getElementById('Area').value = parts[3];
            document.getElementById('No_Card').value = parts[4];
            document.getElementById('Location').value = parts[5];
            
            // Highlight QR-filled fields as valid
            $('#recordForm input[readonly]').addClass('is-valid');
            
            // Stop camera after success
            stopCamera();
        } else {
            alert('Invalid QR Format');
        }
    }

    $('#startScan').on('click', function() {
        $('#reader-container').show();
        $(this).hide();
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        }
        html5QrcodeScanner.render(onScanSuccess);
    });

    $('#stopScan').on('click', function() {
        stopCamera();
    });

    function stopCamera() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear().then(() => {
                $('#reader-container').hide();
                $('#startScan').show();
            }).catch(err => {
                console.error(err);
                $('#reader-container').hide();
                $('#startScan').show();
            });
        }
    }

    // Photo preview
    $('#photos').on('change', function() {
        $('.preview-images').empty();
        const files = this.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();
            reader.onload = function(e) {
                $('.preview-images').append(`<img src="${e.target.result}">`);
            }
            reader.readAsDataURL(file);
        }
    });

    // Live count validation feedback
    $('#Count_Record_Validation').on('input', function() {
        const count = $('#Count_Record').val();
        const validation = $(this).val();
        if (validation && count && validation !== count) {
            $(this).addClass('is-invalid').removeClass('is-valid');
        } else if (validation && count && validation === count) {
            $(this).addClass('is-valid').removeClass('is-invalid');
        }
    });

    // Submission count match validation
    $('#recordForm').on('submit', function(e) {
        const count = $('#Count_Record').val();
        const validation = $('#Count_Record_Validation').val();
        if (count !== validation) {
            e.preventDefault();
            $('#Count_Record_Validation').addClass('is-invalid').removeClass('is-valid');
            $('#count-validation-msg').show();
            $('#Count_Record_Validation').focus();
        }
    });
</script>
@endsection
