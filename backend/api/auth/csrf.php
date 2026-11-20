<?php
require '../../config.php';

json_out(['success' => true, 'token' => csrf_token()]);
