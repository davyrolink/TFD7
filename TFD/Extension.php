<?php
/*
* This file is part of Twig For Drupal 7.
*
* @see http://tfd7.rocks for more information
*
* @author René Bakx
* @description register the drupal specific tags and filters within a proper
* declared twig extension
*/

class TFD_Extension extends Twig_Extension {

  public function getGlobals() {
    return array(
      'base_path' => base_path(),
    );
  }

  public function getNodeVisitors() {
    return array(new TFD_NodeVisitor());
  }

  /**
   * Defines the 'missing' !, || and && operators.
   * @return array
   */
  public function getOperators() {
    return array(
      array(
        '!' => array(
          'precedence' => 50,
          'class' => 'Twig_Node_Expression_Unary_Not'
        ),
      ),
      array(
        '||' => array(
          'precedence' => 10,
          'class' => 'Twig_Node_Expression_Binary_Or',
          'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
        ),
        '&&' => array(
          'precedence' => 15,
          'class' => 'Twig_Node_Expression_Binary_And',
          'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
        ),
      ),
    );
  }

  /* registers the drupal specific tags */
  public function getTokenParsers() {
    $parsers = array();
    $parsers[] = new TFD_TokenParser_With();
    $parsers[] = new TFD_TokenParser_Switch();
    return $parsers;
  }

  /**
   * registers the drupal specific filters
   * @implements hook_twig_filter
   * please note that we use the key for convience, it allows developers of
   * modules to test if their wanted filter is already registered.
   *
   * The key is never used
   */
  public function getFilters() {
    $filters = array();


    // Drupal Core functions or PHP functions
    $filters['size'] = new Twig_SimpleFilter('size', 'format_size');
    $filters['plural'] = new Twig_SimpleFilter('plural', 'format_plural');
    $filters['t'] = new Twig_SimpleFilter('t', 't');
    $filters['attributes'] = new Twig_SimpleFilter('attributes', 'drupal_attributes');
    $filters['check_plain'] = new Twig_SimpleFilter('check_plain', 'check_plain');
    $filters['ucfirst'] = new Twig_SimpleFilter('ucfirst', 'ucfirst');

    // TFD Filters
    $filters['url'] = new Twig_SimpleFilter('url', [$this, 'url']);
    $filters['strreplace'] = new Twig_SimpleFilter('strreplace', [$this,'str_replace']);
    $filters['defaults'] = new Twig_SimpleFilter('defaults', [$this,'defaults_filter']);
    $filters['wrap'] = new Twig_SimpleFilter('wrap', [$this, 'wrap_text']);
    $filters['interval'] = new Twig_SimpleFilter('interval', [$this,'interval']);
    $filters['format_date'] = new Twig_SimpleFilter('format_date', [$this,'format_date']);
    $filters['truncate'] = new Twig_SimpleFilter('truncate', [$this,'truncate_text']);
    $filters['striphashes'] = new Twig_SimpleFilter('striphashes', [$this,'striphashes']);
    $filters['without'] = new Twig_SimpleFilter('without', [$this, 'without']);
    $filters = array_merge($filters, module_invoke_all('twig_filter', $filters, $this));
    return $filters;
  }


  /**
   * registers the drupal specific functions
   *
   * @implements hook_twig_function
   * please note that we use the key for convience, it allows developers of
   * modules to test if their wanted filter is already registered.
   *
   * The key is never used!
   */
  public function getFunctions() {
    $functions = array();
    // Drupal Core functions or PHP functions
    $functions['theme_get_setting'] = new Twig_SimpleFunction('theme_get_setting', 'theme_get_setting');
    $functions['module_exists'] = new Twig_SimpleFunction('module_exists', 'module_exists');
    $functions['classname'] = new Twig_SimpleFunction('classname', 'get_class');
    $functions['variable_get'] = new Twig_SimpleFunction('variable_get', 'variable_get');
    $functions['array_search'] = new Twig_SimpleFunction('array_search', 'array_search');

    $functions['current_path'] = new Twig_SimpleFunction('current_path', 'current_path');

    // TFD Functions
    $functions['dump'] = new Twig_SimpleFunction('dump',  [$this,'dump']);
    $functions['render'] = new Twig_SimpleFunction('render',  [$this,'render']);
    $functions['hide'] = new Twig_SimpleFunction('hide',  [$this,'hide']);
    $functions['url'] = new Twig_SimpleFunction('url',  [$this,'url']);
    $functions['machine_name'] = new Twig_SimpleFunction('machine_name',  [$this,'machine_name']);
    $functions['viewblock'] = new Twig_SimpleFunction('viewblock',  [$this,'view_block']);
    $functions['image_url'] = new Twig_SimpleFunction('image_url',  [$this,'image_url']);
    $functions['file_url'] = new Twig_SimpleFunction('file_url',  [$this,'file_url']);
    $functions['image_size'] = new Twig_SimpleFunction('image_size',  [$this,'image_size']);
    $functions['get_form_errors'] = new Twig_SimpleFunction('get_form_errors',  [$this,'form_get_errors']);
    $functions['children'] = new Twig_SimpleFunction('children',  [$this,'get_children']);
    $functions['theme_path'] = new Twig_SimpleFunction('theme_path',[$this,'theme_path']);


    $functions = array_merge($functions, module_invoke_all('twig_function', $functions, $this));
    return $functions;
  }


  /**
   * registers the drupal specific tests
   *
   * @implements hook_twig_test
   * please note that we use the key for convience, it allows developers of
   * modules to test if their wanted filter is already registered.
   *
   * The key is never used!
   */
  public function getTests() {
    $tests = array();
    $tests['property'] = new Twig_SimpleTest('property', [
      $this,
      'test_property'
    ]);

    $tests['array'] = new Twig_SimpleTest('array', function ($value) {
      return is_array($value);
    });

    $tests['scalar'] = new Twig_SimpleTest('scalar', function ($value) {
      return is_scalar($value);
    });

    $tests['number'] = new Twig_SimpleTest('number', function ($value) {
      return is_numeric($value);
    });

    $tests['string'] = new Twig_SimpleTest('string', function ($value) {
      return is_string($value);
    });

    $tests = array_merge($tests, module_invoke_all('twig_test', $tests, $this));
    return $tests;
  }

  public function getName() {
    return 'twig_for_drupal';
  }


  /* ------------------------------------------------------------------------------------------------
  /* the above declared filter implementations
   ------------------------------------------------------------------------------------------------*/

  /**
   * Wrapper around the default drupal render function.
   * This function is a bit smarter, as twig passes a NULL if the item you want to
   * be rendered is not found in the $context (aka template variables!)
   *
   * @param $var array item from the render array of doom item you wish to be rendered.
   * @return string
   */
  function render($var) {
    if (isset($var) && !is_null($var)) {
      if (is_scalar($var)) {
        return $var;
      }
      elseif (is_array($var)) {
        return render($var);
      }
      return $var;
    }
  }


  /**
   * @deprecated
   * @param $var
   *
   * This function is useless, because of the way Twig works.
   * Please consider using the |without filter instead (like Drupal 8)
   */
  function hide($var) {
    if (!is_null($var) && !is_scalar($var) && count($var) > 0) {
      hide($var);
    }
  }

  /**
   * Additional Twig filter provided in Drupal 8 to render array ommitting
   * certain elements in the array
   *
   * example {{ content|without(['links','language']) }}
   *
   * @param $input array
   * @param $keys_to_remove array
   * @return array
   */

  function without($input, $keys_to_remove) {
    if ($input instanceof ArrayAccess) {
      $filtered = clone $input;
    }
    else {
      $filtered = $input;
    }
    foreach ($keys_to_remove as $key) {
      if (isset($filtered[$key])) {
        unset($filtered[$key]);
      }
    }
    return $filtered;
  }

  /**
   * Returns only the keys of an array that do not start with #
   * AKA, clean the drupal render_array() a little
   *
   * @param $element
   */
  function striphashes($array) {

    if (is_array($array)) {
      $output = array();
      foreach ($array as $key => $value) {
        if ($key[0] !== '#') {
          $output[$key] = $value;
        }
      }
      return $output;
    }
    return $array;
  }

  /**
   * Twig filter for str_replace, switches needle and arguments to provide sensible
   * filter arguments order
   *
   * {{ haystack|replace("needle", "replacement") }}
   *
   * @param  $haystack
   * @param  $needle
   * @param  $repl
   * @return mixed
   */
  function str_replace($haystack, $needle, $repl) {
    $haystack = $this->render($haystack);
    return str_ireplace($needle, $repl, $haystack);
  }


  function defaults_filter($value, $defaults = NULL) {
    $args = func_get_args();
    $args = array_filter($args);
    if (count($args)) {
      return array_shift($args);
    }
    else {
      return NULL;
    }
  }

  /**
   * Wraps the given text with a HTML tag
   * @param $value
   * @param $tag
   * @return string
   */
  function wrap_text($value, $tag) {
    $value = $this->render($value);
    if (!empty($value)) {
      return sprintf('<%s>%s</%s>', $tag, trim($value), $tag);
    }
  }

  function form_get_errors() {
    $errors = form_get_errors();
    if (!empty($errors)) {
      $newErrors = array();
      foreach ($errors as $key => $error) {
        $newKey = str_replace('submitted][', 'submitted[', $key);
        if ($newKey !== $key) {
          $newKey = $newKey . ']';
        }
        $newErrors[$newKey] = $error;
      }
      $errors = $newErrors;
    }
    return $errors;
  }

  function dump($var = NULL, $function = NULL) {

    static $functions = array(
      'dpr' => NULL,
      'dpm' => NULL,
      'kpr' => NULL,
      'print_r' => 'p',
      'var_dump' => 'v'
    );
    if (empty($function)) {
      $functionCalls = array_keys($functions);
      if (!module_exists('devel')) {
        $function = end($functionCalls);
      }
      else {
        $function = reset($functionCalls);
      }
    }
    if (array_key_exists($function, $functions) && is_callable($function)) {
      call_user_func($function, $var);
    }
    else {
      $found = FALSE;
      foreach ($functions as $name => $alias) {
        if (in_array($function, (array) $alias)) {
          $found = TRUE;
          call_user_func($name, $var);
          break;
        }
      }
      if (!$found) {
        throw new InvalidArgumentException("Invalid mode '$function' for TFD_dump()");
      }
    }
  }

  function file_url($file_uri) {
    if (is_scalar($file_uri)) {
      if (file_valid_uri($file_uri)) {
        return file_create_url($file_uri);
      }
      elseif (substr_count($file_uri, 'file/')) {
        return url(drupal_lookup_path('alias', $file_uri));
      }
    }
    return $file_uri;
  }

  function image_url($filepath, $preset = NULL) {
    if (is_array($filepath)) {
      $filepath = $filepath['filepath'];
    }
    if ($preset) {
      return image_style_url($preset, $filepath);
    }
    else {
      return $filepath;
    }
  }


  function image_size($filepath, $preset, $asHtml = TRUE) {
    if (is_array($filepath)) {
      $filepath = $filepath['filepath'];
    }
    $info = image_get_info(image_style_url($preset, $filepath));
    $attr = array(
      'width' => (string) $info['width'],
      'height' => (string) $info['height']
    );
    if ($asHtml) {
      return drupal_attributes($attr);
    }
    else {
      return $attr;
    }
  }


  function url($item, $options = array()) {
    if (is_numeric($item)) {
      $ret = url('node/' . $item, (array) $options);
    }
    else {
      $parsed = drupal_parse_url($item);
      $options += $parsed;
      $ret = url($parsed['path'], (array) $options);
    }
    return check_url($ret);
  }

  function test_property($element, $propertyName, $value = TRUE) {
    return array_key_exists("#{$propertyName}", $element) && $element["#{$propertyName}"] == $value;
  }



  /**
   *
   * @param $value
   * @param int $length
   * @param bool $elipse
   * @param bool $words
   * @return string
   */
  function truncate_text($value, $length = 300, $elipse = TRUE, $words = TRUE) {
    $value = $this->render($value);
    if (drupal_strlen($value) > $length) {
      $value = drupal_substr($value, 0, $length);
      if ($words) {
        $regex = "(.*)\b.+";
        if (function_exists('mb_ereg')) {
          mb_regex_encoding('UTF-8');
          $found = mb_ereg($regex, $value, $matches);
        }
        else {
          $found = preg_match("/$regex/us", $value, $matches);
        }
        if ($found) {
          $value = $matches[1];
        }
      }
      // Remove scraps of HTML entities from the end of a strings
      $value = rtrim(preg_replace('/(?:<(?!.+>)|&(?!.+;)).*$/us', '', $value));
      if ($elipse) {
        $value .= ' ' . t('...');
      }
    }
    return $value;
  }

  /**
   * Convience wrapper around the drupal format_interval method
   * *
   * Instead of receiving the calculated difference in seconds
   * you can just give it a date and it calculates the difference
   * for you.
   *
   * @see format_interval();
   *
   * @param $date  String containing the date, or unix timestamp
   * @param int $granularity
   */
  function interval($date, $granularity = 2, $display_ago = TRUE, $langcode = NULL) {


    $now = time();
    if (preg_match('/[^\d]/', $date)) {
      $then = strtotime($date);
    }
    else {
      $then = $date;
    }
    $interval = $now - $then;
    if ($interval > 0) {
      return $display_ago ? t('!time ago', array('!time' => format_interval($interval, $granularity, $langcode))) :
        t('!time', array('!time' => format_interval($interval, $granularity, $langcode)));
    }
    else {
      return format_interval(abs($interval), $granularity, $langcode);
    }
  }

  function format_date($date, $type, $langcode = NULL) {
    if (preg_match('/[^\d]/', $date)) {
      $date = strtotime($date);
    }

    return format_date($date, $type, '', NULL, $langcode);
  }

  function machine_name($string) {
    return preg_replace(array('/[^a-z0-9]/', '/_+/'), '_', strtolower($string));
  }

  /**
   * Get a block from the DB
   *
   * @param string $delta
   * @param null $module Optional name of the module this block belongs to.
   * @param boolean $render return the raw data instead of the rendered content.
   * @return bool|string
   */
  function view_block($delta, $module = NULL, $render = TRUE) {
    $output = FALSE;
    if (is_null($module)) {
      global $theme;
      if (FALSE !== $block = db_query('SELECT * FROM {block} WHERE theme= :theme AND delta = :delta', array(
          ':theme' => $theme,
          ':delta' => $delta
        ))->fetchObject()
      ) {
        $module = $block->module;
      }
    }
    else {
      $block = db_query('SELECT * FROM {block} WHERE module = :module AND delta = :delta', array(
        ':module' => $module,
        ':delta' => $delta
      ))->fetchObject();
    }
    if ($block) {
      $block->region = 'tfd_block';
      $block->status = 1;
      $blockdata = _block_render_blocks([$block->delta => $block]);
      $build = _block_get_renderable_array($blockdata);
      $output = ($render) ? render($build) : $build;
    }
    return $output;
  }

  /**
   * Return the children of an element
   *
   * @param $render_array
   * @return array
   */
  function get_children($render_array) {
    if (!empty($render_array) && is_array($render_array)) {
      $children = array();
      foreach (element_children($render_array) as $key) {
        $children[] = $render_array[$key];
      }
      return $children;
    }
    return array();
  }




  /**
   * Returns the path the a theme.
   * usage : theme_path({'absolute':true,'theme':'solid'})
   * both parameters are optional.
   * @param $args
   * @return string
   */
  function theme_path($args) {
    global $base_url, $theme_path;
    if (isset($args['theme'])) {
      $path = drupal_get_path('theme', $args['theme']);
    }
    else {
      $path = $theme_path;
    }

    if (isset($args['absolute']) && ($args['absolute'] == TRUE || strtolower($args['absolute']) == 'true')) {
      $path = sprintf('%s/%s/', $base_url, $path);
    }
    else {
      $path = $path . '/';
    }
    return $path;
  }
}