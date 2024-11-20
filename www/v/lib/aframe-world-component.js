/*
 * Center the children of an a-entity vertically
 * May be used for text entities
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
        //~ el.setAttribute("animation__fadein", "property: components.material.material.opacity; from: 0; to: 1; dur: 500; startEvents: fadein");
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
    let loader = new THREE.FileLoader();
    loader.setResponseType("blob");
    loader.setMimeType("image/jpeg");

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
        ev.currentTarget.setAttribute("material", "opacity: 1");
      }

      function loadChunk(chunkList, el, counter){
        let currentChunk = chunkList.pop();
        if (!currentChunk) return;
        let i = currentChunk.x;
        let j = currentChunk.y;

        let texture = loader.load("/data/image/" + i + "/" + j + "/" + src, function(file){
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

          let url = window.location.protocol + "//" + window.location.hostname + ":" +  window.location.port + "/data/image/" + i + "/" + j + "/" + src;
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
          
          chunk[i][j].setAttribute("src", url);
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
        el.setAttribute("material", "opacity: 0");
        j++;
      });
      i++;
    });
  }

});
