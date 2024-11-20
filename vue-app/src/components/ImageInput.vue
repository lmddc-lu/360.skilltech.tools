<script setup>
  import { ref, onMounted, onUpdated, watch, computed, useSlots } from 'vue'
  import { steppedScale } from '../assets/js/stepped-scale.js'
  const props = defineProps(['thumbnail', 'maxWidth', 'maxHeight', 'minWidth', 'minHeight', 'width', 'height', 'objectFit', 'maxSize']);
  const emit = defineEmits(['change']);
  var _file = ref(null); // Local variable
  var file = ref(null);  // Exposed variable
  var previousThumbnail = null; // To prevent the update of the image
  const img = ref(props.thumbnail);
  const width = ref(null);
  const height = ref(null);
  const errors = ref([]);
  const uuid = "id" + crypto.randomUUID().replaceAll("-", "");
  const slots = useSlots();
  /*
   * Prevents the file from being opened by the browser
   */
  function fileDragOverHandler(ev) {
    ev.preventDefault();
  }

  /*
   * Reset the variables
   */
  function reset(){
    _file.value = null;
    file.value = null;
    width.value = null;
    height.value = null;
    errors.value = [];
    previousThumbnail = null;
    img.value = props.thumbnail;
  }

  /*
   * Action when an image file is dropped over the thumbnail dropzone
   */
  async function thumbDropHandler(ev) {
    // Prevents file from being opened by the browser
    ev.preventDefault();
    let file;
    if (ev.dataTransfer.items) {
      // Use DataTransferItemList interface to access the file(s)
      // If dropped items aren't files, reject them
      if (ev.dataTransfer.items[0].kind === "file") {
        file = ev.dataTransfer.items[0].getAsFile();
      } else {
        return;
      }
    } else {
      // Use DataTransfer interface to access the file(s)
      file = ev.dataTransfer.files[0];
    }

    if (file){
      console.log("file drop");
      _file.value = await steppedScale(file, props.width, props.height, props.objectFit);
    }
  }

  // Load the file in the img element
  watch(_file, async (newFile, oldFile) => {
    if (_file.value){
      img.value = URL.createObjectURL(_file.value);
      testImage(img.value);
    }
  });

  // Check image properties
  async function testImage(src){
    if (_file.value){
      errors.value = [];
      let imgEl = document.createElement("img");
      imgEl.addEventListener("load", function(){

        width.value = imgEl.naturalWidth;
        height.value = imgEl.naturalHeight;

        console.debug(Math.round(_file.value.size / 100000)/10);
        if(props.maxSize && _file.value.size > parseInt(props.maxSize)){
          errors.value.push({type: 'maxSize', value: _file.size});
        }

        if(props.maxWidth && imgEl.naturalWidth > parseInt(props.maxWidth)){
          errors.value.push({type: 'maxWidth', value: imgEl.naturalWidth});
        }

        if(props.minWidth && imgEl.naturalWidth < parseInt(props.minWidth)){
          console.log("imgEl.naturalWidth " + imgEl.naturalWidth);
          errors.value.push({type: 'minWidth', value: imgEl.naturalWidth});
        }

        if(props.maxHeight && imgEl.naturalHeight > parseInt(props.maxHeight)){
          errors.value.push({type: 'maxHeight', value: imgEl.naturalHeight});
        }

        if(props.minHeight && imgEl.naturalHeight < parseInt(props.minHeight)){
          errors.value.push({type: 'minHeight', value: imgEl.naturalHeight});
        }

        if (errors.value.length == 0){
          file.value = _file.value;
          emit('change', {file: _file.value, img: src, width: width.value, height: height.value});
        }
      });

      imgEl.src = src;
    }
  }

  onMounted(() => {
    let input = document.querySelector('#' + uuid + ' input');
    input.addEventListener("change", async function(e){
      console.log("file selected");
      _file.value = await steppedScale(input.files[0], props.width, props.height, props.objectFit);
    });
  });

  onUpdated(() => {
    if (_file.value == null){
      img.value = props.thumbnail;
      previousThumbnail = props.thumbnail;
    }
  });

  defineExpose({
    file,
    errors,
    img,
    reset,
    width,
    height,
  })
</script>

<template>
  <div :id="uuid" class="pic_box" @drop="thumbDropHandler" @dragover="fileDragOverHandler">
    <input type="file" :name="'file_' + uuid" :id="'file_' + uuid" style="display:none;" accept="image/png, image/jpeg, image/webp"/>
    <label class="add_pic" :for="'file_' + uuid">
      <slot name="box">
        <div class="img_box">
          <img :src="img" alt="">
        </div>
      </slot>
      <slot name="button">
      </slot>
    </label>
    <slot name="error">
      <div class="error_message" v-if="errors.length > 0">
        <template v-for="error in errors">
          <span v-if="error.type == 'maxSize'">Invalid image: The file size must less than {{Math.round(props.maxSize/1000/1000)}}MB</span>
          <span v-if="error.type == 'minWidth'">Invalid image: Image's width must be bigger than {{ props.minWidth }} pixels</span>
          <span v-if="error.type == 'maxWidth'">Invalid image: Image's width must be smaller than {{ props.maxWidth }} pixels</span>
          <span v-if="error.type == 'minHeight'">Invalid image: Image's height must be bigger than {{ props.minHeight }} pixels</span>
          <span v-if="error.type == 'maxHeight'">Invalid image: Image's height must be smaller than {{ props.maxHeight }} pixels</span>
        </template>
      </div>
    </slot>
    <slot name="footer"></slot>
  </div>
</template>

<style scoped>

</style>
