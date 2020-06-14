<template>
  <Layout ref="load" class="microshop-confirmorder bg-f8">
    <Navbar :isMenu="false" />
    <ChoiceAddress :address="address" @select="onAddress" v-show="shipping_type == 1"></ChoiceAddress>
    <FormGroup :items="formList" ref="FormGroup" v-if="isForm" />
    <van-cell-group class="items card-group-box" v-for="(item,index) in items" :key="index">
      <van-cell icon="shop-o" :title="item.shop_name" />
      <div class="goods-info van-hairline--top-bottom">
        <van-cell v-for="child in item.goods_list" :key="child.goods_id">
          <GoodsCard
            :title="child.goods_name"
            :desc="child.spec | filterSpec"
            :price="child.price"
            :thumb="child.goods_pic | BASESRC"
            :num="child.num"
          ></GoodsCard>
        </van-cell>
      </div>
      <van-cell>
        <div class="cell-foot">
          共&nbsp;
          <span>{{item.goods_list.length}}</span>&nbsp;件商品&nbsp;&nbsp;&nbsp;小计：
          <span class="price">{{item.shop_amount | yuan}}</span>
        </div>
      </van-cell>
    </van-cell-group>
    <van-cell-group class="card-group-box" v-if="total_shipping">
      <van-cell>
        <div class="item">
          <div class="title">运费</div>
          <div class="value">{{total_shipping | yuan}}</div>
        </div>
      </van-cell>
    </van-cell-group>
    <SubmitBar
      label="合计金额（含运费）"
      type="primary"
      :price="order_data.total_amount"
      button-text="提交订单"
      :loading="isLoading"
      loading-text="提交订单..."
      @submit="onSubmit"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ChoiceAddress from "@/components/ChoiceAddress";
import SubmitBar from "@/components/SubmitBar";
import FormGroup from "@/components/FormGroup";
import GoodsCard from "@/components/GoodsCard";
import { _decode } from "@/utils/base64";
import { GET_SHOPINFO } from "@/api/microshop";
import { CREATE_ORDER } from "@/api/order";
import { isEmpty, isIos } from "@/utils/util";
import { getSession } from "@/utils/storage";
export default sfc({
  name: "microshop-confirmorder",
  data() {
    const params = JSON.parse(_decode(this.$route.query.params));
    return {
      items: null,

      isLoading: false,
      address: {},

      formList: [], //自定义表单组件

      shipping_type: 1, // 配送方式 1==> 快递  2==> 自提  0 ==> 虚拟（无）
      total_amount: 0, //总金额
      total_shipping: 0 //总运费
    };
  },
  filters: {
    // 过滤规格数组
    filterSpec(value) {
      if (isEmpty(value)) return "";
      let newArr = [];
      value.forEach(e => {
        let str = e.spec_name + " " + e.spec_value_name;
        newArr.push(str);
      });
      return newArr.join(" , ");
    }
  },
  computed: {
    //请求参数
    params: {
      get() {
        return JSON.parse(_decode(this.$route.query.params));
      },
      set(e) {
        console.log(e);
      }
    },
    // 提交类型 2 ==> 立即开店 /  3 ==> 立即续费 / 4 ==>立即升级
    orderType() {
      return this.params.order_type;
    },
    isForm() {
      return !isEmpty(this.formList);
    },
    // 计算结算金额
    /*pay_total_amount() {
      let total = 0;
      const items = this.items ? this.items : [];
      items.forEach(({ shop_amount }) => {
        total += shop_amount <= 0 ? 0 : shop_amount;
      });
     
      return total;
    },*/
    isshopkeeper() {
      //判断是否为店主 0 => 是 1 => 不是
      return getSession("info").isshopkeeper;
    },
    shopkeeper_id() {
      //微店id
      return getSession("info").uid;
    },
    order_data() {
      const items = this.items;
      const obj = {};
      obj.custom_order = "";
      obj.order_from = this.$store.state.is_weixin ? 1 : 2;
      obj.total_amount = this.total_amount;
      if (this.shipping_type !== 0) {
        obj.shipping_type = this.shipping_type;
      }

      if (this.isshopkeeper) {
        //是否是微店店主
        obj.shopkeeper_id = this.shopkeeper_id;
      }
      if (this.orderType) {
        obj.order_type = this.orderType;
      }

      if (this.shipping_type == 1) {
        obj.address_id = this.address.id;
      }
      obj.shop_list = [];
      if (!items) return {};
      items.forEach(e => {
        let shop_obj = {};
        shop_obj.leave_message = e.leave_message;
        shop_obj.shop_id = e.shop_id;
        shop_obj.rule_id = e.full_cut.rule_id ? e.full_cut.rule_id : ""; //满减id
        shop_obj.coupon_id = e.coupon_id ? e.coupon_id : ""; //优惠券id
        shop_obj.shop_amount =
          e.shop_amount <= 0 ? (e.shop_amount = 0) : e.shop_amount;
        if (this.shipping_type == 2) {
          shop_obj.store_id = e.store_id;
        }
        if (this.shipping_type == 0) {
          shop_obj.card_store_id = e.card_store_id;
        }
        shop_obj.goods_list = [];
        e.goods_list.forEach(g => {
          let goods_obj = {};
          goods_obj.goods_id = g.goods_id;
          goods_obj.sku_id = g.sku_id;
          goods_obj.price = g.price;
          goods_obj.num = g.num;
          goods_obj.discount_price = g.discount_price;
          goods_obj.seckill_id = g.seckill_id ? g.seckill_id : "";
          goods_obj.channel_id = g.channel_id ? g.channel_id : "";
          goods_obj.discount_id = g.discount_id ? g.discount_id : "";
          goods_obj.bargain_id = g.bargain_id ? g.bargain_id : "";
          goods_obj.presell_id = g.presell_id ? g.presell_id : "";

          shop_obj.goods_list.push(goods_obj);
        });
        obj.shop_list.push(shop_obj);
      });
      return obj;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_SHOPINFO($this.params)
        .then(res => {
          if (res.message) $this.$Toast(res.message);
          const data = res.data;
          $this.total_shipping = data.total_shipping;
          $this.total_amount = data.amount;
          $this.items = data.shop.map(e => {
            e.leave_message = "";
            e.shop_amount = e.total_amount;

            // 优惠券相关
            e.coupon_show = false;
            e.coupon_id = "";
            // 门店相关
            e.store_show = false;
            e.store_id = "";
            e.store_name = "";
            e.card_store_id = "";

            return e;
          });
          if (!isEmpty(data.address)) {
            $this.address = {
              name: data.address.consigner,
              tel: data.address.mobile,
              id: data.address.address_id,
              address:
                data.address.province_name +
                data.address.city_name +
                data.address.district_name +
                data.address.address_detail
            };
          }
          $this.formList = !isEmpty(data.customform) ? data.customform : [];
          $this.$refs.load.success();
        })
        .catch(() => {
          if (
            $this.$store.getters.isBingFlag &&
            $this.$store.getters.isBindMobile
          ) {
            $this.$refs.load.fail();
          } else {
            $this.$refs.load.result();
          }
        });
    },
    onSubmit() {
      const $this = this;
      const form_data = $this.$refs["FormGroup"]
        ? $this.$refs["FormGroup"].getFormData()
        : "";
      if ($this.isForm) {
        if (!form_data) return false;
        $this.order_data.custom_order = JSON.stringify(form_data);
      }
      if ($this.shipping_type == 1) {
        if (!$this.order_data.address_id)
          return $this.$Toast("请选择收货地址！");
      }
      // return console.log($this.order_data)
      $this.isLoading = true;
      CREATE_ORDER($this.order_data)
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
    },
    onAddress(address) {
      //选择切换地址
      this.address = address;
      this.params.address_id = address.id;
      this.loadData();
    }
  },
  components: {
    ChoiceAddress,
    SubmitBar,
    FormGroup,
    GoodsCard
  }
});
</script>

<style scoped>
.microshop-gift {
  padding-bottom: 50px;
}
.cell-foot {
  text-align: right;
}

.cell-foot .price {
  color: #ff454e;
}

.gift-type {
  margin-top: 10px;
}
.icon-balance3 >>> .van-icon-v-icon-balance3 {
  color: #ff454e;
  font-size: 20px;
}
.icon-wx-pay >>> .van-icon-v-icon-wx-pay {
  color: #00c403;
  font-size: 20px;
}

.icon-alipay >>> .van-icon-v-icon-alipay {
  color: #009fe8;
  font-size: 20px;
}
.van-cell.disabled {
  color: #999;
  background-color: #e8e8e8;
}
.card-group-box >>> .item {
  width: 100%;
  display: -ms-flexbox;
  display: flex;
  line-height: 24px;
  position: relative;
  background-color: #fff;
  color: #323233;
  font-size: 14px;
  overflow: hidden;
}
.card-group-box >>> .item .title {
  white-space: nowrap;
  max-width: 90px;
  -ms-flex: 1;
  flex: 1;
}
.card-group-box >>> .item .value {
  text-align: right;
  color: rgb(255, 69, 78);
  flex: 1;
  overflow: hidden;
  position: relative;
  vertical-align: middle;
  font-size: 12px;
}
</style>
