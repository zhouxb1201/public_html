<template>
  <div class="load-more-end" v-show="finished">
    <span class="text" :style="{background:textBackground}">{{text}}</span>
  </div>
</template>
<script>
export default {
  data() {
    return {};
  },
  props: {
    finished: {
      type: Boolean,
      default: false
    },
    text: {
      type: String,
      default: "没有更多了"
    },
    textBackground: {
      type: String,
      default: "#f8f8f8"
    }
  }
};
</script>

<style scoped>
.load-more-end {
  position: relative;
  margin: 0px 10%;
  height: 50px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.load-more-end::before {
  content: "";
  position: absolute;
  top: 50%;
  left: 0;
  border: 0 solid #e5e5e5;
  -webkit-transform-origin: 0 0;
  transform-origin: 0 0;
  pointer-events: none;
  width: 100%;
  border-top-width: 1px;
}
.load-more-end .text {
  font-size: 12px;
  position: relative;
  z-index: 1;
  color: #999999;
  padding: 4px 14px;
  background: #f8f8f8;
}
</style>