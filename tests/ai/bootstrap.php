<?php
/*
 | Boots CodeIgniter on the command line without running a page, so a test script
 | can use get_instance()->db and the application models. Runs the harmless
 | "Ai_cron index" route (usage text, swallowed) and keeps the CI instance.
 | Local development only: tests/ is not uploaded to the client server.
 */
if (PHP_SAPI !== 'cli') {
    exit("Command line only\n");
}
$ai_root = realpath(__DIR__ . '/../..');
chdir($ai_root); // index.php resolves 'system' and 'application' from the working directory
$_SERVER['argv'] = array('index.php', 'Ai_cron', 'index');
$_SERVER['argc'] = 3;
// config.php and core/Model.php read these; CLI has none
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
ob_start();
require $ai_root . '/index.php';
ob_end_clean();
$CI = & get_instance();
