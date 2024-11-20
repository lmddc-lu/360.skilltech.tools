/*
 * pinch.js by lmddc is marked with CC0 1.0. To view a copy of this license, visit https://creativecommons.org/publicdomain/zero/1.0/
 * Based on example code published by Mozilla Developer Network 
 * https://github.com/mdn/dom-examples/blob/main/pointerevents/Pinch_zoom_gestures.html
 */

AFRAME.registerComponent("pinch", {
  schema: {
    camera: {type: 'selector'},
    cursor: {type: 'selector'},
    min: {type: 'number'},
    max: {type: 'number'},
    speed: {type: 'number'}, // used for fingers pinch input
    step: {type: 'number'}   // used for the mouse wheel input
  },

  init: function () {
    const target = this.el.sceneEl;
    const camera = this.data.camera;
    const minFov = this.data.minFov ? this.data.minFov : 50;
    const maxFov = this.data.maxFov ? this.data.maxFov : 110;
    const speed =  this.data.speed ? this.data.speed : 1;
    const step  =  this.data.step ? this.data.step : 15;
    // These variables are needed to keep the relative cursor size
    const initFov = camera.getAttribute("fov");
    var cursorScale = {};
    var cursorInitScale = {};
    if (this.data.cursor){
      cursorScale = this.data.cursor.getAttribute("scale");
      cursorInitScale = {x: cursorScale.x, y: cursorScale.y, z: cursorScale.z};
    }

    // Global vars to cache event state
    var evCache = new Array();
    var prevDiff = -1;

    function pointerdown_handler(ev) {
      // The pointerdown event signals the start of a touch interaction.
      // This event is cached to support 2-finger gestures
      evCache.push(ev);
    }

    function pointermove_handler(ev) {
      // This function implements a 2-pointer horizontal pinch/zoom gesture.

      // Find this event in the cache and update its record with this event
      for (var i = 0; i < evCache.length; i++) {
        if (ev.pointerId == evCache[i].pointerId) {
          evCache[i] = ev;
          break;
        }
      }

      // If two pointers are down, check for pinch gestures
      if (evCache.length == 2) {
        // Calculate the distance between the two pointers
        var curDiff = Math.sqrt(
          Math.pow(evCache[1].clientX - evCache[0].clientX, 2) +
            Math.pow(evCache[1].clientY - evCache[0].clientY, 2)
        );

        if (prevDiff > 0) {
          zoom(prevDiff - curDiff, speed);
        }

        // Cache the distance for the next move event
        prevDiff = curDiff;
      }
    }

    function pointerup_handler(ev) {
      // Remove this pointer from the cache and reset the target's
      // background and border
      remove_event(ev);

      // If the number of pointers down is less than two then reset diff tracker
      if (evCache.length < 2) prevDiff = -1;
    }

    this.wheel_handlerFn = function(ev) {
      ev.preventDefault();
      zoom(Math.sign(ev.deltaY), step);
    }

    function remove_event(ev) {
      // Remove this event from the target's cache
      for (var i = 0; i < evCache.length; i++) {
        if (evCache[i].pointerId == ev.pointerId) {
          evCache.splice(i, 1);
          break;
        }
      }
    }

    function zoom (delta, speed) {
      let fov = camera.getAttribute("fov") ? Number(camera.getAttribute("fov")) : 80;
      fov += delta * speed;
      if ( fov >= minFov && fov <= maxFov ){
        camera.setAttribute("fov", fov);
        // We adjust the size of the cursor
        if (cursorScale.x){
          cursorScale.x = cursorInitScale.x * Math.tan(fov/2 * 3.14/180) / Math.tan(initFov/2 * 3.14/180);
          cursorScale.y = cursorInitScale.y * Math.tan(fov/2 * 3.14/180) / Math.tan(initFov/2 * 3.14/180);
        }
      }
    }

    // Install event handlers for the pointer target
    target.onpointerdown = pointerdown_handler;
    target.onpointermove = pointermove_handler;

    // Use same handler for pointer{up,cancel,out,leave} events since
    // the semantics for these events - in this app - are the same.
    target.onpointerup = pointerup_handler;
    target.onpointercancel = pointerup_handler;
    target.onpointerout = pointerup_handler;
    target.onpointerleave = pointerup_handler;
    target.addEventListener("wheel", this.wheel_handlerFn);
  },
  remove: function() {
    const target = this.el.sceneEl;
    target.removeEventListener("wheel", this.wheel_handlerFn, false);
  }
});
