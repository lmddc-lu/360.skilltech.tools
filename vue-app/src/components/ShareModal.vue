<script setup>
  // Depends of bootstrap which must be added to the main app
  import {ref, onMounted, onUpdated } from 'vue';
  import { QRCode } from '/src/assets/js/qr.js';
  const props = defineProps(['tour']);
  const copied = ref(null);
  const modalRef = ref(null);
  const code = ref(null);

  function show(){
    bootstrap.Modal.getOrCreateInstance(modalRef.value).show();
  };

  function hide(){
    bootstrap.Modal.getOrCreateInstance(modalRef.value).hide();
  };

  // Copy text to clipboard
  async function clipboard(filename) {
    try {
      await navigator.clipboard.writeText( 'https://360.skilltech.tools/v/' + filename );
      copied.value = filename;
    } catch (error) {
      console.error(error.message);
    }
  }

  onUpdated(() => {
    if(props.tour.filename){
      let options = {
        ecclevel: "M",
        margin: 1,
        modulesize: 3
      };
      console.log(props.tour);
      code.value = QRCode.generatePNG('https://360.skilltech.tools/v/' + props.tour.filename, options);
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
          <img :src="code" alt="QR Code">
          <h4>Copy link</h4>
          <div class="link">
            <input type="text" :value="'https://360.skilltech.tools/v/' + tour.filename" autofocus readonly>
            <div>
              <button class="btn link btn-primary" @click="clipboard(tour.filename)">
                <img src="/src/assets/img/icon_whiteLink.svg" alt="copy button"> Copy
              </button>
              <div class="copied" v-show="copied == tour.filename">
                <p>Copied!</p>
              </div>
            </div>
          </div> 
        </div>
      </div>
    </div>
  </div>
</template>
