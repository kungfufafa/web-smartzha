<?php
defined('BASEPATH') or exit('No direct script access allowed');
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
        <!-- Absensi Menu for Tendik -->
        <div class="user-panel mb-3">
            <div class="image text-center d-flex">
                <div class="profile-img ml-3" style="height: 50px; width: 50px;">
                    <img src="<?= base_url('assets/img/user.png') ?>" class="img-circle profile-avatar" alt="User Image">
                </div>
                <div class="profile-info ml-2">
                    <a class="username text-muted" href="#">Tenaga Kependidikan</a>
                    <span class="text-muted small">Panel Absensi</span>
                </div>
            </div>
        </div>

        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
            <li class="nav-item">
                <a href="<?= base_url('absensi') ?>" class="nav-link <?= in_array($page, ['absensi', 'riwayat']) ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <p>Check-in / Check-out</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('absensi/riwayat') ?>" class="nav-link <?= $page === 'absensi' && $this->uri->segment(2) === 'riwayat' ? 'active' : '' ?>">
                    <i class="nav-icon fas fa-history"></i>
                    <p>Riwayat</p>
                </a>
            </li>
        </ul>
    </div>
</aside>
