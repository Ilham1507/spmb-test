<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DatabaseBackupController extends Controller
{
    public function index()
    {
        return view('admin.database-backup.index', [
            'backups' => collect(File::files(storage_path('app/database-backups')))
                ->filter(fn ($file) => str_ends_with($file->getFilename(), '.sql'))
                ->sortByDesc(fn ($file) => $file->getMTime())
                ->take(10)
                ->values(),
        ]);
    }

    public function download()
    {
        $directory = storage_path('app/database-backups');
        File::ensureDirectoryExists($directory);
        $filename = 'backup-spmb-' . now()->format('Ymd-His') . '.sql';
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        $process = $this->mysqlProcess('mysqldump.exe', ['--single-transaction', '--routines', '--triggers', config('database.connections.mysql.database')]);
        $process->run();

        if (! $process->isSuccessful()) {
            return back()->with('error', 'Backup database gagal dibuat. Pastikan layanan MySQL Laragon sedang berjalan.');
        }

        File::put($path, $process->getOutput());
        return response()->download($path, $filename, ['Content-Type' => 'application/sql']);
    }

    public function restore(Request $request)
    {
        $validated = $request->validate([
            'backup_file' => ['required', 'file', 'mimes:sql,txt', 'max:102400'],
            'confirmation' => ['required', 'in:KEMBALIKAN DATABASE'],
        ], [
            'confirmation.in' => 'Ketik KEMBALIKAN DATABASE untuk melanjutkan pemulihan.',
        ]);

        $file = $validated['backup_file'];
        $contents = File::get($file->getRealPath());
        if (! str_contains($contents, 'CREATE') && ! str_contains($contents, 'INSERT')) {
            return back()->with('error', 'File SQL tidak berisi data backup yang dapat dipulihkan.');
        }

        $directory = storage_path('app/database-backups');
        File::ensureDirectoryExists($directory);
        $safetyCopy = $directory . DIRECTORY_SEPARATOR . 'sebelum-pemulihan-' . now()->format('Ymd-His') . '.sql';
        $dump = $this->mysqlProcess('mysqldump.exe', ['--single-transaction', '--routines', '--triggers', config('database.connections.mysql.database')]);
        $dump->run();
        if (! $dump->isSuccessful()) {
            return back()->with('error', 'Pemulihan dibatalkan karena backup pengaman tidak dapat dibuat.');
        }
        File::put($safetyCopy, $dump->getOutput());

        $restore = $this->mysqlProcess('mysql.exe', [config('database.connections.mysql.database')]);
        $restore->setInput($contents);
        $restore->setTimeout(180);
        $restore->run();

        if (! $restore->isSuccessful()) {
            return back()->with('error', 'Pemulihan gagal. Backup pengaman tetap tersimpan di server.');
        }

        return redirect()->route('admin.database-backup.index')->with('success', 'Database berhasil dipulihkan. Backup pengaman dibuat otomatis sebelum pemulihan.');
    }

    private function mysqlProcess(string $executable, array $arguments): Process
    {
        $configuredBinary = $executable === 'mysqldump.exe'
            ? env('DATABASE_DUMP_BINARY')
            : env('DATABASE_CLIENT_BINARY');
        $localBinary = collect(File::glob('C:/laragon/bin/mysql/*/bin/' . $executable))
            ->sortDesc()
            ->first(fn ($candidate) => File::exists($candidate));
        $binary = $configuredBinary && File::exists($configuredBinary)
            ? $configuredBinary
            : ($localBinary ?: ($executable === 'mysqldump.exe' ? 'mysqldump' : 'mysql'));

        abort_unless($binary, 500, 'Program backup database tidak ditemukan. Atur DATABASE_DUMP_BINARY dan DATABASE_CLIENT_BINARY di environment hosting.');
        $connection = config('database.connections.mysql');
        $process = new Process(array_merge([$binary, '--host=' . $connection['host'], '--port=' . $connection['port'], '--user=' . $connection['username']], $arguments));
        $process->setEnv(['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
        $process->setTimeout(180);
        return $process;
    }
}
