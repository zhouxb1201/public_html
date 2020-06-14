<template>
  <Layout ref="load" class="account-relevant bg-f8">
    <Navbar :isMenu="false" />
    <van-cell-group>
      <van-cell
        icon="v-icon-wechat"
        title="微信"
        :value="wechat ? '已关联' : '关联'"
        is-link
        class="wx-cell"
        :class="wechat ? 'on' : ''"
        @click="onOtherLogin('WCHAT')"
        v-if="$store.getters.config.wechat_login && $store.state.isWeixin"
      />
      <van-cell
        icon="v-icon-qq2"
        title="QQ"
        :value="qq ? '已关联' : '关联'"
        is-link
        class="qq-cell"
        :class="qq ? 'on' : ''"
        @click="onOtherLogin('QQLOGIN')"
        v-if="$store.getters.config.qq_login"
      />
    </van-cell-group>
    <div
      class="tip"
      v-if="$store.getters.config.wechat_login || $store.getters.config.qq_login"
    >账号关联之后，用户可使用微信QQ账号快速登录商城。</div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ACCOUNTRELEVANT } from "@/api/member";
import { getSession, setSession, removeSession } from "@/utils/storage";
export default sfc({
  name: "account-relevant",
  data() {
    return {
      qq: null,
      wechat: null
    };
  },
  activated() {
    GET_ACCOUNTRELEVANT()
      .then(({ data }) => {
        this.qq = data.qq;
        this.wechat = data.wechat;
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
      });
  },
  methods: {
    onOtherLogin(type) {
      const $this = this;
      if (type === "WCHAT" && $this.wechat) {
        $this.$Toast("您已关联微信！");
        return false;
      }
      if (type === "QQLOGIN" && $this.qq) {
        $this.$Toast("您已关联QQ！");
        return false;
      }
      if (type === "WCHAT" && !$this.$store.getters.config.is_wchat) {
        $this.$Toast("请配置微信公众号！");
        return false;
      }
      setSession("toPath", $this.$route.fullPath);
      $this.$store
        .dispatch("otherLogin", {
          action: "relevant",
          form: {
            type
          }
        })
        .then(() => {
          $this.$Toast.success("关联成功");
          removeSession("toPath");
        });
    }
  }
});
</script>

<style scoped>
.wx-cell >>> .van-icon-v-icon-wechat {
  color: #08be14;
}

.qq-cell >>> .van-icon-v-icon-qq2 {
  color: #18acfc;
}

.van-cell >>> .van-cell__left-icon {
  font-size: 22px;
}

.van-cell >>> .van-cell__value {
  color: #ff454e;
}

.van-cell.on >>> .van-cell__value {
  color: #999999;
}

.account-relevant >>> .tip {
  color: #606266;
  font-size: 12px;
  padding: 20px 15px;
  text-align: center;
}
</style>
