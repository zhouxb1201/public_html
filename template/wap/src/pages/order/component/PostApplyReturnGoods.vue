<template>
  <div>
    <van-cell-group>
      <van-cell>
        <van-row>
          <van-col span="24" class="blod">
            卖家已经同意退货，请把商品回寄到以下地址：
          </van-col>
          <van-col span="10">{{ shopInfo.consigner }}</van-col>
          <van-col span="14">{{ shopInfo.mobile }}</van-col>
          <van-col span="24">{{ shopInfo.address }}</van-col>
        </van-row>
      </van-cell>
      <CellExpressCompanyGroup
        v-if="returnGoodsType === 1"
        :name="refund_shipping_company_name || '请选择物流公司'"
        @select="onSelect"
      />
      <van-field
        label="物流公司"
        readonly
        :value="
          info.refund_shipping_company_name || refund_shipping_company_name
        "
        v-else-if="returnGoodsType === 2"
      />
      <van-field
        label="物流单号"
        clearable
        :placeholder="'请输入物流单号'"
        v-model.trim="refund_shipping_code"
        v-if="returnGoodsType === 1"
      />
      <van-field
        label="物流单号"
        readonly
        :value="
          info.refund_shipping_code
            ? info.refund_shipping_code
            : refund_shipping_code
        "
        v-else-if="returnGoodsType === 2"
      />
    </van-cell-group>
    <div class="foot-btn-group" v-if="returnGoodsType === 1">
      <van-button
        size="normal"
        block
        round
        type="danger"
        :loading="isLoading"
        @click="onSubmit"
        >提交</van-button
      >
    </div>
  </div>
</template>

<script>
import { SUB_REFUNDEXPRESS } from "@/api/order";
import CellExpressCompanyGroup from "./CellExpressCompanyGroup";
export default {
  data() {
    return {
      refund_shipping_code: null,
      refund_shipping_company: null,
      refund_shipping_company_name: null,
      columns: [],

      isLoading: false
    };
  },
  props: {
    info: {
      type: Object
    },
    companyList: {
      type: Array
    },
    shopInfo: {
      type: Object
    },
    returnGoodsType: {
      type: Number
    }
  },
  methods: {
    onSelect(item) {
      this.refund_shipping_company = item.co_id;
      this.refund_shipping_company_name = item.company_name;
    },
    onSubmit() {
      const $this = this;
      const info = $this.info;
      if (!$this.refund_shipping_company) {
        $this.$Toast("请选择物流公司！");
        return false;
      }
      if (!$this.refund_shipping_code) {
        $this.$Toast("请填写物流单号！");
        return false;
      }
      let order_goods_id = [];
      info.goods_list.forEach(e => {
        order_goods_id.push(e.order_goods_id);
      });
      const params = {};
      params.order_id = info.order_id;
      params.order_goods_id = order_goods_id;
      params.refund_express_company = $this.refund_shipping_company;
      params.refund_shipping_no = $this.refund_shipping_code;
      // return false;
      $this.isLoading = true;
      SUB_REFUNDEXPRESS(params)
        .then(res => {
          $this.$Toast.success("提交成功");
          $this.$router.replace({
            name: "order-list"
          });
        })
        .catch(() => {
          $this.isLoading = false;
        });
    }
  },
  components: {
    CellExpressCompanyGroup
  }
};
</script>

<style scoped>
.blod {
  font-weight: 800;
}
</style>
