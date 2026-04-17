<?php
declare(strict_types=1);

auth_logout_api();
flash_set('success', 'Cerraste sesión correctamente.');
redirect('/login');
