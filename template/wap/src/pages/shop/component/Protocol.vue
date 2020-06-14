<template>
  <van-popup
    v-model="value"
    position="bottom"
    :close-on-click-overlay="false"
    class="popup-protocol"
  >
    <van-nav-bar
      :title="title"
      left-arrow
      fixed
      :z-index="999"
      @click-left="$emit('input', false)"
    />
    <div class="content" v-html="content"></div>
    <div class="fixed-foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onApply">同意，前往申请</van-button>
    </div>
  </van-popup>
</template>

<script>
import { NavBar } from "vant";
import { pushState } from "@/mixins";
export default {
  data() {
    return {};
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    title: String,
    content: String
  },
  mixins: [pushState],
  methods: {
    goBack() {
      this.$emit("input", false);
    },
    onApply() {
      this.$emit("input", false);
      setTimeout(() => {
        this.$router.push("/shop/apply");
      }, 100);
    }
  },
  components: {
    [NavBar.name]: NavBar
  }
};
</script>

<style scoped>
.popup-protocol {
  width: 100%;
  height: 100%;
  border-radius: 0;
}

.content {
  padding: 10px;
  margin-top: 46px;
  margin-bottom: 80px;
}
</style>
