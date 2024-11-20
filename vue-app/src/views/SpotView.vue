<script setup>
import { RouterLink, RouterView } from 'vue-router';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { user } from '/src/state.js';
import { config } from '/src/assets/js/config.js';
import {
  fetchGet,
  fetchPost,
  fetchForm,
  fetchTour,
  fetchSpots,
  fetchSpot,
  fetchPois,
  fetchSkies,
  fetchCSRF,
  fetchDeleteTourJSON,
  fetchSetOnboardingSession,
  fetchSetOnboardingDatabase,
  logout,
  fnCounter,
  resizeImage,
  getCookie,
  getHydratedSpot,
  testSkyImage,
  initImageProperties,
  getFileProperties,
} from '/src/assets/js/functions.js';
import VOnboardingWrapper from '../components/VOnboardingWrapper.vue';
import OnboardingModal from '../components/OnboardingModal.vue';
import HelpButton from '../components/HelpButton.vue';
import ImageInput from '../components/ImageInput.vue';
import {steppedScale} from '/src/assets/js/stepped-scale.js';
import {splitImage} from '/src/assets/js/split-image.js';
import {onboardingSteps as onboardingStepsSpot} from '/src/assets/js/onboarding-spot.js';
import {onboardingSteps as onboardingStepsPoi} from '/src/assets/js/onboarding-poi.js';

const route = useRoute();
const router = useRouter();
//~ const tour = ref({});
const spot = ref({});
const pois = ref({});
const selectedPoi = ref({});
var poiPreviewTimer = null;
const skies = ref({});
const csrf = ref({});
const skyInputRef = ref(null);
const poiInputRef = ref(null);
var viewer = null;
const rightPanel = ref("spot");
const onboardingSpot = ref(false);
const onboardingPoi = ref(false);
const modalOnboardingRef = ref(null);
const action = ref(null);

/*
 * True if this tour is suitable for the onboarding app
 */
const isOnboarding = computed(() => {
  return (
    spot.value.creation_date == config.demoDate &&
    spot.value.modification_date == config.demoDate
  );
})

/*
 * Reactivate the onboarding
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

/*
 * Reactivate the onboarding
 */
async function handleHelpButton(){
  if (isOnboarding.value){
    user.value.onboarding = 1;
    fetchSetOnboardingSession(1, csrf.value);
    fetchSetOnboardingDatabase(1, csrf.value);
    // This will restart the onboarding
    onboardingPoi.value=true;
    onboardingSpot.value=true;
  } else {
    modalOnboardingRef.value.show();
  }
}

function initSelectedPoi(poi = null){
  // If poi is given, copy the POI's data to selectedPoi
  if (poi){
    selectedPoi.value = {
      id: poi.id,
      icon: poi.icon,
      title: poi.title,
      text: poi.text,
      image_id: poi.image_id,
      image_filename: poi.image_filename,
      image_width: poi.image_width,
      image_height: poi.image_height,
      spot_id: poi.spot_id,
      x: poi.x,
      y: poi.y,
      layer: poi.layer,
      template: poi.template,
      creation_date: poi.creation_date,
      modification_date: poi.modification_date,
      modified: false,
    }
  } else {
    selectedPoi.value = {
      id: null,
      icon: null,
      title: null,
      text: null,
      image_id: null,
      image_filename: null,
      spot_id: null,
      x: null,
      y: null,
      layer: null,
      template: null,
      creation_date: null,
      modification_date: null,
      modified: false,
    };
  }
}

async function handleSaveSpot (){
  let formData = new FormData();
  formData.append("id", route.params.spotId);
  formData.append("title", spot.value.title);
  if (skyInputRef.value.file) {
    let preload = await steppedScale(skyInputRef.value.file, config.skyPreloadWidth, config.skyPreloadHeight);
    let thumb = await steppedScale(skyInputRef.value.file, config.skyThumbWidth, config.skyThumbHeight, 'contain');

    let cols = 16;
    let rows = 8;
    let tiles = await splitImage(skyInputRef.value.file, cols, rows);
    formData.append("cols", cols);
    formData.append("rows", rows);
    let counter = 0;
    tiles.forEach(function(tile){
      formData.append("tile[]", tile);
      console.log(counter + " added")
      counter++;
    });
    formData.append("thumb", thumb);
    formData.append("preload", preload);
    formData.append("thumb", thumb);
  }
  formData.append("csrf", csrf.value);
  await fetchForm("/editSpot.php", formData, spot);
  fetchDeleteTourJSON(spot.value.tour_id, csrf.value);
  router.push({ name: 'tour', params: { tourId: spot.value.tour_id } });
}

async function handleSavePoi (){
  if (!selectedPoi.value.title){
    selectedPoi.value.showErrors = true;
    return;
  }
  let formData = new FormData();
  formData.append("id", selectedPoi.value.id);
  formData.append("spot_id", route.params.spotId);
  formData.append("icon", selectedPoi.value.icon);
  formData.append("title", selectedPoi.value.title);
  formData.append("text", selectedPoi.value.text);
  formData.append("x", selectedPoi.value.x);
  formData.append("y", selectedPoi.value.y);
  formData.append("layer", selectedPoi.value.layer);
  formData.append("template", selectedPoi.value.template);
  if(poiInputRef.value.file){
    formData.append("file", poiInputRef.value.file);
  }
  formData.append("delete_image", selectedPoi.value.delete_image == true ? 1 : 0 );
  formData.append("csrf", csrf.value);
  await fetchForm("/savePoi.php", formData);
  fetchDeleteTourJSON(spot.value.tour_id, csrf.value);
  rightPanel.value = "spot";
  action.value = null;
  let waitPois = fetchPois(pois, route.params.spotId);
  //~ fetchCSRF(csrf);
  initSelectedPoi();
  await waitPois;
  sendPois();
}

function handleDeletePoiImage(){
  // Deletes saved image
  selectedPoi.value.delete_image = true;
  selectedPoi.value.image_filename = null;
  selectedPoi.value.image_width = null;
  selectedPoi.value.image_height = null;
  // Ignores previously selected image
  poiInputRef.value.reset();
  selectedPoi.value.modified = true;
  updatePoiPreview();
}

/*
 * Executed when a POI input changes. It will update
 * the POI's preview after a delay.
 */
function updatePoiPreview(){
  clearTimeout(poiPreviewTimer);
  poiPreviewTimer = setTimeout(function(){sendPoi(true)}, 400);
  selectedPoi.value.modified = true;
  // Reset error display
  selectedPoi.value.showErrors = false;
}

function handleChangePoiImage(ev){
  selectedPoi.value.modified = true;
  selectedPoi.value.delete_image = false;
  sendPoi(true);
}

async function handleChangeImage(ev){
  let preview = JSON.parse(JSON.stringify(spot.value));
  // The A-frame sky primitive doesn't accept a blob, then we create a dataURL
  let reader = new FileReader();
  reader.addEventListener(
    "load",
    () => {
      preview.skies = [{
        filename: reader.result,
        height: ev.height,
        width: ev.width,
        id: 0,
        image_id: 0,
        layer: 0,
        modification_date: "2024-01-01 00:00:00",
        creation_date: "2024-01-01 00:00:00",
        spot_id: spot.value.id
      }];
      sendSpot(preview, viewer);
    },
    false,
  );
  reader.readAsDataURL(ev.file);
}

/*
* Deletes the POI and refreshes the view
*/
async function handleDeletePoi (){
  let formData = new FormData();
  formData.append("id", selectedPoi.value.id);
  formData.append("csrf", csrf.value);
  await fetchForm("/deletePoi.php", formData, spot);
  fetchDeleteTourJSON(spot.value.tour_id, csrf.value);
  // Update the shown POIs
  pois.value = pois.value.filter((poi) => poi.id != selectedPoi.value.id);
  sendPois();
  
  let modalconfirmDeletePoi = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmDeletePoi"));
  modalconfirmDeletePoi.hide();
  //~ fetchCSRF(csrf);
  initSelectedPoi();
  poiInputRef.value.reset();
}

/*
 * Deletes the spot, route to tourView
 */
async function handleDeleteSpot(){
  let modalEl = document.getElementById("confirmDeleteSpot");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  let tourId = spot.value.tour_id;
  let formData = new FormData();
  formData.append("id", route.params.spotId);
  formData.append("csrf", csrf.value);
  await fetchForm("/deleteSpot.php", formData, spot);
  fetchDeleteTourJSON(spot.value.tour_id, csrf.value);

  if (spot.value.success !== undefined){
    //~ fetchCSRF(csrf);
    modalEl.addEventListener('hidden.bs.modal', function (event) {
      router.push({ name: 'tour', params: { tourId: tourId } });
    });
  }

  // Let's close the modal
  modal.hide();
}

/*
* Send spot to the 360° viewer
*/
async function sendSpot(spot, viewer, destY=null){
  viewer.contentWindow.postMessage({'type': 'spot', 'value': JSON.stringify(spot)});
  viewer.setAttribute("data-spot-id", spot.id);
  if (destY){
    // We rotate the camera to the target position
    getHotspotsComponent(viewer).rotateCamera(destY);
  }
}

/*
* Send selectedPoi to the 360° viewer
*/
async function sendPoi(open){
  console.log("sendPoi");
  let poi = selectedPoi.value;
  let otherPois = pois.value.filter((p) => p.id != poi.id);
  // Force the POI template
  poi.template = bestTemplate(selectedPoi, poiInputRef.value.img);
  // if no POI is selected, send an empty array
  if (!poi.icon){
    viewer.contentWindow.postMessage({'type': 'pois', 'value': JSON.stringify([])});
    return;
  }
  // If a new image has been selected, show this image in preview
  if (poiInputRef.value.file && poiInputRef.value.img){
    poi.image_filename = poiInputRef.value.img;
    poi.image_width = poiInputRef.value.width;
    poi.image_height = poiInputRef.value.height;
  }
  poi.state = open==true ? "open" : "closed";
  viewer.contentWindow.postMessage({'type': 'pois', 'value': JSON.stringify([poi].concat(otherPois))});
  //~ if (destY){
    //~ // We rotate the camera to the target position
    //~ getHotspotsComponent(viewer).rotateCamera(destY);
    //~ }
}

/*
* Send all POIs to the 360° viewer
*/
async function sendPois(){
  if (pois.value.length > 0){
    viewer.contentWindow.postMessage({'type': 'pois', 'value': JSON.stringify(pois.value)});
  }
}

/*
* Select the POI and open the edit modal
*/
function handleEditPoi(event){
  let poi = pois.value[event.currentTarget.dataset.index];
  if ( poi.id != event.currentTarget.dataset.id ){
    console.log("POI ID error");
    return;
  }
  initSelectedPoi(poi);
  rightPanel.value = "poi";
  sendPoi(true);
  viewer.contentWindow.postMessage({'type': 'rotate', 'value': poi.y});
}

/*
 * Select the POI and display its icon
 */
function showPoi(event){
  let poi = pois.value[event.currentTarget.dataset.index];
  if ( poi.id != event.currentTarget.dataset.id ){
    console.log("POI ID error");
    return;
  }
  initSelectedPoi(poi);
  //sendPoi(false);
  viewer.contentWindow.postMessage({'type': 'rotate', 'value': poi.y});
}

/*
* Select the POI and show the confirmation modal
*/
function handleConfirmDeletePoi(event){
  let modalconfirmDeletePoi = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmDeletePoi"));
  let poi = pois.value[event.currentTarget.dataset.index];
  if ( poi.id != event.currentTarget.dataset.id ){
    console.log("POI ID error");
    return;
  }
  initSelectedPoi(poi);
  modalconfirmDeletePoi.show();
}

// Show confirmation modal on POI close
function handleConfirmClosePOI(){
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmClosePOI"));
  if (selectedPoi.value.modified){
    modal.show();
  } else {
    initSelectedPoi();
    rightPanel.value = "spot";
    action.value = null;
    poiInputRef.value.reset();
    sendPois();
  }
}

// Show confirmation modal on Spot close
function handleConfirmCloseSpot(){
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmLeave"));
  if (spot.value.modified){
    // Are you sure?
    modal.show();
  } else {
    // Go back to the Tour page
    router.push({ name: 'tour', params: { tourId: spot.value.tour_id } });
  }
}

// Close the POI panel, discard changes
function closePOI(){
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmClosePOI"));
  modal.hide();
  initSelectedPoi();
  poiInputRef.value.reset();
  poiInputRef.value.reset();
  rightPanel.value = "spot";
  action.value = null;
  sendPois();
}

// Go back to the Tour page
function closeSpot(){
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmLeave"));
  modal.hide();
  router.push({ name: 'tour', params: { tourId: spot.value.tour_id } });
}

/*
 * Define which template to use for this POI
 */
function bestTemplate(poiRef){
  let width, height;
  if (poiInputRef.value.file && poiInputRef.value.width) {
    width = poiInputRef.value.width;
    height = poiInputRef.value.height;
    console.log("new img");
  } else if (poiRef.value.image_width && poiRef.value.image_height ){
    width = poiRef.value.image_width;
    height = poiRef.value.image_height;
    console.log("old img");
  }

  if (!width || !height || poiRef.value.delete_image) {
    // No image: Template title and text
    return 3;
  }
  if ( width > height) {
    // Template horizontal image and text
    return 1;
  }
  // Template vertical image and text
  return 2;
}

function handleNewPoi(position){
  rightPanel.value = "poi";
  initSelectedPoi();
  selectedPoi.value.icon = "image";
  selectedPoi.value.title = "";
  selectedPoi.value.text = "";
  selectedPoi.value.spot_id = route.params.spotId;
  selectedPoi.value.x = position.x;
  selectedPoi.value.y = position.y;
  selectedPoi.value.layer = 0;
  sendPoi();
}

async function handleMessage(event){
  if (
  event.origin !== window.location.origin
  ) return;
  //console.log("Msg type: " + event.data.type);
  switch (event.data.type) {
    case 'ready':
      if (route.params.spotId){
        sendSpot(await getHydratedSpot(spot.value), viewer);
        sendPois();
        ack(event.data.type);
      }
      break;
    case 'click-sky':
      // Open a new POI panel only if we are on the spot 
      if (rightPanel.value == "spot"){
        if (action.value == "newPoi"){
          handleNewPoi(event.data.value);
        } else {
          sendPois();
        }
      } else {
        sendPoi();
      }
      break;
    case 'ack':
      console.debug("ack: " + event.data.value + " *from* " + event.data.sender);
      break;
  }
}

function ack(message){
  viewer.contentWindow.postMessage({
    type: "ack",
    value: message,
    sender: window.location.hash,
  });
}

function handleFinishSpot(){
  setTimeout(function(){
    // Disable the spot onboarding
    onboardingSpot.value=false;
    // Set the id of the POI we want to edit
    let event = {
      "currentTarget": {
        "dataset": {
          "index": 0,
          "id": pois.value[0].id
        }
      }
    };
    // Go to the edit POI tab and continue the onboarding
    handleEditPoi(event);
  }, 500);
}

function handleFinishPoi(){
  setTimeout(function(){onboardingPoi.value=false;}, 500);
}

/*
 * Decline the onboarding for the session length and hide the modal
 */
async function handleDeclineOnboarding(){
  user.value.onboarding = 0;
  let result = await fetchSetOnboardingSession(0, csrf.value);
}

async function main(){
  initSelectedPoi();
  viewer = document.getElementById("viewer");

  if (route.params.spotId){
    // Edit spot
    let wait_skies = fetchSkies(skies, route.params.spotId);
    let wait_spot = fetchSpot(spot, route.params.spotId);
    let wait_fetchPois = fetchPois(pois, route.params.spotId);
    //~ await fetchTour(tour, spot.value.tour_id);
    await wait_skies;
    await wait_spot;
    if(spot.value.error == "Spot not found"){
      router.push({ name: 'home' });
    }
    spot.value.skies = skies.value;
    // Now load the viewer
    viewer.src = "/v/spot.html?ready=1#spotview";
  }

  // fn counters
  var counterTriggerList = [].slice.call(document.querySelectorAll('[data-fn-toggle="counter"]'));
  counterTriggerList.forEach(function(el){
    fnCounter(el);
  });

  fetchCSRF(csrf);

  // Launch onboarding
  setTimeout(function(){
    if (user.value.onboarding == 1 &&
      isOnboarding.value
    ){
      onboardingSpot.value = true;
      onboardingPoi.value = true;
    }
  }, 1500);

  console.log("end main");
};

onMounted(() => {
  window.addEventListener("message", handleMessage);
  main();
  console.log("end mounted");
});

onUnmounted(() => {
  window.removeEventListener("message", handleMessage);
});
</script>

<template>
  <HelpButton @click="handleHelpButton"/>
  <main class="panorama">
    <router-link class="back" v-if="spot.tour_id && rightPanel=='spot'" :to="{ name: 'tour', params: { tourId: spot.tour_id }}">
      <img src="/src/assets/img/icon_back.svg" alt="icon back to tour">
    </router-link>
    <a class="back" v-if="rightPanel=='poi'" @click="handleConfirmClosePOI">
      <img src="/src/assets/img/icon_back.svg" alt="icon back to tour">
    </a>
    <iframe src="/v/blank.html" id="viewer"></iframe>
    <!--
      <img src="/src/assets/img/del/panorama.png" alt=""  data-bs-toggle="modal" data-bs-target="#addHotspot">
    -->

    <!-- Modal -->
    <!-- Edit Panorama -->

    <div class="side" id="aboutPanorama" v-show="rightPanel == 'spot'">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h2><img src="/src/assets/img/icon_panorama.svg" alt="icon panorama">360° Photo</h2>
            <button type="button" class="close" aria-label="Close" @click="handleConfirmCloseSpot">
              <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
            </button>
          </div>
          <div class="modal-body">
            <div>
              <h3>Name</h3>
              <div class="input_box">
                <input type="text" placeholder="" v-model="spot.title" @change="spot.modified = true" :maxlength="config.spotTitlemaxSize" data-fn-target="#titleCounter" data-fn-toggle="counter">
                <p class="counter"><span id="titleCounter"></span></p>
              </div>
            </div>
            <div class="image">
              <ImageInput :thumbnail="skies[0] ? '/data/image/thumb/' + skies[0].filename : '/img/img_placeholder.svg'" :maxSize="config.skyMaxSize" :width="config.skyMaxWidth" :height="config.skyMaxWidth/2" skyMinWidth="2048" ref="skyInputRef" @change="handleChangeImage">
                <template #button>
                  <span class="btn btn-outline-dark">Change 360° photo</span>
                </template>
              </ImageInput>
            </div>
            <div>
              <h3>Points of interest</h3>
              <div class="box_poi">
                <div class="listHotspots" v-if="pois.length > 0">
                  <div class="poi" v-for="poi, index in pois">
                    <div class="left">
                      <img :src="'/poi_icons/' + poi.icon +'.svg'" alt="POI about" class="hotspotIcon" @click.prevent="showPoi" :data-id="poi.id" :data-index="index">
                      <p @click.prevent="showPoi" :data-id="poi.id" :data-index="index">{{ poi.title }}</p>
                    </div>
                    <div>
                      <a @click.prevent="handleEditPoi" :data-index="index" :data-id="poi.id">
                        <img class="edit" src="/src/assets/img/icon_edit.svg" alt="edit icon">
                      </a>
                      <a @click.prevent="handleConfirmDeletePoi" :data-index="index" :data-id="poi.id">
                        <img class="delete" src="/src/assets/img/icon_bin.svg" alt="delete icon">
                      </a>
                    </div>
                  </div>
                </div>
                <div class="hotspots">
                  <div :class="{'addHot': true, 'disabled': action == 'newPoi'}" @click="action = 'newPoi'">
                    <span></span>
                    <img src="/src/assets/img/icon_hotspotAdd2.svg" alt="icon hotspot">
                  </div>
                  
                  <p v-show="action != 'newPoi'" @click="action = 'newPoi'">Add a new <span>point of interest</span></p>
                  <p v-show="action == 'newPoi'" class="click"><span>Click on the 360° view</span> to <br> place a point of interest</p>
                  <button v-show="action == 'newPoi'" type="button" class="btn btn-secondary" @click.prevent="action = null">Cancel</button>
                </div>
              </div>

            </div>
          </div>
          <div class="modal-footer">
            <a href="#" class="delete" data-bs-toggle="modal" data-bs-target="#confirmDeleteSpot">
              <img data-v-eb1a3f06="" class="delete" src="/src/assets/img/icon_bin.svg" alt="delete icon">Delete 360° photo
            </a>
            <button type="button" class="btn btn-primary" @click="handleSaveSpot">Save</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <!-- Add Hotspots -->

    <div class="side" id="addHotspot" v-show="rightPanel == 'poi'">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h2><img src="/src/assets/img/icon_hotspot2.svg" alt="icon panorama">Points of interest</h2>
            <button type="button" class="close" aria-label="Close" @click="handleConfirmClosePOI">
              <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
            </button>
          </div>
          <div class="modal-body">
            <div>
              <h3>Image <span>optional</span></h3> 
              <ImageInput :thumbnail="selectedPoi.image_filename ? '/data/image/' + selectedPoi.image_filename : '/img/img_placeholder3.svg'" :maxSize="config.imgMaxSize" :width="config.imgMaxWidth" :height="config.imgMaxWidth" ref="poiInputRef" @change="handleChangePoiImage(true)">
                <template #button >
                  <div class="remove">
                    <a href='#' v-show="selectedPoi.image_filename || poiInputRef && poiInputRef.width" @click="handleDeletePoiImage">
                      <img class="delete" src="/src/assets/img/icon_bin.svg" alt="delete icon">
                    </a>
                    <span class="btn btn-outline-dark" v-if="selectedPoi.image_filename || (poiInputRef && poiInputRef.width)">Change picture</span>
                    <span class="btn btn-outline-dark" v-else>Upload picture</span>
                  </div>
                </template>
              </ImageInput>
            </div>
            <div>
              <h3>Icon</h3>
              <div class="iconHotspots">
                <ul>
                  <li><input type="radio" name="hotpsot" id="cb1" value="idea" v-model="selectedPoi.icon" @change="sendPoi(true)" />
                    <label for="cb1"><img src="/poi_icons/idea.svg" /></label>
                  </li>
                  <li><input type="radio" name="hotpsot" id="cb2" value="image" v-model="selectedPoi.icon" @change="sendPoi(true)" />
                    <label for="cb2"><img src="/poi_icons/image.svg" /></label>
                  </li>
                  <li><input type="radio" name="hotpsot" id="cb3" value="question" v-model="selectedPoi.icon" @change="sendPoi(true)" />
                    <label for="cb3"><img src="/poi_icons/question.svg" /></label>
                  </li>
                  <li><input type="radio" name="hotpsot" id="cb4" value="place" v-model="selectedPoi.icon" @change="sendPoi(true)" />
                    <label for="cb4"><img src="/poi_icons/place.svg" /></label>
                  </li>
                  <li><input type="radio" name="hotpsot" id="cb5" value="text" v-model="selectedPoi.icon" @change="sendPoi(true)" />
                    <label for="cb5"><img src="/poi_icons/text.svg" /></label>
                  </li>
                </ul>
              </div>
            </div>
            <div>
              <h3>Title <span class="required">*</span></h3>
              <!-- Jess : Add a CSS for the error class on input below -->
              <input type="text" placeholder="Cool title" :class="{'error': selectedPoi.title == ''}" v-model="selectedPoi.title" @input="updatePoiPreview">
              <div class="error">
                <span v-show="!selectedPoi.title && selectedPoi.showErrors">Please add a title to your hotspot</span>
              </div>

            </div>
            <div>
              <h3>Description <span>optional</span></h3>
              <textarea name="description" id="" placeholder="Add a description" required cols="30" rows="10" v-model="selectedPoi.text" @input="updatePoiPreview"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-tertiary" @click="handleConfirmClosePOI">Back</button>
            <div class="right">
              <button type="button" class="btn btn-primary" data-bs-dismiss="modal" @click.prevent="handleSavePoi">Save</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert message -->
    <!-- Leave Spot -->
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
            <button type="button" class="btn btn-primary" @click="closeSpot">Yes, leave!!</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert message -->
    <!-- Leave POI -->
    <div class="modal modal_alert fade" id="confirmClosePOI" tabindex="-1" role="dialog" aria-labelledby="confirmClosePOILabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmClosePOILabel">You have unsaved changes</h5>
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
            <button type="button" class="btn btn-primary" @click="closePOI">Yes, leave</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert message -->
    <!-- Confirm delete POI -->
    <div class="modal modal_alert fade" id="confirmDeletePoi" tabindex="-1" role="dialog" aria-labelledby="confirmDeletePoiLabel" >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmDeletePoiLabel">Confirm delete</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
              <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this point of interest? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary delete" @click="handleDeletePoi(selectedPoi.id)">Yes, delete</button>
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
            <button type="button" class="btn btn-primary">Yes, leave</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert message -->
    <!-- Confirm delete Spot -->
    <div class="modal modal_alert fade" id="confirmDeleteSpot" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteSpotLabel" >
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmDeleteSpotLabel">Confirm delete</h5>
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
              <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
            </button>
          </div>
          <div class="modal-body">
            <p>Are you sure you want to delete this panorama? This action cannot be undone.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary delete" @click="handleDeleteSpot()">Yes, delete</button>
          </div>
        </div>
      </div>
    </div>
    <!-- Onboarding Spot -->
    <VOnboardingWrapper
      :steps="onboardingStepsSpot"
      label-finish="Continue"
      v-if="onboardingSpot && rightPanel=='spot'"
      @exit="handleDeclineOnboarding"
      @finish="handleFinishSpot"
    />
    <!-- Onboarding POI -->
    <VOnboardingWrapper
      :steps="onboardingStepsPoi"
      v-if="onboardingPoi && rightPanel=='poi'"
      @exit="handleDeclineOnboarding"
      @finish="handleFinishPoi"
    />
    <OnboardingModal ref="modalOnboardingRef" @start="handleSetOnboarding"/>
  </main>

</template>

<style scoped>
table {
  width: 100%;
  background-color: #dddddd;
}
td {
  border: 2px solid grey;
  background-color: #eeeeee;
  padding: 1em;
}
td.left {
  width: 30%;
}
td.right {
  width: 70%;
}
iframe {
  width: 100%;
  height: 99vh;
  border-width: 0;
}
</style>
