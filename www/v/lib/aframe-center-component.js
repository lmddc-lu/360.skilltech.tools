/*
 * Center the children of an a-entity vertically
 * May be used for text entities
 */

AFRAME.registerComponent("center", {
  schema: {
    direction: {default: ['vertical'], type: 'array'},
    spacing: {default: 0, type: 'number'},
    margin: {default: 0, type: 'number'},
    marginTop: {default: 0, type: 'number'},
    marginBottom: {default: 0, type: 'number'},
    anchor: {default: ['center'], type: 'array'},
    disabled: {default: 'false', type: 'string'},
  },

  init: function () {
    const direction = this.data.direction;
    const spacing = this.data.spacing;
    const marginTop = this.data.marginTop != 0 ? this.data.marginTop : this.data.margin;
    const marginBottom = this.data.marginBottom != 0 ? this.data.marginBottom : this.data.margin;
    const anchor = this.data.anchor;
    const disabled = this.data.disabled;

    let children = this.el.children;
    let height = 0;
    let length = 0;
    let skip = [];
    // First loop to compute the adition of all height
    for (let i=0; i<children.length; i++){
      if ( !children[i].getAttribute('center') || children[i].getAttribute('center').disabled !== "true" ){
        if (children[i].getAttribute('geometry')){
          if (children[i].getAttribute('geometry').height != 0){
            height += children[i].getAttribute('geometry').height;
            length++;
          } else {
            // The height is 0, we will skip this element in the placement loop
            skip.push(i);
          }
        } else if (children[i].getAttribute('height')){
          if (Number(children[i].getAttribute('height')) != 0){
            height += Number(children[i].getAttribute('height'));
            length++;
          } else {
          }
        }
      }
    }
    height += (length - 1) * spacing + marginTop + marginBottom;
    let verticalShift = 0;
    if (anchor.includes('middle')){
      verticalShift = this.el.getAttribute('height')/2;
    }
    else if (anchor.includes('bottom')){
      verticalShift = height;
    }

    let pos;
    for (let i=0; i<children.length; i++){
      if (skip.includes(i)){
        // The element's height is 0, we will skip its placement
        continue;
      }
      if (children[i].getAttribute('float')){
        if ( children[i].getAttribute('float').top && !Number.isNaN(children[i].getAttribute('float').top) ){
          children[i].setAttribute('position', {
            x: children[i].getAttribute('position').x,
            y: children[i].getAttribute('float').top + this.el.getAttribute("height")/2,
            z: children[i].getAttribute('position').z
          });
          continue;
        }
        if ( children[i].getAttribute('float').bottom && !Number.isNaN(children[i].getAttribute('float').bottom) ){
          children[i].setAttribute('position', {
            x: children[i].getAttribute('position').x,
            y: children[i].getAttribute('float').bottom - this.el.getAttribute("height")/2,
            z: children[i].getAttribute('position').z
          });
          continue;
        }
        if ( children[i].getAttribute('float').left && !Number.isNaN(children[i].getAttribute('float').left) ){
          children[i].setAttribute('position', {
            x: children[i].getAttribute('float').left - this.el.getAttribute("width")/2,
            y: children[i].getAttribute('position').y,
            z: children[i].getAttribute('position').z
          });
          continue;
        }
        if ( children[i].getAttribute('float').right && !Number.isNaN(children[i].getAttribute('float').right) ){
          children[i].setAttribute('position', {
            x: children[i].getAttribute('float').right + this.el.getAttribute("width")/2,
            y: children[i].getAttribute('position').y,
            z: children[i].getAttribute('position').z
          });
          continue;
        }
      }
      if ( children[i].getAttribute('center') && children[i].getAttribute('center').disabled == "true" ){
        continue;
      }
      if (pos == undefined){
        // we compute the initial position
        pos = -marginTop;
      } else {
        if (children[i-1].getAttribute('geometry')){
          pos = pos - children[i-1].getAttribute('geometry').height - spacing;
        } else if (children[i-1].getAttribute('height')){
          pos = pos - Number(children[i-1].getAttribute('height')) - spacing;
        }
      }
      if (children[i].getAttribute('geometry')){
        children[i].setAttribute('position', {
          x: children[i].getAttribute('position').x,
          y: pos - children[i].getAttribute('geometry').height / 2 + verticalShift,
          z: children[i].getAttribute('position').z
        });
      } else if (children[i].getAttribute('height')){
        children[i].setAttribute('position', {
          x: children[i].getAttribute('position').x,
          y: pos - Number(children[i].getAttribute('height')) / 2 + verticalShift,
          z: children[i].getAttribute('position').z
        });
      }
      children[i].setAttribute('baseline', "center");
    }
  }
});
