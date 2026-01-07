<?php
defined('BASEPATH') or exit('No direct script access allowed');

$controller = $this->uri->segment(1);
$method = $this->uri->segment(2) ?? '';
$is_tendik = ($controller === 'tendik');
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-light-teal">
    <!-- Brand Logo -->
    <a href="<?= base_url(); ?>" class="brand-link bg-white">
        <?php $logo_app = $setting->logo_kiri == null ? base_url() . 'assets/img/favicon.png' : base_url() . $setting->logo_kiri; ?>
        <img src="<?= $logo_app ?>" alt="App Logo" class="brand-image" style="opacity: .8">
        <span class="brand-text text-sm"><?= $setting->nama_aplikasi ?></span>
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Presensi Menu for Tendik -->
        <div class="user-panel mb-3">
            <div class="image text-center d-flex">
                <div class="profile-img ml-3" style="height: 50px; width: 50px;">
                    <img src="<?= base_url('assets/img/user.png') ?>" class="img-circle profile-avatar" alt="User Image">
                </div>
                <div class="profile-info ml-2">
                    <a class="username text-muted" href="#">Tenaga Kependidikan</a>
                    <span class="text-muted small">Panel Presensi</span>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
            <li class="nav-item">
                <a href="<?= base_url('tendik#presensi') ?>" class="nav-link <?= $is_tendik && in_array($method, ['', 'presensi', 'absensi'], true) ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>Presensi</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('tendik/riwayat') ?>" class="nav-link <?= $is_tendik && $method === 'riwayat' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-history"></i>
                    <p>Riwayat</p>
                </a>
            </li>
        </ul>
    </div>
</aside>
