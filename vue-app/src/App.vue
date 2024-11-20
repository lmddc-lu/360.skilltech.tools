<script setup>
  import { onMounted, watch} from 'vue';
  import { user } from '/src/state.js';
  import { useRouter, onBeforeRouteUpdate } from 'vue-router';

  import {
    //~ fetchGet,
    //~ fetchPost,
    fetchUser,
    fetchTours,
    logout,
    //~ getCookie
  } from '/src/assets/js/functions.js';

  const router = useRouter();

  async function main(){
    await fetchUser(user);

    // Test if user is connected on route change, else redirect to login page
    router.beforeEach((to, from) => {
      if(to.name != "oidc" && !user.value.email){
        // we cancel the navigation
        return false;
      }
    });

    // Test if user is connected when app is launching, else redirect to login page
    if (!user.value.email){
      router.push({ name: 'oidc' });
      return;
    }

  };

  onMounted(() => {
    main();
  });
</script>

<template>
  <router-view v-slot="{ Component }">
    <transition name="slide-fade">
      <component :is="Component" />
    </transition>
  </router-view>
</template>

<style scoped>
li {
  display: inline-block;
  list-style: none;
  margin: 0 1em;
}
</style>
