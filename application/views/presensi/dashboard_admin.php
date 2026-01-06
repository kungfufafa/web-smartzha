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
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="card-title mb-0"><?= $stats['hadir'] ?></h2>
                                        <p class="mb-0">Hadir</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="card-title mb-0"><?= $stats['terlambat'] ?></h2>
                                        <p class="mb-0">Terlambat</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="card-title mb-0"><?= $stats['alpha'] ?></h2>
                                        <p class="mb-0">Alpha</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h2 class="card-title mb-0"><?= $stats['izin'] ?></h2>
                                        <p class="mb-0">Izin/Sakit/Cuti</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5>Akses Cepat</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="<?= base_url('presensi/shift_management') ?>" class="btn btn-primary btn-block mb-2">
                                <i class="fas fa-clock"></i> Manajemen Shift
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= base_url('presensi/location_management') ?>" class="btn btn-success btn-block mb-2">
                                <i class="fas fa-map-marker-alt"></i> Manajemen Lokasi
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= base_url('presensi/group_config') ?>" class="btn btn-warning btn-block mb-2">
                                <i class="fas fa-users-cog"></i> Konfigurasi Group
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?= base_url('presensi/jadwal_kerja') ?>" class="btn btn-info btn-block mb-2">
                                <i class="fas fa-calendar-alt"></i> Jadwal Kerja
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
