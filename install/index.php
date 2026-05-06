<?php
declare(strict_types=1);

if (isset($isInstalled) && $isInstalled) {
    echo '<!doctype html><html><body style="font-family: sans-serif; padding:2rem"><h2>Already Installed</h2><p>Delete or protect the install folder after deployment.</p><p><a href="/">Go to app</a></p></body></html>';
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string)($_POST['db_host'] ?? '127.0.0.1'));
    $dbPort = trim((string)($_POST['db_port'] ?? '3306'));
    $dbName = trim((string)($_POST['db_name'] ?? 'jmath2'));
    $dbUser = trim((string)($_POST['db_user'] ?? 'root'));
    $dbPass = (string)($_POST['db_pass'] ?? '');

    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        $error = 'Please complete all required fields.';
    } else {
        try {
            $dsnNoDb = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $dbHost, (int)$dbPort);
            $pdo = new PDO($dsnNoDb, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $dbName) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $pdo->exec('USE `' . str_replace('`', '', $dbName) . '`');

            $schema = file_get_contents(dirname(__DIR__) . '/sql/schema.sql');
            if ($schema === false) {
                throw new RuntimeException('Cannot read schema file.');
            }

            $pdo->exec($schema);

            $configContent = "<?php\nreturn [\n"
                . "    'db_host' => '" . addslashes($dbHost) . "',\n"
                . "    'db_port' => " . (int)$dbPort . ",\n"
                . "    'db_name' => '" . addslashes($dbName) . "',\n"
                . "    'db_user' => '" . addslashes($dbUser) . "',\n"
                . "    'db_pass' => '" . addslashes($dbPass) . "',\n"
                . "];\n";

            $target = dirname(__DIR__) . '/config/config.php';
            file_put_contents($target, $configContent, LOCK_EX);
            @chmod($target, 0640);

            $success = 'Installation complete. You can now open the app.';
        } catch (Throwable $e) {
            $error = 'Install failed: ' . $e->getMessage();
        }
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
    body { font-family: Arial, sans-serif; background: #f3f9ff; margin: 0; }
    .wrap { max-width: 680px; margin: 2rem auto; background: #fff; padding: 1rem 1.2rem; border-radius: 12px; box-shadow: 0 8px 30px #0001; }
    h1 { margin-top: 0; }
    label { display:block; margin-top: .7rem; font-weight: 600; }
    input { width: 100%; padding: .58rem; border-radius: 8px; border: 1px solid #b8d6f3; }
    button { margin-top: .9rem; background: #ffcd4e; border: 0; padding: .7rem 1rem; border-radius: 8px; font-weight: 700; cursor: pointer; }
    .err { background:#ffe4e6; border:1px solid #fca5a5; padding:.6rem; border-radius: 8px; }
    .ok { background:#dcfce7; border:1px solid #86efac; padding:.6rem; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>JMath2 MySQL Installer</h1>
    <p>Set your database credentials then click install.</p>

    <?php if ($error !== ''): ?>
      <p class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <p class="ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
      <p><a href="/">Open JMath2</a></p>
    <?php endif; ?>

    <form method="post">
      <label>DB Host</label>
      <input type="text" name="db_host" value="127.0.0.1" required>

      <label>DB Port</label>
      <input type="number" name="db_port" value="3306" required>

      <label>DB Name</label>
      <input type="text" name="db_name" value="jmath2" required>

      <label>DB User</label>
      <input type="text" name="db_user" value="root" required>

      <label>DB Password</label>
      <input type="password" name="db_pass" value="">

      <button type="submit">Install Database</button>
    </form>
  </div>
</body>
</html>
