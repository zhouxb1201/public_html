<template>
  <div>
    <div class="head">
      <img class="bg" :src="bgSrc">
      <component :is="'Style0'+styleType" class="box-info" :info="item.info"/>
      <router-link class="btn-set" to="/account/set">
        <van-icon name="setting-o" size="20px" class="btn-icon"/>
        <span class="text">设置</span>
      </router-link>
    </div>
  </div>
</template>

<script>
import { style } from "./header-style";
export default {
  props: {
    item: Object
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
          this.$BASEIMGPATH + "style/member-head-0" + this.styleType + ".png";
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
  margin-bottom: -30px;
}

.head .bg {
  display: block;
  width: 100%;
  min-height: 140px;
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

.btn-set {
  position: absolute;
  right: 20px;
  top: 15px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: #fff;
  z-index: 99;
}

.btn-set .text {
  line-height: 1;
  padding-top: 5px;
  font-size: 10px;
}
</style>
