<script setup>
import { RouterLink, RouterView, useRoute, useRouter} from 'vue-router';
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { user, tours } from '/src/state.js';
import { config } from '/src/assets/js/config.js';
import {
  fetchGet,
  fetchPost,
  fetchUser,
  fetchNewDemo,
  fetchTours,
  fetchTourSize,
  fetchForm,
  fetchCSRF,
  fetchDeleteTour,
  logout,
  fetchSetOnboardingDatabase,
  fetchSetOnboardingSession,
  getFileProperties,
  testImage,
  fnCounter,
  getCookie
} from '/src/assets/js/functions.js';
import VOnboardingWrapper from '../components/VOnboardingWrapper.vue';
import HelpButton from '../components/HelpButton.vue';
import {onboardingSteps} from '/src/assets/js/onboarding-home.js';
import ImageInput from '../components/ImageInput.vue';
import ShareModal from '../components/ShareModal.vue';

const router = useRouter()
const route = useRoute()

const copied = ref(null);
const csrf = ref({});
const demoTourId = ref(false);
const importedTour = ref({});
const onboarding = ref(false);
const newTour = ref({});
const selectedTour = ref({title: "noname"});
const shareTourEl = ref(null);
const thumbInputRef = ref({})
const tooltipList = ref([]);

const rTours = computed(() => {
  if (Array.isArray(tours.value)) {
    let r = tours.value.slice();
    return r.reverse();
  }
  return [];
})

const imgMaxSize = Math.round(config.imgMaxSize/1000000);

function resetNewTour(){
  newTour.value.title = "";
  newTour.value.description = "";
  newTour.value.step = 1;
}

async function createNewTour(){
  newTour.value.step = 4;
  let modalCreateTour = bootstrap.Modal.getOrCreateInstance(document.getElementById("createTour"));
  setTimeout(() => {
    modalCreateTour.hide();
    if (tour.value.id){
      //redirect to new tour
      router.push({ name: 'tour', params: { tourId: tour.value.id } });
    }
  }, "2000");

  const tour = ref({});
  let formData = new FormData();
  formData.append("title", newTour.value.title ? newTour.value.title : "");
  formData.append("description", newTour.value.title ? newTour.value.description : "");
  formData.append("author", newTour.value.author ? newTour.value.author : "");
  formData.append("license", newTour.value.license ? newTour.value.license : "");
  formData.append("csrf", csrf.value);
  if (thumbInputRef.value.file){
    formData.append("file", thumbInputRef.value.file);
  }
  await fetchForm("/saveTour.php", formData, tour);
  console.debug(tour.value.id);
  fetchCSRF(csrf);
}

// Copy text to clipboard
async function clipboard(filename) {
  try {
    await navigator.clipboard.writeText( 'https://360.skilltech.tools/v/' + filename );
    copied.value = filename;
  } catch (error) {
    console.error(error.message);
  }
}

/*
 * Show the shareTour modal
 */
function handleShareTour(event){
  selectedTour.value = tours.value.filter((tour) => tour.id == event.currentTarget.dataset.tourId)[0];
  console.log(selectedTour.value);
  shareTourEl.value.show();
}

/*
 * Show the exportTour modal
 */
async function exportTour(event){
  // Select the displayed tour
  selectedTour.value = tours.value.filter((tour) => tour.id == event.currentTarget.dataset.tourId)[0];
  // Show the modal
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("modalExport"));
  modal.show();
  // Refresh the tour size
  let tourSize = ref({});
  await fetchTourSize(tourSize, selectedTour.value.id);
  selectedTour.value.size = tourSize.value.size;
}

const selectedTourSize = computed(() => {
  // returns the rounded value in MB
  if (selectedTour.value.size) {
    return Math.round(selectedTour.value.size/1000000);
  }
  return null;
})

/*
 * Show the confirmDelete modal
 */
function confirmDelete(event){
  event.preventDefault();
  selectedTour.value = tours.value.filter((tour) => tour.id == event.currentTarget.dataset.tourId)[0];
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmDelete"));
  modal.show();
}

/*
 * Deletes the selected tour
 */
async function deleteTour(event){
  event.preventDefault();
  let tourId = event.currentTarget.dataset.tourId;
  await fetchDeleteTour(tourId, csrf.value);
  await fetchTours(tours);
  //Close the modal
  let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("confirmDelete"));
  modal.hide();
  fetchCSRF(csrf);
}


/*
 * Start the onboarding application
 */
async function handleStartOnboarding(){
  // We search for an unmodified demo tour in the collection
  let demo = tours.value.filter((tour) => tour.creation_date == config.demoDate);

  if (demo.length > 0 && demo[demo.length - 1].modification_date == config.demoDate){
    demoTourId.value = demo[demo.length - 1].id;
  } else {
    demoTourId.value = await fetchNewDemo(csrf.value);
    await fetchTours(tours);
  }
  let modalEl = document.getElementById("modalOnboarding");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  if (demoTourId.value != false){
    
    modalEl.addEventListener('hidden.bs.modal', function (event) {
      onboarding.value = true;
    });
  }
  modal.hide();
}

/*
 * Disable the onboarding permanently for this user
 */
async function handleDisableOnboarding(){
  user.value.onboarding = 0;
  let modalEl = document.getElementById("modalOnboarding");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.hide();
  let result = await fetchSetOnboardingDatabase(0,csrf.value);
}

/*
 * Decline the onboarding for the session length and hide the modal
 */
async function handleDeclineOnboarding(){
  user.value.onboarding = 0;
  onboarding.value = false;
  let modalEl = document.getElementById("modalOnboarding");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.hide();
  let result = await fetchSetOnboardingSession(0, csrf.value);
}

/*
 * Switch to the next vue and continue the onboarding
 */
async function handleFinishOnboarding(){
  onboarding.value = false;

  console.log(demoTourId.value);
  setTimeout(() => {
    if (demoTourId.value){
      //redirect to the demo tour
      router.push({ name: 'tour', params: { tourId: demoTourId.value } });
    }
  }, "500");
}

/*
 * Reactivate the onboarding
 */
async function handleSetOnboarding(){
  user.value.onboarding = 1;
  fetchSetOnboardingSession(1, csrf.value);
  fetchSetOnboardingDatabase(1, csrf.value);
  let modalEl = document.getElementById("modalOnboarding");
  let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();
}

/*
 *
 */
async function importTour(){
  const tour = ref({});
  let formData = new FormData();
  formData.append("password", importedTour.value.password ? importedTour.value.password : "");
  formData.append("filename", importedTour.value.filename ? importedTour.value.filename : "");
  formData.append("csrf", csrf.value);
  await fetchForm("/copyTour.php", formData, tour);
  console.debug(tour.value);

  if (tour.value.id){
    // Close the modal and redirect to the copied tour
    let modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("importTour"));
    setTimeout(() => {
      modal.hide();
      importedTour.value = {};
        //redirect to new tour
        router.push({ name: 'tour', params: { tourId: tour.value.id } });
    }, "1000");
  } else {
    alert("Check the tour identifier and password");
  }
}

async function updateTours(){
  await fetchTours(tours);
  // If a share modal is open, we update its tour
  if (selectedTour.value.id){
    selectedTour.value = tours.value.filter((tour) => tour.id == selectedTour.value.id)[0];
  }
  console.log("tours updated");
}

async function main(){
  await fetchTours(tours);
  fetchCSRF(csrf);
  resetNewTour();

  // Bootstrap tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipList.value = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  });

  // fn counters
  var counterTriggerList = [].slice.call(document.querySelectorAll('[data-fn-toggle="counter"]'));
  counterTriggerList.forEach(function(el){
    fnCounter(el);
  });

  setTimeout(function(){
    if (user.value.onboarding == 1){
      let modalEl = document.getElementById("modalOnboarding");
      let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();
    }
  }, 1500);

};
  
onMounted(() => {
  main();
});

onUnmounted(() => {
  // Hide all tooltips before loading a new view
  tooltipList.value.forEach((tooltip) => {tooltip.hide();});
});



</script>

<template>
  <HelpButton @click="handleSetOnboarding"/>
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
  <nav>
      <div class="container">
          <ul>
              <li><a href="#" class="active">My tours</a></li>
              <li><span data-bs-toggle="tooltip" data-bs-placement="top" title="Coming soon!"><a href="#" class="disabled">Library</a></span></li>
          </ul>
      </div>
  </nav>
  <main>
    <div class="container">
      <a href="" class="btn big" data-bs-toggle="modal" id="btn-create-tour" data-bs-target="#createTour">
        <span></span>Create new tour
      </a>
      &nbsp;
      <a href="" class="btn big import" data-bs-toggle="modal" id="btn-import-tour" data-bs-target="#importTour">
        <span></span>Import tour
      </a>
      <div class="tour_row" v-if="user.email">
        <div :class="{tour: true, demo: tour.id==demoTourId}" v-for="tour in rTours">
          <h2>{{ tour.title }}</h2>
          <div class="thumbnail">
              <router-link :to="{ name: 'tour', params: { tourId: tour.id }}">
              <img v-if="tour.thumb_filename" :src="'/data/image/' + tour.thumb_filename" alt="">
              <img v-else src="/src/assets/img/icon_panorama2.svg" alt="" class="placeholder">
              </router-link>
          </div>
          <div class="btn-group">
            <button class="btn btn-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="/src/assets/img/icon_dots.svg" alt="">
            </button>
            <ul class="dropdown-menu">
              <li class="edit">
                <router-link :to="{ name: 'tour', params: { tourId: tour.id }}" class="dropdown-item">
                  <span></span>Edit tour
                </router-link>
              </li>
              <li class="launch">
                <a :class="{'dropdown-item': true}" :href="'https://360.skilltech.tools/v/' + tour.filename" target="_blank">
                  Launch tour
                </a>
              </li>
              <li class="share">
                <a class="dropdown-item" href="#" :data-tour-id="tour.id" @click="handleShareTour">
                  <span></span>Share
                </a>
              </li>
              <li class="download">
                <a class="dropdown-item" href="#" :data-tour-id="tour.id" @click="exportTour">
                  <span></span>Download
                </a>
              </li>
              <li class="delete">
                <a class="dropdown-item" href="#" :data-tour-id="tour.id" @click="confirmDelete">
                  <span></span>Delete
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </main>
  <VOnboardingWrapper
    :steps="onboardingSteps"
    label-finish="Continue"
    v-if="rTours.length > 0 && onboarding"
    @exit="handleDeclineOnboarding"
    @finish="handleFinishOnboarding"
  />
  <!-- Modal -->
  <!-- Delete message -->
  <div class="modal modal_alert fade" id="confirmDelete" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmDeleteLabel">Confirm delete</h5>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
          </button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete the tour «<strong>{{ selectedTour.title }}</strong>»? <br> This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary delete" :data-tour-id="selectedTour.id" @click="deleteTour">Yes, delete</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <!-- Create tour -->
  <div class="modal modal_createTour fade" id="createTour" tabindex="-1" role="dialog" aria-labelledby="createTourLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="back" aria-label="Back"
            @click="newTour.step -= 1"
            v-if="newTour.step == 2 || newTour.step == 3">
              <span aria-hidden="true"><img src="/src/assets/img/icon_back.svg" alt=""></span>
          </button>
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"
            v-if="newTour.step != 4">
              <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
          </button>
        </div>
        <div class="modal-body">
          <div class="" v-show="newTour.step == 1">
                <h5 class="modal-title" id="createTourLabel">How should we call it?</h5>
            <div class="input_box">
              <input type="text" placeholder="Enter a name" v-model="newTour.title" :maxlength="config.tourTitlemaxSize" data-fn-target="#titleCounter" data-fn-toggle="counter" autofocus>
              <p class="counter"><span id="titleCounter">0</span></p>
            </div>
            <div class="answer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary disabled" disabled v-if="!newTour.title">Continue</button>
              <button type="button" class="btn btn-primary" v-else @click="newTour.step = 2">Continue</button>
            </div>


          </div>
          <div class=""  v-show="newTour.step == 2">
            <h5 class="modal-title" id="createTourLabel">Add a description <span>optional</span></h5>
            <div class="input_box">
              <textarea placeholder="Enter description" name="" id="" cols="30" rows="10" v-model="newTour.description" :maxlength="config.descriptionmaxSize" data-fn-target="#descriptionCounter" data-fn-toggle="counter"></textarea>
              <p class="counter"><span id="descriptionCounter"></span></p>
            </div>
            <div class="answer">
              <button type="button" class="btn btn-tertiary" @click="newTour.step = 3">Skip</button>
              <button type="button" class="btn btn-primary" @click="newTour.step = 3">Continue</button>
            </div>
          </div>
          <div class=""  v-show="newTour.step == 3">
            <h5 class="modal-title" id="createTourLabel">Add a thumbnail <span>optional</span></h5>

            <ImageInput thumbnail="/img/img_placeholder3.svg" :maxSize="config.imgMaxSize" :width="config.tourThumbWidth" :height="config.tourThumbHeight" objectFit="contain" ref="thumbInputRef">
              <template #button>
                <span class="">Upload picture</span>
              </template>
            </ImageInput>

            <div class="answer">
              <button type="button" class="btn btn-tertiary" @click="createNewTour">Skip</button>
              <button type="button" :class="{
                'btn': true,
                'btn-primary': true,
                'disabled': !thumbInputRef.file
              }" @click="createNewTour">Continue</button>
            </div>
          </div>
          <div class="" v-show="newTour.step == 4">
            <img src="" alt="">
            <div class="loader"></div>
            <h5 class="modal-title" id="createTourLabel">Almost done!</h5>
            <p>We are creating your tour</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <!-- Import tour -->
  <div class="modal modal_createTour fade" id="importTour" tabindex="-1" role="dialog" aria-labelledby="createTourLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
        </div>
        <div class="modal-body">
          <div class="" v-show="newTour.step == 1">
            <h5>Import Tour</h5>
            <p><span>Paste the code</span> of the tour you want to import and, if it is protected, enter the password.</p>
            <h6 class="modal-title" id="createTourLabel">Tour code</h6>
            <div class="input_box">
              <input type="text" placeholder="abCDef1234" v-model="importedTour.filename" maxlength="10" autofocus>
            </div>
            <h6 class="modal-title" id="createTourLabel">Password <span>Optional</span></h6>
            <div class="input_box">
              <input type="password" v-model="importedTour.password">
            </div>
            <div class="answer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" @click="importTour">Import</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <ShareModal ref="shareTourEl" :tour="selectedTour" :csrf="csrf" @change="updateTours"></ShareModal>

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
                  <img v-if="selectedTour.thumb_filename" :src="'/data/image/' + selectedTour.thumb_filename" alt="">
                  <img v-else src="/src/assets/img/img_placeholder3.svg" alt="">
                </div>
            </div>
            <div class="about">
              <h3>{{ selectedTour.title }}</h3>
              <p>{{ selectedTour.description }}</p>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="link">
            <a class="btn link btn-primary" target="_blank" :href="'/exportTour.php?tour_id=' + selectedTour.id" >Download Zip</a>
          </div> 
          <p>File size: {{ selectedTourSize }}MB</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <!-- Onboarding -->
  <div class="modal modal_alert fade" id="modalOnboarding" tabindex="-1" role="dialog" aria-labelledby="modalOnboardingLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
<!--           <h5 class="modal-title" id="modalOnboardingLabel"></h5> -->
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg" alt=""></span>
          </button>
        </div>
        <div class="modal-body">
          <h5>Welcome!</h5>
          <p>Would you like an onboarding through the 360 app?</p>
          <br>
          <p class="alert alert-primary"><i><b>Note:</b> You can relaunch this onboarding through the swim buoy on the bottom left.</i></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="handleDeclineOnboarding">Maybe later</button>
          <button type="button" class="btn btn-primary" :data-tour-id="selectedTour.id" @click="handleStartOnboarding">Start</button>
          <br><a href="javascript:;" @click="handleDisableOnboarding">Don't ask me again</a>
        </div>
      </div>
    </div>
  </div>

</template>
<style scoped>
.dropdown-menu {
  z-index: 10;
}
</style>
