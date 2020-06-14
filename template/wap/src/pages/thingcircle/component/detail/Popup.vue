<template>
  <div class="popup-wrap">
    <div class="popup-overlay" v-if="isShow"></div>
    <div class="popup--bottom" v-if="isShow">
      <slot name="content"></slot>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    isShow: [Boolean]
  },
  mounted() {},
  methods: {},
  components: {}
};
</script>

<style scoped>
.popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 2100;
}
.popup--bottom {
  position: fixed;
  left: 50%;
  width: 90%;
  border-radius: 10px;
  bottom: 10px;
  transform: translate3d(-50%, 0, 0);
  right: auto;
  z-index: 2145;
  max-height: 100%;
  overflow-y: auto;
  background-color: #fff;
  transition: 0.3s ease-out;
}
</style>