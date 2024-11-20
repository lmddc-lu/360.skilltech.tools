AFRAME.registerComponent('poi-rounded-square', {
  schema: {
    width: {default: 0, min: 0, type: 'number'},
    height: {default: 0, min: 0, type: 'number'},
    minWidth: {default: 0, min: 0, type: 'number'},
    minHeight: {default: 0, min: 0, type: 'number'},
    ratio: {default: 1, min: 0, type: 'number'},
    image: {default: "", type: 'string'},
    radius: {default: 0.1, min: 0, type: 'number'},
    corner: {default: ['tr', 'tl', 'br', 'bl'], type: 'array'},
    // Accepted values: top, middle, bottom, left, center, right
    anchor: {default: ['center', 'middle'], type: 'array'}
  },

  update: function (oldData) {
    var width    = this.data.width;
    var height   = this.data.height;
    var ratio    = this.data.ratio;
    const corner = this.data.corner;
    const anchor = this.data.anchor;
    const image  = this.data.image;

    if (width){
      this.el.setAttribute("width", width);
    }
    if (height){
      this.el.setAttribute("height", height);
    }

    if (this.mesh){
      this.el.object3D.remove(this.mesh);
    }
    this.mesh = this.getMesh(width,height,ratio,corner,anchor,image);
    this.el.object3D.add(this.mesh);
  },
  getMesh: function(width,height,ratio,corner,anchor,image){
    if (ratio < 1){
      if (width != 0) {
        height = width / ratio;
      } else if (height != 0){
        width = height * ratio;
      } else {
        console.error("When ratio attribute is set, you must set width or height attributes");
        width = 1;
        height = 1;
      }
    }

    const radius = this.data.radius

    var texture;
    if (image) {
      texture = new THREE.TextureLoader().load(image);
      texture.colorSpace = THREE.SRGBColorSpace;
      texture.offset.set(0.5, 0.5);
      texture.repeat.set(1/width, 1/height);
    }

    // Setting the anchor
    let verticalShift = 0;
    let horizontalShift = 0;
    if (anchor.includes('left')){
      horizontalShift = width/2;
    }
    else if (anchor.includes('right')){
      horizontalShift = -width/2;
    }
    if (anchor.includes('top')){
      verticalShift = -height/2;
    }
    else if (anchor.includes('bottom')){
      verticalShift = height/2;
    }

    // We specify manually the points of the shape to have rectangle whit border-top-radius
    let points = [];

    // top left
    if (corner.includes('tl')){
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.000)*radius, verticalShift+height/2-(1-1.000)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.195)*radius, verticalShift+height/2-(1-0.980)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.383)*radius, verticalShift+height/2-(1-0.923)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.555)*radius, verticalShift+height/2-(1-0.831)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.707)*radius, verticalShift+height/2-(1-0.707)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.831)*radius, verticalShift+height/2-(1-0.555)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.923)*radius, verticalShift+height/2-(1-0.383)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.980)*radius, verticalShift+height/2-(1-0.195)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-1.000)*radius, verticalShift+height/2-(1-0.000)*radius));
    } else {
        points.push(new THREE.Vector2(horizontalShift-width/2, verticalShift+height/2));
    }

    // bottom left
    if (corner.includes('bl')){
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-1.000)*radius, verticalShift-height/2+(1-0.000)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.980)*radius, verticalShift-height/2+(1-0.195)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.923)*radius, verticalShift-height/2+(1-0.383)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.831)*radius, verticalShift-height/2+(1-0.555)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.707)*radius, verticalShift-height/2+(1-0.707)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.555)*radius, verticalShift-height/2+(1-0.831)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.383)*radius, verticalShift-height/2+(1-0.923)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.195)*radius, verticalShift-height/2+(1-0.980)*radius));
        points.push(new THREE.Vector2(horizontalShift-width/2+(1-0.000)*radius, verticalShift-height/2+(1-1.000)*radius));
    } else {
        points.push(new THREE.Vector2(horizontalShift-width/2, verticalShift-height/2));
    }

    // bottom right
    if (corner.includes('br')){
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.000)*radius, verticalShift-height/2+(1-1.000)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.195)*radius, verticalShift-height/2+(1-0.980)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.383)*radius, verticalShift-height/2+(1-0.923)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.555)*radius, verticalShift-height/2+(1-0.831)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.707)*radius, verticalShift-height/2+(1-0.707)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.831)*radius, verticalShift-height/2+(1-0.555)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.923)*radius, verticalShift-height/2+(1-0.383)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.980)*radius, verticalShift-height/2+(1-0.195)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-1.000)*radius, verticalShift-height/2+(1-0.000)*radius));
    } else {
        points.push(new THREE.Vector2(horizontalShift+width/2, verticalShift-height/2));
    }

    // top right
    if (corner.includes('tr')){
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-1.000)*radius, verticalShift+height/2-(1-0.000)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.980)*radius, verticalShift+height/2-(1-0.195)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.923)*radius, verticalShift+height/2-(1-0.383)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.831)*radius, verticalShift+height/2-(1-0.555)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.707)*radius, verticalShift+height/2-(1-0.707)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.555)*radius, verticalShift+height/2-(1-0.831)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.383)*radius, verticalShift+height/2-(1-0.923)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.195)*radius, verticalShift+height/2-(1-0.980)*radius));
        points.push(new THREE.Vector2(horizontalShift+width/2-(1-0.000)*radius, verticalShift+height/2-(1-1.000)*radius));
    } else {
        points.push(new THREE.Vector2(horizontalShift+width/2, verticalShift+height/2));
    }

    let shape = new THREE.Shape(points);
    let geometry = new THREE.ShapeGeometry(shape);
    var material;
    if (texture) {
      material = new THREE.MeshBasicMaterial({
        map: texture,
        reflectivity: 0,
        color: 0xffffff,
        refractionRatio: 0,
      });
    } else {
      material = new THREE.MeshBasicMaterial({
        color: 0xffffff
      });
    }
    var mesh = new THREE.Mesh(geometry, material);
    return mesh;
  }
});
