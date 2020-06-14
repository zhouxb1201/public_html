<template>
  <Layout ref="load" class="integral-order-confirm">
    <Navbar :isMenu="false" />
    <ChoiceAddress :address="address" @select="onAddress" v-if="isGoodsType" />

    <div class="list" v-if="items">
      <van-cell-group class="items card-group-box" v-for="(item,index) in items" :key="index">
        <van-cell v-for="child in item.goods_list" :key="child.goods_id">
          <GoodsCard
            :title="child.goods_name"
            :desc="child.spec | filterSpec"
            :coupon="child.coupon.coupon_name"
            :giftvoucher="child.gift_voucher.gift_voucher_name"
            :balance="child.balance"
            :price="child.price"
            :exchange="child.point_exchange"
            :thumb="child.goods_pic | BASESRC"
            :goodsType="goodsType"
          >
            <div slot="bottomRight" class="tags">
              <van-stepper
                class="stepper-box"
                integer
                v-model="params.sku_list[0].num"
                @change="onStepperChange"
                disable-input
                :max="child.max_buy === 0 ? child.stock : child.stock > child.max_buy ? child.max_buy : child.stock"
              />
            </div>
          </GoodsCard>
        </van-cell>
        <van-cell
          :title="cellShippingText"
          :value="cellShippingValue(item)"
          class="cell-right-text"
        />

        <van-cell>
          <div class="foot">
            共&nbsp;
            <span>{{ item.goods_list.length }}</span>&nbsp;件商品&nbsp;&nbsp;&nbsp;小计：
            <span
              class="price"
              v-if="item.total_point && item.total_amount > 0"
            >{{item.total_point}}{{pointText}} + {{item.total_amount | yuan}}</span>
            <span class="price" v-else>{{item.total_point}}{{pointText}}</span>
          </div>
        </van-cell>
      </van-cell-group>
    </div>
    <!--立即支付-->
    <SubmitPay
      :items="items"
      :goodsType="goodsType"
      :shippingType="shipping_type"
      :address="address"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ChoiceAddress from "@/components/ChoiceAddress";
import GoodsCard from "../component/GoodsCard";
import SubmitPay from "../component/SubmitPay";
import { Stepper } from "vant";
import { _decode } from "@/utils/base64";
import { yuan } from "@/utils/filter";
import { GET_ORDERINFO } from "@/api/integral";
import { isEmpty } from "@/utils/util";
export default sfc({
  name: "integral-order-confirm",
  data() {
    const params = JSON.parse(_decode(this.$route.query.params));
    return {
      goodsType: 0, // 商品类型 0==> 普通商品  1 ==> 优惠券 2 ==> 礼品券 3 ==> 余额

      address: {},
      items: [],
      shipping_type: 1,
      orderData: {}
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
    // 请求参数
    params: {
      get() {
        return JSON.parse(_decode(this.$route.query.params));
      },
      set(e) {
        console.log(e);
      }
    },
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    },
    isGoodsType() {
      //根据商品类型判断是否需要地址
      return this.goodsType == 0 ? true : false;
    },
    cellShippingText() {
      let text = "配送方式";
      return text;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_ORDERINFO($this.params)
        .then(res => {
          const data = res.data;
          $this.items = data.shop.map(e => {
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
          $this.goodsType = $this.items[0].goods_list[0].goods_exchange_type;
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
    onAddress(address) {
      this.address = address;
      this.params.address_id = address.id;
      this.loadData();
    },
    cellShippingValue({ shipping_fee }) {
      let text = "快递 ¥" + shipping_fee;
      if (parseFloat(shipping_fee) == 0) {
        text = "快递 包邮";
      }
      if (
        this.isGoodsType == true &&
        (shipping_fee == undefined || shipping_fee == null)
      ) {
        text = "未选择收货地址";
      } else if (
        this.isGoodsType == false &&
        (shipping_fee == undefined || shipping_fee == null)
      ) {
        text = "快递 包邮";
      }
      return text;
    },
    onStepperChange(value) {
      let params = this.params;
      //this.disabledStepper = true;
      this.$Toast.loading({
        forbidClick: true,
        duration: 0
      });
      this.$Toast.clear();
      this.loadData();
    }
  },
  components: {
    ChoiceAddress,
    GoodsCard,
    SubmitPay,
    [Stepper.name]: Stepper
  }
});
</script>

<style scoped>
.integral-order-confirm {
  padding-bottom: 50px;
}
.foot {
  text-align: right;
}

.foot .price {
  color: #ff454e;
}

.tags {
  display: flex;
  justify-content: flex-end;
  align-items: center;
}
.list >>> .van-stepper__input {
  color: #000;
}
</style>
