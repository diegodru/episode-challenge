<?php

/*
 * Deep Copying a nested structure requires a recursive traversal. However,
 * because the structure has a pre-defined nested relation between layers,
 * nested loops would do the trick.
 * 
 * However, to avoid database roundtrips, rely on storing copies of registers
 * in memory and duplicating 
 *
 */

use Expection;

// Laravel imports
use App\Models\Episode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class DeepCopyJob implements ShouldQueue
{
  use Dispatchable, Queueable;

  private $episode_id;

  public function __construct($episode_id)
  {
    $this->episode_id = $episode_id;
  }

  public function handle()
	{
		DB::beginTransaction();

		try {

      $episode_data = DB::table('episodes')
        ->where('id', $this->episode_id)
        ->get();

      $parts = DB::table('parts')
        ->where('episode_id', $this->episode_id)
        ->get();

			$items = DB::table('items')
        ->whereIn('part_id', array_map(
          function($part) {
            return $part['id'];
          }, $parts)
        )
        ->get();

			$blocks = DB::table('blocks')
        ->whereIn('item_id', array_map(
          function($item) {
            return $item['id'];
          }, $items)
        )
        ->get();

      /*
       * if id generation is handled locally, then iterate over each array to
       * modify ids. otherwise, filter the id field from arrays and let the
       * database handle the id assignment on insertion
       */

      DB::table('episodes')->insert($episode_data);
      DB::table('parts')->insert($parts);
      DB::table('items')->insert($items);
      DB::table('blocks')->insert($blocks);

			DB::commit();
		} catch (Exception $e) {
			DB::rollBack();
			throw $e;
		}

  }
}
