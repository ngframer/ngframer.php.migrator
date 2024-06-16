<?php

namespace NGFramer\NGFramerPHPMigrator;

use app\config\ApplicationConfig;

final class MigrationLogger
{
    // Variable to store the class instance and log data.
    private static ?self $instance = null;
    private array $migrationLog = [];
    private array $migrationClasses = [];


    public static function init()
    {
        // Setting up the instance of the class.
        if (self::$instance === null) {
            self::$instance = new MigrationLogger();
        }

        // Get the application mode from the Configuration.
        $appMode = ApplicationConfig::get('appMode');
        // Only run the migration logger if in the development mode.
        if ($appMode == 'development') {
            self::$instance->readClasses();
            self::$instance->buildLog();
        }
    }


    private function readClasses(): void
    {
        // Get the location of the main folder.
        $appPath = ApplicationConfig::get('root');
        $appNamespace = ApplicationConfig::get('namespace');

        // Get the list of files in the migrations folder.
        $migrationFiles = array_values(self::readDirectory($appPath . "/migrations"));

        // Check if _migrationLog.json if not exists and then close it.
        $logPath = $appPath . "/migrations/_migrationLog.json";
        $logFile = fopen("$logPath", "w");
        fwrite($logFile, json_encode(array()));
        fclose($logFile);

        // Remove _migrationLog.json from the list of migration files.
        $migrationFiles = array_filter($migrationFiles, function ($file) {
            return $file !== '_migrationLog.json';
        });

        // Get the name of the classes, and get the namespace.
        foreach ($migrationFiles as $migrationFile) {
            // Remove the '.php' extension from the file name.
            $migrationFile = str_replace('.php', '', $migrationFile);
            // Add the fully qualified class name to the migrationClasses array.
            $this->migrationClasses[] = "$appNamespace\\migrations\\" . $migrationFile;
        }
    }


    private function buildLog(): void
    {
        // Loop through the classes and get the migration script for up and down continuously.
        foreach ($this->migrationClasses as $migrationClass) {
            $migrationInstance = new $migrationClass();
            $migrateUpScript = $migrationInstance->up();
            $this->migrationLog[] = ['up' => $migrateUpScript];
            // Write the migrationLog to the file upon creation of migrationLog['up'].
            $this->writeLog();
            // Now the turn to make an migration script for down migration, and add to the last element that was created.
            $migrateDownScript = $migrationInstance->down();
            // Get the index of the last element and update it.
            $lastIndex = count($this->migrationLog) - 1;
            $this->migrationLog[$lastIndex]['down'] = $migrateDownScript;
            // Write the migrationLog to the file also upon the creation of migrationLog['down'].
            $this->writeLog();
        }
    }


    private function writeLog(): void
    {
        // Get the location of the log file.
        $logPath = ApplicationConfig::get('root') . "/migrations/_migrationLog.json";
        // Make a file if not exists, else open.
        $logFile = fopen("$logPath", "w");

        // Get the content to write into the file by first building the migration log.
        $logContent = json_encode($this->migrationLog);

        // Write the content to the file and close it.
        fwrite($logFile, $logContent);
        fclose($logFile);
    }


    // Supportive function for reading the directory.
    public function readDirectory(string $path): array
    {
        $files = scandir($path);
        return array_diff($files, ['.', '..']);
    }
}
