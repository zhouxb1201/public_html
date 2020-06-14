<template>
  <div class="popup" v-if="isShow">
    <div class="popup-wrap">
      <img :src="$BASEIMGPATH + 'share.png'" class="pic-share">
    </div>
  </div>
</template>
<script>
export default {
  props: {
    //是否弹出
    isShow: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {};
  },
  methods: {
    close() {
      this.$emit("close");
    }
  }
};
</script>
<style scoped>
.popup {
  background-color: rgba(0, 0, 0, 0.5);
  position: fixed;
  top: 0;
  left: 0;
  transform: translate3d(0, 0, 0);
  -webkit-transform: translate3d(0, 0, 0);
  height: 100%;
  width: 100%;
  z-index: 2001;
  transition: all 0.2s;
}
.popup-wrap {
  position: relative;
  width: 100%;
  height: 100%;
}
.pic-share {
  width: 60%;
  height: auto;
  transform: translateX(65%);
  display: block;
}
</style>

