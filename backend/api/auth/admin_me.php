<?php
require '../../config.php';

require_staf();
json_out(['success' => true, 'admin' => staf_aktif()]);
