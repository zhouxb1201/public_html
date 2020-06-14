<template>
  <div id="app" class="app" :version="version">
    <transition name="van-fade" mode="out-in">
      <keep-alive>
        <router-view v-if="!$route.meta.noKeepAlive"></router-view>
      </keep-alive>
    </transition>
    <transition name="van-fade" mode="out-in">
      <router-view v-if="$route.meta.noKeepAlive"></router-view>
    </transition>
  </div>
</template>

<script>
export default {
  name: "App",
  data() {
    return {
      version: process.env.VERSION
    };
  }
};
</script>

<style>
.app-vh {
  min-height: calc(100vh - 50px) !important;
}
</style>
