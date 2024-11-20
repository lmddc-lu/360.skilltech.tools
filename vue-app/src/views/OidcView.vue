<script setup>
import { ref, onMounted, watch } from 'vue';
import {config} from '/src/assets/js/config.js';
import { user } from '/src/state.js';
import {
  fetchGet,
  fetchPost,
  fetchUser,
  logout,
  getCookie
} from '/src/assets/js/functions.js';

async function main(){
  console.log("end main");
};
  
onMounted(() => {
  main();
  console.log("end mounted");
});

</script>

<template>
  <main class="oidc">
    <div class="box">
      <div class="connect">
        <h1><img src="/src/assets/img/logo_360_2.svg" alt="Skilltech - 360 tour"></h1>
<!--         <div class="user">
          {{ user.email.substring(0,1).toUpperCase() }}
        </div> -->
        <p>{{ user.email }}</p>
        <a href="/oidc.php">Connect</a>
      </div>
    </div>
  </main>
</template>
