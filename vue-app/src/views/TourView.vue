<script setup>
import { RouterLink, RouterView } from 'vue-router';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { user } from '/src/state.js';
import { config } from '/src/assets/js/config.js';
import {
  fetchGet,
  fetchPost,
  fetchUser,
  fetchTour,
  fetchSpots,
  fetchSkies,
  fetchSkiesFromTour,
  fetchSpotHasSpots,
  fetchDeleteTourJSON,
  fetchDeleteTour,
  fetchSetOnboardingSession,
  fetchSetOnboardingDatabase,
  fetchForm,
  fetchCSRF,
  fetchTourSize,
  sendSpot,
  testSkyImage,
  testImage,
  getFileProperties,
  initImageProperties,
  fnCounter,
  logout,
  getCookie
} from '/src/assets/js/functions.js';
import VOnboardingWrapper from '../components/VOnboardingWrapper.vue';
import OnboardingModal from '../components/OnboardingModal.vue';
import HelpButton from '../components/HelpButton.vue';
import {onboardingSteps} from '/src/assets/js/onboarding-tour.js';
import ImageInput from '../components/ImageInput.vue';
import {steppedScale} from '/src/assets/js/stepped-scale.js';
import {splitImage} from '/src/assets/js/split-image.js';
import Graph from '../components/Graph.vue';
import ShareModal from '../components/ShareModal.vue';

const imgMaxSize = Math.round(config.imgMaxSize/1000000);
const route = useRoute();
const router = useRouter();
//const user = ref({});
const tour = ref({});
const spots = ref([]);
const spotsPositions = ref([]);
const skies = ref([]);
const spotHasSpot = ref({});
const spotHasSpots = ref([]);
const csrf = ref({});
const tour_form = ref({})
var viewers = new Array();
const viewerSpot = ref([undefined, undefined]);
const viewerTitles = ref(["View A", "View B"]);
const viewerPositions = ref(["", ""]);
const viewerLinked = ref([false, false]);
const linked = ref(false);
var dropzone = new Array();
const newSky = ref({});
const selectedSpot = ref({id: null, title: null, index: null});
const inputSearchSpot = ref("");
const tourSize = ref();
const thumbInputRef = ref(null);
const skyInputRef = ref(null);
const shareTourEl = ref(null);
const graphRef = ref(null);
const modalOnboardingRef = ref(null);
var cy;

const placeholderNumber = computed(() => {
  // We wants between 0 and 3 placeholders
  return Math.max(1 - spots.value.length, 0);
})

const skyMaxSize = computed(() => {
  // returns the rounded value in MB
  return Math.round(config.skyMaxSize/1000000);
})

const tourSizeMB = computed(() => {
  // returns the rounded value in MB
  if (tourSize) {
    return Math.round(tourSize.value/1000000);
  }
  return null;
})

/*
 * True if this tour is suitable for the onboarding app
 */
const isOnboarding = computed(() => {
  return (
    tour.value.modification_date == config.demoDate &&
    tour.value.creation_date == config.demoDate
  );
})

/*
 * True if the onboarding must be shown
 */
const showOnboarding = computed(() => {
  return (
    isOnboarding.value &&
    user.value.onboarding == 1
  );
})

/*
 * Reactivate the onboarding and redirect to home page
 */
async function handleSetOnboarding(){
  user.value.onboarding = 1;
  await Promise.all([
    fetchSetOnboardingSession(1, csrf.value),
    fetchSetOnboardingDatabase(1, csrf.value)
  ]);

  if (!isOnboarding.value){
    router.push({ name: 'home' });
  }
}

function openEditTab(){
  let tab = document.getElementById("about");
  tab.classList.add("open");
  let curtain = document.getElementById("curtain");
  curtain.style.display = "block";
}

/*
 * Show the exportTour modal
 */
async function exportTour(event){
  // Show the modal
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalExport"));
  modal.show();
  // Refresh the tour size
  let resp = ref({});
  await fetchTourSize(resp, route.params.tourId);
  tourSize.value = resp.value.size;
}

const rSpots = computed(() => {
  // reverses the spot list, adds the original index and check if the 360vue is used
  let r = spots.value.slice();
  let i = 0;
  r.forEach(function (spot, index) {
    // Store the index in the reverse array
    spot.index = index;
    // Check if the vue is used
    if (spotHasSpots.value){
      spot.linked = (spotHasSpots.value.filter((shs) => shs.spot1 == spot.id || shs.spot2 == spot.id).length > 0);
    } else {
      spot.linked = false;
    }
  });
  return r.reverse();
})

async function saveTour(){
  let result = ref({});
  let formData = new FormData();
  formData.append("title", tour_form.value.title ? tour_form.value.title : "");
  formData.append("description", tour_form.value.description ? tour_form.value.description : "");
  formData.append("author", tour_form.value.author ? tour_form.value.author : "");
  formData.append("license", tour_form.value.license ? tour_form.value.license : "");
  formData.append("id", tour.value.id ? tour.value.id : "");
  formData.append("start_id", tour.value.start_id ? tour.value.start_id : "");
  formData.append("delete_image", tour_form.value.delete_image == true ? 1 : 0 );
  if (thumbInputRef.value.file) {
    formData.append("file", thumbInputRef.value.file);
  }
  formData.append("csrf", csrf.value);
  let tabAbout = document.getElementById("about");
  tabAbout.classList.remove("open");
  let curtain = document.getElementById("curtain");
  curtain.style.display = "none";
  await fetchForm("/saveTour.php", formData, result);
  if (result.value.error){
    console.error(result.value.error);
  } else {
    tour.value = result.value;
    fetchDeleteTourJSON(tour.value.id, csrf.value);
    initTourForm();
    thumbInputRef.value.reset();
  }
  //~ fetchCSRF(csrf);
}

function handleDeleteThumb(){
  console.log("delimage");
  // Deletes saved image
  tour_form.value.delete_image = true;
  thumbInputRef.value.reset();
}

const filteredSpots = computed(() => {
  if (inputSearchSpot.value == ""){
    return spots.value.map((spot => spot.title));
  }
  return spots.value.map((spot => spot.title)).filter((title) => title.indexOf(inputSearchSpot.value) !== -1 );
});

async function refreshViewers(){
  viewers.forEach(async (viewer) => {
    displaySpot(
      await getHydratedSpot(viewerSpot.value[viewer.dataset.id]),
      viewer
    );
  });
}

async function connectSpots(){
  if (!viewers[0].dataset.spotId || !viewers[1].dataset.spotId){
    console.log("You need to select two spots first");
    return;
  }

  // Get the cameras positions
  let rotations = [
    getHotspotsComponent(viewers[0]).getCameraRotation(),
    getHotspotsComponent(viewers[1]).getCameraRotation()
  ];

  let formData = new FormData();
  formData.append("spot1",  viewers[0].dataset.spotId);
  formData.append("spot1x", rotations[0].x);
  formData.append("spot1y", rotations[0].y);
  formData.append("spot1t", viewers[0].dataset.target);
  formData.append("spot2",  viewers[1].dataset.spotId);
  formData.append("spot2x", rotations[1].x);
  formData.append("spot2y", rotations[1].y);
  formData.append("spot2t", viewers[1].dataset.target);
  formData.append("csrf", csrf.value);
  let result = ref({});
  await fetchForm("/connectSpots.php", formData, result);
  if (!result.value.error && result.value.id){
    spotHasSpots.value.push(result.value);
    linked.value = true;
    refreshViewers();
    graphRef.value.update(); // Force the update of the graph
    viewerLinked[0] = true;
    viewerLinked[1] = true;
    // Change the cursor of the viewers
    let cursor = {'src': '/v/img/cursor2.png'};
    viewers[0].contentWindow.postMessage({'type': 'cursor', 'value': JSON.stringify(cursor)});
    viewers[1].contentWindow.postMessage({'type': 'cursor', 'value': JSON.stringify(cursor)});
  }
  await fetchDeleteTourJSON(tour.value.id, csrf.value);
  //~ fetchCSRF(csrf);
}

async function disconnectSpots(event){
  // search for the spotHasSpot object
  if (viewerSpot.value[0] != undefined && viewerSpot.value[1] != undefined &&
    viewerSpot.value[0] != viewerSpot.value[1]){
    let spot1 = Math.min(viewerSpot.value[0], viewerSpot.value[1]);
    let spot2 = Math.max(viewerSpot.value[0], viewerSpot.value[1]);
    let shs = spotHasSpots.value.filter((shs) => shs.spot1 == spot1 && shs.spot2 == spot2);
    if (shs.length > 0){
      let formData = new FormData();
      formData.append("id", shs[0].id);
      formData.append("csrf", csrf.value);
      let result = ref({});
      await fetchForm("/deleteSpotHasSpot.php", formData, result);
      if (result.value.success){
        linked.value = false;
        // We refresh the list of connected spots then refresh the viewers
        await fetchSpotHasSpots(spotHasSpots, route.params.tourId);
        refreshViewers();
        //~ cyUpdateNodes();
        fetchDeleteTourJSON(tour.value.id, csrf.value);
        viewerLinked[0] = true;
        viewerLinked[1] = true;

        // Change the cursor of the viewers
        refreshCursors();
      }
      //~ fetchCSRF(csrf);
    }
  }
}

/*
 * Send spot to the 360° viewer
 */
async function displaySpot(spot, viewer, destY=null){
  sendSpot(spot, viewer, destY);
  viewerSpot.value[viewer.dataset.id] = spot.id;
  let shs = findSpotHasSpot();
  linked.value = (shs != null);
  viewerTitles.value[viewer.dataset.id] = spot.title;
  viewerPositions.value[viewer.dataset.id] = spot.position;
  viewerLinked.value[viewer.dataset.id] = spot.linked;
  refreshCursors(linked.value);

  if(linked.value){
    // Determines which target is used for which viewer using shs
    let viewer0t, viewer1t;
    if (shs.spot1 == viewers[0].dataset.spotId){
      // the spot1 match the spot in viewer0
      viewer0t = shs.spot1t == 0 ? "v" : "h";
      viewer1t = shs.spot2t == 0 ? "v" : "h";
    } else {
      // the spot1 match the spot in viewer1
      viewer0t = shs.spot2t == 0 ? "v" : "h";
      viewer1t = shs.spot1t == 0 ? "v" : "h";
    }
    document.getElementById("target0" + viewer0t).click();
    document.getElementById("target1" + viewer1t).click();
  }
}

/*
 * Returns the spotHasSpot corresponding to the the displayed spots, if any
 */
function findSpotHasSpot(){
  if (viewerSpot.value[0] != undefined && viewerSpot.value[1] != undefined &&
    viewerSpot.value[0] != viewerSpot.value[1]){
    let spot1 = Math.min(viewerSpot.value[0], viewerSpot.value[1]);
    let spot2 = Math.max(viewerSpot.value[0], viewerSpot.value[1]);
    return spotHasSpots.value.filter((shs) => shs.spot1 == spot1 && shs.spot2 == spot2)[0];
  }
  return null;
}

/*
 * Display selected spots into the 360° viewer
 */
async function handleGraphClick(ev){
  if (ev.spots.length == 2){
    displaySpot( await getHydratedSpot(ev.spots[0].id), viewers[0], ev.spots[0].y );
    displaySpot( await getHydratedSpot(ev.spots[1].id), viewers[1], ev.spots[1].y );
  } else if (ev.spots.length == 1){
    if (!viewerSpot.value.includes(ev.spots[0].id)){
      // Displays spot in an empty viewer if possible
      if (!viewerSpot.value[0] || viewerSpot.value[1]){
        displaySpot( await getHydratedSpot(ev.spots[0].id), viewers[0] );
      } else {
        displaySpot( await getHydratedSpot(ev.spots[0].id), viewers[1] );
      }
    }
    scrollToSpot(ev.spots[0].id);
  }
}

/*
 * Get the hotspots A-frame components from the viewers as an array
 */
function getHotspotsComponent(viewer){
  return viewer.contentWindow.document.querySelector('[spots]').components.spots;
}

/*
 * Hydrates the spot
 */
async function getHydratedSpot(id){
  let skies = ref({});
  let getSkies = Promise.resolve( fetchSkies(skies, id) );
  // Get a copy of the spot object
  let spot = JSON.parse(JSON.stringify(spots.value.find((spot) => {return spot.id === id})));

  // We add the linked spots by parsing the spotHasSpots array
  spot.spots = [];
  spotHasSpots.value.forEach((shs) => {
    if (shs.spot1 == id){
      spot.spots.push({
        id: shs.spot2,
        x: shs.spot1x,
        y: shs.spot1y,
        t: shs.spot1t
      });
    } else if (shs.spot2 == id){
      spot.spots.push({
        id: shs.spot1,
        x: shs.spot2x,
        y: shs.spot2y,
        t: shs.spot2t
      });
    }
  });

  // We wait for the skies query to fulfill before adding the skies to the spot
  await getSkies;
  //~ spot.skies = skies.value.map((sky => sky.filename));
  spot.skies = skies.value;
  return spot;
}

/*
 * Add the thumbnail property to the spots ref
 */
function setThumbs(spotsRef, skiesRef, layer=null){
  spotsRef.value.forEach((spot) => {
    let sky = skiesRef.value.filter((sky) => sky.spot_id == spot.id);
    if (sky.length > 0) {
      spot.thumbnail = sky[0].filename;
    }
  });
}

// Manage the drag and drop
function dragstartHandler(ev) {
  // Prevent the drag if the image is already used in a view
  if (viewerPositions.value.includes(parseInt(ev.currentTarget.dataset.position))){
    ev.preventDefault();
    return;
  }
  // Add the target element's id to the data transfer object
  ev.dataTransfer.setData("position", ev.currentTarget.dataset.position);
  ev.dataTransfer.setData("text/plain", ev.currentTarget.dataset.index);
  raiseDropzones();
}

async function dropHandler(ev) {
  ev.preventDefault();
  let viewer = viewers[ev.target.dataset.viewerId];
  let index = ev.dataTransfer.getData("text/plain");
  let spotId = spots.value[index].id;
  //~ console.log(ev.target.dataset.viewerId);
  displaySpot( await getHydratedSpot(spotId), viewer );
  //~ viewer.setAttribute("data-spot-id", spotId);
  viewer.classList.remove("hover")
}

function dragendHandler(ev) {
  sinkDropzones();
}

function dragenterHandler(ev) {
  let viewer = viewers[ev.currentTarget.dataset.viewerId];
  viewer.classList.add("hover")
}

function dragleaveHandler(ev) {
  let viewer = viewers[ev.currentTarget.dataset.viewerId];
  viewer.classList.remove("hover")
}

function raiseDropzones(){
  /*
   * Due to a Chrome's bug, we cannot modify the DOM into the dragstart handler. As a
   * workaround we wrap the following code into a setTimeout function.
   */
  setTimeout(
    function(){
      dropzone.forEach((zone) => {
        zone.style.zIndex = "10";
      })
      viewers[0].style.zIndex = "9";
      viewers[1].style.zIndex = "9";
      curtain.style.zIndex = "5";
      curtain.style.opacity = "0";
    }
  );
}

function sinkDropzones(){
  dropzone.forEach((zone) => {
    zone.style.zIndex = "";
  })
  viewers[0].style.zIndex = "";
  viewers[1].style.zIndex = "";
}

// Manage messages coming from iFrames
async function handleMessage(event){
  // Only accept messages from known hosts
  if (
    event.origin !== "http://localhost" &&
    event.origin !== "https://360.skilltech.tools"
  ) return;
  let type = event.data.type;
  switch (type) {
    case "focus":
      // We block events over the other iFrame
      if (event.data.target == "#0") {
        viewers[0].style.pointerEvents = "";
        viewers[1].style.pointerEvents = "none";
      } else if (event.data.target == "#1") {
        viewers[0].style.pointerEvents = "none";
        viewers[1].style.pointerEvents = "";
      }
      break;
    case "blur":
      // An iFrame lost focus, we enable mouse events for all iFrames
      viewers[0].style.pointerEvents = "";
      viewers[1].style.pointerEvents = "";
      break;
    case "spot":
      // A target has been clicked, we show the related spot into the other viewer
      let originViewerNum = event.data.sender == "#0" ? 0 : 1;
      let targetViewerNum = event.data.sender == "#0" ? 1 : 0;
      let originSpotId = viewerSpot.value[originViewerNum];
      let targetSpotId = event.data.id;
      // We search for the spotHasSpot that link the two views
      let shs = spotHasSpots.value.filter((shs) => (shs.spot1 == Math.min(originSpotId, targetSpotId) && shs.spot2 == Math.max(originSpotId, targetSpotId) ));
      let rotation = null;
      if (shs.length == 1){
        rotation = shs[0].spot1 == originSpotId ? shs[0].spot2y : shs[0].spot1y;
      }
      displaySpot( await getHydratedSpot(event.data.id), viewers[targetViewerNum], rotation );
      console.log(event.data);
      break;
  }
}

/*
  Modify the cursor's image depending of the linked status of views
*/
function refreshCursors(){
  let cursor;
  if (linked.value){
    cursor = {'src': config.cursor.linked.src};
  } else {
    cursor = {'src': config.cursor.unlinked.src};
  }
  viewers[0].contentWindow.postMessage({'type': 'cursor', 'value': JSON.stringify(cursor)});
  viewers[1].contentWindow.postMessage({'type': 'cursor', 'value': JSON.stringify(cursor)});
}

// Initialize the edit tour form
function initTourForm() {
  tour_form.value = {
    title: tour.value.title,
    description: tour.value.description,
    author: tour.value.author,
    license: tour.value.license,
    delete_image: false,
  }
}


// Test if the value of the form has changed
function isModifiedTourForm() {
  return (
    tour_form.value.title != tour.value.title ||
    tour_form.value.description != tour.value.description ||
    tour_form.value.author != tour.value.author ||
    tour_form.value.license != tour.value.license ||
    thumbInputRef.value.file != null
  );
}

/*
 * Ask for confirmation when user close the About tab
 */
function handleTabAboutClose(){
  if (!isModifiedTourForm()){
    let tab = document.getElementById("about");
    tab.classList.remove("open");
    let curtain = document.getElementById("curtain");
    curtain.style.display = "none";
    thumbInputRef.value.reset(); // Reset the input in case it contains an image with errors
    return;
  }
  let modalConfirmLeave = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmLeave"));
  modalConfirmLeave.show();
}

/*
 * Do not save changes, reset the form and hide the modals
 */
function handleModalConfirmLeave(){
  let tabAbout = document.getElementById("about");
  let modalConfirmLeave = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmLeave"));
  modalConfirmLeave.hide();
  tabAbout.classList.remove("open");
  let curtain = document.getElementById("curtain");
  curtain.style.display = "none";
  initTourForm();
  thumbInputRef.value.reset();
}

function confirmDeleteSpot(id, index){
  // We use index to find quickly the spot in the list, we confirm it is the good spot with its id
  let spot = spots.value[index];
  if (spot.id != id){
    alert("error! " + spot.id + "/" + id);
    return;
  }
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmDelete"));
  selectedSpot.value.id = id;
  selectedSpot.value.title = spot.title;
  selectedSpot.value.index = index;
  modal.show();
}

async function handleDeleteSpot(id){
  let spot = ref({});
  let formData = new FormData();
  formData.append("id", id);
  formData.append("csrf", csrf.value);
  await fetchForm("/deleteSpot.php", formData, spot);
  // Let's close the modal
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmDelete"));
  modal.hide();
  if (spot.value.success !== undefined){
    let waitSpots = fetchSpots(spots, route.params.tourId);
    fetchSpotHasSpots(spotHasSpots, route.params.tourId);
    await waitSpots;
    setThumbs(spots, skies, 0);
    fetchDeleteTourJSON(route.params.tourId, csrf.value);
  }
  //~ fetchCSRF(csrf);
}

/*
 * Deletes the selected tour
 */
async function handleDeleteTour(event){
  event.preventDefault();
  await fetchDeleteTour(route.params.tourId, csrf.value);
  fetchDeleteTourJSON(spot.value.tour_id, csrf.value);

  //Close the modal then go back to home
  let modalEl = document.getElementById("confirmDeleteProject");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modalEl.addEventListener('hidden.bs.modal', function (event) {
    router.push({ name: 'home' });
  });
  modal.hide();
}

function handleSetStartId(spot_id){
  let old = tour.value.start_id;
  tour.value.start_id = spot_id;
  saveTour();
  graphRef.value.setStartId(spot_id);
}

/**********************************
 * Below are Sky upload functions *
 **********************************/

/*
 * Shows the second step of the sky upload modal
 */
function gotoSkyUploadStep2(ev){
  newSky.value.type = ev.file.type;
  newSky.value.file = ev.file;
  newSky.value.placeholder = ev.file.name.substring(0,config.spotTitlemaxSize);
  newSky.value.filename = ev.file.name;
  newSky.value.size = ev.file.size;
  newSky.value.sizeMB = Math.round(ev.file.size/1000000);
  newSky.value.thumbnail = ev.img;

  setTimeout(
    function(){
      document.getElementById("input_sky_title").focus();
    },
    500
  );
  newSky.value.step = 2;
}

/*
 * Shows the first step of the sky upload modal
 */
function gotoSkyUploadStep1(){
  initImageProperties(newSky);
  newSky.value.step = 1;
}

/*
 * Closes the sky upload modal
 */
function closeSkyUploadModal(){
  let modalEl = document.getElementById("uploadPanorama");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modalEl.addEventListener('hidden.bs.modal', function (event) {
    initImageProperties(newSky);
    skyInputRef.value.reset();
  });
  modal.hide();
}

/*
 * Uploads the sky file and the parameters
 */
async function handleNewSpot(event){
  //modal uploadPanorama
  let modalEl = document.getElementById("uploadPanorama");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);

  // Displays the uploading message
  newSky.value.step = 3;
  let wait = 1000; // minimum time before we hide the modal
  let start = performance.now();
  let formData = new FormData();
  let spot = ref({});
  if (newSky.value.title) {
    formData.append("title", newSky.value.title);
  } else {
    formData.append("title", newSky.value.placeholder);
  }
  formData.append("tour_id", route.params.tourId);
  let preload = await steppedScale(newSky.value.file, config.skyPreloadWidth, config.skyPreloadHeight);
  let thumb = await steppedScale(newSky.value.file, config.skyThumbWidth, config.skyThumbHeight, 'contain');
  let cols = 16;
  let rows = 8;
  let tiles = await splitImage(newSky.value.file, cols, rows);
  formData.append("cols", cols);
  formData.append("rows", rows);
  let counter = 0;
  tiles.forEach(function(tile){
    formData.append("tile[]", tile);
    counter++;
  });
  formData.append("thumb", thumb);
  formData.append("preload", preload);
  formData.append("csrf", csrf.value);
  await fetchForm("/addSpot.php", formData, spot);
  if (!spot.value.error){
    await fetchSkiesFromTour(skies, route.params.tourId);
    await fetchSpots(spots, route.params.tourId);
    setThumbs(spots, skies, 0);
    setDragstartListeners();
    // Scroll to the new spot
    scrollToSpot(spots.value[spots.value.length-1].id);
    console.log("no error, fetch skies")
  }

  modalEl.addEventListener('hidden.bs.modal', function (event) {
    initImageProperties(newSky);
      console.debug("initSky")
  });
  wait = Math.max(0, wait - (performance.now() - start));
  setTimeout(function(){
    modal.hide();
  }, wait);
  //~ fetchCSRF(csrf);
}

function setDragstartListeners(){
  // Add the ondragstart event listener
  const elements = document.getElementsByClassName("draggable_bis");
  for (let i=0; i<elements.length; i++){
    elements[i].addEventListener("dragstart", dragstartHandler);
    elements[i].addEventListener("dragend", dragendHandler);
  }
}

function scrollToSpot(id){
  let el = document.getElementById("panorama_" + id);
  if (el) el.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
}

/*
 * Decline the onboarding for the session length and hide the modal
 */
async function handleDeclineOnboarding(){
  user.value.onboarding = 0;
  let result = await fetchSetOnboardingSession(0, csrf.value);
}

/*
 * Switch to the next vue and continue the onboarding
 */
async function handleFinishOnboarding(){
  setTimeout(() => {
    let spotId = spots.value.length != 0 ? spots.value[spots.value.length - 1].id : null;
    if (spotId){
      //redirect to the last spot
      router.push({ name: 'editSpot', params: { spotId: spotId } });
    }
  }, "500");
}

/*
 * Reactivate the onboarding
 */
async function handleHelpButton(){
  if (isOnboarding.value){
    user.value.onboarding = 1;
    let result = await fetchSetOnboardingSession(1, csrf.value);
  } else {
    modalOnboardingRef.value.show();
  }
}

async function main(){
  if (route.params.tourId){
    await fetchTour(tour, route.params.tourId);
    if(tour.value.error == "Tour not found"){
      router.push({ name: 'home' });
    }
    // fill the form with tour data
    initTourForm();
    await fetchSpots(spots, route.params.tourId);
    await fetchSkiesFromTour(skies, route.params.tourId);
    setThumbs(spots, skies, 0);
    await fetchSpotHasSpots(spotHasSpots, route.params.tourId);
  }
  initImageProperties(newSky);
  // We initialize the graph first to prevent the use of undefined cy variable
  //~ cyInit();
  fetchCSRF(csrf);
  document.getElementById("link_visit").href="/v/" + tour.value.filename;
  // Add the drop eventListeners to the viewers
  for (let i=0; i<=1; i++){
    viewers[i] = document.getElementById("viewer" + i);
    dropzone[i] = document.getElementById("dropzone" + i);
    dropzone[i].addEventListener("dragover", (event) => {
      // prevent default to allow drop
      event.preventDefault();
      event.dataTransfer.dropEffect = "move";
    });
    dropzone[i].addEventListener("drop", dropHandler);
    dropzone[i].addEventListener("dragenter", dragenterHandler);
    dropzone[i].addEventListener("dragleave", dragleaveHandler);
  }
  setDragstartListeners();

  // populating the graph
  //~ cyUpdateNodes();

  // fn counters
  var counterTriggerList = [].slice.call(document.querySelectorAll('[data-fn-toggle="counter"]'));
  counterTriggerList.forEach(function(el){
    fnCounter(el);
  });

};
  
onMounted(() => {
  window.addEventListener("message", handleMessage, false);
  main();
});

onUnmounted(() => {
  window.removeEventListener("message", handleMessage);
});
</script>

<template>
  <HelpButton @click="handleHelpButton"/>
  <header>
    <h1><img src="/src/assets/img/logo_360.svg" alt="Skilltech - 360 tour"></h1>
    <ul>
      <li>
<!--    <router-link to="/" :alt="t_home">
          Home
        </router-link> -->
      </li>
      <template v-if="user.email">
        <li>
          <a href="#" >
            
          </a>
        </li>
        <li class="dropdown user">
          <a href="#" class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
            {{ user.email.substring(0,1).toUpperCase() }}
          </a>
          <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
            <li><a class="dropdown-item email" href="#">{{ user.email }}</a></li>
            <li><a class="dropdown-item signout" href="#" @click="logout()" :alt="t_sign_out"><img src="/src/assets/img/icon_signout.svg" alt="">Sign out</a></li>
          </ul>
        </li>
      </template>
      <template v-else>
        <li>
          <router-link to="/oidc" :alt="t_connection">
            Connection
          </router-link>
        </li>
      </template>
    </ul>
  </header>
  <nav class="tour">
    <ul class="left">
      <li><router-link to="/" :alt="t_home" class="home"><img src="/src/assets/img/icon_home.svg" alt="home icon"></router-link></li>
      <li><a href="#" class="edit" @click="openEditTab"><img src="/src/assets/img/icon_edit.svg" alt="edit icon"></a></li>
      <li class="title">{{ tour.title }}</li>
      
    </ul>
    <ul class="right">
      <li><a href="#" :class="{preview: true, disabled: spotHasSpots.length == 0}" id="link_visit" target="_blank"><img src="/src/assets/img/icon_eye.svg" alt="preview icon">Launch tour</a></li>
      <li><a :class="{download: true, disabled: spotHasSpots.length == 0}" @click="exportTour"><img src="/src/assets/img/icon_download.svg" alt="download icon">Download</a></li>
      <li><a href="#" :class="{share: true, disabled: spotHasSpots.length == 0}" @click="shareTourEl.show"><img src="/src/assets/img/icon_whiteLink.svg" alt="link icon">Share</a></li>
    </ul>
  </nav>
  <main v-if="tour.id">
    <div class="list">
      <div class="top">
        <a href="" class="btn big" data-bs-toggle="modal" data-bs-target="#uploadPanorama"><span></span>Add 360° photo</a>
        <input class="search" type="text" placeholder="Search for photo" v-model="inputSearchSpot">
      </div>
      <div class="thumbnail_list">
        <template v-if="spots.length > 0">
          <template v-for="spot, index in rSpots">
            <div :class="{'startingPoint': tour.start_id == spot.id, 'panorama': true, 'hover': spotHover == spot.id}"
              v-show="filteredSpots.includes(spot.title)" :id="'panorama_' + spot.id">
              <div class="pic">
                <div :class="{
                  'pic_box': true,
                  'draggable_bis': true,
                  'in_use': viewerPositions.includes(spot.position),
                  'no-drop': viewerPositions.includes(spot.position),
                  }" :data-index="spot.index" :data-position="spot.position">
                  <div class="start" v-show="tour.start_id == spot.id"><img src="/src/assets/img/icon_pin.svg" alt="Starting point icon"><p>starting point</p></div>
                  <router-link :to="{ name: 'editSpot', params: { spotId: spot.id }}"
                    :class="{
                      'no-drop': viewerPositions.includes(spot.position),
                      'grab': !viewerPositions.includes(spot.position)
                    }">
                    <img v-show="spot.thumbnail" :src="'/data/image/thumb/' + spot.thumbnail" alt="" @mouseenter="graphRef.highlightNode(spot.id)" @mouseout="graphRef.unHighlightNode(spot.id)">
                    <img v-show="!spot.thumbnail" src="/src/assets/img/img_placeholder3.svg" alt="" >
                  </router-link>
                </div>
                <div class="more">
                  <div class="btn-group sm">
                    <button class="btn btn-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                      <img src="/src/assets/img/icon_dots.svg" alt="">
                    </button>
                    <ul class="dropdown-menu">
                      <li class="edit">
                        <router-link class="dropdown-item" :to="{ name: 'editSpot', params: { spotId: spot.id }}">
                          <span></span>Edit parameters
                        </router-link>
                      </li>
                      <li :class="{'pin': true, 'disabled': true}">
                        <a :class="{'dropdown-item': true, 'disabled': (spot.linked!=true || tour.start_id == spot.id)}" @click="handleSetStartId(spot.id)">
                          <span></span>Set as starting point
                        </a>
                      </li>
                      <li class="add_hotspot">
                        <router-link class="dropdown-item" :to="{ name: 'editSpot', params: { spotId: spot.id }}">
                          <span></span>Add point of interest
                        </router-link>
                      </li>
                      <li class="delete">
                        <a class="dropdown-item" href="#" @click.prevent="confirmDeleteSpot(spot.id, spot.index)"><span></span>Delete</a>
                      </li>
                    </ul>
                  </div>
                  <router-link class="hotspots" :to="{ name: 'editSpot', params: { spotId: spot.id }}" v-if="spot.poi_nb > 0">
                    <span>{{ spot.poi_nb }}</span><img src="/src/assets/img/icon_whiteHotspot.svg" alt="icon hotspot">
                  </router-link>
                </div>
              </div>
              <router-link :to="{ name: 'editSpot', params: { spotId: spot.id }}">
              <p :class="{'name': true, 'active': spot.linked==true}">
                <span>{{ spot.index+1 }}</span>{{spot.title}}
              </p>
              </router-link>
            </div>
          </template>
        </template>
        <div class="panorama placeholder" v-show="spots.length == 0">
          <a>
            <div class="pic">
              <div class="pic_box">
                <div class="add">
                </div>
              </div>
            </div>
            <p class="name"></p>
          </a>
        </div>
        <br><br><br>
      </div>
    </div>
    <div class="playground">
      <div class="dropzone" style="display:flex;">
        <div class="viewA">
          <!--:class="{'name': true, 'active': spot.linked==true}"-->
          <p :class="{empty: !viewerPositions[0], active: viewerLinked[0]==true}"><span>{{ viewerPositions[0] }}</span> {{ viewerTitles[0] }}</p>
          <div class="dropbox">
            <div class="drop_container">
              <div id="dropzone0" class="dropzone1" data-viewer-id="0"></div>
              <iframe src="/v/spot.html#0" id="viewer0" data-id="0" class="viewer"></iframe>
              <div style="position: absolute; bottom: 0; right:50%; transform: translate(50%, 0px); color: #ffffff" title="Vertical or Horizontal target">
                <div class="wrapper">
                  <div class="radio_group">
                    <input type="radio" id="target0v" name="target_0" data-id="0" style="width: auto;" title="Vertical target" @click="viewers[0].dataset.target = 0" checked>
                    <label for="target_0">
                    </label>
                  </div>
                  <div class="radio_group">
                    <input type="radio" id="target0h" name="target_0" data-id="0" style="width: auto;" title="Horizontal target" @click="viewers[0].dataset.target = 1">
                    <label for="target_0">
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
        <div class="viewB">
          <p :class="{empty: !viewerPositions[1], active: viewerLinked[1]==true}"><span>{{ viewerPositions[1] }}</span> {{ viewerTitles[1] }}</p>
          <div class="dropbox">
            <div class="drop_container">
              <div id="dropzone1" class="dropzone1" data-viewer-id="1"></div>
              <iframe src="/v/spot.html#1" id="viewer1" data-id="1" class="viewer"></iframe>
              <div style="position: absolute; bottom: 0; right:50%; transform: translate(50%, 0px); color: #ffffff;" title="Vertical or Horizontal target">
                <div class="wrapper">
                  <div class="radio_group">
                    <input type="radio" id="target1v" name="target_1" data-id="1" style="width: auto;" title="Vertical target" @click="viewers[1].dataset.target = 0" checked>
                    <label for="target_1">
                    </label>
                  </div>
                  <div class="radio_group">
                    <input type="radio" id="target1h" name="target_1" data-id="1" style="width: auto;" title="Horizontal target" @click="viewers[1].dataset.target = 1">
                    <label for="target_1">
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Button if two different spots selected -->
        <template v-if="viewerSpot[0] != undefined && viewerSpot[1] != undefined && viewerSpot[0] != viewerSpot[1]">
          <!-- link -->
          <button type="button" class="link" v-if="linked" @click="disconnectSpots">
            <img src="/src/assets/img/icon_whiteLink.svg" alt="link icon">unlink views
          </button>
          <!-- unlink -->
          <button v-else type="button" class="link" @click="connectSpots">
            <img src="/src/assets/img/icon_whiteLink.svg" alt="link icon" >link views
          </button>
        </template>
        <!-- Disabled button -->
        <template v-else>
          <button type="button" class="link disabled">
              <img src="/src/assets/img/icon_whiteLink.svg" alt="link icon">link views
          </button>
        </template>
      </div>
      <!-- Ludo : 
        - add class .disabled when no pictures + change icon with icon_unlink.svg
        - add class .unlink when images need to be unlinked
      -->
      <Graph ref="graphRef" id="graph" :spotHasSpots="spotHasSpots" :spots="spots" :startId="tour.start_id" @click="handleGraphClick"/>
      <VOnboardingWrapper
        :steps="onboardingSteps"
        label-finish="Continue"
        v-if="showOnboarding"
        @exit="handleDeclineOnboarding"
        @finish="handleFinishOnboarding"
      />
    </div>
  </main>

  <div id="curtain" @click="handleTabAboutClose" style="display: none"></div>

  <!-- Modal -->
  <!-- Edit tour -->
  <div class="fade come-from-modal right show" id="about" tabindex="-1" role="dialog" aria-labelledby="aboutTour" aria-hidden="true" >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
        <h2><img src="/src/assets/img/icon_about.svg" alt="icon panorama">About tour</h2>
        <button type="button" class="close" aria-label="Close" @click="handleTabAboutClose">
          <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
        </button>
      </div>
        <div class="modal-body">
          <div>
            <h3>Thumbnail</h3>
            <ImageInput :thumbnail="tour.thumb_filename && !tour_form.delete_image ? '/data/image/' + tour.thumb_filename : '/img/img_placeholder3.svg'"  :maxSize="config.imgMaxSize" :width="config.tourThumbWidth" :height="config.tourThumbHeight" objectFit="contain" ref="thumbInputRef">
              <template #button >
                <div class="remove">
                  <a href='#' v-show="tour.thumb_filename || thumbInputRef && thumbInputRef.width" @click="handleDeleteThumb">
                    <img class="delete" src="/src/assets/img/icon_bin.svg" alt="delete icon">
                  </a>
                  <span class="btn btn-outline-dark" v-if="tour.thumb_filename || (thumbInputRef && thumbInputRef.width)">Change picture</span>
                  <span class="btn btn-outline-dark" v-else>Upload picture</span>
                </div>
              </template>
            </ImageInput>
          </div>
          <div>
            <h3>Title</h3>
            <div class="input_box">
              <input type="text" v-model="tour_form.title" :maxlength="config.tourTitlemaxSize" placeholder="Please choose a title" data-fn-target="#titleCounter" data-fn-toggle="counter" autofocus>
              <p class="counter"><span id="titleCounter"></span></p>
            </div>
          </div>
          <div>
            <h3>Description</h3>
            <div class="input_box">
              <textarea name="" v-model="tour_form.description" :maxlength="config.descriptionmaxSize" id="" cols="30" rows="10" maxlength="250" placeholder="Optional" data-fn-target="#descriptionCounter" data-fn-toggle="counter"></textarea>
              <p class="counter"><span id="descriptionCounter"></span></p>
            </div>
          </div>
          <div>
            <h3>Author</h3>
            <input type="text" v-model="tour_form.author" placeholder="Optional">
          </div>
          <div>
            <h3>License</h3>
            <select v-model="tour_form.license">
              <optgroup label="Open licenses">
                <option value="CC0-1.0">CC0 1.0</option>
                <option value="CC-BY-4.0 ">CC BY 4.0</option>
                <option value="CC-BY-SA-4.0">CC BY-SA 4.0</option>
              </optgroup>
              <optgroup label="Other licenses">
                <option value="CC-BY-NC-4.0">CC BY-NC 4.0</option>
                <option value="CC-BY-NC-SA-4.0">CC BY-NC-SA 4.</option>
                <option value="CC-BY-ND-4.0">CC BY-ND 4.0</option>
                <option value="CC-BY-NC-ND-4.0">CC BY-NC-ND 4.0</option>
                <option value="UNLICENSED" default>All rights reserved</option>
              </optgroup> 
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <a href="#" class="delete" data-bs-toggle="modal" data-bs-target="#confirmDeleteProject">
            <img data-v-eb1a3f06="" class="delete" src="/src/assets/img/icon_bin.svg" alt="delete icon">Delete project
          </a>
          <button type="button" class="btn btn-primary" @click="saveTour">Save changes</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <!-- Upload panorama -->
  
  <div class="modal modal_createTour fade" id="uploadPanorama" tabindex="-1" role="dialog" aria-labelledby="uploadPanoramaLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" v-show="newSky.step != 3">
          <button class="btn btn-secondary cancel" aria-label="Close" @click="closeSkyUploadModal">Cancel</button>
          <h1>{{ tour.title }}</h1>
          <button :class="{'btn': true, 'btn-primary': true, 'continue': true, 'disabled': !newSky.thumbnail || (newSky.error && newSky.error.length > 0)}" @click="handleNewSpot">Continue</button>
          <div>
            
          </div>

        </div>
        <div class="modal-body">
          <div class="upload" v-show="newSky.step == 1">
            <h2>Upload a 360° picture</h2>
            <ImageInput thumbnail="/img/img_placeholder3.svg" :minWidth="config.skyMinWidth" :maxSize="config.skyMaxSize" :width="config.skyMaxWidth" :height="config.skyMaxWidth/2" ref="skyInputRef" @change="gotoSkyUploadStep2">
              <template #box>
                <div class="drop">
                  <p>Drag and drop an image here or</p>
                    <span class="btn btn-primary">browse file</span>
                  <ul>
                    <li><span>Min-width:</span> {{config.skyMinWidth}} pixels</li>
<!--
                    <li><span>Max-width:</span> {{config.skyMaxWidth}}px</li>
                    <li><span>Max</span> {{ skyMaxSize }}MB</li>
-->
                  </ul>
                </div>
              </template>
            </ImageInput>
          </div>
          <!-- TODO Jess: remove the style following attribute-->
          <div class="pic" v-show="newSky.step == 2" style="display: block">
            <div>
              <img src="/src/assets/img/icon_edit2.svg" alt="Edit icon" onclick="document.getElementById('input_sky_title').focus()">
              <div class="input_box">
                <input id="input_sky_title" type="text" class="editTitle" :placeholder="newSky.placeholder" :maxlength="config.spotTitlemaxSize" data-fn-target="#title2Counter" data-fn-toggle="counter" v-model="newSky.title" autofocus>
                <p class="counter"><span id="title2Counter"></span></p>
              </div>
            </div>
            <img :src="newSky.thumbnail" class="panorama" alt="Panorama" id="newSky_preview">
            <div class="gbtn">
              <button class="btn" @click="gotoSkyUploadStep1"><img class="replace" src="/src/assets/img/icon_bin.svg" alt="Delete"></button>
            </div>

<!--
            <div v-for="error in newSky.error" class="error_message">
              <span v-if="error == 'proportions'">Invalid image: Image must be of 2:1 format</span>
              <span v-if="error == 'width'">Invalid image: Image's width must greater than <b>2048 pixels</b> ({{ newSky.width }})</span>
              <span v-if="error == 'height'">Invalid image: Image's height must be greater than 1024 pixels ({{ newSky.height }})</span>
              <span v-if="error == 'size'">Invalid image: The file size must less than {{skyMaxSize}}MB ({{ newSky.sizeMB }})</span>
              <span v-if="error == 'type'">Invalid image: This file type is not supported ({{ newSky.type }})</span>
            </div>
-->
          </div>
          <div class="pic load" v-show="newSky.step == 3" style="display: block">
            <div class="loader"></div>
            <h5 class="modal-title" id="createTourLabel">We are uploading your picture</h5>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Alert message -->
  <!-- Leave page -->

  <div class="modal modal_alert fade" id="confirmLeave" tabindex="-1" role="dialog" aria-labelledby="confirmLeaveLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmLeaveLabel">You have unsaved changes</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
              <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
            </button>
        </div>
          <div class="modal-body">
            <p>Are you sure you want to leave this page?<br>
              Changes you made will not be saved.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="handleModalConfirmLeave">Yes, leave</button>
          </div>
        </div>
    </div>
  </div>

  <!-- Modal -->
  <!-- Confirm delete picture -->

  <div class="modal modal_alert fade" id="confirmDelete" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteLabel" >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteLabel">Confirm delete</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this picture?</p>
          <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary delete" @click="handleDeleteSpot(selectedSpot.id)">Yes, delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <!-- Confirm delete project -->

  <div class="modal modal_alert fade" id="confirmDeleteProject" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteProjectLabel" >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteProjectLabel">Confirm delete</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this project?</p>
          <p>This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary delete" @click="handleDeleteTour">Yes, delete</button>
        </div>
      </div>
    </div>
  </div>

  <ShareModal ref="shareTourEl" :tour="tour"></ShareModal>

  <!-- Modal -->
  <!-- Export Tour -->
  <div class="modal fade show" id="modalExport" tabindex="-1" role="dialog" aria-labelledby="modalExportLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title" id="modalExportLabel">Export tour</h2>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
          </button>
        </div>
        <div class="modal-body">
          <div>
            <div class="pic_box">
                <div class="img_box">
                  <img v-if="tour.thumb_filename" :src="'/data/image/' + tour.thumb_filename" alt="">
                  <img v-else src="/src/assets/img/img_placeholder3.svg" alt="">
                </div>
            </div>
            <div class="about">
              <h3>{{ tour.title }}</h3>
              <p>{{ tour.description }}</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="link">
            <a class="btn link btn-primary" target="_blank" :href="'/exportTour.php?tour_id=' + tour.id" >Download Zip</a>
          </div> 
          <p>File size: {{ tourSizeMB }}MB</p>
        </div>
      </div>
    </div>
  </div>

  <OnboardingModal ref="modalOnboardingRef" @start="handleSetOnboarding"/>

</template>

<style scoped>
  table {
    transform: scale(1);
  }
  td {
    border: 2px solid grey;
    background-color: #eeeeee;
    padding: 1em;
  }
  .no-drag {
    pointer-events: none;
  }
  /*.draggable_bis {
    cursor: grab;
  }*/
  .drop_container{
    position: relative;
    height:300px;
    width: 100%;
    display: inline-block;
    padding: 0;
    border: 2px solid black;
  }
  .viewer_container{
    display: inline-block;
  }
  .dropzone1 {
    z-index: -1;
    position: absolute;
    width: 100%;
    height: 100%;
  }
  iframe {
    width: 100%;
    height: 100%;
    border-width: 0;
  }
  #curtain {
    background-color: black;
    opacity: .5;
    width: 100vw;
    height: 100vh;
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1000;
  }
  .viewer {
    position: relative;
  }

  /* Required by the onboarding JS library */
  .dropdown-menu {
    z-index: 10;
  }

  /*TODO Jess: remove style below*/
  .pic_box{
    color: rgb(0 0 0 / 0%);
    text-align: center;
  }
  .pic_box:hover {
    color: rgb(0 0 0 / 70%);
  }
  .grab:hover {
    cursor: grab;
  }
  .no-drop:hover {
    cursor: no-drop;
  }
  iframe.hover {
    opacity: 0.5;
  }
  div.panorama.hover{
    background-color: #ddd;
  }
</style>
