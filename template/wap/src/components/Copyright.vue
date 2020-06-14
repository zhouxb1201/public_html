<template>
  <div class="copyright" v-if="copyright && copyright.params.is_show != '0'">
    <div class="vui-copyright">
      <router-link :to="copyright.params.linkurl" class="box" :class="className">
        <img v-if="copyright.params.showlogo == '1'" :src="copyright.params.src | BASESRC">
        <span class="text">{{copyright.params.text}}</span>
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: "copyright",
  data() {
    return {};
  },
  computed: {
    copyright() {
      return this.$store.state.custom.copyright;
    },
    className() {
      return this.copyright.style.showtype == "0" ||
        !this.copyright.style.showtype
        ? "flex-column"
        : "";
    }
  }
};
</script>

<style scoped>
.vui-copyright {
  text-align: center;
  padding: 10px 24px;
  line-height: 20px;
}

.vui-copyright .box {
  display: inline-flex;
  justify-content: center;
  align-items: center;
}

.vui-copyright .box.flex-column {
  flex-direction: column;
}

.vui-copyright img {
  max-width: 80%;
  height: 40px;
  vertical-align: middle;
}

.vui-copyright .box.flex-column img {
  max-width: 100%;
}

.vui-copyright .text {
  color: #cecece;
  font-size: 12px;
  line-height: 1.2;
}
</style>
