<?php

namespace Ajaxray\ServerSync\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Symfony\Component\Process\Process;

class SyncPullCommand extends Command
{
    protected $signature = 'sync:pull 
        {--host= : Production server hostname or IP}
        {--user= : SSH username for production server}
        {--path= : Path to production installation}
        {--remote=production : Remote server configuration to use (e.g., production, staging)}
        {--skip-db : Skip database sync}
        {--skip-files : Skip files sync}
        {--delete : Remove files that don\'t exist in production}
        {--exclude-tables= : Comma-separated list of tables to exclude}
        {--only-tables= : Comma-separated list of tables to include}
        {--force : Skip confirmation in production environment}';

    protected $description = 'Pull and sync database and files from production to local environment';

    protected string $remoteHost;
    protected string $remoteUser;
    protected string $remotePath;

    public function handle()
    {        
        if (App::environment('production') && !$this->option('force')) {
            $this->error('This command cannot be run in production environment. Use --force to override.');
            return 1;
        }

        $remote = $this->option('remote');
        
        $this->remoteHost = $this->option('host') ?: Config::get("server-sync.$remote.host");
        $this->remoteUser = $this->option('user') ?: Config::get("server-sync.$remote.user");
        $this->remotePath = $this->option('path') ?: Config::get("server-sync.$remote.path");

        if ($remote) {
            $this->info("Pulling from {$this->remoteHost} as {$this->remoteUser}");            
        }

        if (!$this->validateRequirements()) {            
            return 1;
        }

        if (!$this->option('skip-db')) {
            $this->syncDatabase();
        }

        if (!$this->option('skip-files')) {
            $this->syncFiles();
        }

        $this->info('Production sync completed successfully!');
        return 0;
    }

    protected function validateRequirements(): bool
    {
        if (!$this->remoteHost || !$this->remoteUser || !$this->remotePath) {
            $this->error('Production server details are required. Please provide --host, --user and --path options or configure them in config/server-sync.php');
            return false;
        }

        // Check if mysql client is installed
        if (!$this->option('skip-db') && !$this->commandExists('mysql')) {
            $this->error('MySQL client is not installed. Please install it to sync database.');
            return false;
        }

        // Check if rsync is installed
        if (!$this->option('skip-files') && !$this->commandExists('rsync')) {
            $this->error('rsync is not installed. Please install it to sync files.');
            return false;
        }

        // Test SSH connection
        if (!$this->option('skip-db') || !$this->option('skip-files')) {
            $testConnection = Process::fromShellCommandline("ssh -q {$this->remoteUser}@{$this->remoteHost} exit");
            $testConnection->run();

            if (!$testConnection->isSuccessful()) {
                $this->error('Failed to connect to production server. Please check your SSH configuration.');
                return false;
            }
        }

        return true;
    }

    protected function syncDatabase()
    {
        $this->info('Starting database sync...');
        
        // Create dumps directory if it doesn't exist
        $dumpsPath = Config::get('server-sync.database.dump_path', App::storagePath('dumps'));
        if (!is_dir($dumpsPath)) {
            mkdir($dumpsPath, 0755, true);
        }

        $dumpFile = $dumpsPath . '/production_' . date('Y_m_d_His') . '.sql';
        
        // Get production database credentials
        $dbConfig = $this->getProductionDatabaseConfig();
        if (!$dbConfig) return;

        // Build mysqldump command with table filters
        $dumpCommand = $this->buildMysqlDumpCommand($dbConfig, $dumpFile);
        
        $this->info('Downloading database dump from production...');
        $process = Process::fromShellCommandline($dumpCommand);
        $process->setTimeout(null); // No timeout for large databases
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->output->write('.');
            }
        });
        
        if (!$process->isSuccessful()) {
            $this->error('Failed to create database dump: ' . $process->getErrorOutput());
            return;
        }

        // Import dump to local database
        $this->info('Importing database dump to local database...');
        $importCommand = sprintf(
            'mysql -h%s -u%s -p\'%s\' %s < %s',
            Config::get('database.connections.mysql.host'),
            Config::get('database.connections.mysql.username'),
            Config::get('database.connections.mysql.password'),
            Config::get('database.connections.mysql.database'),
            $dumpFile
        );
        
        $process = Process::fromShellCommandline($importCommand);
        $process->setTimeout(null);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $this->error('Failed to import database dump: ' . $process->getErrorOutput());
            return;
        }

        // Clean up dump file
        unlink($dumpFile);
        $this->info('Database sync completed successfully!');
    }

    protected function syncFiles()
    {
        $this->info('Starting files sync...');

        $deleteFlag = $this->option('delete') ? ' --delete' : '';
        $excludePatterns = Config::get('server-sync.files.exclude', []);
        $excludeFlags = '';
        
        foreach ($excludePatterns as $pattern) {
            $excludeFlags .= " --exclude='$pattern'";
        }

        foreach (Config::get('server-sync.files.paths', [storage_path('app')]) as $path) {
            $relativePath = str_replace(base_path() . '/', '', $path);
            
            $rsyncCommand = sprintf(
                'rsync -avz --compress %s %s --progress %s@%s:%s/%s/ %s/',
                $deleteFlag,
                $excludeFlags,
                $this->remoteUser,
                $this->remoteHost,
                $this->remotePath,
                $relativePath,
                $path
            );

            $this->info("Syncing {$relativePath}...");
            $process = Process::fromShellCommandline($rsyncCommand);
            $process->setTimeout(null);
            $process->run(function ($type, $buffer) {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $this->error("Failed to sync {$relativePath}: " . $process->getErrorOutput());
                return;
            }
        }

        $this->info('Files sync completed successfully!');
    }

    protected function getProductionDatabaseConfig(): ?array
    {
        $this->info('Retrieving production database credentials...');
        $sshCommand = sprintf(
            'ssh %s@%s "cd %s && grep -E \'^DB_(HOST|DATABASE|USERNAME|PASSWORD)=\' .env"',
            $this->remoteUser,
            $this->remoteHost,
            $this->remotePath
        );
        
        $process = Process::fromShellCommandline($sshCommand);
        $process->run();
        
        if (!$process->isSuccessful()) {
            $this->error('Failed to get production database credentials: ' . $process->getErrorOutput());
            return null;
        }
        
        $dbConfig = [];
        foreach (explode("\n", $process->getOutput()) as $line) {
            if (empty($line)) continue;
            list($key, $value) = explode('=', $line, 2);
            $dbConfig[strtolower(str_replace('DB_', '', $key))] = trim($value);
        }

        $requiredKeys = ['host', 'database', 'username', 'password'];
        foreach ($requiredKeys as $key) {
            if (empty($dbConfig[$key])) {
                $this->error("Missing required database configuration: DB_" . strtoupper($key));
                return null;
            }
        }

        return $dbConfig;
    }

    protected function buildMysqlDumpCommand(array $dbConfig, string $tempFile): string
    {
        $command = sprintf(
            'ssh %s@%s "mysqldump -h%s -u%s -p\'%s\' %s',
            $this->remoteUser,
            $this->remoteHost,
            $dbConfig['host'],
            $dbConfig['username'],
            $dbConfig['password'],
            $dbConfig['database']
        );

        // Add table filters
        if ($this->option('only-tables')) {
            $tables = explode(',', $this->option('only-tables'));
            $command .= ' ' . implode(' ', $tables);
        } elseif ($this->option('exclude-tables')) {
            $tables = explode(',', $this->option('exclude-tables'));
            foreach ($tables as $table) {
                $command .= " --ignore-table={$dbConfig['database']}.{$table}";
            }
        } else {
            $excludedTables = Config::get('server-sync.database.tables.exclude', []);
            foreach ($excludedTables as $table) {
                $command .= " --ignore-table={$dbConfig['database']}.{$table}";
            }
        }

        // Create a temporary file on the remote server, then download it
        $remoteTempFile = '/tmp/temp_dump.sql';
        $command .= " > {$remoteTempFile}\" && scp {$this->remoteUser}@{$this->remoteHost}:{$remoteTempFile} {$tempFile} && ssh {$this->remoteUser}@{$this->remoteHost} \"rm {$remoteTempFile}\"";
        
        return $command;
    }

    protected function commandExists(string $command): bool
    {
        $process = Process::fromShellCommandline("which $command");
        $process->run();
        return $process->isSuccessful();
    }
} 