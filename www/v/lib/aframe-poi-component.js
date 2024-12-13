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
    const scene       = this.el.sceneEl;
    const x           = this.data.x;
    const y           = this.data.y;
    const distance    = this.data.distance;
    const width       = this.data.width;
    const height      = this.data.height;
    const icon        = this.data.icon;
    const image       = this.data.image;
    const imageWidth  = this.data.imageWidth;
    const imageHeight = this.data.imageHeight;
    const text        = this.data.text;
    const title       = this.data.title;
    const template    = this.data.template;
    var state         = this.data.state;
    var id            = this.data.id;

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
    el.setAttribute("data-richtext", btoa(unescape(encodeURIComponent(text))));
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
      //~ let material = el.getObject3D('mesh').material;
      let poisOpen = document.getElementById("pois-open")

      // Hide the POI Icons
      let poi = ev.currentTarget;
      let icon = poi.querySelector(".poi-icon");
      icon.object3D.visible = false;
      icon.classList.remove("clickable");

      if (ev.currentTarget.dataset.text){
        /*
         * To work, the html2canvas library requires that the HTML code to render is attached to the DOM.
         * We attach a div to the body element just to create the canvas then we detach it. This div should not be
         * seen by the user (negative z-index and hidden into a 1px wide div)
         */
        let body = document.querySelector("body");
        // This is the 1px wide container
        let container = document.createElement("div");
        container.setAttribute("class", "aframe-poi-text-container");
        
        body.appendChild(container);
        // This is the div that will limit the size of our rendered HTML
        let externalDiv = document.createElement("div");

        // Finally the div that will contain the HTML we want to render
        let internalDiv = document.createElement("div");
        externalDiv.appendChild(internalDiv);

        let converter = new showdown.Converter({tables: true});
        let output = converter.makeHtml(poi.dataset.text);

        const clean = DOMPurify.sanitize(output, {
          USE_PROFILES: { html: true },
          SAFE_FOR_TEMPLATES: true,
          ALLOWED_TAGS: ['b', 'p', '#text'],
          FORBID_ATTR: ['style', 'id', 'width', 'height'],
          ALLOWED_ATTR: ['alt'],
        });
        internalDiv.innerHTML = clean;
        container.appendChild(externalDiv);

        // We generate an image from the text input
        await html2canvas(externalDiv, {backgroundColor:null, scale: 1}).then(async function(canvas) {
          const blob = await new Promise(resolve => canvas.toBlob(resolve));
          // The data-tx, data-ty properties and their halves is used
          // by the templates to center the elements properly
          if (canvas.width && canvas.height && canvas.width > canvas.height){
            el.setAttribute("data-tx", 1 );
            el.setAttribute("data-tx_half", 0.5 );
            el.setAttribute("data-ty", (canvas.height/canvas.width) );
            el.setAttribute("data-ty_half", (canvas.height/canvas.width/2) );
          } else if(canvas.width && canvas.height) {
            el.setAttribute("data-tx", (canvas.width/canvas.height) );
            el.setAttribute("data-tx_half", (canvas.width/canvas.height/2) );
            el.setAttribute("data-ty", 1);
            el.setAttribute("data-ty_half", 0.5);
          } else {
            el.setAttribute("data-ty", 1);
            el.setAttribute("data-ty_half", 0.5);
            el.setAttribute("data-tx", 1);
            el.setAttribute("data-tx_half", 0.5);
          }
          let imgurl = await(URL.createObjectURL(blob));
          el.setAttribute("data-richtext", imgurl);
          let ratio = Number(canvas.width) / Number(canvas.height);
          el.setAttribute("data-richtext-ratio", ratio);

          // Cleaning the DOM
          body.removeChild(container);
        });
      } else {
        el.setAttribute("data-tx", "0" );
        el.setAttribute("data-tx_half", "0" );
        el.setAttribute("data-ty", "0");
        el.setAttribute("data-ty_half", "0");
        el.setAttribute("data-richtext", "");
        el.setAttribute("data-richtext-ratio", 5);
      }

      poisOpen.appendChild(el);
      el.setAttribute("position", this.getAttribute('position'));
      el.setAttribute("rotation", {x: this.dataset.x, y: this.dataset.y})

      // Put dynamic data into the POI template
      el.setAttribute("data-title", poi.dataset.title);
      el.setAttribute("data-image", poi.dataset.image);
      el.setAttribute("data-icon",  poi.dataset.icon);
      el.setAttribute("data-text",  poi.dataset.text);
      //~ el.setAttribute("data-richtext", ev.currentTarget.dataset.richtext);

      // We scale the image so that it will not be stretched
      //let size = await getImageSize(ev.target.dataset.image);
      let size = {
        width: Number(poi.dataset.imageWidth),
        height: Number(poi.dataset.imageHeight)
      };
      // The data-sx, data-sy properties and their halves is used
      // by the templates to center all the elements properly
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
