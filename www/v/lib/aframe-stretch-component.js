/*
 * Stretch a rounded-square to fit its children
 * May be used for text entities parents
 */

AFRAME.registerComponent("stretch", {
  schema: {
    direction: {default: ['vertical'], type: 'array'},
    margin: {default: 0, type: 'number'},
    marginTop: {default: 0, type: 'number'},
    marginBottom: {default: 0, type: 'number'},
    spacing: {default: 0, type: 'number'},
    disabled: {default: 'false', type: 'string'},
  },

  init: function () {
    const direction = this.data.direction;
    const marginTop = this.data.marginTop != 0 ? this.data.marginTop : this.data.margin;
    const marginBottom = this.data.marginBottom != 0 ? this.data.marginBottom : this.data.margin;
    const spacing = this.data.spacing;

    let children = this.el.children;
    let height = 0;
    let length = 0;
    for (let i=0; i<children.length; i++){
      if ( children[i].getAttribute('float') ){
        continue;
      }
      if ( children[i].getAttribute('stretch') && children[i].getAttribute('stretch').disabled == "true" ){
        continue;
      }
      height = height + children[i].getAttribute('geometry').height;
      length++;
    }
    height += marginTop + marginBottom + (length - 1) * spacing;
    let attributes = this.el.getAttribute("poi-rounded-square");
    if ( this.el.getAttribute('poi-rounded-square') ){
      this.el.setAttribute("poi-rounded-square", {height: height});
    }
    this.el.setAttribute("height", height);
  }
});
