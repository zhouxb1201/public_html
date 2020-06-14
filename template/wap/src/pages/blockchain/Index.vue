<template>
  <Layout ref="load" class="blockchain bg-f8">
    <Navbar />
    <van-cell-group>
      <EthItem v-if="showEth" />
      <EosItem v-if="showEos" />
    </van-cell-group>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import EthItem from "./component/EthItem";
import EosItem from "./component/EosItem";
export default sfc({
  name: "blockchain",
  data() {
    return {
      showEth: false,
      showEos: false
    };
  },
  computed: {},
  mounted() {
    if (this.$store.state.config.addons.blockchain) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启区块链应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      this.$store
        .dispatch("getBlockchainSet")
        .then(({ wallet_type }) => {
          const arr = wallet_type.split(",");
          if (arr[0] == 1) {
            this.showEth = true;
          }
          if (arr[0] == 2 || arr[1] == 2) {
            this.showEos = true;
          }
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    }
  },
  components: {
    EthItem,
    EosItem
  }
});
</script>

<style scoped>
</style>
