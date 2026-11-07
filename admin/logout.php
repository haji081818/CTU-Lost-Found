<?php
require_once __DIR__ . '/../config/db.php';

session_destroy();
redirect(BASE_URL . 'login.php');
