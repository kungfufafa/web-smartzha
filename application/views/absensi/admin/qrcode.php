<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url('absensi') ?>">Absensi</a></li>
                        <li class="breadcrumb-item active">QR Code Generator</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Generator Form -->
                <div class="col-md-4">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Generate QR Code Baru</h3>
                        </div>
                        <?= form_open('absensi/generateQr', ['id' => 'formGenerateQr']) ?>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Tipe QR Code</label>
                                <select class="form-control" name="token_type" id="token_type">
                                    <option value="both">Check-in & Check-out</option>
                                    <option value="checkin">Hanya Check-in</option>
                                    <option value="checkout">Hanya Check-out</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Lokasi (opsional)</label>
                                <select class="form-control" name="id_lokasi" id="id_lokasi">
                                    <option value="">-- Semua Lokasi --</option>
                                    <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc->id_lokasi ?>"><?= htmlspecialchars($loc->nama_lokasi) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Kosongkan untuk berlaku di semua lokasi</small>
                            </div>

                            <div class="form-group">
                                <label>Shift (opsional)</label>
                                <select class="form-control" name="id_shift" id="id_shift">
                                    <option value="">-- Semua Shift --</option>
                                    <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s->id_shift ?>"><?= htmlspecialchars($s->nama_shift) ?> (<?= substr($s->jam_masuk, 0, 5) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Validitas (menit)</label>
                                <input type="number" class="form-control" name="validity_minutes" value="<?= $config['qr_validity_minutes'] ?? 5 ?>" min="1" max="60">
                            </div>

                            <div class="form-group">
                                <label>Maks. Penggunaan</label>
                                <input type="number" class="form-control" name="max_usage" placeholder="Kosongkan = unlimited" min="1">
                                <small class="text-muted">Berapa kali QR bisa digunakan</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-qrcode"></i> Generate QR Code
                            </button>
                        </div>
                        <?= form_close() ?>
                    </div>

                    <!-- Active Tokens -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-list"></i> QR Aktif Hari Ini</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Tipe</th>
                                            <th>Berlaku</th>
                                            <th>Digunakan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activeTokensTable">
                                        <?php if (!empty($active_tokens)): ?>
                                            <?php foreach ($active_tokens as $token): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $badge = 'badge-primary';
                                                    if ($token->token_type == 'checkin') $badge = 'badge-success';
                                                    if ($token->token_type == 'checkout') $badge = 'badge-info';
                                                    ?>
                                                    <span class="badge <?= $badge ?>"><?= ucfirst($token->token_type) ?></span>
                                                </td>
                                                <td>
                                                    <small>s/d <?= date('H:i', strtotime($token->valid_until)) ?></small>
                                                </td>
                                                <td>
                                                    <?= $token->used_count ?><?= $token->max_usage ? '/' . $token->max_usage : '' ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">Belum ada QR aktif</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- QR Display -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-dark">
                            <h3 class="card-title text-white"><i class="fas fa-qrcode"></i> QR Code Display</h3>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool text-white" id="btnFullscreen" title="Fullscreen">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body text-center" id="qrDisplayArea">
                            <div class="py-5" id="qrPlaceholder">
                                <i class="fas fa-qrcode fa-5x text-muted mb-3"></i>
                                <p class="text-muted">Klik "Generate QR Code" untuk membuat QR baru</p>
                            </div>
                            
                            <div id="qrContent" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 offset-md-3">
                                        <div class="card border">
                                            <div class="card-body">
                                                <h4 class="mb-3" id="qrTitle">ABSENSI</h4>
                                                <div id="qrCodeImage" class="mb-3"></div>
                                                <p class="mb-1"><strong id="qrType">-</strong></p>
                                                <p class="mb-1 text-muted" id="qrLocation">-</p>
                                                <div class="alert alert-info py-2">
                                                    <small>
                                                        Berlaku: <span id="qrValidUntil">-</span><br>
                                                        <span id="qrCountdown" class="font-weight-bold">-</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="card card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle"></i> Petunjuk Penggunaan</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-qrcode fa-3x text-primary mb-2"></i>
                                        <h6>1. Generate</h6>
                                        <small class="text-muted">Buat QR code baru dengan konfigurasi yang diinginkan</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-desktop fa-3x text-success mb-2"></i>
                                        <h6>2. Tampilkan</h6>
                                        <small class="text-muted">Tampilkan QR di layar monitor atau proyektor</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-mobile-alt fa-3x text-info mb-2"></i>
                                        <h6>3. Scan</h6>
                                        <small class="text-muted">Pegawai scan QR dengan halaman absensi mereka</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

<script>
var countdownInterval = null;
var refreshInterval = null;
var currentToken = null;

$(document).ready(function() {
    $('#formGenerateQr').on('submit', function(e) {
        e.preventDefault();
        generateQr();
    });

    $('#btnFullscreen').on('click', function() {
        var elem = document.getElementById('qrDisplayArea');
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
    });
    
    var autoRefresh = <?= $config['qr_refresh_interval'] ?? 60 ?>;
    if (autoRefresh > 0) {
        refreshInterval = setInterval(function() {
            if (currentToken) {
                generateQr();
            }
        }, autoRefresh * 1000);
    }
});

function generateQr() {
    var btn = $('#formGenerateQr button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
    
    $.ajax({
        url: '<?= base_url("absensi/generateQr") ?>',
        type: 'POST',
        data: $('#formGenerateQr').serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.status) {
                currentToken = res.data;
                displayQr(res.data);
                toastr.success('QR Code berhasil di-generate');
                loadActiveTokens();
            } else {
                toastr.error(res.message);
            }
        },
        error: function() {
            toastr.error('Terjadi kesalahan sistem');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-qrcode"></i> Generate QR Code');
        }
    });
}

function displayQr(data) {
    $('#qrPlaceholder').hide();
    $('#qrContent').show();
    
    var typeLabels = {
        'both': 'CHECK-IN & CHECK-OUT',
        'checkin': 'CHECK-IN',
        'checkout': 'CHECK-OUT'
    };
    
    $('#qrTitle').text('ABSENSI ' + (data.location_name || ''));
    $('#qrType').text(typeLabels[data.token_type] || data.token_type.toUpperCase());
    $('#qrLocation').text(data.location_name || 'Semua Lokasi');
    $('#qrValidUntil').text(data.valid_until);
    
    $('#qrCodeImage').html('');
    
    var qrUrl = '<?= base_url("absensi/scanQr") ?>?token=' + data.token_code;
    
    QRCode.toCanvas(document.createElement('canvas'), qrUrl, {
        width: 300,
        margin: 2
    }, function(error, canvas) {
        if (error) {
            console.error(error);
            return;
        }
        $('#qrCodeImage').append(canvas);
    });
    
    startCountdown(data.valid_until);
}

function startCountdown(validUntil) {
    if (countdownInterval) {
        clearInterval(countdownInterval);
    }
    
    var endTime = new Date(validUntil).getTime();
    
    countdownInterval = setInterval(function() {
        var now = new Date().getTime();
        var distance = endTime - now;
        
        if (distance < 0) {
            clearInterval(countdownInterval);
            $('#qrCountdown').html('<span class="text-danger">EXPIRED</span>');
            setTimeout(function() {
                generateQr();
            }, 2000);
            return;
        }
        
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        var color = distance < 60000 ? 'text-danger' : 'text-success';
        $('#qrCountdown').html('<span class="' + color + '">Sisa: ' + minutes + 'm ' + seconds + 's</span>');
    }, 1000);
}

function loadActiveTokens() {
    $.ajax({
        url: '<?= base_url("absensi/getActiveQr") ?>',
        type: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status && res.data.length > 0) {
                var html = '';
                res.data.forEach(function(token) {
                    var badge = 'badge-primary';
                    if (token.token_type == 'checkin') badge = 'badge-success';
                    if (token.token_type == 'checkout') badge = 'badge-info';
                    
                    html += '<tr>';
                    html += '<td><span class="badge ' + badge + '">' + token.token_type.charAt(0).toUpperCase() + token.token_type.slice(1) + '</span></td>';
                    html += '<td><small>s/d ' + token.valid_until.substring(11, 16) + '</small></td>';
                    html += '<td>' + token.used_count + (token.max_usage ? '/' + token.max_usage : '') + '</td>';
                    html += '</tr>';
                });
                $('#activeTokensTable').html(html);
            }
        }
    });
}
</script>

<style>
#qrDisplayArea {
    min-height: 400px;
    background: #f8f9fa;
}

#qrDisplayArea:-webkit-full-screen,
#qrDisplayArea:-moz-full-screen,
#qrDisplayArea:-ms-fullscreen,
#qrDisplayArea:fullscreen {
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

#qrCodeImage canvas {
    max-width: 100%;
    height: auto;
}
</style>
