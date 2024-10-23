<?php

/*
 * This script implements the three basic operations for the `parts` data structure
 * handled by a web server:
 *
 * - insert
 * - delete
 * - sort
 *
 * Below is a figure that shows the data the script expects to handle:
 *
 * Episodes:         
 *
 * | id |
 * | -- |
 * | 1  |
 * | 2  |
 * | 3  |
 * | 4  |
 *
 * Parts:
 *
 * | id | episode_id | position |
 * | -- | ---------- | -------- |
 * | a  | 1          | 0        |
 * | b  | 1          | 1        |
 * | c  | 1          | 2        |
 * | d  | 2          | 0        |
 *
 * Associative array literals were defined to simulate an in-memory database.
 *
 * NOTE: All methods were defined without considering the implementation of a web server. 
 *
 */

/*

TODO explain expected structure

$Episodes = [
  [
    'a' => ['position' => 0, 'data' => 'gur'],
    'b' => ['position' => 1, 'data' => 'arkg'],
    'c' => ['position' => 2, 'data' => 'rcvfbqr'],
    'd' => ['position' => 3, 'data' => 'qeqer']
  ],
  [
    'e' => ['position' => 1, 'data' => 'unccl'],
    'f' => ['position' => 0, 'data' => 'unyybjrra'],
  ]
];

*/


class Episode
{

  public array $parts;
  private ?string $debug_id;

  public function __construct(array $parts, string $debug_id = null)
  {
    $this->parts = $parts;
    $this->debug_id = $debug_id;
  }

  /*
   * inserts a part given an episode id and a map with its data containing position
   *
   * method: POST
   *
   * NOTE: when multiple paths use the same http method, the operation should
   * be included in the uri
   *
   * valid expected path:
   *
   * /episode_manager/insert?episode=<episode_id>
   *
   * expected payload structure (application/json):
   *
   * {
   *   "position": <position>,
   *   ...data
   * }
   *
   */

  public function insert(array $part_data): void
  {
      
    /* 
     * ideally, the identifier would be stored as a raw binary hash to reduce
     * colissions and text encode when requested. for simplicity, the
     * generation is implemented as a random character while checking for
     * collisions
     *
     * ids are characters starting from 'a'. any nested registers within a part
     * register would be stored in a different data structure. 
     * 
     * the count of parts needs to be done globally across all parts within every
     * episode since each id must be unique. hence the recursive count which is
     * then subtracted by the count of episodes
     */

    do {
      $id = $this->debug_id ?? chr(random_int(0, 25) + 97 /* 'a' in ascii */);
    } while (isset($this->parts[$id]));

    if (isset($part_data['position']))
    {
      foreach ($this->parts as &$part) 
        if ($part['position'] >= $part_data['position'])
        {
          $part['position']++;
        }
    }
    else
    {
      $part_data['position'] = count($this->parts);
    }

    $this->parts[$id] = $part_data;
  }


  /* 
   * Deleting a part can be done by providing the id or the position.
   * specifying as a query parameter in the uri of the request is expected.
   * 
   * method: DELETE
   *
   * valid expected path:
   *
   * /episode_manager?episode=<episode_id>&part=<part_id>
   *
   */

  public function delete_by_id(string $part_id): void
  {
    $deleted_position = $this->parts[$part_id]['position'];

    unset($this->parts[$part_id]);

    foreach ($this->parts as &$part)
    {
      if ($part['position'] > $deleted_position)
      {
        $part['position']--;
      }
    }
  }


  /* 
   * method: DELETE
   *
   * valid expected path:
   *
   * /episode_manager?episode=<episode_id>&position=<position>
   *
   *
   */

  public function delete_by_position(int $position): void
  {
    foreach ($this->parts as $key => &$part)
    {
      if ($part['position'] > $position)
      {
        $part['position']--;
      } else if ($part['position'] == $position)
      {
        unset($this->parts[$key]);
      }
    }
  }

  /*
   *
   * method: POST
   *
   * NOTE: when multiple paths use the same http method, the operation should
   * be included in the uri
   *
   * valid expected path:
   *
   * /episode_manager/sort?episode=<episode_id>
   *
   * expected payload structure (application/json):
   *
   * {
   *   ids: [
   *     <id1>,
   *     <id2>,
   *     <id3>,
   *     ...other_ids
   *   ]
   * }
   *
   */
  public function sort_by_id(array $sorted_ids): void
  {

    $modified_parts_set = [];

    $i = 0; 
    $j = 0;
    for (; $i < count($sorted_ids); $j++, $i++)
    {
      $this->parts[$sorted_ids[$i]]['position'] = $j;
      
      // if part was already modified, skip
      if (isset($modified_parts_set[$sorted_ids[$i]])) {
        $j--;
      }
      $modified_parts_set[$sorted_ids[$i]] = true;
    }

    $remaining_parts = array_diff_key($this->parts, $modified_parts_set);

    uasort($remaining_parts, function ($a, $b) {
      return $a['position'] <=> $b['position'];
    });

    foreach ($remaining_parts as $key => $value)
    {
      $this->parts[$key]['position'] = $j++;
    }
  }

  /*
   * 
   *
   * method: POST
   *
   * valid expected path:
   *
   * NOTE: when multiple paths use the same http method, the operation should
   * be included in the uri
   *
   * /episode_manager/sort?episode=<episode_id>
   *
   * expected payload structure (application/json):
   *
   * {
   *   positions: [
   *     <id1>,
   *     <id2>,
   *     <id3>,
   *     ...other_ids
   *   ]
   * }
   *
   */

  public function sort_by_position(array $sorted_positions): void
  {

    $sorted_ids = [];

    $part_position_id_map = [];

    foreach ($this->parts as $key => $value)
    {
      $part_position_id_map[$value['position']] = $key;
    }

    foreach ($sorted_positions as $part_position)
    {
      $sorted_ids[] = $part_position_id_map[$part_position];
    }

    // calling function to reuse sorting code and centralize logic in one place
    $this->sort_by_id($sorted_ids);
  }
}

