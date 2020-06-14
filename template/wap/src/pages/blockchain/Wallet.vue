<template>
  <Layout ref="load" class="blockchain-wallet bg-f8">
    <Navbar />
    <WalletInfo :type="$route.params.type" :info="info" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import WalletInfo from "./component/WalletInfo";
import blockchain from "./mixin";
export default sfc({
  name: "blockchain-wallet",
  data() {
    return {
      info: {}
    };
  },
  mixins: [blockchain],
  methods: {
    loadData(data) {
      this.info = data;
    }
  },
  components: {
    WalletInfo
  }
});
</script>

<style scoped>
</style>
