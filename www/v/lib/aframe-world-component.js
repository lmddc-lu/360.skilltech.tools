/*
 * A sky sphere composed of 32 tiles
 * that will load one after the other
 */

AFRAME.registerComponent("world", {
  schema: {
    src: {type: 'string'},
    radius: {default: 50, type: 'string'},
  },

  init: function () {
    // Init function will add the sphere chunks to the parent element
    // There will be 32 elements
    const src = this.data.src;
    const radius = this.data.radius;
    this.chunk = [];
    const chunk = this.chunk;
    const textureLoaded = this.textureLoaded;
    this.el.setAttribute("data-counter", "0");
    const removeListeners = this.removeListeners;
    let cols = 16;
    let rows = 8;
    for (let i=0; i<cols; i++){
      this.chunk.push([]);
      for (let j=0; j<rows; j++){
        let el = document.createElement("a-sky");
        el.setAttribute("phi-start", i*360/cols);
        el.setAttribute("theta-start", j*360/cols);
        el.setAttribute("radius", radius);
        el.setAttribute("phi-length", 360/cols);
        el.setAttribute("theta-length", 360/cols);
        el.setAttribute("transparent", true);
        el.setAttribute("opacity", 0);
        el.setAttribute("data-x", i);
        el.setAttribute("data-y", j);
        el.setAttribute("animation__fadeout", "property: opacity; to: 0; dur: 300; startEvents: fadeOut");
        el.setAttribute("animation__fadein", "property: opacity; to: 1; dur: 500; startEvents: fadeIn");
        this.el.appendChild(el);
        this.chunk[i].push(el);
      }
    }

    this.el.addEventListener("fadeout", (ev) => {
      removeListeners(chunk);
    });
  },

  update: async function (oldData) {
    let cols = 16;
    let rows = 8;
    const el = this.el;
    const chunk = this.chunk;
    const counter = this.el.dataset.counter;
    const src = this.data.src;
    //const textureLoaded = this.textureLoaded;
    let loader = new THREE.ImageLoader();

    if(src && oldData.src != src) {
      el.setAttribute("data-src", src);
      this.removeListeners(chunk);

      // You may compute the tile that is in front of the camera
      // using el.dataset.cameraX and el.dataset.cameraY

      // We list the chunks in the reverse order we want to download them
      // TODO: list the chunks in a clever order
      var chunkList = []
      // The (j+2)%rows below is to start downloading tiles from the third row
      // The first two rows will be downloaded at the end
      for (let j=rows-1; j>=0; j--){
        for (let i=0; i<cols; i++){
          chunkList.push({x: i, y: (j+2)%rows});
        }
      }

      function removeListener(ev){
        ev.currentTarget.removeEventListener(
          'materialtextureloaded',
          textureLoaded
        );
      }

      function textureLoaded(ev){
        if (src != ev.currentTarget.parentElement.dataset.src){
          console.log(src);
          console.log(ev.currentTarget.parentElement.dataset.src);
          return;
        }
        ev.currentTarget.emit("fadeIn");
      }

      function loadChunk(chunkList, el, counter){
        let currentChunk = chunkList.pop();
        if (!currentChunk) return;
        let i = currentChunk.x;
        let j = currentChunk.y;
        let baseUrl = window.location.href.substring(0, (window.location.href.lastIndexOf('/'))-2) + "/data/image/";
        let texture = loader.load(baseUrl + i + "/" + j + "/" + src, function(file){
          /*
           * Maybe the user clicked another target before the hi-res sky is loaded. In such case
           * we didn't modify the sky texture
           */
          // If the counter is updated, these chunks are no more needed
          if (counter != el.dataset.counter) {
            return;
          };
          // Download the next chunk
          loadChunk(chunkList, el, counter);
          chunk[i][j].addEventListener(
            'materialtextureloaded',
            textureLoaded,
            {once: true}
          );
          chunk[i][j].addEventListener(
            'removeListener',
            removeListener,
            {once: true}
          );
          chunk[i][j].setAttribute("src", file.src);
        });
      }
      loadChunk(chunkList, el, counter);
    }
  },

  removeListeners: function(chunk){
    //~ const textureLoaded = this.textureLoaded;
    let i=0;
    chunk.forEach( function(x){
      let j=0;
      x.forEach( function(el){
        el.emit("removeListener");
        el.emit("fadeOut");
        j++;
      });
      i++;
    });
  }

});
