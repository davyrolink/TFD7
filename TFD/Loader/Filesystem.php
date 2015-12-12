<?php
/*
 * This file is part of Twig For Drupal 7.
 *
 * @author René Bakx
 * @see http://tfd7.rocks for more information
 *
 * @description Loads template from the filesystem.
 */
class TFD_Loader_Filesystem extends Twig_Loader_Filesystem {
  protected $resolverCache;

  public function __construct() {
    parent::__construct(array());
    $this->resolverCache = array();
  }


  public function getSource($filename) {
    return file_get_contents($this->getCacheKey($filename));
  }


  public function findTemplate($name) {
    $this->validateName($name);

    try {
      return parent::findTemplate($name);
    } catch (Twig_Error_Loader $e) {
      $previous = $e;

      // for BC
      if (!isset($this->resolverCache[$name])) {
        $found = FALSE;
        if (is_readable($name)) {
          $this->resolverCache[$name] = $name;
          $found = TRUE;
        }
        else {
          $paths = twig_get_discovered_templates();

          if (array_key_exists($name, $paths)) {
            $completeName = $paths[$name];
            if (is_readable($completeName)) {
              $this->resolverCache[$name] = $completeName;
              $found = TRUE;
            }
          }
        }
        if (!$found) {
          throw new Twig_Error_Loader(sprintf('Could not find a cache key for template "%s"', $name), -1, null, $previous);
        }
      }
      return $this->resolverCache[$name];
    }
  }

}

