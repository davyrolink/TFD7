<?php

/*
* This file is part of Twig For Drupal 7.
*
* @see http://tfd7.rocks for more information
*
* @author René Bakx
*
* @description : TFD_NodeVisitor checks of the auto_render is set in the
* twig environment, and if so it converts nodes of the Twig_Node_Print into
* TFD_Node_Render types.
*
* At the same time it checks if a user explicitily called {{ hide(some.var) }}
* and then cast the Twig_Node_Expression_Function into a TFD_Node_Hide class
* the properly calls the hide function with variables by reference
*
* This visitor is only called during compilation time, and has no slowing down
* sideeffects during normal runtime
*
* This done to have a more fluent interface for the developer instead of having
* to call {% hide(this) %} and {{ render(that) }}
*
*
* In a drupal 7 template a single variable can contain an array, but Twig only
* triggers the Twig_Template::getAttribute only for variables that are an object
* or array.
*
* Normally you would call something like {{tabs|render}} but to make it easier
* for those who work on the frontend {{tabs}} makes more sense.
*
*/

class TFD_NodeVisitor implements Twig_NodeVisitorInterface {
  /**
   * Called before child nodes are visited.
   *
   * @param Twig_NodeInterface $node The node to visit
   * @param Twig_Environment $env The Twig environment instance
   *
   * @return Twig_NodeInterface The modified node
   */
  function enterNode(Twig_NodeInterface $node, Twig_Environment $env) {
    return $node;
  }

  function leaveNode(Twig_NodeInterface $node, Twig_Environment $env) {
    if ($node instanceof Twig_Node_Print) {
      // make sure that every {{ }} printed object is handled as a TFD_Node_Render node (aka autorender)
      if (!$node->getNode('expr') instanceof Twig_Node_Expression_Function) {
        if ($env->isAutoRender()) {
          $targetNode = $node->getNode('expr');
          if ($targetNode instanceof Twig_Node_Expression_Name) {
            $targetNode->setAttribute('always_defined', TRUE);
          }
          if (!$targetNode instanceof Twig_Node_Expression_MethodCall) {
            $node = new TFD_Node_Render($targetNode, $node->getLine(), $node->getNodeTag());
          }
        }
      }
    }
    return $node;
  }
  /**
   * @return integer The priority level
   */
  function getPriority() {
    return 10;
  }
}
