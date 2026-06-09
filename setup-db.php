<?php

$host = '127.0.0.1';
$user = 'root';
$pass = '';

echo "Conectando al servidor MySQL local ($host)...\n";
try {
    // Intentar conectar al servidor MySQL (por defecto en XAMPP host: 127.0.0.1, usuario: root, sin contraseña)
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "¡Conexión establecida con éxito!\n";

    // Crear la base de datos si no existe
    echo "Creando base de datos 'CronosNotes' si no existe...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `CronosNotes` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "¡Base de datos verificada/creada!\n";

    // Seleccionar la base de datos
    $pdo->exec("USE `CronosNotes`;");

    $sqlFile = __DIR__ . '/Contexto/Cronos Notes - Primer Incremento.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("No se encontró el archivo SQL en la ruta: $sqlFile");
    }

    echo "Leyendo el archivo SQL de la base de datos...\n";
    $sql = file_get_contents($sqlFile);

    // Limpiar comentarios de MySQL
    $sql = preg_replace('/^--.*\n/m', '', $sql);
    $sql = preg_replace('/^\s*#.*\n/m', '', $sql);

    // Dividir consultas por punto y coma (;) respetando strings
    $queries = preg_split('/;(?=(?:[^\']*\'[^\']*\')*[^\']*$)/', $sql);

    echo "Importando tablas y estructura a 'CronosNotes'...\n";
    $count = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if ($query !== '') {
            try {
                $pdo->exec($query);
                $count++;
            } catch (PDOException $e) {
                // Listar advertencias pero continuar la ejecución
                echo "Nota en consulta: " . substr($query, 0, 70) . "... Detalle: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n¡Instalación de Base de Datos finalizada con éxito! Se importaron $count consultas.\n";

} catch (PDOException $e) {
    echo "\n[ERROR] Falló la conexión a MySQL: " . $e->getMessage() . "\n";
    echo "Por favor, abre el XAMPP Control Panel e inicia el módulo de MySQL (clic en 'Start').\n";
    exit(1);
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
