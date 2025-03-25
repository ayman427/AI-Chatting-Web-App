<?php
namespace Database\Seeders;

use App\Models\ModelSelection;
use Illuminate\Database\Seeder;

class ModelSelectionSeeder extends Seeder
{
    public function run()
    {
        ModelSelection::insert([
            [
                'name'       => 'GPT-4-Turbo',
                'api_key'    => 'your_openrouter_api_key',
                'api_url'    => 'https://openrouter.ai/api/v1/chat/completions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Mistral-7B',
                'api_key'    => 'your_openrouter_api_key',
                'api_url'    => 'https://openrouter.ai/api/v1/chat/completions',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
