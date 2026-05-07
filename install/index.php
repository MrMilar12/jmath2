<?php
declare(strict_types=1);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
    $dbPort = (int)($_POST['db_port'] ?? 3306);
    $dbName = trim((string)($_POST['db_name'] ?? 'jmath2'));
    $dbUser = trim((string)($_POST['db_user'] ?? 'root'));
    $dbPass = (string)($_POST['db_pass'] ?? '');

    try {
        if (!extension_loaded('pdo_mysql')) {
            throw new RuntimeException('pdo_mysql extension is required.');
        }

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $dbHost, $dbPort),
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $safeDbName = str_replace('`', '', $dbName);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . $safeDbName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . $safeDbName . '`');

        $schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
        if ($schema === false) {
            throw new RuntimeException('Cannot read schema.sql');
        }
        $pdo->exec($schema);

        $config = "<?php\nreturn [\n"
            . "    'db_host' => '" . addslashes($dbHost) . "',\n"
            . "    'db_port' => " . $dbPort . ",\n"
            . "    'db_name' => '" . addslashes($dbName) . "',\n"
            . "    'db_user' => '" . addslashes($dbUser) . "',\n"
            . "    'db_pass' => '" . addslashes($dbPass) . "',\n"
            . "    'app_name' => 'JMath2 Interactive Module',\n"
            . "];\n";

        $configPath = dirname(__DIR__) . '/config/config.php';
        if (!is_writable(dirname($configPath))) {
            $error = 'Permission denied: the <strong>config/</strong> directory is not writable by the web server. '
                . 'Run <code>chmod 777 ' . htmlspecialchars(dirname($configPath)) . '</code> in your terminal, then try again.';
        } else {
            $result = file_put_contents($configPath, $config, LOCK_EX);
            if ($result === false) {
                $error = 'Permission denied: could not write <strong>config/config.php</strong>. '
                    . 'Run <code>chmod 777 ' . htmlspecialchars(dirname($configPath)) . '</code> and try again.';
            } else {
                $success = 'Install completed. You can now open the app.';
            }
        }
    } catch (Throwable $e) {
        $error = 'Install failed: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>JMath2 Installer</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4fbff; margin: 0; }
    .wrap { max-width: 680px; margin: 2rem auto; background: #fff; border-radius: 14px; padding: 1rem 1.2rem; box-shadow: 0 10px 30px #0001; }
    label { display: block; margin-top: .7rem; font-weight: 700; }
    input { width: 100%; padding: .65rem; border-radius: 10px; border: 1px solid #bad7ef; }
    button { margin-top: 1rem; border: 0; border-radius: 10px; padding: .7rem 1.1rem; background: #ffd166; font-weight: 700; cursor: pointer; }
    .err { background: #ffe4e6; border: 1px solid #fca5a5; padding: .6rem; border-radius: 8px; }
    .ok { background: #dcfce7; border: 1px solid #86efac; padding: .6rem; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>JMath2 Installer</h1>
    <p>Configure MySQL then install schema and default data.</p>
    <?php if ($error !== ''): ?><p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($success !== ''): ?><p class="ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><p><a href="/">Open App</a></p><?php endif; ?>

    <form method="post">
      <label>DB Host</label><input name="db_host" value="127.0.0.1" required>
      <label>DB Port</label><input type="number" name="db_port" value="3306" required>
      <label>DB Name</label><input name="db_name" value="jmath2" required>
      <label>DB User</label><input name="db_user" value="root" required>
      <label>DB Password</label><input type="password" name="db_pass" value="">
      <button type="submit">Install</button>
    </form>
  </div>
</body>
</html>
