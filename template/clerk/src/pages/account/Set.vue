<template>
  <Layout ref="load" class="account-set bg-f8">
    <Navbar/>
    <van-cell-group class="mb-10">
      <van-cell title="头像" is-link class="avatar-cell" to="/account/avatar">
        <div class="img">
          <img :src="avatar" :onerror="$ERRORPIC.noAvatar">
        </div>
      </van-cell>
      <van-cell title="修改登录密码" is-link :to="{name:'account-post',params:{pagetype:1}}"/>
    </van-cell-group>

    <div class="foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onLogout">注销</van-button>
    </div>
  </Layout>
</template>

<script>
export default {
  name: "account-set",
  data() {
    return {};
  },
  computed: {
    avatar() {
      return this.$store.getters.avatar || "";
    }
  },
  created() {
    this.$store
      .dispatch("getAccountInfo")
      .then(() => {
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
      });
  },
  methods: {
    onLogout() {
      const $this = this;
      $this.$store.dispatch("logout").then(() => {
        setTimeout(() => {
          window.location.href = $this.$store.state.domain + "/clerk/";
        }, 500);
      });
    }
  }
};
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
