<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use Illuminate\Support\Facades\Validator;

class BulkImportRouters extends Command
{
    protected $signature = 'router:bulk-import {file : Path to CSV file}';
    
    protected $description = 'Bulk import routers from CSV file';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info('📂 Reading CSV file...');
        
        $csv = array_map('str_getcsv', file($filePath));
        $headers = array_shift($csv);
        
        $this->info("Found " . count($csv) . " routers to import");
        $this->newLine();

        // Validate headers
        $requiredHeaders = ['name', 'location', 'ip_address', 'nas_identifier'];
        $missingHeaders = array_diff($requiredHeaders, $headers);
        
        if (!empty($missingHeaders)) {
            $this->error('Missing required columns: ' . implode(', ', $missingHeaders));
            $this->line('Required columns: name, location, ip_address, nas_identifier');
            $this->line('Optional columns: secret, api_user, api_password, api_port, description, is_active');
            return Command::FAILURE;
        }

        // Preview first 3 rows
        $this->line('📋 Preview:');
        $this->table($headers, array_slice($csv, 0, 3));
        
        if (!$this->confirm('Import these routers?', true)) {
            $this->warn('Import cancelled.');
            return Command::FAILURE;
        }

        $success = 0;
        $failed = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar(count($csv));
        $progressBar->start();

        foreach ($csv as $row) {
            $data = array_combine($headers, $row);
            
            try {
                // Validate
                $validator = Validator::make($data, [
                    'name' => 'required|string|max:255',
                    'location' => 'required|string|max:255',
                    'ip_address' => 'required|ip|unique:routers,ip_address',
                    'nas_identifier' => 'required|string|max:255|unique:routers,nas_identifier',
                ]);

                if ($validator->fails()) {
                    $failed++;
                    $errors[] = [
                        'router' => $data['name'] ?? 'Unknown',
                        'error' => $validator->errors()->first()
                    ];
                    $progressBar->advance();
                    continue;
                }

                // Create router
                Router::create([
                    'name' => $data['name'],
                    'location' => $data['location'],
                    'ip_address' => $data['ip_address'],
                    'nas_identifier' => $data['nas_identifier'],
                    'secret' => $data['secret'] ?? env('RADIUS_SECRET_KEY', 'testing123'),
                    'api_user' => $data['api_user'] ?? null,
                    'api_password' => $data['api_password'] ?? null,
                    'api_port' => $data['api_port'] ?? 8728,
                    'description' => $data['description'] ?? null,
                    'is_active' => isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : true,
                ]);

                $success++;
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'router' => $data['name'] ?? 'Unknown',
                    'error' => $e->getMessage()
                ];
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✅ Successfully imported: {$success} routers");
        
        if ($failed > 0) {
            $this->error("❌ Failed: {$failed} routers");
            $this->newLine();
            $this->line('Errors:');
            $this->table(['Router', 'Error'], $errors);
        }

        $this->newLine();
        $this->info('🎉 Bulk import complete!');
        $this->line('📍 View routers: /admin/routers');

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
