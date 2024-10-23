<?php

include 'index.php';

class Test
{
  private static function are_episodes_equal($a, $b): bool
  {
    foreach ($a as $key => $value)
    {
      if (
        !isset($b[$key])
        ||
        $value != $b[$key]
      )
      {
        return false;
      }
    }
    return true;
  }

  public static function sort_by_id()
  {
    $episode_a = new Episode(
      json_decode(
        file_get_contents("test_data/sort/in.json"), 
        true
      )
    );

    $episode_b = new Episode(
      json_decode(
        file_get_contents("test_data/sort/out.json"), 
        true
      )
    );

    $episode_a->sort_by_id(['c', 'a', 'e']);

    return Test::are_episodes_equal($episode_a->parts, $episode_b->parts);
  }


  public static function insert()
  {
    $episode_a = new Episode(
      json_decode(
        file_get_contents("test_data/insert/in.json"), 
        true
      ),
      'f'
    );

    $episode_b = new Episode(
      json_decode(
        file_get_contents("test_data/insert/out.json"), 
        true
      )
    );

    $episode_a->insert(['position' => 2]);

    return Test::are_episodes_equal($episode_a->parts, $episode_b->parts);
  }

  public static function delete_by_id()
  {
    $episode_a = new Episode(
      json_decode(
        file_get_contents("test_data/delete/in.json"), 
        true
      )
    );

    $episode_b = new Episode(
      json_decode(
        file_get_contents("test_data/delete/out.json"), 
        true
      )
    );

    $episode_a->delete_by_id('c');

    return Test::are_episodes_equal($episode_a->parts, $episode_b->parts);
  }

  public static function delete_by_position()
  {
    $episode_a = new Episode(
      json_decode(
        file_get_contents("test_data/delete/in.json"), 
        true
      )
    );

    $episode_b = new Episode(
      json_decode(
        file_get_contents("test_data/delete/out.json"), 
        true
      )
    );

    $episode_a->delete_by_position(2);

    return Test::are_episodes_equal($episode_a->parts, $episode_b->parts);
  }

  public static function run()
  {
    if(self::sort_by_id())
    {
      echo "sort_by_id test succeeded\n";
    }
    if(self::insert())
    {
      echo "insert test succeeded\n";
    }
    if(self::delete_by_id())
    {
      echo "delete_by_id test succeeded\n";
    }
    if(self::delete_by_position())
    {
      echo "delete_by_position test succeeded\n";
    }
  }
}

Test::run();
