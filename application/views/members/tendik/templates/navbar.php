<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<nav class="main-header navbar navbar-expand navbar-dark navbar-teal">
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
    </ul>

    <h1 class="navbar-title navbar-brand ml-3">
        <span class="brand-text font-weight-light">Portal Absensi</span>
    </h1>

    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <a href="<?= base_url('auth/change_password') ?>" class="dropdown-item">
                    <i class="fas fa-lock mr-2"></i>Ganti Password
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" onclick="logout()" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </li>
    </ul>
</nav>
