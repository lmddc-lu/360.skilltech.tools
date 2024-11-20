<template>
  <nav>
    <ul>
      <li>
        <router-link to="/" :alt="t_home">
          {{ t_home }}
        </router-link>
      </li>
      <template v-if="user.email">
        <li>
          <a href="#" @click="logout()" :alt="t_sign_out" >
            {{ t_sign_out }}
          </a>
        </li>
        <li>
          {{ user.email }}
        </li>
      </template>
      <template v-else>
        <li>
          <router-link to="/oidc" :alt="t_connection">
            {{ t_connection }}
          </router-link>
        </li>
      </template>
    </ul>
  </nav>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { config } from '/src/assets/js/config.js';
import {
  fetchGet,
  fetchPost,
  fetchUser,
  logout,
  getCookie
} from '/src/assets/js/functions.js';
defineProps(["user", "t_home", "t_sign_out", "t_connection"]);

onMounted(() => {

});
</script>

<style scoped>
li {
  display: inline-block;
  list-style: none;
  margin: 0 1em;
}
</style>
