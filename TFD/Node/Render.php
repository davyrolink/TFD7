<?php
/*
 * This file is part of Twig For Drupal 7.
 *
 * @author René Bakx
 * @see http://tfd7.rocks for more information
 *
 * Class TFD_Node_Render
 *
 * Transports the variable to be passed to the render method into a variable
 * before calling the render method. Prevents strict warnings.
 */
class TFD_Node_Render extends Twig_Node_Print {

  public function compile(Twig_Compiler $compiler) {
    $compiler
      ->addDebugInfo($this)
      ->write("echo  \$this->getEnvironment()->getExtension('twig_for_drupal')->render(")
      ->subcompile($this->getNode('expr'))
      ->raw(");\n");
  }
}
 
