@extends('layouts.base')

@section('style')
<style>
    #reader { width: 100%; margin: 0 auto; }
    .preview-images { display: flex; flex-wrap: wrap; gap: 10px; }
    .preview-container { position: relative; width: 80px; height: 80px; }
    .preview-container img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
    .preview-container .btn-remove { position: absolute; top: -8px; right: -8px; width: 22px; height: 22px; border-radius: 50%; background: #F25961; color: #fff; border: 2px solid #fff; font-size: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 5; }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Scan Record</h4>
        </div>
        @if(session('recorded'))
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-success alert-dismissible" id="recordedAlert">
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    <strong>Recorded:</strong>
                    <div class="row mt-2">
                        <div class="col-6">{{ session('Code_Rack') }}</div>
                        <div class="col-6">{{ session('Area') }}</div>
                        <div class="col-6">{{ session('Code_Part') }}</div>
                        <div class="col-6">{{ session('Location') }}</div>
                        <div class="col-6">{{ session('Name_Part') }}</div>
                        <div class="col-6">Count: {{ session('Count_Record') }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if($errors->any())
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            </div>
        </div>
        @endif
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div id="reader-container" style="display: none;">
                            <div id="reader"></div>
                            <button type="button" class="btn btn-danger w-100 mt-2" id="stopScan">Stop Camera</button>
                        </div>
                        <button type="button" class="btn btn-primary w-100 mb-3" id="startScan">Scan</button>
                        <hr>
                        <form id="recordForm" action="{{ route('page.record.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label>Code Rack</label>
                                    <input type="text" name="Code_Rack" id="Code_Rack" class="form-control" readonly required>
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
                                    <label>Photos (Multiple)</label>
                                    <input type="file" name="photos[]" id="photos" class="form-control" multiple accept="image/*" capture="environment">
                                    <div class="preview-images mt-2"></div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info w-100 mt-3" id="submitBtn">Submit</button>
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
        const parts = decodedText.split('|');
        if (parts.length >= 6) {
            document.getElementById('Code_Part').value = parts[0];
            document.getElementById('Name_Part').value = parts[1];
            document.getElementById('Code_Rack').value = parts[2];
            document.getElementById('Area').value = parts[3];
            document.getElementById('No_Card').value = parts[4];
            document.getElementById('Location').value = parts[5];

            $('#recordForm input[readonly]').addClass('is-valid');
            stopCamera();
        } else {
            alert('Invalid QR Format');
        }
    }

    $('#startScan').on('click', function() {
        $('#recordedAlert').alert('close');
        $('#reader-container').show();
        $(this).hide();
        if (!html5QrcodeScanner) {
            html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        }
        html5QrcodeScanner.render(onScanSuccess);
    });

    $('#stopScan').on('click', function() { stopCamera(); });

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

    $('#Count_Record_Validation').on('input', function() {
        const count = $('#Count_Record_Manual').val();
        const validation = $(this).val();
        if (validation && count && validation !== count) {
            $(this).addClass('is-invalid').removeClass('is-valid');
        } else if (validation && count && validation === count) {
            $(this).addClass('is-valid').removeClass('is-invalid');
        }
    });
    
    // ==========================================
    // MULTIPLE PHOTO APPEND LOGIC
    // ==========================================
    let photoFiles = [];

    $('#photos').on('change', async function(e) {
        const newFiles = Array.from(e.target.files);
        if (newFiles.length > 0) {
            // Show a small processing indicator if possible
            $('#submitBtn').prop('disabled', true).append(' <small id="photoProcessing">(Processing...)</small>');
            
            for (const file of newFiles) {
                try {
                    const resizedBlob = await resizeImage(file, 1280, 1280);
                    const resizedFile = new File([resizedBlob], file.name, { type: 'image/jpeg' });
                    photoFiles.push(resizedFile);
                } catch (err) {
                    console.error('Resize failed, using original:', err);
                    photoFiles.push(file);
                }
            }
            
            renderPhotoPreviews();
            $('#photoProcessing').remove();
            $('#submitBtn').prop('disabled', false);
            $(this).val('');
        }
    });

    function resizeImage(file, maxWidth, maxHeight) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function(e) {
                const img = new Image();
                img.src = e.target.result;
                img.onload = function() {
                    let width = img.width;
                    let height = img.height;

                    if (width > height) {
                        if (width > maxWidth) {
                            height *= maxWidth / width;
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width *= maxHeight / height;
                            height = maxHeight;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    canvas.toBlob((blob) => {
                        resolve(blob);
                    }, 'image/jpeg', 0.8);
                };
                img.onerror = reject;
            };
            reader.onerror = reject;
        });
    }

    function renderPhotoPreviews() {
        const container = $('.preview-images');
        container.empty();
        
        photoFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                container.append(`
                    <div class="preview-container">
                        <img src="${e.target.result}">
                        <button type="button" class="btn-remove" onclick="removePhoto(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });
    }

    window.removePhoto = function(index) {
        photoFiles.splice(index, 1);
        renderPhotoPreviews();
    };

    $('#recordForm').on('submit', function(e) {
        if (photoFiles.length > 0) {
            try {
                const dataTransfer = new DataTransfer();
                photoFiles.forEach(file => dataTransfer.items.add(file));
                document.getElementById('photos').files = dataTransfer.files;
            } catch (err) {
                console.error('DataTransfer not supported or failed:', err);
            }
        }
    });
</script>
@endsection
