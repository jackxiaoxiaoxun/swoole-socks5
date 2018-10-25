<?php

use ss5\ss5local;
use ss5\ss5server;



require __DIR__ . "/ss5/socks5.php";
require __DIR__ . "/ss5/ss5server.php";


$ss5    = new ss5server('127.0.0.1', 9980);


$ss5->run();


