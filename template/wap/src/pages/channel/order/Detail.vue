<template>
  <Layout ref="load" class="channel-order-detail bg-f8">
    <Navbar/>

    <template v-if="$route.params.type == 'pickupgoods' || $route.params.type == 'retail'">
      <DetailAddress class="card-group-box" :detail="detail"/>
      <DetailLogistic
        :status="detail.order_status"
        :orderid="detail.order_id"
        :info="logistic_info"
        class="card-group-box"
      />
    </template>

    <DetailGoods class="card-group-box" :detail="detail"/>
    <DetailMessage
      class="cell-group card-group-box"
      :detail="detail"
      v-if="$route.params.type == 'pickupgoods' || $route.params.type == 'retail'"
    />
    <DetailInfo class="card-group-box" :detail="detail"/>
    <DetailLog class="card-group-box" :detail="detail"/>

    <div class="foot" v-if="detail.order_status == 0 || detail.order_status == 2">
      <FootOperation
        :items="detail.member_operation"
        :orderid="detail.order_id"
        @callback="onCallback"
      />
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import GoodsCard from "@/components/GoodsCard";
import FootOperation from "../component/FootOperation";
import { GET_ORDERDETAIL } from "@/api/channel";
import { isEmpty } from "@/utils/util";
import DetailLogistic from "../component/order-detail/Logistic";
import DetailAddress from "../component/order-detail/Address";
import DetailInfo from "../component/order-detail/Info";
import DetailGoods from "../component/order-detail/Goods";
import DetailMessage from "../component/order-detail/Message";
import DetailLog from "../component/order-detail/Log";

export default sfc(
  {
  name: "channel-order-detail",
  data() {
    return {
      detail: {},
      logistic_info: {}
    };
  },
  mounted() {
    const $this = this;
    const order_id = $this.$route.params.orderid;
    const order_type = $this.$route.params.type;
    GET_ORDERDETAIL({
      order_id,
      order_type
    })
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
  methods: {
    onCallback({ message }) {
      const $this = this;
      $this.$Toast.success(message);
      setTimeout(() => {
        $this.$router.replace({
          name: "channel-order-list",
          params: {
            type: $this.$route.params.type
          }
        });
      }, 1000);
    }
  },
  components: {
    GoodsCard,
    FootOperation,
    DetailLogistic,
    DetailAddress,
    DetailInfo,
    DetailGoods,
    DetailMessage,
    DetailLog
  }
}
);

</script>
<style scoped>
.channel-order-detail {
  padding-bottom: 60px;
}

.channel-order-detail >>> .foot {
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

