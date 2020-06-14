<template>
  <div class="category-view" :style="[viewHeight,viewStyle]">
    <div class="category">
      <div class="nav" :style="navStyle">
        <slot name="nav" />
      </div>
      <div class="content" :style="contentStyle">
        <slot name="content" />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    viewStyle: {
      type: [Object, Boolean]
    },
    navStyle: {
      type: Object
    },
    contentStyle: {
      type: Object
    },
    top: {
      type: [Number, String],
      default: 0
    },
    bottom: {
      type: [Number, String],
      default: 0
    }
  },
  computed: {
    viewHeight() {
      const h = Number(this.top) + Number(this.bottom);
      return {
        height: `calc(100vh - ${h + "px"})`
      };
    }
  }
};
</script>

<style scoped>
.category-view {
  width: 100%;
  height: 100vh;
}

.category {
  display: flex;
  width: 100%;
  height: 100%;
}

.category .nav {
  width: 90px;
  height: 100%;
  background-color: #f8f8f8;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
}

.category .content {
  display: block;
  flex: 1;
  height: 100%;
  background-color: #fff;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  position: relative;
}
</style>