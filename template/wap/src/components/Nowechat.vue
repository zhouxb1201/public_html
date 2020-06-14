<template>
  <div class="nowechat">
    <Empty page-type="fail" :message="message">
      <van-cell-group :border="false" slot="footer" class="group-box">
        <van-field center disabled :value="$store.state.domain+'/wap/'" class="field">
          <van-button
            class="a-copy"
            slot="button"
            size="mini"
            type="danger"
            :data-clipboard-text="$store.state.domain+'/wap/'"
            @click="onCopy"
          >复制</van-button>
        </van-field>
      </van-cell-group>
    </Empty>
  </div>
</template>

<script>
import { clipboard } from "@/mixins";
import Empty from "@/components/Empty";
export default {
  name: "nowechat",
  computed: {
    message() {
      const title = "该商城未对接公众号";
      document.title = title;
      return `<p class="nowechat-text">${title}</p><p class="text">
        请使用手机浏览器访问
      </p>`;
    }
  },
  mixins: [clipboard],
  components: {
    Empty
  }
};
</script>

<style scope>
.nowechat-text {
  line-height: 1.4;
}
.nowechat .field {
  background: #f5f5f5;
}
.nowechat .group-box {
  width: 80%;
  margin: 0 auto;
}
</style>

