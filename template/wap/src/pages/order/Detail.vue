<template>
  <Layout ref="load" class="order-detail bg-f8">
    <Navbar />
    <DetailHead :detail="detail" />
    <DetailAddress class="card-group-box" :detail="detail" v-if="isShowAddress" />
    <DetailLogistic
      class="card-group-box"
      :status="detail.order_status"
      :orderid="detail.order_id"
      :info="logistic_info"
      v-if="isShowLogistic"
    />
    <DetailTakeCode
      class="card-group-box"
      :detail="detail"
      v-if="detail.store_id && detail.order_status == 1"
    />
    <CellAssemble
      class="card-group-box"
      v-if="detail.group_record_id"
      :record_id="detail.group_record_id"
    />
    <DetailGoods class="card-group-box" :detail="detail" />
    <DetailMessage class="card-group-box" :detail="detail" />
    <DetailInvoice class="card-group-box" :detail="detail" />
    <DetailInfo class="card-group-box" :detail="detail" />
    <DetailLog class="card-group-box" :detail="detail" />
    <div class="foot" v-if="detail.member_operation && detail.member_operation.length > 0">
      <FootOperation :info="detail" @callback="onCallback" />
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import DetailHead from "./component/detail/Head";
import DetailInfo from "./component/detail/Info";
import DetailAddress from "./component/detail/Address";
import DetailLogistic from "./component/detail/Logistic";
import DetailMessage from "./component/detail/Message";
import DetailGoods from "./component/detail/Goods";
import DetailLog from "./component/detail/Log";
import DetailTakeCode from "./component/detail/TakeCode";
import DetailInvoice from "./component/detail/Invoice";
import CellAssemble from "../assemble/component/CellAssemble";
import FootOperation from "./component/FootOperation";
import { GET_ORDERDETAIL } from "@/api/order";
import { isEmpty } from "@/utils/util";

export default sfc({
  name: "order-detail",
  data() {
    return {
      detail: {},
      logistic_info: {}
    };
  },
  computed: {
    isShowAddress() {
      const { card_store_id, order_type, is_virtual, goods_type } = this.detail;
      return (
        !card_store_id && !is_virtual && goods_type != 3 && goods_type != 4
      );
    },
    isShowLogistic() {
      const {
        card_store_id,
        store_id,
        order_type,
        is_virtual,
        goods_type
      } = this.detail;
      return (
        !card_store_id &&
        !store_id &&
        !is_virtual &&
        goods_type != 3 &&
        goods_type != 4
      );
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_ORDERDETAIL($this.$route.params.orderid)
        .then(({ data }) => {
          $this.detail = data;
          if (!isEmpty($this.detail.goods_packet_list)) {
            let shipping_info = $this.detail.goods_packet_list[0].shipping_info;
            if (shipping_info) {
              const logistic_info = {};
              logistic_info.expTextName = shipping_info.expTextName;
              logistic_info.mailNo = shipping_info.mailNo;
              logistic_info.newestInfo = !isEmpty(shipping_info.data)
                ? shipping_info.data[0]
                : "";
              $this.logistic_info = logistic_info;
            }
          }
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onCallback({ message }) {
      this.$Toast.success(message);
      this.$router.replace({
        name: "order-list",
        query: { order_status: "" }
      });
    }
  },
  components: {
    DetailHead,
    DetailInfo,
    DetailAddress,
    DetailLogistic,
    DetailMessage,
    DetailGoods,
    DetailLog,
    DetailTakeCode,
    DetailInvoice,
    FootOperation,
    CellAssemble
  }
});
</script>

<style scoped>
.order-detail {
  padding-bottom: 60px;
}

.order-detail >>> .foot {
  left: 0;
  bottom: 0;
  width: 100%;
  height: 50px;
  z-index: 100;
  padding: 0 15px;
  background: #ffffff;
  position: fixed;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  border-top: 1px solid #f5f5f5;
  -webkit-box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
}
</style>
