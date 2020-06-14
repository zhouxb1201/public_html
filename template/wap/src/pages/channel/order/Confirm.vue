<template>
  <Layout ref="load" class="channel-order-confirm bg-f8">
    <Navbar :isMenu="false" />
    <ChoiceAddress :address="address" @select="onAddress" v-if="buy_type == 'pickupgoods'" />
    <van-cell-group class="items card-group-box">
      <van-cell icon="shop-o" :title="items.shop_name" />
      <van-cell v-for="(item,index) in items.shop_list" :key="index">
        <GoodsCard
          class="goods-card"
          :thumb="item.goods_picture"
          :title="item.goods_name"
          :desc="item.sku_name"
          :price="buy_type == 'purchase' ? item.price : ''"
          :num="item.num"
        >
          <div slot="tags" class="text-right" v-if="buy_type == 'purchase'">采购于：{{item.purchase_to}}</div>
        </GoodsCard>
      </van-cell>
      <van-cell title="配送方式" v-if="buy_type == 'pickupgoods'">
        <div class="fs-12">{{items.total_shipping_fee | filterShipping}}</div>
      </van-cell>
      <van-field
        label="买家留言"
        v-if="buy_type == 'pickupgoods'"
        v-model="items.buyer_message"
        type="textarea"
        placeholder="选填：留言内容尽量和商家沟通"
        rows="1"
        autosize
      />
      <van-cell>
        <div class="cell-foot">
          <div>
            共
            <span>{{items.total_quantity}}</span>件商品
          </div>
          <div>
            小计：
            <span>{{countTotalAmount | yuan}}</span>
          </div>
        </div>
      </van-cell>
    </van-cell-group>
    <SubmitBar
      :price="countTotalAmount"
      :disabled="isDisabled"
      :loading="isLoading"
      label="合计金额："
      button-text="提交订单"
      @submit="onSubmit"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ChoiceAddress from "@/components/ChoiceAddress";
import GoodsCard from "@/components/GoodsCard";
import SubmitBar from "@/components/SubmitBar";
import { GET_ORDERINFO, CREATE_ORDER, COUNT_FREIGHT } from "@/api/channel";
import { isIos } from "@/utils/util";
export default sfc({
  name: "channel-order-confirm",
  data() {
    return {
      address: {},
      items: {},
      isLoading: false
    };
  },
  filters: {
    filterShipping(value) {
      let text = "快递 ¥" + value;
      if (parseFloat(value) == 0) {
        text = "快递 包邮";
      }
      return value == undefined || value == null ? "未选择收货地址" : text;
    }
  },
  computed: {
    buy_type() {
      return this.$route.params.type;
    },
    isDisabled() {
      return this.items.total_money >= 0 && this.items.total_money !== undefined
        ? false
        : true;
    },
    // 计算结算金额
    countTotalAmount() {
      let total_money = 0;
      let shop_total_amount = 0;
      if (this.items.shop_list) {
        let total_shipping_fee = parseFloat(this.items.total_shipping_fee); // 运费
        this.items.shop_list.forEach(e => {
          shop_total_amount += parseFloat(e.price) * parseInt(e.num);
        });
        total_money = shop_total_amount + total_shipping_fee;
      }
      return total_money;
    }
  },
  mounted() {
    const $this = this;
    GET_ORDERINFO($this.buy_type)
      .then(({ data }) => {
        let items = data;
        if ($this.buy_type == "pickupgoods") {
          $this.address = {
            name: data.consigner,
            tel: data.mobile,
            id: data.address_id,
            address: data.address_info
          };
          items.buyer_message = "";
        }
        $this.items = items;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    onAddress(address) {
      this.address = address;
      let goods_info = "";
      let arr = [];
      this.items.shop_list.forEach(e => {
        arr.push(e.goods_id + ":" + e.num);
      });
      goods_info = arr.join(",");
      COUNT_FREIGHT({ address_id: address.id, goods_info }).then(({ data }) => {
        this.items.total_shipping_fee = data.free_money;
      });
    },
    onSubmit() {
      const $this = this;
      const params = {};
      params.buy_type = $this.buy_type;
      if ($this.buy_type == "pickupgoods") {
        if (!$this.address.id) {
          $this.$Toast("请选择收货地址！");
          return false;
        }
        params.address_id = $this.address.id;
        params.buyer_message = $this.items.buyer_message;
      }
      // console.log(params);
      // return;
      $this.isLoading = true;
      CREATE_ORDER(params)
        .then(({ data }) => {
          if (isIos()) {
            location.replace(
              `${$this.$store.state.domain}/wap/pay/payment?out_trade_no=${data.out_trade_no}`
            );
          } else {
            $this.$router.replace({
              name: "pay-payment",
              query: {
                out_trade_no: data.out_trade_no
              }
            });
          }
        })
        .catch(error => {
          $this.isLoading = false;
        });
    }
  },
  components: {
    GoodsCard,
    SubmitBar,
    ChoiceAddress
  }
});
</script>
<style scoped>
.channel-order-confirm {
  padding-bottom: 50px;
}

.goods-card {
  background: #ffffff;
  padding: 0;
}

.cell-foot {
  display: flex;
  justify-content: flex-end;
}

.cell-foot > div {
  display: flex;
  margin-left: 10px;
}

.cell-foot span {
  padding: 0 4px;
  color: #ff454e;
}
</style>
