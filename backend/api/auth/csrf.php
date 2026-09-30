<?php
require '../../config.php';

// Token CSRF untuk sesi ini; diambil sekali oleh common.js sebelum permintaan pertama yang mengubah data
json_out(['success' => true, 'token' => csrf_token()]);
