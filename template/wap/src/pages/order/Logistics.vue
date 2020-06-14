<template>
  <Layout ref="load" class="order-logistics bg-f8">
    <Navbar />
    <van-cell-group class="head">
      <van-cell>
        <van-row type="flex" class="box">
          <van-col span="8" class="img">
            <img
              v-lazy="headInfo.express_company_logo"
              pic-type="shop"
              :key="headInfo.express_company_logo"
            />
          </van-col>
          <van-col span="16" class="text">
            <div>
              订单编号：
              <span>{{order_no}}</span>
            </div>
            <div>
              快递公司：
              <span>{{headInfo.expTextName}}</span>
            </div>
            <div>
              快递单号：
              <span>{{headInfo.mailNo}}</span>
            </div>
          </van-col>
        </van-row>
      </van-cell>
    </van-cell-group>
    <van-tabs v-model="active" class="info" :class="isShowTitle">
      <van-tab :title="items.packet_name" v-for="(items,index) in packet_list" :key="index">
        <van-steps
          direction="vertical"
          :active="0"
          active-color="chocolate"
          v-if="items.shipping_info.expTextName"
        >
          <van-step v-for="(item,i) in items.shipping_info.data" :key="i">
            <div class="mb-06">{{item.context}}</div>
            <div class="fs-12">{{item.time}}</div>
          </van-step>
        </van-steps>
        <div v-else class="empty">暂无物流信息</div>
      </van-tab>
    </van-tabs>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { Step, Steps } from "vant";
import { GET_LOGISTICSDETAIL } from "@/api/order";
import { isEmpty } from "@/utils/util";

export default sfc({
  name: "order-logistics",
  data() {
    return {
      active: 0,
      order_no: "",
      packet_list: []
    };
  },
  computed: {
    headInfo() {
      const $this = this;
      const obj = {};
      const list = $this.packet_list[$this.active];
      if (!isEmpty(list)) {
        obj.express_company_logo = list.express_company_logo;
        obj.mailNo = list.shipping_info
          ? list.shipping_info.mailNo
          : "暂无快递公司";
        obj.expTextName = list.shipping_info
          ? list.shipping_info.expTextName
          : "暂无快递单号";
      }
      return obj;
    },
    isShowTitle() {
      return this.packet_list.length > 1 ? "" : "hidden";
    }
  },
  mounted() {
    const $this = this;
    GET_LOGISTICSDETAIL($this.$route.params.orderid)
      .then(res => {
        $this.order_no = res.data.order_no;
        $this.packet_list = res.data.goods_packet_list;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  components: {
    [Step.name]: Step,
    [Steps.name]: Steps
  }
});
</script>

<style scoped>
.order-logistics >>> .head {
  margin-bottom: 10px;
}

.order-logistics >>> .head .box {
  display: flex;
  align-items: center;
}

.order-logistics >>> .head .img {
  display: flex;
  justify-content: center;
}

.order-logistics >>> .head .img img {
  display: block;
  width: auto;
  max-width: 100%;
  height: auto;
  max-height: 72px;
}

.order-logistics >>> .head .text {
  font-size: 12px;
  padding-left: 10px;
}

.mb-06 {
  margin-bottom: 6px;
}

.order-logistics .info {
  margin-bottom: 50px;
}

.order-logistics .info.hidden {
  padding-top: 0;
}

.order-logistics .info.hidden >>> .van-tabs__wrap {
  display: none;
}
</style>
