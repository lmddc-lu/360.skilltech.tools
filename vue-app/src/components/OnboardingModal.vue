<script setup>
  // Depends of bootstrap which must be added to the main app
  import {ref } from 'vue';

  const emit = defineEmits(['start']);
  const modalRef = ref(null);

  function show(){
    bootstrap.Modal.getOrCreateInstance(modalRef.value).show();
  };

  function hide(){
    bootstrap.Modal.getOrCreateInstance(modalRef.value).hide();
  };

  async function handleStart(){
    let listener = modalRef.value.addEventListener('hidden.bs.modal', function (ev) {
      emit('start');
    },{once : true});
    hide();
  }

  defineExpose({
    show,
  })
</script>

<template>
  <!-- OnboardingModal.vue -->
  <div class="modal modal_alert fade" id="modalStartOnboarding" ref="modalRef" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><img src="/src/assets/img/icon_close.svg"></span>
          </button>
        </div>
        <div class="modal-body">
          <h5>Launch the demo tour</h5>
          <p>You will be redirected to the home page</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary"  @click="handleStart()">Go!</button>
        </div>
      </div>
    </div>
  </div>
</template>
