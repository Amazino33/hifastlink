<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DataImportController extends Controller
{
    public function import(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'data' => 'required|array',
                'api_key' => 'required|string'
            ]);
            
            // Verify API key
            if ($validated['api_key'] !== config('app.import_api_key')) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
            
            // Save JSON file to storage
            $filename = 'imports/data_' . time() . '.json';
            Storage::put($filename, json_encode($validated['data'], JSON_PRETTY_PRINT));
            
            // Process your data here
            // Example: foreach($validated['data'] as $item) { ... }
            
            Log::info('Data imported successfully', ['filename' => $filename]);
            
            return response()->json([
                'success' => true, 
                'message' => 'Data imported successfully',
                'file' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage());
            return response()->json(['error' => 'Import failed'], 500);
        }
    }
}