/*
 * Place an a-entity to an edge of its parent
 * To be used in conbination with the center component on the parent element
 */

AFRAME.registerComponent("float", {
  /*
   * This will be used to parse the float properties and make it accessible using eg. :
   *   element.getAttribute("float").top
   */
  schema: {
    top: {default: undefined, type: 'number'},
    bottom: {default: undefined, type: 'number'},
    left: {default: undefined, type: 'number'},
    right: {default: undefined, type: 'number'},
  }
});
