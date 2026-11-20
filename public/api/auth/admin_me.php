<?php
require __DIR__ . '/../../../backend/config.php';

require_staf();
json_out(['success' => true, 'admin' => staf_aktif()]);
