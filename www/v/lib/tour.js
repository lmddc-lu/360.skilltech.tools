import {fetchGet, sendSpot, sendSkies} from './functions.js';
console.log("### viewer/lib/tour.js");



async function tourApp(iframe, spot_app) {
  console.log("Tour app start");
  // Try to get the tour name by the tour parameter
  let params = new URLSearchParams(document.location.search);
  let tourFile = params.get("tour");
  // Try to get the tour name by the last url element
  if (!tourFile){
    tourFile = window.location.href.substring(window.location.href.lastIndexOf('/') + 1);
  }
  // Set the default tour name
  if (tourFile.includes('.') || !tourFile){
    tourFile = "tour";
  }

  function ack(message){
    iframe.contentWindow.postMessage({
      type: "ack",
      value: message,
      sender: window.location.hash,
    });
  }

  // Download the selected visit
  let response = await fetchGet('../data/tour/' + tourFile + '.json');
  let tour = {};
  if (response.ok) {
    tour = await response.json();
  }

  // We manage the state of the visit inside the spot object itself
  tour.state = {};

  // Set the starting spot

  if (tour.start_id && tour.start_id.toString() in tour.spots){
    tour.state.spotId = tour.start_id;
  } else if( tour.spots && Object.keys(tour.spots)[0] ){
    tour.state.spotId = Object.keys(tour.spots)[0];
  } else {
    tour.state.spotId = null;
    console.log("ERROR: No spot found in this tour");
    return false;
  }

  // Set the current layer
  tour.layer = tour.default_layer ? tour.default_layer : 0;
  window.addEventListener(
    "message",
    async (event) => {
      if (
        event.origin !== window.location.origin
      ) return;

      console.debug("Msg type: " + event.data.type);
      switch (event.data.type) {
        case 'ready':
          await sendSpot(tour.spots[tour.state.spotId], iframe);
          await sendSkies(tour.spots[tour.state.spotId], tour, iframe);
          // /!\ Following actions may be adapted depending of the user's device
          // For now, this is well suited for browser on a Desktop computer
          // We allow clicks with the mouse
          iframe.contentWindow.postMessage({'type': 'mouse', 'value': 'on'});
          // We hide the a-cursor element
          iframe.contentWindow.postMessage({'type': 'cursor', 'value': 'off'});
          ack(event.data.type);
        case 'ack':
          console.debug("ack: " + event.data.value);
          break;

        case 'spot':
          let originId = event.data.origin;
          let askedId = event.data.id
          /*
           * We need to rotate the Camera so that the visitor will see the new spot with the
           * same orientation. That's because the different images may be took with different orientation.
           * To get around that, we will search in the asked spot the position of the spot that correspond
           * to the origin spot, and add 180° to its y value.
           */
          let rotation;
          try {
            rotation = (tour.spots[askedId].spots.find((s) => s.id == originId).y);
            sendSpot(tour.spots[event.data.id], iframe, rotation + 180);
            setTimeout(() => {
              sendSkies(tour.spots[askedId], tour, iframe);
            }, 100);
          } catch (error) {
            console.error(error);
          }
          // Hide the about div
          document.querySelector(".about").classList.remove("show");

          ack(event.data.type);
          break;
      }
    }
  );

/*
 * About div frame
 */

  function showAbout(){
    document.querySelector(".about").classList.add("show");
  }

  function hideAbout(){
    document.querySelector(".about").classList.remove("show");
  }

  document.getElementById("btn_show_about").addEventListener("click", showAbout);
  document.getElementById("btn_hide_about").addEventListener("click", hideAbout);

  document.querySelector(".about h2").appendChild(
    document.createTextNode(tour.title)
  );

  document.querySelector(".about p.description").appendChild(
    document.createTextNode(tour.description)
  );

  document.querySelector(".about li:nth-child(1)").appendChild(
    document.createTextNode(tour.author)
  );

  let license;
  switch (tour.license){
    case "CC0-1.0":
      license = "CC0 1.0";
      break;
    case "CC-BY-4.0":
      license = "CC BY 4.0";
      break;
    case "CC-BY-SA-4.0":
      license = "CC BY-SA 4.0";
      break;
    case "CC-BY-NC-4.0":
      license = "CC BY-NC 4.0";
      break;
    case "CC-BY-NC-SA-4.0":
      license = "CC BY-NC-SA 4.0";
      break;
    case "CC-BY-ND-4.0":
      license = "CC BY-ND 4.0";
      break;
    case "CC-BY-NC-ND-4.0":
      license = "CC BY-NC-ND 4.0";
      break;
    case "UNLICENSED":
      license = "All rights reserved";
      break;
    default:
      license = "Unknown";
  }

  document.querySelector(".about li:nth-child(2)").appendChild(
    document.createTextNode(license)
  );

  // Open the about div on startup
  document.querySelector(".about").classList.add("show");

/*
 * To avoid timing issues, it is important to set the src attribute
 * of iframe at the end of this function. 
 */
  iframe.src = spot_app;
  return true;
}

export { tourApp };
