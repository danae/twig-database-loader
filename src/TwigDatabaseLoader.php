<?php
namespace TwigDatabaseLoader;

use Database\Database;
use Twig_Error_Loader;
use Twig_LoaderInterface;
use Twig_Source;

class TwigDatabaseLoader implements Twig_LoaderInterface
{
  // Variables
  private $database;
  private $table;

  // Constructor
  public function __construct(Database $database, string $table = 'templates')
  {
    $this->database = $database;
    $this->table = $table;
  }

  // Create the table
  public function createTable()
  {
    $this->database->exec("CREATE TABLE {$this->table} (name VARCHAR(255) PRIMARY KEY, source VARCHAR(65535), last_modified INT)");
  }

  // Get a template from the database
  public function get(string $name)
  {
    return $this->database->selectOne($this->table, ['name' => $name]);
  }

  // Get a template from the database or throw an exception is nonexistent
  public function fetch(string $name)
  {
    if (($template = $this->getTemplate($name)) === null)
      throw new Twig_Error_Loader($name);
    return $template;
  }

  // Return if a template with this name exists
  public function exists($name): bool
  {
    return $this->get($name) !== null;
  }

  // Set a template in the database
  public function set(array $template)
  {
    if ($this->get($template['name']) === null)
      $this->database->insert($this->table, $template);
    else
      $this->database->update($this->table, $template, ['name' => $template['name']]);
  }

  // Return the cache key for a template
  public function getCacheKey($name): string
  {
    return $name;
  }

  // Return the source context for a template
  public function getSourceContext($name): Twig_Source
  {
    $template = $this->fetch($name);
    return new Twig_Source($template['source'], $name);
  }

  // Return if a template is still fresh
  public function isFresh($name, $time): bool
  {
    $template = $this->fetch($name);
    return $template['last_modified'] ? int($template['last_modified']) <= $time : false;
  }
}
