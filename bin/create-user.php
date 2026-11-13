<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use ELabore\Models\User;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';
require $projectRoot . '/config/database.php';

try {
    Dotenv::createImmutable($projectRoot)->safeLoad();
} catch (Throwable $exception) {
    fwrite(STDERR, "Não foi possível carregar a configuração local. Confira o arquivo .env.\n");
    exit(1);
}

$promptLine = static function (string $prompt): string {
    if (function_exists('readline')) {
        return trim((string) readline($prompt));
    }

    fwrite(STDOUT, $prompt);
    return trim((string) fgets(STDIN));
};

$promptSecret = static function (): string {
    if (PHP_OS_FAMILY === 'Windows') {
        $script = '$secure = Read-Host -Prompt "Senha (oculta)" -AsSecureString; '
            . '$pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure); '
            . 'try { [Console]::WriteLine([Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)) } '
            . 'finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }';
        $descriptors = [0 => ['file', 'php://stdin', 'r'], 1 => ['pipe', 'w'], 2 => ['file', 'php://stderr', 'w']];
        $process = proc_open(['powershell.exe', '-NoProfile', '-Command', $script], $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException('Não foi possível abrir a leitura segura da senha.');
        }

        $password = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exitCode = proc_close($process);
        if ($exitCode !== 0) {
            throw new RuntimeException('A leitura segura da senha foi cancelada.');
        }

        return rtrim($password, "\r\n");
    }

    fwrite(STDOUT, 'Senha (entrada oculta): ');
    shell_exec('stty -echo 2>/dev/null');
    try {
        return rtrim((string) fgets(STDIN), "\r\n");
    } finally {
        shell_exec('stty echo 2>/dev/null');
        fwrite(STDOUT, PHP_EOL);
    }
};

try {
    fwrite(STDOUT, "Criar conta local do E-Labore\n");
    $name = $promptLine('Nome: ');
    $email = $promptLine('E-mail: ');
    $role = strtolower($promptLine('Perfil (aluno/professor): '));
    $password = $promptSecret();
    $passwordConfirmation = $promptSecret();

    if (!hash_equals($password, $passwordConfirmation)) {
        throw new InvalidArgumentException('As senhas informadas não conferem.');
    }

    $id = (new User(createDatabaseConnection()))->create($name, $email, $password, $role);
    fwrite(STDOUT, sprintf("Conta criada com sucesso. ID: %d | Perfil: %s\n", $id, $role));
} catch (InvalidArgumentException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
} catch (Throwable $exception) {
    error_log(sprintf('Falha ao criar conta local: %s', $exception->getMessage()));
    fwrite(STDERR, "Não foi possível criar a conta. Confira o .env e a conexão com o banco.\n");
    exit(1);
}
