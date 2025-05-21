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
        $default_engine = $this->getDefaultEngine();

        if (!$default_engine || $default_engine === 'MyISAM') {
            return;
        }

        foreach (self::$tables as $table) {
            $this->alterTableEngine($table, $default_engine);
        }
    }

    public function down()
    {
        $default_engine = $this->getDefaultEngine();

        if (!$default_engine || $default_engine === 'MyISAM') {
            return;
        }

        foreach (self::$tables as $table) {
            $this->alterTableEngine($table, 'MyISAM');
        }
    }

    private function getDefaultEngine(): ?string
    {
        $default_engine = null;

        $query = "SHOW ENGINES";
        DBManager::get()->query($query, [], function (array $row) use (&$default_engine) {
            if ($row['Support'] === 'DEFAULT') {
                $default_engine = $row['Engine'];
            }
        });

        return $default_engine;
    }

    private function alterTableEngine(string $table, string $engine)
    {
        $query = "ALTER TABLE `{$table}` ENGINE = {$engine}";
        DBManager::get()->exec($query);
    }
}