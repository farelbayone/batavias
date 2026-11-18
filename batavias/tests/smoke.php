<?php
// Run: php tests/smoke.php (after database setup)
require __DIR__.'/../app/bootstrap.php';
$pdo=db(); echo "DB connection: OK\n";
foreach(['users','events','ticket_types','orders','payments','tickets','api_logs','system_logs'] as $table){$n=$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();echo "$table: $n rows\n";}
