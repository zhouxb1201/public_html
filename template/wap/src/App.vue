<template>
  <div id="app" class="app" :class="tabbarClass" :version="version">
    <transition name="van-fade" mode="out-in">
      <keep-alive>
        <router-view :class="appVh" v-if="!$route.meta.noKeepAlive"></router-view>
      </keep-alive>
    </transition>
    <transition name="van-fade" mode="out-in">
      <router-view :class="appVh" v-if="$route.meta.noKeepAlive"></router-view>
    </transition>
    <Empty
      v-if="errorParam.show"
      :style="{'z-index':1000}"
      :pageType="errorParam.pageType"
      :message="errorParam.message"
      :btnText="errorParam.btnText"
      event
      @click="reload"
    />
    <Floater />
    <Tabbar v-if="isShowTabbar" />
  </div>
</template>

<script>
import Tabbar from "@/components/Tabbar";
import Empty from "@/components/Empty";
import Floater from "@/components/Floater";
import sockets from "./mixins/sockets";
export default {
  name: "App",
  data() {
    return {
      version: process.env.VERSION
    };
  },
  mixins: [sockets],
  computed: {
    tabbarClass() {
      return this.$store.state.tabbar.isShowTabbar ? "showTabbar" : "";
    },
    isShowTabbar() {
      return this.$store.state.tabbar.isShowTabbar;
    },
    appVh() {
      return this.$store.state.tabbar.isShowTabbar ? "app-vh" : "";
    },
    errorParam() {
      return this.$store.state.config.errorParam;
    }
  },
  mounted() {
    // 接收后台装修iframe预览装修数据
    window.addEventListener(
      "message",
      function(event) {
        if (event.data.custom) {
          document
            .getElementById("app")
            .setAttribute("data-custom", event.data.custom);
        }
      },
      false
    );
  },
  methods: {
    reload() {
      location.reload();
    }
  },
  components: {
    Tabbar,
    Empty,
    Floater
  }
};
</script>

<style>
.app-vh {
  min-height: calc(100vh - 50px) !important;
}
</style>
