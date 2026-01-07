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
                <div class="card-header">
                    <h6 class="card-title"><?= $subjudul ?></h6>
                </div>
                <div class="card-body">
                    <?php if (empty($jadwal) || !is_array($jadwal)): ?>
                        <div class="alert alert-warning text-center mb-0">
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
                                                <strong><?= $j->nama_hari ?></strong>
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
                                                <?php if (!empty($j->is_working_day) && $j->jam_masuk): ?>
                                                    <?= date('H:i', strtotime($j->jam_masuk)) ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($j->is_working_day) && $j->jam_pulang): ?>
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
    </section>
</div>

