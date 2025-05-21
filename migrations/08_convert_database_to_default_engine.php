<?php
class ConvertDatabaseToDefaultEngine extends Migration
{
    private static $tables = [
        'superwiki_cms',
        'superwiki_pages',
        'superwiki_settings',
        'superwiki_versions',
    ];

    public function up()
    {
        $default_engine = null;

        $query = "SHOW ENGINES";
        DBManager::get()->query($query, [], function (array $row) use (&$default_engine) {
            if ($row['Support'] === 'DEFAULT') {
                $default_engine = $row['Engine'];
            }
        });

        if (!$default_engine) {
            return;
        }

        foreach (self::$tables as $table) {
            $query = "ALTER TABLE `{$table}` ENGINE = {$default_engine}";
            DBManager::get()->exec($query);
        }
    }
}