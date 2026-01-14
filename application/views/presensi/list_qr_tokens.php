<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="content-wrapper bg-white pt-4">
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
                <div class="card-header">
                    <div class="card-title"><i class="fas fa-qrcode mr-1"></i> <?= $subjudul ?></div>
                    <div class="card-tools">
                        <button type="button" onclick="reloadPage()" class="btn btn-sm btn-default">
                            <i class="fa fa-sync"></i> <span class="d-none d-sm-inline-block ml-1">Reload</span>
                        </button>
                        <button type="button" id="btn-add-token" class="btn btn-sm bg-gradient-primary">
                            <i class="fas fa-plus"></i><span class="d-none d-sm-inline-block ml-1">Generate QR Token</span>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($tokens)): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-qrcode fa-3x mb-3"></i>
                            <h4>Tidak Ada Token</h4>
                            <p class="mb-0">Belum ada QR token yang dibuat hari ini</p>
                        </div>
                    <?php else: ?>
                        <?= form_open('', array('id' => 'qrTableForm')) ?>
                        <div class="table-responsive">
                            <table id="qrTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="d-none">ID</th>
                                    <th width="50" height="50" class="text-center p-0 align-middle">No.</th>
                                    <th class="text-center p-0 align-middle">Token</th>
                                    <th class="text-center p-0 align-middle">Tipe</th>
                                    <th class="text-center p-0 align-middle">Lokasi</th>
                                    <th class="text-center p-0 align-middle">Shift</th>
                                    <th class="text-center p-0 align-middle">Tanggal</th>
                                    <th class="text-center p-0 align-middle">Valid Dari</th>
                                    <th class="text-center p-0 align-middle">Valid Sampai</th>
                                    <th class="text-center p-0 align-middle">Digunakan</th>
                                    <th class="text-center p-0 align-middle">Max Usage</th>
                                    <th class="text-center p-0 align-middle">Status</th>
                                    <th class="text-center p-0 align-middle">Dibuat</th>
                                    <th class="text-center p-0 align-middle">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($tokens as $key => $token): ?>
                                    <?php
                                    $token_code = (string) $token->token_code;
                                    $token_short = strlen($token_code) > 12 ? substr($token_code, 0, 12) . '...' : $token_code;
                                    $lokasi_label = !empty($token->lokasi_nama) ? $token->lokasi_nama : '-';
                                    $shift_label = !empty($token->shift_nama) ? $token->shift_nama : '-';
                                    $tanggal_label = !empty($token->tanggal) ? date('d/m/Y', strtotime($token->tanggal)) : '-';
                                    $valid_from = !empty($token->valid_from) ? date('d/m/Y H:i', strtotime($token->valid_from)) : '-';
                                    $valid_until = !empty($token->valid_until) ? date('d/m/Y H:i', strtotime($token->valid_until)) : '-';
                                    $created_at = !empty($token->created_at) ? date('d/m/Y H:i', strtotime($token->created_at)) : '-';
                                    $max_usage = $token->max_usage !== null ? (int) $token->max_usage : 'Tidak terbatas';
                                    ?>
                                    <tr data-id="<?= (int) $token->id_token ?>">
                                        <td class="d-none row-id"><?= (int) $token->id_token ?></td>
                                        <td class="text-center"><?= ($key + 1) ?></td>
                                        <td class="text-center">
                                            <code title="<?= htmlspecialchars($token_code, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($token_short, ENT_QUOTES, 'UTF-8') ?>
                                            </code>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars(strtoupper((string) $token->token_type), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $lokasi_label, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string) $shift_label, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-center"><?= $tanggal_label ?></td>
                                        <td class="text-center"><?= $valid_from ?></td>
                                        <td class="text-center"><?= $valid_until ?></td>
                                        <td class="text-center"><?= (int) $token->used_count ?></td>
                                        <td class="text-center"><?= $max_usage ?></td>
                                        <td class="text-center">
                                            <?= !empty($token->is_active)
                                                ? '<span class="text-success"><i class="fa fa-check mr-1"></i>Aktif</span>'
                                                : '<span class="text-muted">Nonaktif</span>' ?>
                                        </td>
                                        <td class="text-center"><?= $created_at ?></td>
                                        <td class="text-center">
                                            <button type="button"
                                                    class="btn btn-xs btn-info btn-show-qr"
                                                    data-token="<?= htmlspecialchars($token_code, ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-qrcode"></i> Lihat
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?= form_close() ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<?= form_open('', array('id' => 'qrTokenForm')) ?>
<div class="modal fade" id="qrTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate QR Token</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Tipe Token</label>
                    <select class="form-control" name="token_type" id="qr-token-type">
                        <option value="checkin">Masuk Saja</option>
                        <option value="checkout">Pulang Saja</option>
                        <option value="both">Masuk &amp; Pulang</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Lokasi</label>
                    <select class="form-control" name="id_lokasi" id="qr-location">
                        <option value="0">Semua Lokasi</option>
                        <?php foreach ($lokasi as $lok): ?>
                            <option value="<?= (int) $lok->id_lokasi ?>">
                                <?= htmlspecialchars((string) $lok->nama_lokasi, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Shift</label>
                    <select class="form-control" name="id_shift" id="qr-shift">
                        <option value="0">Semua Shift</option>
                        <?php foreach ($shifts as $shift): ?>
                            <option value="<?= (int) $shift->id_shift ?>">
                                <?= htmlspecialchars((string) $shift->nama_shift, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Validity (menit)</label>
                    <input type="number" class="form-control" name="validity_minutes" id="qr-validity" value="5" min="1">
                </div>

                <div class="form-group">
                    <label>Max Usage</label>
                    <input type="number" class="form-control" name="max_usage" id="qr-max-usage" min="1" placeholder="Kosong untuk tidak terbatas">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="is_active" id="qr-active">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Generate
                </button>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="modal fade" id="qrDisplayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
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

function reloadPage() {
    window.location.reload();
}

function resetTokenForm() {
    if ($('#qrTokenForm').length) {
        $('#qrTokenForm')[0].reset();
    }
    $('#qr-token-type').val('both');
    $('#qr-location').val('0');
    $('#qr-shift').val('0');
    $('#qr-validity').val('5');
    $('#qr-max-usage').val('');
    $('#qr-active').val('1');
}

function showQRCode(token) {
    currentQRToken = token;
    document.getElementById('qr-token-display').textContent = token;

    var qrContainer = document.getElementById('qr-code-display');
    qrContainer.innerHTML = '';

    if (typeof QRCode === 'undefined' || typeof QRCode.toCanvas !== 'function') {
        qrContainer.innerHTML = '<div class="alert alert-danger mb-0">Library QR code tidak tersedia</div>';
        $('#qrDisplayModal').modal('show');
        return;
    }

    var canvas = document.createElement('canvas');
    qrContainer.appendChild(canvas);

    QRCode.toCanvas(canvas, token, { width: 256, margin: 1 }, function (error) {
        if (error) {
            console.error(error);
            qrContainer.innerHTML = '<div class="alert alert-danger mb-0">Gagal membuat QR code</div>';
        }
    });

    $('#qrDisplayModal').modal('show');
}

function copyQRToken() {
    if (!currentQRToken) {
        swal.fire({
            title: 'Gagal',
            text: 'Tidak ada token untuk disalin',
            icon: 'error'
        });
        return;
    }

    if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
        var tempInput = document.createElement('input');
        tempInput.value = currentQRToken;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand('copy');
        document.body.removeChild(tempInput);
        swal.fire({
            title: 'Berhasil',
            text: 'Token berhasil disalin!',
            icon: 'success'
        });
        return;
    }

    navigator.clipboard.writeText(currentQRToken).then(function () {
        swal.fire({
            title: 'Berhasil',
            text: 'Token berhasil disalin!',
            icon: 'success'
        });
    }).catch(function () {
        swal.fire({
            title: 'Gagal',
            text: 'Gagal menyalin token',
            icon: 'error'
        });
    });
}

$(document).ready(function () {
    ajaxcsrf();
    $('#qrTokenModal, #qrDisplayModal').appendTo('body');

    $('#btn-add-token').on('click', function () {
        resetTokenForm();
        $('#qrTokenModal').modal('show');
    });

    $('#qrTokenForm').on('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $.ajax({
            url: base_url + 'presensi/generate_qr_token',
            type: 'POST',
            dataType: 'JSON',
            data: $(this).serialize(),
            success: function (response) {
                if (response.status) {
                    $('#qrTokenModal').modal('hide');
                    if (response.token) {
                        showQRCode(response.token);
                    }
                    swal.fire({
                        title: 'Berhasil',
                        text: response.msg || 'QR token berhasil di-generate',
                        icon: 'success'
                    });
                } else {
                    swal.fire({
                        title: 'Gagal',
                        text: response.msg || 'Gagal generate token',
                        icon: 'error'
                    });
                }
            },
            error: function (xhr) {
                var message = 'Terjadi kesalahan saat generate token';
                if (xhr.responseText) {
                    try {
                        var err = JSON.parse(xhr.responseText);
                        message = err.msg || err.message || message;
                    } catch (e) {
                        message = xhr.responseText;
                    }
                }
                swal.fire({
                    title: 'Error',
                    text: message,
                    icon: 'error'
                });
            }
        });
    });

    $('#qrTable').on('click', '.btn-show-qr', function () {
        showQRCode($(this).data('token'));
    });
});
</script>
