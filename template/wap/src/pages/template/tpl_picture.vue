<template>
<div :class="item.id">
  <div class="vui-picture" :style="itemStyle">
    <router-link tag="div" class="item e-handle" v-for="(child,index) in item.data" :key="index" :style="childStyle" :to="child.linkurl">
      <img v-lazy="child.imgurl" :key="child.imgurl" pic-type="rectangle" />
    </router-link>
  </div>
</div>
</template>

<script>
export default {
  name: "tpl_picture",
  data() {
    return {
      itemStyle: {
        paddingBottom: this.item.style.paddingtop + "px",
        background: this.item.style.background
      },
      childStyle: {
        padding:
          this.item.style.paddingtop +
          "px" +
          " " +
          this.item.style.paddingleft +
          "px" +
          " " +
          0
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  }
};
</script>

<style scoped>
.vui-picture {
  overflow: hidden;
}
.item img {
  display: block;
  width: 100%;
}
</style>
