<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="margin-top: -1px;">
    <div class="sticky"></div>
    <section class="content overlap p-4">
        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = isset($tendik) && $tendik && $tendik->foto ? $tendik->foto : 'assets/adminlte/dist/img/avatar5.png';
                ?>
                <img class="avatar rounded-circle" src="<?= base_url($foto) ?>" width="80" height="80" style="object-fit: cover;">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= isset($profile) ? $profile->nama_lengkap : 'Tendik' ?></b></h5>
                    <span class="info-box-text text-white"><?= $judul ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-secondary">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-user mr-2"></i><?= $subjudul ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($this->session->flashdata('success')): ?>
                                <div class="alert alert-success"><?= $this->session->flashdata('success') ?></div>
                            <?php endif; ?>
                            <?php if ($this->session->flashdata('error')): ?>
                                <div class="alert alert-danger"><?= $this->session->flashdata('error') ?></div>
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-4 text-center mb-4">
                                    <img src="<?= base_url($foto) ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                                    <h4><?= isset($profile) ? $profile->nama_lengkap : 'Unknown' ?></h4>
                                    <p class="text-muted"><?= isset($profile) ? $profile->jabatan : '' ?></p>
                                </div>
                                <div class="col-md-8">
                                    <div class="table-responsive">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td width="200"><i class="fas fa-user mr-2"></i>Nama Lengkap</td>
                                                <td><strong><?= isset($tendik) && $tendik ? $tendik->nama_tendik : '-' ?></strong></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-id-card mr-2"></i>NIP/NIK</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->nip ?: '-') : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-briefcase mr-2"></i>Jabatan</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->tipe_tendik ?: '-') : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-envelope mr-2"></i>Email</td>
                                                <td><?= isset($user) ? $user->email : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-phone mr-2"></i>No. Telepon</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->no_telepon ?: '-') : '-' ?></td>
                                            </tr>
                                            <tr>
                                                <td><i class="fas fa-map-marker-alt mr-2"></i>Alamat</td>
                                                <td><?= isset($tendik) && $tendik ? ($tendik->alamat ?: '-') : '-' ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Akun -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-info">
                        <div class="card-header">
                            <div class="card-title text-white">
                                <i class="fas fa-key mr-2"></i>Informasi Akun
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tr>
                                        <td width="200"><i class="fas fa-user-circle mr-2"></i>Username</td>
                                        <td><strong><?= isset($user) ? $user->username : '-' ?></strong></td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-clock mr-2"></i>Terakhir Login</td>
                                        <td><?= isset($user) && $user->last_login ? date('d/m/Y H:i', $user->last_login) : '-' ?></td>
                                    </tr>
                                </table>
                            </div>
                            <a href="<?= base_url('auth/change_password') ?>" class="btn btn-warning">
                                <i class="fas fa-lock mr-1"></i> Ubah Password
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
