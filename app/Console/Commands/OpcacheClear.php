<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OpcacheClear extends Command
{
    protected $signature = 'opcache:clear';
    protected $description = 'Clear OPCache for both CLI and FPM (separate caches)';

    public function handle(): int
    {
        $this->clearCliOpcache();
        $this->clearFpmOpcache();

        return self::SUCCESS;
    }

    private function clearCliOpcache(): void
    {
        if (! function_exists('opcache_reset')) {
            $this->components->warn('OPCache extension not loaded — CLI cache skip.');
            return;
        }

        opcache_reset();
        $this->components->info('CLI OPCache cleared.');
    }

    private function clearFpmOpcache(): void
    {
        $key = hash('sha256', config('app.key'));
        $filename = '__opcache_clear__.php';
        $filepath = public_path($filename);

        $phpCode = '<?php' . PHP_EOL
            . 'if (($_GET["key"] ?? "") !== "' . $key . '") {'
            . ' http_response_code(403); echo json_encode(["status" => "forbidden"]); exit; }' . PHP_EOL
            . 'header("Content-Type: application/json");' . PHP_EOL
            . 'if (function_exists("opcache_reset")) {'
            . ' opcache_reset(); echo json_encode(["status" => "ok"]); }' . PHP_EOL
            . 'else { echo json_encode(["status" => "opcache_not_available"]); }';

        file_put_contents($filepath, $phpCode);

        try {
            $url = rtrim(config('app.url'), '/') . "/{$filename}?key={$key}";

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 5,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $this->components->error("FPM OPCache: cURL error — {$error}");
                return;
            }

            $data = json_decode($response, true);

            if ($httpCode === 200 && ($data['status'] ?? '') === 'ok') {
                $this->components->info('FPM OPCache cleared.');
            } elseif (($data['status'] ?? '') === 'opcache_not_available') {
                $this->components->warn('FPM OPCache: extension not loaded on FPM.');
            } else {
                $this->components->error("FPM OPCache: unexpected response (HTTP {$httpCode}).");
            }
        } finally {
            @unlink($filepath);
        }
    }
}
