<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCategoriesFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upsert categories from JSON file into the database';

    /**
     * Number of records per chunk for upsert.
     *
     * @var int
     */
    private int $chunkSize = 300;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $json     = json_decode(
            file_get_contents(storage_path('app/jsonfiles/extracted_categories.json')),
            true
        );
        $categories = collect($json);
        $now        = now();

        // Prepare the two sets of rows
        $allRows = $categories
            ->map(fn($c) => [
                'id'                 => $c['id'],
                'name'               => $c['name'],
                'slug'               => $c['slug'],
                'description'        => $c['description'],
                'category_image_id'  => $c['category_image_id'],
                'category_icon_id'   => $c['category_icon_id'],
                'status'             => $c['status'],
                'type'               => $c['type'],
                'commission_rate'    => $c['commission_rate'],
                'created_by_id'      => $c['created_by_id'],
                'parent_id'          => null,         // null out in phase 1
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);

        $parentRows = $categories
            ->map(fn($c) => [
                'id'        => $c['id'],
                'parent_id' => $c['parent_id'] ?: null,
            ]);

        // pick a chunk size so that (rows_per_chunk × columns) < 65 535
        // e.g. columns in phase1 = 13, so 5 000 rows → 65 000 params
        $phase1ChunkSize = 4000;  // safe bound

        // Phase 1: insert/update *without* parent relationships
        foreach ($allRows->chunk($phase1ChunkSize) as $chunk) {
            DB::table('categories')
                ->upsert(
                    $chunk->values()->all(),        // rows for this batch
                    ['id'],                         // unique by id
                    [   // columns to update on conflict
                        'name','slug','description',
                        'category_image_id','category_icon_id',
                        'status','type','commission_rate',
                        'created_by_id','updated_at','created_at',
                    ]
                );
            $this->info("Upserted " . count($chunk) . " base records");
        }

        // Phase 2: now set the real parent_id (only 2 columns → very few params)
        $phase2ChunkSize = 10000;  // plenty of room: only 2 params per row
        foreach ($parentRows->chunk($phase2ChunkSize) as $chunk) {
            DB::table('categories')
                ->upsert(
                    $chunk->values()->all(),
                    ['id'],            // match on id
                    ['parent_id']      // only update parent_id
                );
            $this->info("Updated parent_id for " . count($chunk) . " records");
        }

        $this->info("🎉 All categories imported with parent relationships.");
    }

}
