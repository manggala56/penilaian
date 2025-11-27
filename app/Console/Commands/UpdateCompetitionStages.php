<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\CompetitionStage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateCompetitionStages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-competition-stages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update active competition stages based on current date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $competitions = Competition::where('is_active', true)->get();

        foreach ($competitions as $competition) {
            $this->info("Checking competition: {$competition->name}");

            // Find the stage that matches today's date
            $matchingStage = $competition->stages()
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($matchingStage) {
                if ($competition->active_stage_id !== $matchingStage->id) {
                    $competition->update(['active_stage_id' => $matchingStage->id]);
                    $this->info("Updated active stage to: {$matchingStage->name}");
                    Log::info("Competition '{$competition->name}' active stage updated to '{$matchingStage->name}' via scheduler.");
                    
                    // Optional: Send notification?
                } else {
                    $this->info("Active stage is already correct: {$matchingStage->name}");
                }
            } else {
                $this->warn("No matching stage found for today.");
            }
        }
    }
}
