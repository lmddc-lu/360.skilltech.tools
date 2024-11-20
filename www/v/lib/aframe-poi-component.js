AFRAME.registerComponent("poi", {
  schema: {
    x: {type: 'number'},
    y: {type: 'number'},
    distance: {type: 'number', default: 48},
    width: {type: 'number', default: 5},
    height: {type: 'number', default: 5},
    icon: {type: 'string', default: 'did-you-know.png'},
    image: {type: 'string'},
    imageWidth: {type: 'int', default: 5},
    imageHeight: {type: 'int', default: 5},
    text: {type: 'string'},
    title: {type: 'string'},
    template: {type: 'number', default: 0},
    state: {type: 'string'},
    id: {type: 'number'},
  },

  init: function () {
    const scene    = this.el.sceneEl;
    const x        = this.data.x;
    const y        = this.data.y;
    const distance = this.data.distance;
    const width    = this.data.width;
    const height   = this.data.height;
    const icon     = this.data.icon;
    const image    = this.data.image;
    const imageWidth    = this.data.imageWidth;
    const imageHeight   = this.data.imageHeight;
    const text     = this.data.text;
    const title    = this.data.title;
    const template    = this.data.template;
    var state         = this.data.state;
    var id         = this.data.id;

    // The POI template file is hardcoded here
    let templateFile;
    switch (template) {
      case 1:
        templateFile = "./lib/aframe-poi-img-text-horizontal.template";
        break;
      case 2:
        templateFile = "./lib/aframe-poi-img-text-vertical.template";
        break;
      case 3:
        // text
        templateFile = "./lib/aframe-poi-text.template";
        break;
      case 4:
        // image
        templateFile = "./lib/aframe-poi-img.template";
        break;
      default:
        if (width && height && width > height){
          templateFile = "./lib/aframe-poi-img-text-horizontal.template";
        } else {
          templateFile = "./lib/aframe-poi-img-text-vertical.template";
        }
    }

    // We create a new entity displaying the icon
    //~ let el = document.createElement("a-entity");
    let el = this.el;

    let rot = getRotation(x, y);
    let pos = getPosition(x, y);
    el.setAttribute("data-icon", "./img/icon/" + icon);
    el.setAttribute("data-title", title.replaceAll('"', "''"));
    el.setAttribute("data-text", text.replaceAll('"', "''"));
    el.setAttribute("data-image", image );
    el.setAttribute("data-image-width", imageWidth );
    el.setAttribute("data-image-height", imageHeight );
    el.setAttribute("data-x", x);
    el.setAttribute("data-y", y);
    el.setAttribute("data-template", templateFile);
    el.setAttribute("data-state", "closed");
    el.setAttribute("data-id", id);
    el.object3D.rotation.y = rot.y;
    el.object3D.rotateX(rot.x);
    el.setAttribute("position", { x: pos.x, y: pos.y, z: pos.z });
    el.setAttribute("template", { src: "./lib/aframe-poi-icon.template" });
    el.onclick = openPOI_handler;
    if (state == "open"){
      let openPOI = openPOI_handler.bind(el);
      openPOI({currentTarget: el}, true);
    }

    async function openPOI_handler(ev, preview=undefined){
      // Create a new entity for our opened POI
      let el = document.createElement("a-entity");
      let poisOpen = document.getElementById("pois-open")

      // Hide the POI Icons
      let poi = ev.currentTarget;
      let icon = poi.querySelector(".poi-icon");
      icon.object3D.visible = false;
      icon.classList.remove("clickable");

      poisOpen.appendChild(el);
      el.setAttribute("position", this.getAttribute('position'));
      el.setAttribute("rotation", {x: this.dataset.x, y: this.dataset.y})

      // Put dynamic data into the POI template
      el.setAttribute("data-title", ev.currentTarget.dataset.title);
      el.setAttribute("data-image", ev.currentTarget.dataset.image);
      el.setAttribute("data-icon", ev.currentTarget.dataset.icon);
      el.setAttribute("data-text", ev.currentTarget.dataset.text);

      // We scale the image so that it will not be stretched
      //let size = await getImageSize(ev.target.dataset.image);
      let size = {
        width: Number(ev.currentTarget.dataset.imageWidth),
        height: Number(ev.currentTarget.dataset.imageHeight)
      }
      if (size.width && size.height && size.width > size.height){
        el.setAttribute("data-sx", 1 );
        el.setAttribute("data-sx_half", 0.5 );
        el.setAttribute("data-sy", (size.height/size.width) );
        el.setAttribute("data-sy_half", (size.height/size.width/2) );
      } else if(size.width && size.height) {
        el.setAttribute("data-sx", (size.width/size.height) );
        el.setAttribute("data-sx_half", (size.width/size.height/2) );
        el.setAttribute("data-sy", 1);
        el.setAttribute("data-sy_half", 0.5);
      } else {
        el.setAttribute("data-sy", 1);
        el.setAttribute("data-sy_half", 0.5);
        el.setAttribute("data-sx", 1);
        el.setAttribute("data-sx_half", 0.5);
      }
      el.setAttribute("data-ratio", size.width/size.height);
      el.setAttribute("template", { src: this.dataset.template });

      // Discard click events
      el.addEventListener("click", (event) => {
        event.stopPropagation();
        if (event.target.dataset.btn == "close"){
          // Remove the opened POI entity
          el.parentNode.removeChild(el);
          // Show the POI Icons again
          document.getElementById("targets").object3D.visible = true;
          icon.object3D.visible = true;
          icon.classList.add("clickable");
        }
      });
    }

    /**
     * Returns the rotation of the hotspot in radians
     * from the x and y angles in degrees of the camera facing this hotspot
     */
    function getRotation (deg_x, deg_y) {
      return {
        x: THREE.MathUtils.degToRad(deg_x),
        y: THREE.MathUtils.degToRad(deg_y),
      };
    }

    /**
     * Compute the position of the hotspot
     * from the x and y angles of the camera facing this hotspot
     */
    function getPosition (deg_x, deg_y) {
      let rot_y = THREE.MathUtils.degToRad(-deg_y);
      let rot_x = THREE.MathUtils.degToRad(deg_x);
      return {
        x: Math.sin(rot_y) * distance * Math.cos(rot_x),
        y: Math.sin(rot_x) * distance,
        z: -distance * Math.cos(rot_y) * Math.cos(rot_x),
      };
    }

    async function getImageSize(src) {
      return new Promise((resolve) => {
        let img = new Image();
        img.onload = async function () {
          let size = { width: this.width, height: this.height };
          resolve(size);
        };
        img.onerror = async function () {
          resolve(null);
        };
        img.src = src;
      });
    }
  }
});
