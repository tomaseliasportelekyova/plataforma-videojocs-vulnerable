<?php
/**
 * Script de Copia de Seguridad de la Base de Datos
 *
 * Utiliza mysqldump para exportar la base de datos a un archivo SQL
 * en la misma carpeta del script.
 */

// 1. Credenciales de la Base de Datos (tomadas de db_mysqli.php)
$DB_HOST = "localhost";
$DB_USER = "plataforma_user";
$DB_PASS = "123456789a";
$DB_NAME = "plataforma_videojocs";

// 2. Definición de la Ruta del Archivo
$BACKUP_DIR = __DIR__; // Directorio actual del script
$FILE_NAME = "plataforma_videojocs.sql";
$FILE_PATH = $BACKUP_DIR . '/' . $FILE_NAME;

// 3. Construcción del Comando mysqldump
// Usamos la opción --single-transaction para bases de datos InnoDB para evitar bloqueos
// 2>&1 redirige la salida de error a la salida estándar, útil para el log de cron
$command = sprintf(
    'mysqldump --single-transaction -h %s -u %s -p\'%s\' %s > %s 2>&1',
    escapeshellarg($DB_HOST),
    escapeshellarg($DB_USER),
    $DB_PASS, // Se pasa sin escapeshellarg ya que se incluye en comillas simples en el comando
    escapeshellarg($DB_NAME),
    escapeshellarg($FILE_PATH)
);

// 4. Ejecución del Comando
$output = shell_exec($command);

// 5. Verificación y Log (Opcional, pero recomendado)
if ($output === null || strpos($output, 'error') !== false) {
    // Si el comando falló
    $log_message = sprintf("[%s] ERROR: Falló la copia de seguridad de %s. Output: %s\n", date('Y-m-d H:i:s'), $DB_NAME, $output);
    error_log($log_message, 3, $BACKUP_DIR . '/backup_error.log');
    echo "ERROR: Falló la copia de seguridad. Revisa el log.\n";
    exit(1);
} else {
    // Si el comando tuvo éxito
    $log_message = sprintf("[%s] SUCCESS: Base de datos %s respaldada en %s\n", date('Y-m-d H:i:s'), $DB_NAME, $FILE_PATH);
    error_log($log_message, 3, $BACKUP_DIR . '/backup_success.log');
    echo "SUCCESS: Copia de seguridad creada en " . $FILE_PATH . "\n";
    exit(0);
}
?>