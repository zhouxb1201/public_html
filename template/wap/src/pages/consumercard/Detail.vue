<template>
  <Layout ref="load" class="consumercard-detail bg-f8">
    <Navbar />
    <CellAddWxCard
      class="card-group-box"
      :params="detail.card_id"
      v-if="!detail.wx_card_state && detail.card_type == 2"
      @success="loadData"
    />
    <DetailGoods class="card-group-box" :detail="detail" />
    <DetailCode class="card-group-box" :detail="detail" v-if="!$route.query.card_id" />
    <DetailStore class="card-group-box" :info="detail" />
    <DetailSituation class="card-group-box" :detail="detail" />
    <DetailLog class="card-group-box" :card_id="detail.card_id" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_CONSUMERCARDDETAIL } from "@/api/consumercard";
import CellAddWxCard from "./component/CellAddWxCard";
import DetailGoods from "./component/detail/Goods";
import DetailCode from "./component/detail/Code";
import DetailStore from "./component/CellStoreInfo";
import DetailSituation from "./component/detail/Situation";
import DetailLog from "./component/detail/Log";
export default sfc({
  name: "consumercard-detail",
  data() {
    return {
      detail: {}
    };
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      let params = {};
      params.card_id = $this.$route.params.cardid;
      if ($this.$route.query.card_id) {
        params.wx_card_id = $this.$route.query.card_id;
      }
      GET_CONSUMERCARDDETAIL(params)
        .then(({ data }) => {
          $this.detail = data;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  },
  components: {
    CellAddWxCard,
    DetailGoods,
    DetailCode,
    DetailStore,
    DetailSituation,
    DetailLog
  }
});
</script>

<style scoped>
</style>

