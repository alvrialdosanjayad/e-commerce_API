<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

REST API E-COMMERCE
===================

REST API E-COMMERCE dibuat dengan Framework Laravel 8 dengan versi PHP 7.4.26 yang digunakan untuk website <a href="https://github.com/kukuhnugrh/Maten-Gematen">Mande Gematen</a>

Setup
------------

- Menginstall server MongoDB. Panduan menginstall dapat melihat https://www.mongodb.com/docs/v4.4/installation/
- Menginstall driver MongoDB PHP minimal versi 1.8.1. Panduan menginstall dapat melihat http://php.net/manual/en/mongodb.installation.php
- Clone project dari github
- Jalankan perintah `composer require jenssegers/mongodb:3.8.4` dengan terminal pada folder yang sudah di clone sebelumnya untuk menggunakan package dari https://github.com/jenssegers/laravel-mongodb
- Jalankan `cp .env.example .env` dan lakukan pengaturan database Mysql pada `.env`. Import file database dari folder `dokumentasi/e-commerce_user.sql`
- Jalankan `php artisan config:cache`
- Jalankan `php artisan key:generate`
