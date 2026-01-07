<!-- Content Wrapper. Contains page content -->
	<div class="content-wrapper" style="margin-top: -1px;">
	    <div class="sticky"></div>
	    <section class="content overlap p-4">
	        <div class="container">
            <!-- Profile Card -->
            <div class="info-box bg-transparent shadow-none">
                <?php 
                $foto = 'assets/adminlte/dist/img/avatar5.png';
                ?>
                <img class="avatar rounded-circle" src="<?= base_url($foto) ?>" width="80" height="80" style="object-fit: cover;">
                <div class="info-box-content">
                    <h5 class="info-box-text text-white text-wrap"><b><?= isset($profile) ? $profile->nama_lengkap : 'Tendik' ?></b></h5>
                    <span class="info-box-text text-white"><?= $judul ?></span>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
	                    <div class="card card-warning">
	                        <div class="card-header">
	                            <div class="card-title text-white">
	                                <i class="fas fa-clock mr-2"></i><?= $subjudul ?>
	                            </div>
	                        </div>
	                        <div class="card-body">
	                            <?php if (empty($jadwal) || !is_array($jadwal)): ?>
	                                <div class="alert alert-warning mb-0">
	                                    <i class="fas fa-exclamation-triangle mr-2"></i>
	                                    Jadwal shift Anda belum dikonfigurasi. Silakan hubungi admin.
	                                </div>
	                            <?php else: ?>
	                                <?php
	                                $today_num = (int) date('N');
	                                $has_working_day = false;
	                                foreach ($jadwal as $j) {
	                                    if (!empty($j->is_working_day)) {
	                                        $has_working_day = true;
	                                        break;
	                                    }
	                                }
	                                ?>
	
	                                <div class="table-responsive">
	                                    <table class="table table-bordered">
	                                        <thead class="bg-light">
	                                            <tr>
	                                                <th>Hari</th>
	                                                <th>Shift</th>
	                                                <th>Jam Masuk</th>
	                                                <th>Jam Pulang</th>
	                                            </tr>
	                                        </thead>
	                                        <tbody>
	                                            <?php foreach ($jadwal as $j): ?>
	                                                <tr class="<?= $today_num === (int) $j->day_of_week ? 'table-primary' : '' ?>">
	                                                    <td>
	                                                        <strong><?= $j->nama_hari ?? '-' ?></strong>
	                                                        <?php if ($today_num === (int) $j->day_of_week): ?>
	                                                            <span class="badge badge-success ml-2">Hari Ini</span>
	                                                        <?php endif; ?>
	                                                    </td>
	                                                    <td>
	                                                        <?php if (!empty($j->is_working_day)): ?>
	                                                            <?= $j->nama_shift ?>
	                                                        <?php else: ?>
	                                                            <span class="text-muted">Libur</span>
	                                                        <?php endif; ?>
	                                                    </td>
	                                                    <td>
	                                                        <?php if (!empty($j->is_working_day) && !empty($j->jam_masuk)): ?>
	                                                            <?= date('H:i', strtotime($j->jam_masuk)) ?>
	                                                        <?php else: ?>
	                                                            -
	                                                        <?php endif; ?>
	                                                    </td>
	                                                    <td>
	                                                        <?php if (!empty($j->is_working_day) && !empty($j->jam_pulang)): ?>
	                                                            <?= date('H:i', strtotime($j->jam_pulang)) ?>
	                                                        <?php else: ?>
	                                                            -
	                                                        <?php endif; ?>
	                                                    </td>
	                                                </tr>
	                                            <?php endforeach; ?>
	                                        </tbody>
	                                    </table>
	                                </div>
	
	                                <?php if (!$has_working_day): ?>
	                                    <div class="alert alert-warning mt-3 mb-0">
	                                        <i class="fas fa-exclamation-triangle mr-2"></i>
	                                        Jadwal shift Anda belum dikonfigurasi. Silakan hubungi admin.
	                                    </div>
	                                <?php endif; ?>
	                            <?php endif; ?>
	                        </div>
	                    </div>
	                </div>
	            </div>
	        </div>
	    </section>
	</div>
