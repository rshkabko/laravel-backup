<?php

namespace Flamix\LaravelBackup\Tests\Feature;

use Flamix\LaravelBackup\LaravelBackupProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use RuntimeException;
use ZipArchive;

class DatabaseBackupTest extends TestCase
{
    private string $bin;

    private string $path;

    private ?string $envPath;

    protected function getPackageProviders($app)
    {
        return [LaravelBackupProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.name', 'Shop');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql', 'host' => 'db.local', 'port' => 3307,
            'database' => 'shop', 'username' => 'shop_user', 'password' => 's3cret', 'prefix' => '',
        ]);
        $app['config']->set('backup.telegram.token', 'fake-token');
        $app['config']->set('backup.telegram.chat_id', '-100500');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->bin = sys_get_temp_dir() . '/laravel-backup-test-' . getmypid();
        File::ensureDirectoryExists($this->bin);
        $this->fakeMysqldump();

        $this->path = getenv('PATH');
        $this->envPath = $_ENV['PATH'] ?? null;
        // Symfony Process reads $_ENV and $_SERVER, not only getenv(); a runner with mysql-client must still hit the fake.
        $path = $this->bin . PATH_SEPARATOR . $this->path;
        putenv("PATH={$path}");
        $_ENV['PATH'] = $_SERVER['PATH'] = $path;

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    protected function tearDown(): void
    {
        putenv("PATH={$this->path}");
        $_SERVER['PATH'] = $this->path;
        if ($this->envPath === null) {
            unset($_ENV['PATH']);
        } else {
            $_ENV['PATH'] = $this->envPath;
        }
        File::deleteDirectory($this->bin);
        parent::tearDown();
    }

    public function test_a_disabled_backup_dumps_and_sends_nothing()
    {
        config(['backup.database.enabled' => false]);

        $this->artisan('backup:database')->expectsOutput('Database backup is disabled!')->assertExitCode(0);

        $this->assertFileDoesNotExist($this->bin . '/calls.log');
        Http::assertNothingSent();
    }

    public function test_a_backup_arrives_in_telegram_as_one_zip()
    {
        Carbon::setTestNow('2026-03-05 14:37:00');

        $this->artisan('backup:database')->assertExitCode(0);

        $sent = $this->documents();
        $this->assertCount(1, $sent);
        $this->assertSame('2026_03_05_14_37.zip', $sent[0]['filename']);
        $this->assertSame('-100500', $sent[0]['chat_id']);
        $this->assertStringContainsString('Database backup to Shop successfully created', $sent[0]['caption']);
        $this->assertSame(['data_shop.sql', 'structure_shop.sql'], $this->zipEntries($sent[0]['contents']));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/botfake-token/sendDocument'));
        $this->assertDirectoryDoesNotExist(storage_path('backup'));
    }

    public function test_mysqldump_gets_the_mysql_connection_and_the_password_only_through_the_environment()
    {
        config(['backup.database.ignore' => ['cache', 'activity_log']]);

        $this->artisan('backup:database')->assertExitCode(0);

        [$structure, $data] = file($this->bin . '/calls.log', FILE_IGNORE_NEW_LINES);
        $this->assertSame('pwd=s3cret -h db.local -P 3307 -u shop_user --no-tablespaces --no-data shop', $structure);
        $this->assertSame('pwd=s3cret -h db.local -P 3307 -u shop_user --no-tablespaces --ignore-table=shop.cache --ignore-table=shop.activity_log shop', $data);
    }

    public function test_a_zip_over_the_chunk_size_arrives_in_parts_that_restore_by_cat()
    {
        config(['backup.telegram.chunk_size' => 2048]);

        $this->artisan('backup:database')->assertExitCode(0);

        $sent = $this->documents();
        $total = count($sent);
        $this->assertGreaterThan(1, $total);
        foreach ($sent as $i => $part) {
            $this->assertStringEndsWith(sprintf('.zip.%03d', $i + 1), $part['filename']);
            $this->assertSame(sprintf('part %d/%d', $i + 1, $total), $part['caption']);
        }
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && str_contains($r['text'], "split into {$total} parts"));
        $this->assertSame(['data_shop.sql', 'structure_shop.sql'], $this->zipEntries(implode('', array_column($sent, 'contents'))));
    }

    public function test_an_empty_chunk_size_still_delivers_the_backup()
    {
        $this->markTestIncomplete('Bug: an empty BACKUP_TELEGRAM_CHUNK_SIZE casts to 0, src/FilesController.php:63 split() returns [] and the command reports success having sent nothing.');

        config(['backup.telegram.chunk_size' => '']);

        $this->artisan('backup:database')->assertExitCode(0);

        $this->assertNotEmpty($this->documents());
    }

    public function test_a_failed_dump_is_reported_to_telegram_and_rethrown()
    {
        $this->fakeMysqldump(fail: true);

        try {
            Artisan::call('backup:database');
            $this->fail('The command did not throw.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('mysqldump failed (exit 2): Access denied', $e->getMessage());
        }

        $this->assertSame([], $this->documents());
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && str_starts_with($r['text'], '❌ Database backup failed for Shop: mysqldump failed'));
        $this->assertDirectoryDoesNotExist(storage_path('backup'));
    }

    public function test_with_telegram_disabled_the_dump_is_made_and_thrown_away()
    {
        config(['backup.telegram.enabled' => false]);

        $this->artisan('backup:database')->assertExitCode(0);

        $this->assertCount(2, file($this->bin . '/calls.log'));
        Http::assertNothingSent();
        $this->assertDirectoryDoesNotExist(storage_path('backup'));
    }

    private function fakeMysqldump(bool $fail = false): void
    {
        $body = $fail
            ? 'echo "Access denied for user" >&2; exit 2'
            : 'head -c 3000 /dev/urandom | base64';

        File::put($this->bin . '/mysqldump', "#!/bin/sh\necho \"pwd=\$MYSQL_PWD \$*\" >> \"$this->bin/calls.log\"\n{$body}\n");
        chmod($this->bin . '/mysqldump', 0755);
    }

    /** @return list<array{filename: string, contents: string, chat_id: string, caption: string}> */
    private function documents(): array
    {
        return Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/sendDocument'))
            ->map(function ($pair) {
                $fields = collect($pair[0]->data())->keyBy('name');

                return [
                    'filename' => $fields['document']['filename'],
                    'contents' => (string) $fields['document']['contents'],
                    'chat_id' => (string) $fields['chat_id']['contents'],
                    'caption' => (string) $fields['caption']['contents'],
                ];
            })->values()->all();
    }

    /** @return list<string> */
    private function zipEntries(string $contents): array
    {
        $file = $this->bin . '/restored.zip';
        File::put($file, $contents);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($file));
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        sort($names);

        return $names;
    }
}
