<?php
require __DIR__ . '/../../../backend/config.php';

json_out(['success' => true, 'token' => csrf_token()]);
