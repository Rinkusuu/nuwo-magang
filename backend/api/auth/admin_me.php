<?php
require '../../config.php';

require_admin();

json_out(['success' => true, 'admin' => [
    'id' => $_SESSION['admin_id'],
    'nama' => $_SESSION['admin_nama'],
    'email' => $_SESSION['admin_email'],
]]);
