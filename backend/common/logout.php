<?php
session_start();
session_destroy();
header('Location: ../../frontend/common/login.html');
?> 