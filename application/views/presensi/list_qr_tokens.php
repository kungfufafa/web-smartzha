<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><?= $judul ?></h1>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-default my-shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                    <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#qrTokenModal" onclick="clearQRTokenForm()">
                        <i class="fas fa-plus"></i> Generate QR Token Baru
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($tokens)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-qrcode fa-3x mb-3"></i>
                            <h4>Tidak Ada Token</h4>
                            <p class="mb-0">Belum ada QR token yang dibuat hari ini</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Token Code</th>
                                        <th>Tipe</th>
                                        <th>Valid Dari</th>
                                        <th>Valid Sampai</th>
                                        <th>Digunakan</th>
                                        <th>Max Usage</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tokens as $token): ?>
                                    <tr>
                                        <td><code><?= substr($token->token_code, 0, 12) ?>...</code></td>
                                        <td><?= strtoupper($token->token_type) ?></td>
                                        <td><?= date('H:i:s', strtotime($token->valid_from)) ?></td>
                                        <td><?= date('H:i:s', strtotime($token->valid_until)) ?></td>
                                        <td><?= $token->used_count ?></td>
                                        <td><?= $token->max_usage ?? 'Unlimited' ?></td>
                                        <td>
                                            <span class="badge badge-<?= $token->is_active ? 'success' : 'secondary' ?>">
                                                <?= $token->is_active ? 'Aktif' : 'Non-Aktif' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" onclick="showQRCode('<?= $token->token_code ?>')">
                                                <i class="fas fa-qrcode"></i> Lihat
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- QR Token Modal -->
<div class="modal fade" id="qrTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Generate QR Token</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="qrTokenForm">
                    <div class="form-group">
                        <label>Tipe Token</label>
                        <select class="form-control" name="token_type" id="qr-token-type">
                            <option value="checkin">Check-In Only</option>
                            <option value="checkout">Check-Out Only</option>
                            <option value="both">Check-In & Check-Out</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Validity (menit)</label>
                        <input type="number" class="form-control" name="validity_minutes" id="qr-validity" value="5">
                    </div>
                    
                    <div class="form-group">
                        <label>Max Usage</label>
                        <input type="number" class="form-control" name="max_usage" id="qr-max-usage" placeholder="Kosong untuk unlimited">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" onclick="generateQRToken()">Generate</button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Display Modal -->
<div class="modal fade" id="qrDisplayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">QR Code</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-code-display" class="mb-3"></div>
                <p><strong id="qr-token-display"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="copyQRToken()">
                    <i class="fas fa-copy"></i> Salin Token
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.1/build/qrcode.min.js"></script>

<script>
var currentQRToken = '';

function clearQRTokenForm() {
    document.getElementById('qr-token-type').value = 'both';
    document.getElementById('qr-validity').value = '5';
    document.getElementById('qr-max-usage').value = '';
    $('#qrTokenModal').modal('show');
}

function generateQRToken() {
    var form = document.getElementById('qrTokenForm');
    var formData = new FormData(form);
    
    fetch('<?= base_url('presensi/generate_qr_token') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            $('#qrTokenModal').modal('hide');
            showQRCode(result.token);
            alert('QR token berhasil di-generate');
        } else {
            alert('Gagal generate token: ' + result.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
    });
}

function showQRCode(token) {
    currentQRToken = token;
    document.getElementById('qr-token-display').textContent = token;
    
    var qrContainer = document.getElementById('qr-code-display');
    qrContainer.innerHTML = '';
    
    new QRCode(qrContainer, {
        text: token,
        width: 256,
        height: 256
    });
    
    $('#qrDisplayModal').modal('show');
}

function copyQRToken() {
    navigator.clipboard.writeText(currentQRToken).then(() => {
        alert('Token berhasil disalin!');
    }).catch(err => {
        alert('Gagal menyalin token');
    });
}
</script>
