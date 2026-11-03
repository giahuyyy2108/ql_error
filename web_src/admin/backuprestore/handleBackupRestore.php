<?php

// Legacy dispatcher compatibility: keep one implementation of backup/restore.
require_once 'web_src/admin/backupAction.php';

$backupAction = new backupAction();
return $backupAction->backup();
