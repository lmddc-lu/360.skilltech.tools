import { ref, onMounted, watch } from 'vue';
import { config } from '/src/assets/js/config.js';

/**
 * Fetch an URL with the GET method and
 * return the server's response
 */
async function fetchGet(url){
  var response = new Object();
  try {
    response = await fetch(url,{
      method: "GET",
      mode: "cors",
      credentials: 'include',
      cache: "no-store",
      headers: {
        "Content-Type": "application/json",
      },
    });
  } catch (error) {
    console.log("error Get");
  }
  return response;
}

/**
 * Fetch an URL with the POST method with a body as an object
 * and the Authentik csrf token into the header.
 * Return the server's response
 */
async function fetchPost(url, body, csrf=""){
  const response = await fetch(url,{
    method: "POST",
    mode: "cors",
    credentials: 'include',
    headers: {
      "Content-Type": "application/json",
      "X-authentik-CSRF": csrf,
    },
    body: JSON.stringify(body),
  });
  return response;
}

/**
 * Submits a FormData object
 * Returns the server's response
 */
async function fetchForm(url, formdata, ref=null){
  const response = await fetch(url,{
    method: "POST",
    credentials: 'include',
    body: formdata,
  });
  if(ref && response.status == 200){
    const jsondata = await response.json();
    //~ if (!jsondata.error){
      ref.value=jsondata;
    //~ }
  }
  return response;
}

/**
 * Get information for the current user
 */
async function fetchUser(user){
  const response = await fetchGet("/getUser.php");
  if(response.status == 200){
    const jsondata = await response.json();
    user.value=jsondata;
  }
  return response;
}

/*
 * Add a new demo tour in user's collection 
 */
async function fetchNewDemo(csrf){
  let result=ref({});
  let formData = new FormData();
  formData.append("csrf", csrf);
  await fetchForm("/newDemo.php", formData, result);
  if (result.value.success){
    return result.value.id;
  }
  return (false);
}



/**
 * Get size of the images used in this tour
 */
async function fetchTourSize(sizeRef, tourId){
  const response = await fetchGet("/getTourSize.php?id=" + tourId);
  if(response.status == 200){
    const jsondata = await response.json();
    sizeRef.value=jsondata;
  }
  return response;
}

/**
 * Get information for the current user
 */
async function fetchTours(tours){
  const response = await fetchGet("/getTours.php");
  if(response.status == 200){
    const jsondata = await response.json();
    tours.value=jsondata;
  }
  return response;
}

/**
 * Get information for the requested tour ID
 */
async function fetchTour(tourRef, id){
  const response = await fetchGet("/getTour.php?id=" + id);
  if(response.status == 200){
    const jsondata = await response.json();
    tourRef.value=jsondata;
  }
  return response;
}

/**
 * Get Spots for the requested Tour ID
 */
async function fetchSpots(spotsRef, tourId){
  const response = await fetchGet("/getSpots.php?tour_id=" + tourId);
  if(response.status == 200){
    const jsondata = await response.json();
    spotsRef.value=jsondata;
  }
  return response;
}

/**
 * Get POIs for the requested Spot ID
 */
async function fetchPois(poisRef, spotId){
  const response = await fetchGet("/getPois.php?spot_id=" + spotId);
  if(response.status == 200){
    const jsondata = await response.json();
    poisRef.value=jsondata;
  }
  return response;
}

/**
 * Get SpotHasSpots for the requested Tour ID
 */
async function fetchSpotHasSpots(spotHasSpotsRef, tourId){
  const response = await fetchGet("/getSpotHasSpots.php?tour_id=" + tourId);
  if(response.status == 200){
    const jsondata = await response.json();
    spotHasSpotsRef.value=jsondata;
  }
  return response;
}

/**
 * Get information for the requested tour ID
 */
async function fetchSpot(spotRef, id){
  const response = await fetchGet("/getSpot.php?id=" + id);
  if(response.status == 200){
    const jsondata = await response.json();
    spotRef.value=jsondata;
  }
  return response;
}

/**
 * Get Spots for the requested Tour ID
 */
async function fetchSkies(skiesRef, spotId){
  const response = await fetchGet("/getSkies.php?spot_id=" + spotId);
  if(response.status == 200){
    const jsondata = await response.json();
    skiesRef.value=jsondata;
  }
  return response;
}

/**
 * Get Spots for the requested Tour ID
 */
async function fetchSkiesFromTour(skiesRef, tourId){
  const response = await fetchGet("/getSkies.php?tour_id=" + tourId);
  if(response.status == 200){
    const jsondata = await response.json();
    skiesRef.value=jsondata;
  }
  return response;
}

/**
 * Get a new CSRF token
 */
async function fetchCSRF(csrfRef){
  const response = await fetchGet("/getCSRF.php");
  if(response.status == 200){
    const jsondata = await response.json();
    csrfRef.value=jsondata.csrf;
  }
  return response;
}

async function fetchDeleteTour(id, csrf){
  let tour=ref({});
  let formData = new FormData();
  formData.append("id", id);
  formData.append("csrf", csrf);
  await fetchForm("/deleteTour.php", formData, tour);
  //~ await fetchSkies(skies, spot.value.id);
  return (tour.value.success !== undefined);
}

/**
 * Deletes the cached tour JSON file
 */
async function fetchDeleteTourJSON(id, csrf){
  let tour=ref({});
  let formData = new FormData();
  formData.append("id", id);
  formData.append("csrf", csrf);
  await fetchForm("/deleteTourJSON.php", formData, tour);
  return (tour.value.error == undefined);
}

/**
 * Clears the session user and redirect to the home page
 */
async function logout(){
  const response = await fetchGet("/logout.php");
  window.location = "/";
}

/**
 * Disable the onboarding on startup, forever
 */
async function fetchSetOnboardingDatabase(value, csrf){
  let result=ref({});
  let formData = new FormData();
  formData.append("value", value);
  formData.append("csrf", csrf);
  await fetchForm("/setOnboardingDatabase.php", formData, result);
  return (result.value.error == undefined);
}

/**
 * Disable the onboarding on startup only for this session
 */
async function fetchSetOnboardingSession(value, csrf){
  let result=ref({});
  let formData = new FormData();
  formData.append("value", value);
  formData.append("csrf", csrf);
  await fetchForm("/setOnboardingSession.php", formData, result);
  return (result.value.error == undefined);
}

/*
 * Activate the character counter on inputs
 */
function fnCounter(el){
  let textEl = el;
  let counter = document.querySelector(textEl.dataset.fnTarget);
  if(counter === null){
    console.error("Add a 'data-fn-target' attribute to the input\
 field with a query selector targetting the element displaying the counter");
    console.log(el);
    return;
  }
  let maximumCharacters = textEl.getAttribute("maxlength") ? textEl.getAttribute("maxlength") : 42;
  let warningCharacters = Math.round(maximumCharacters * 0.7);
  //~ counter.textContent = textEl.value.length + "/" + maximumCharacters;
  function setClass(){
    const typedCharacters = textEl.value.length;
    counter.textContent = typedCharacters + "/" + maximumCharacters;
    if (typedCharacters < warningCharacters) {
      counter.classList.remove("text-warning");
      counter.classList.remove("text-danger");
    } else if (typedCharacters >= warningCharacters && typedCharacters < maximumCharacters) {
      counter.classList.add("text-warning");
      counter.classList.remove("text-danger");
    } else if (typedCharacters >= maximumCharacters) {
      counter.classList.add("text-danger");
      counter.classList.remove("text-warning");
    }
  };
  textEl.addEventListener("input", setClass);
  setClass();
}

async function resizeImage(image, width, height, quality=0.85) {
  return new Promise((resolve) => {

    const img = new Image();
    img.onload = async function () {
      // We load the image into a img element to get its dimensions
      let height2 = height;
      let width2 = width;
      if (this.width / this.height > width / height) {
        //We need to crop the width, so we will keep the height and compute the new width
        width2 = Number(this.width) * height / Number(this.height);
      } else {
        //We need to crop the height, so we will keep the width and compute the new height
        height2 = this.height * width / this.width;
      }
      let canvas = document.createElement('canvas');
      canvas.width = width;
      canvas.height = height;
      let ctx = canvas.getContext('2d');
      let mvx = -(width2 - width) / 2;
      let mvy = -(height2 - height) / 2;
      ctx.drawImage(this, mvx, mvy, width2, height2);

      //ctx.drawImage(this, 0, 0, width2, height2);
      let data = canvas.toDataURL("image/jpeg", quality);
      resolve(data);
    }
    img.src = image;
  })
}

/**
 * Retrieve the value of a cookie
 */
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return "";
}

/*
 * Hydrates the spot
 */
async function getHydratedSpot(spot){
  let skies = ref({});
  let getSkies = Promise.resolve( fetchSkies(skies, spot.id) );
  // Get a copy of the spot object
  //let spot = JSON.parse(JSON.stringify(spots.value.find((spot) => {return spot.id === id})));
  //~ let filteredSHS = spotHasSpots.value.filter((shs) => {return shs.spot1 === id || shs.spot2 === id});
  //~ let linkedSpotsID = filteredSHS.map((shs) => {return shs.spot1 == id ? shs.spot2 : shs.spot1});
  //~ let linkedSpots = spots.value.filter((s) => {return linkedSpotsID.includes(s.id)});
  //~ spot.spots = linkedSpots;
  spot.spots = [];
  // We wait for the skies query to fulfill before adding the skies to the spot
  await getSkies;
  //~ spot.skies = skies.value.map((sky => sky.filename));
  spot.skies = skies.value;
  return spot;
}

/*
 * Send spot to the 360° viewer
 */
async function sendSpot(spot, viewer, destY=null, viewerSpot=null, viewerTitles=null, linked=null){
  viewer.contentWindow.postMessage({'type': 'spot', 'value': JSON.stringify(spot)});
  viewer.setAttribute("data-spot-id", spot.id);
  if (destY !== null){
    // We rotate the camera to the target position
    rotateCamera(viewer, destY);
  }
}

/*
 * rotate the camera from the 360° viewer
 */
async function rotateCamera(viewer, angle){
  viewer.contentWindow.postMessage({'type': 'rotate', 'value': angle});
}

/*
 * Test if the file is suitable for a 360° view
 * The function is called when the image element is loaded
 */
function testSkyImage(img, ref){
  ref.value.width = img.naturalWidth;
  ref.value.height = img.naturalHeight;
  ref.value.error=[];
  if (ref.value.size > config.skyMaxSize){
    ref.value.error.push("size");
  }
  if (ref.value.width < 2048){
    ref.value.error.push("width");
  }
  //~ if (ref.value.height < 1024){
    //~ ref.value.error.push("height");
  //~ }
  //~ if (ref.value.width / ref.value.height !=2){
    //~ ref.value.error.push("proportions");
  //~ }
}

/*
 * Test if the file is suitable for eg. a POI or thumbnail
 * The function is called when the image element is loaded
 */
function testImage(img, ref){
  ref.value.width = img.naturalWidth;
  ref.value.height = img.naturalHeight;
  if (ref.value.size > config.imgMaxSize){
    ref.value.error.push("size");
  }
  //~ if (ref.value.width < 2048){
    //~ ref.value.error.push("width");
  //~ }
  //~ if (ref.value.height < 1024){
    //~ ref.value.error.push("height");
  //~ }
}

function initImageProperties(ref){
  ref.value = {
    title: null,
    filename: null,
    file: null,
    step: 1,
    thumbnail: null,
    img: null,
    isOk: null,
    width: null,
    height: null,
    size: null,
    sizeMB: null,
    error: [],
  };
}

/*
 * Put image file properties into a ref object
 */
function getFileProperties(file, ref){
  if (file){
    ref.value.file = file;
    ref.value.title = file.name;
    ref.value.filename = file.name;
    ref.value.size = file.size;
    ref.value.sizeMB = Math.round(file.size/1000000);
    ref.value.img = URL.createObjectURL(file);
  }
}

export{fetchGet,
  fetchPost,
  fetchUser,
  fetchNewDemo,
  fetchTours,
  fetchTour,
  fetchTourSize,
  fetchSpot,
  fetchSpots,
  fetchPois,
  fetchSpotHasSpots,
  fetchSkies,
  fetchSkiesFromTour,
  fetchCSRF,
  fetchForm,
  fetchDeleteTour,
  fetchDeleteTourJSON,
  logout,
  fetchSetOnboardingDatabase,
  fetchSetOnboardingSession,
  fnCounter,
  resizeImage,
  getCookie,
  getHydratedSpot,
  sendSpot,
  testSkyImage,
  testImage,
  initImageProperties,
  getFileProperties};
