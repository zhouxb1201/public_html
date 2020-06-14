<template>
  <div>
    <span class="a-link fs-12" @click.shop="show = true">《注册协议》</span>
    <van-popup
      v-model="show"
      position="bottom"
      :close-on-click-overlay="false"
      get-container="body"
      class="popup-protocol"
    >
      <van-nav-bar
        title="注册协议"
        left-text="返回"
        left-arrow
        fixed
        :z-index="999"
        @click-left="show = false"
      />
      <div class="content" v-html="content"></div>
    </van-popup>
  </div>
</template>

<script>
import { NavBar } from "vant";
import { GET_REGISTERPROTOCOL } from "@/api/config";
import { pushState } from "@/mixins";
export default {
  data() {
    return {
      show: false,
      content: ""
    };
  },
  mixins: [pushState],
  mounted() {
    GET_REGISTERPROTOCOL().then(res => {
      this.content = res.data.register_protocol;
    });
  },
  methods: {
    goBack() {
      this.show = false;
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
}

.content {
  padding: 15px;
  margin-top: 50px;
}
</style>

