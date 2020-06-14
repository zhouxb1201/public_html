<template>
  <Layout ref="load" class="property bg-f8">
    <Navbar />
    <van-cell-group class="card-group-box">
      <van-cell
        :title="$store.state.member.memberSetText.balance_style"
        is-link
        :value="$store.state.member.info.balance | yuan"
        value-class="fs-12 text-secondary"
        to="/property/balance"
      />
      <van-cell
        :title="$store.state.member.memberSetText.point_style"
        is-link
        :value="$store.state.member.info.point"
        to="/property/points"
      />
      <van-cell
        title="优惠券"
        is-link
        :value="$store.state.member.info.coupon_num + ' 张'"
        value-class="fs-12 text-secondary"
        to="/coupon/list"
        v-if="$store.state.config.addons.coupontype"
      />
      <van-cell
        title="礼品券"
        is-link
        :value="$store.state.member.info.giftvoucher_num + ' 张'"
        value-class="fs-12 text-secondary"
        to="/giftvoucher/list"
        v-if="$store.state.config.addons.giftvoucher"
      />
      <van-cell
        title="消费卡"
        is-link
        :value="$store.state.member.info.store_card_num + ' 张'"
        value-class="fs-12 text-secondary"
        to="/consumercard/list"
        v-if="$store.state.config.addons.store"
      />
    </van-cell-group>
    <van-cell-group class="card-group-box">
      <van-cell
        title="数字钱包"
        is-link
        :value="($store.state.member.info.digital_assets || 0) + ' 个'"
        value-class="fs-12 text-secondary"
        to="/blockchain"
        v-if="$store.state.config.addons.blockchain"
      />
      <van-cell title="提现账户" is-link to="/property/account" />
    </van-cell-group>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
export default sfc({
  name: "property",
  data() {
    return {};
  },
  mounted() {
    if (this.$store.state.member.info) {
      this.$refs.load.success();
    } else {
      this.$refs.load.fail();
    }
  }
});
</script>

<style scoped>
</style>
