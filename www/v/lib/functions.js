async function fetchPost(url, body){
  //console.log("fetchPost " + url);
  const response = await fetch(url,{
    method: "POST",
    mode: "cors",
    credentials: 'include',
    headers: {
      "Content-Type": "application/json",

    },
    body: JSON.stringify(body),
  });
  return response;
}

async function fetchGet(url){
  var response = new Object();
  try {
    //console.log("fetchGet " + url);
    response = await fetch(url,{
      method: "GET",
      mode: "cors",
      credentials: 'include',
      headers: {
        "Content-Type": "application/json",
      },
      cache: "no-store",
    });
  } catch (error) {
    console.error("error Get");
  }
  return response;
}

async function sendSpot(spot, iframe, destY=null){
  console.debug("sendSpot");
  iframe.contentWindow.postMessage({'type': 'spot', 'value': JSON.stringify(spot)});

  iframe.setAttribute("data-spot-id", spot.id);
  if (destY){
    // We rotate the camera to the target position
    iframe.contentWindow.postMessage({'type': 'rotate', 'value': destY});
  }
}

/*
 * Send the list of skies linked to this spot to prefetch them
 *
 * @param {Object} spot We search for the skies related to this spot
 * @param {Object} tour The tour containing all the datas we need
 */
async function sendSkies(spot, tour, iframe){
  return;
  console.debug("sendSkies");
  let ids = spot.spots.map( (s) => s.id);
  let skies = [];
  ids.forEach((id) => { skies.push(tour.spots[id].skies[0]) });
  iframe.contentWindow.postMessage({'type': 'skies', 'value': JSON.stringify(skies)});
}

export {
  fetchGet,
  fetchPost,
  sendSpot,
  sendSkies,
}
