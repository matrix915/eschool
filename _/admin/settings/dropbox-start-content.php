<?php

require_once 'dropbox-auth-header.php';

header('Location: ' . $authHelper->getAuthUrl($callbackUrl));
exit();