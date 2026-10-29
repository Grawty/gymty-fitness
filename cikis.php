<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();

setFlash('success', 'Başarıyla çıkış yaptınız.');
redirect(BASE_URL . '/giris');
