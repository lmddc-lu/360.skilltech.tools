<script setup>
  // Depends of bootstrap which must be added to the main app
  import {ref, onMounted, onUnmounted, onUpdated } from 'vue';
  import { QRCode } from '/src/assets/js/qr.js';
  import { fetchForm } from '/src/assets/js/functions.js';

  const baseURL = window.location.origin + '/v/';
  const chkPassword = ref(null);
  const chkShare = ref(null);
  const code = ref(null);
  const copied = ref(null);
  const inputPassword = ref(null);
  const modalRef = ref(null);
  const savedPassword = ref(null);
  const showPassword = ref(false);
  const tourId = ref(null);

  const props = defineProps(['tour', 'csrf']);
  const emit = defineEmits(['change']);

  function show(){
    bootstrap.Modal.getOrCreateInstance(modalRef.value).show();
  };

  function hide(){
    bootstrap.Modal.getOrCreateInstance(modalRef.value).hide();
  };

  // Copy text to clipboard
  async function clipboard(text) {
    try {
      await navigator.clipboard.writeText( text );
      // We put the value into the "copied" variable to show the "Copied!" tooltip
      copied.value = text;
      // Hide the tooltip after 2 seconds
      setTimeout(function(){
        if (copied.value == text){
          copied.value = null;
        }
      }, 2000);
    } catch (error) {
      console.error(error.message);
    }
  }

  function handleToggleShare(){
    chkShare.value = !chkShare.value;
    setShare();
  }

  async function setShare(ev){
    let result = ref({});
    let formData = new FormData();
    formData.append("id", props.tour.id);
    if(chkShare.value === true){
      formData.append("share", "1");
      if (chkPassword.value == true && inputPassword.value != ""){
        formData.append("password", inputPassword.value);
        savedPassword.value = inputPassword.value;
        let cachedSavedPassword = savedPassword.value;
        setTimeout(function(){
          if (cachedSavedPassword == inputPassword.value){
            savedPassword.value = null;
          }
        }, 2000);
      }
    } else {
      formData.append("share", "0");
    }

    formData.append("csrf", props.csrf);

    await fetchForm("/setTourShare.php", formData, result);
    if (result.value.error){
      console.error(result.value.error);
    } else {
      emit('change', {tour: result.value});
    }
  }

  onUpdated(() => {
    if(props.tour.filename){
      let options = {
        ecclevel: "M",
        margin: 1,
        modulesize: 3
      };
      code.value = QRCode.generatePNG(baseURL + props.tour.filename, options);
    }

    // If another tour is selected
    if (props.tour.id != tourId.value){
      tourId.value = props.tour.id ;
      chkShare.value = props.tour.share == '1';
      if(chkShare.value !== true){
        chkPassword.value = false;

      }
      inputPassword.value = "";
      chkPassword.value = props.tour.password == '1';
    }
  });

  defineExpose({
    show,
  })
</script>

<template>
  <!-- OnboardingModal.vue -->
  <div class="modal fade show" ref="modalRef" id="getLink" tabindex="-1" role="dialog" aria-labelledby="getLinkLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title">Share tour</h2>
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
          <h4>Copy link</h4>
          <img :src="code" class="qrCode" alt="QR Code">
         
          <div class="link">
            <input type="text" :value="baseURL + tour.filename" autofocus readonly>
            <div>
              <button class="btn link" @click="clipboard(baseURL + tour.filename)">
                <img src="/src/assets/img/icon_clipboard.svg" alt="copy button">
              </button>
              <div class="copied" v-show="copied == baseURL + tour.filename">
                <p>Copied!</p>
              </div>
            </div>
          </div> 
        </div>
        <div class="modal-footer">
          <h4>Share editable tour</h4>
          <!-- <h4>Enable copy</h4> -->
          <!-- <h4>Duplicate and share</h4> -->
          <div class="enable">
            <p>Shared</p>
            <div :class="{button: true, on: chkShare == 1, off: chkShare != 1}" @click="handleToggleShare">
              <input type="checkbox" class="checkbox" v-model="chkShare" />
              <div class="knobs"></div>
              <div class="layer"></div>
            </div>
          </div>
          <p>Allow others to import and modify this tour in their collection.</p>
          <p class="send" v-if="chkShare == 1">Send them this tour code and password:</p>
          <div class="link share"  v-show="chkShare == 1">
            <input type="text" :value="tour.filename" autofocus readonly>
            <div>
              <button class="btn link" @click="clipboard(tour.filename)">
                <img src="/src/assets/img/icon_clipboard.svg" alt="copy button">
              </button>
              <div class="copied" v-show="copied && copied == tour.filename">
                <p>Copied!</p>
              </div>
            </div>
          </div>
          <div class="password_link"  v-show="chkShare == 1">
            <input type="checkbox" id="password" name="password" v-model="chkPassword" @change="setShare"/>
            <label for="password">Set a password <br><span> Optional</span></label>
            <!-- <h4>Password</h4> -->
            <div class="link" v-if="chkPassword === true && chkShare === true">
              <input :type="showPassword == true ? 'text' : 'password'" v-model="inputPassword" autofocus>
              <button class="show" @click="showPassword = !showPassword">
                <img v-if="showPassword == false" src="/src/assets/img/icon_show.svg" alt="Show password">
                <img v-else src="/src/assets/img/icon_hide.svg" alt="Show password">
              </button>
              <div>
                <button class="btn link" @click="clipboard(inputPassword)">
                  <img src="/src/assets/img/icon_clipboard.svg" alt="copy button">
                </button>
                <div class="copied" v-show="copied && copied == inputPassword">
                  <p>Copied!</p>
                </div>
              </div>
            </div>
          </div>
          <button
            type="button"
            :class="{'btn': true, 'btn-secondary': true, 'save': true, 'disabled': inputPassword == ''}"
            v-if="chkPassword === true && chkShare === true && inputPassword != savedPassword"
            @click="setShare">Save password</button>
          <button
            type="button"
            class="btn btn-secondary saved"
            v-if="chkPassword === true && chkShare === true && savedPassword === inputPassword"
          >Password saved</button>
        </div>
      </div>
    </div>
  </div>
</template>
