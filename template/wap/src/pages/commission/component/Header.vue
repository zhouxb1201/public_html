<template>
  <div>
    <div class="head">
      <img class="bg" :src="bgSrc">
      <component :is="'Style0'+styleType" class="box-info" :info="info"/>
    </div>
  </div>
</template>

<script>
import { style } from "./header-style";
export default {
  props: {
    item: Object,
    info: Object
  },
  computed: {
    styleType() {
      let type = 1;
      if (this.item.params && this.item.params.styletype) {
        type = this.item.params.styletype;
      }
      return type;
    },
    bgSrc() {
      let src = "";
      if (this.item.style && this.item.style.backgroundimage) {
        src = this.item.style.backgroundimage;
      } else {
        src =
          this.$BASEIMGPATH +
          "style/commission-head-0" +
          this.styleType +
          ".png";
      }
      return src;
    }
  },
  components: {
    ...style
  }
};
</script>

<style scoped>
.head {
  position: relative;
}

.head .bg {
  display: block;
  width: 100%;
  min-height: 120px;
  background: #fff;
}

.box-info {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 10;
}
</style>
