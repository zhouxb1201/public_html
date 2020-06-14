<template>
  <Layout ref="load" class="shop-result bg-f8">
    <Navbar />
    <ResultState :state="stateInfo.state" :message="stateInfo.message">
      <div class="foot-btn-group" slot="footer" v-if="stateInfo.state == 'error'">
        <van-button round size="normal" block type="danger" to="/shop/apply">重新申请</van-button>
      </div>
      <div class="flex-auto-center" slot="footer" v-if="stateInfo.state == 'success'">
        <van-field class="field" input-align="center" :value="stateInfo.url" disabled />
      </div>
    </ResultState>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ResultState from "@/components/ResultState";
export default sfc({
  name: "shop-result",
  data() {
    return {};
  },
  computed: {
    stateInfo() {
      const { applyState, shopManageUrl } = this.$store.state.shop;
      let info = {};
      if (applyState == "is_system") {
        info.state = "success";
        info.message = "您已是店铺卖家，请用电脑浏览器访问卖家后台管理你的店铺";
        info.url = shopManageUrl;
      }
      if (applyState == "refuse_apply") {
        info.state = "error";
        info.message = "商家拒绝了您的入驻申请";
      }
      if (applyState == "is_apply") {
        info.state = "wait";
        info.message = "店铺入驻审核中";
      }
      return info;
    }
  },
  mounted() {
    this.$store
      .dispatch("getShopApplyState")
      .then(data => {
        if (data.status == "apply") {
          this.$router.replace("/shop/centre");
        } else {
          this.$refs.load.success();
        }
      })
      .catch(error => {
        if (error) {
          this.$refs.load.fail({
            errorText: "未开启店铺应用",
            showFoot: false
          });
        } else {
          this.$refs.load.fail();
        }
      });
  },
  components: {
    ResultState
  }
});
</script>

<style scoped>
.field >>> .van-field__control:disabled {
  padding: 10px;
}
</style>


