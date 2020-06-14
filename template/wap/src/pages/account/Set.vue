<template>
  <Layout ref="load" class="account-set bg-f8">
    <Navbar />
    <van-cell-group class="mb-10">
      <van-cell title="头像" is-link class="avatar-cell" to="/account/avatar">
        <div class="img">
          <img :src="avatar" :onerror="$ERRORPIC.noAvatar" />
        </div>
      </van-cell>
      <van-cell title="基本信息" is-link to="/account/info" />
    </van-cell-group>

    <van-cell-group class="mb-10" v-if="$store.getters.isBingFlag&&$store.getters.isBindMobile">
      <van-cell title="修改登录密码" is-link :to="{name:'account-post',params:{pagetype:1}}" />
      <van-cell title="设置支付密码" is-link :to="{name:'account-post',params:{pagetype:2}}" />
      <van-cell title="修改关联手机" is-link :to="{name:'account-post',params:{pagetype:3}}" />
      <van-cell title="绑定电子邮箱" is-link :to="{name:'account-post',params:{pagetype:4}}" />
    </van-cell-group>

    <van-cell-group class="mb-10">
      <van-cell title="收货地址管理" is-link to="/address/list" />
    </van-cell-group>

    <van-cell-group class="mb-10">
      <van-cell title="关联账号" is-link to="/account/relevant" />
    </van-cell-group>

    <div class="foot-btn-group" v-if="!$store.state.isWeixin || !$store.getters.config.is_wchat">
      <van-button size="normal" round type="danger" block @click="onLogout">注销</van-button>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { isEmpty } from "@/utils/util";
export default sfc({
  name: "account-set",
  data() {
    return {};
  },
  computed: {
    avatar: {
      get() {
        return this.$store.state.account.info.avatar
          ? this.$store.state.account.info.avatar
          : "";
      },
      set() {}
    }
  },
  activated() {
    if (isEmpty(this.$store.state.account.info)) {
      this.$store
        .dispatch("getAccountInfo")
        .then(() => {
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    } else {
      this.$refs.load.success();
    }
  },
  methods: {
    onLogout() {
      const $this = this;
      $this.$store.dispatch("logout").then(() => {
        setTimeout(() => {
          window.location.href = $this.$store.state.domain + "/wap/";
        }, 500);
      });
    }
  }
});
</script>

<style scoped>
.mb-10 {
  margin-bottom: 10px;
}

.avatar-cell {
  display: flex;
  align-items: center;
}

.avatar-cell .img {
  width: 50px;
  height: 50px;
  overflow: hidden;
  border-radius: 50%;
  float: right;
  background: #f9f9f9;
}

.avatar-cell .img img {
  width: 100%;
  height: 100%;
}
</style>
