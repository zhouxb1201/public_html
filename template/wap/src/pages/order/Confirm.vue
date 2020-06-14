<template>
  <Layout ref="load" class="order-confirm bg-f8">
    <Navbar :isMenu="false" />

    <template v-if="isShowShipping">
      <van-cell
        title="配送方式"
        class="cell-panel"
        v-if="!noExpress && isPickup"
      >
        <van-radio-group v-model="shipping_type" class="cell-radio-group">
          <van-radio :name="1">快递配送</van-radio>
          <van-radio :name="2">线下自提</van-radio>
        </van-radio-group>
      </van-cell>
      <div class="shipping-tip" v-show="shippingTip">{{ shippingTip }}</div>
      <ChoiceAddress
        :address="address"
        @select="onAddress"
        v-show="isShowAddress"
      />
    </template>

    <CellPayTypeGroup v-if="isDpay" v-model="payType" />

    <FormGroup :items="formList" ref="FormGroup" v-if="isForm" />

    <div class="list">
      <van-cell-group
        class="items card-group-box"
        v-for="(item, index) in items"
        :key="index"
      >
        <van-cell :title="item.shop_name" icon="shop-o" />
        <van-cell v-for="child in item.goods_list" :key="child.goods_id">
          <GoodsCard
            :title="child.goods_name"
            :desc="child.spec | filterSpec"
            :price="child.discount_price"
            :thumb="child.goods_pic | BASESRC"
            :num="orderType === 'cart' ? child.num : null"
          >
            <div
              slot="bottomRight"
              class="tags"
              v-if="orderType === 'buy_now' && params.goodsType != 4"
            >
              <van-stepper
                class="stepper-box"
                integer
                v-model="params.sku_list[0].num"
                :disabled="disabledStepper"
                disable-input
                @change="onStepperChange"
                @overlimit="onStepperLimit"
                :max="
                  child.max_buy === 0
                    ? child.stock
                    : child.stock > child.max_buy
                    ? child.max_buy
                    : child.stock
                "
              />
            </div>
          </GoodsCard>
        </van-cell>

        <CellPresell v-if="params.presell_id" :info="item.presell_info" />

        <van-cell
          v-if="!isVirtualGoods"
          :title="cellShippingText"
          :value="cellShippingValue(item)"
          class="cell-right-text"
          :is-link="isSelectStore(item)"
          @click="onPopupStore(item)"
        />

        <van-cell
          title="优惠券"
          :value="
            item.coupon_name
              ? '已选：' + item.coupon_name
              : '有 ' + item.coupon_num + ' 张可用优惠券'
          "
          class="cell-right-text"
          is-link
          v-if="item.coupon_num > 0"
          @click="item.coupon_show = true"
        />

        <CellInvoiceGroup
          :tax_fee="item.tax_fee"
          :shop_id="item.shop_id"
          :price="item.amount_for_coupon_discount"
          @getInvoice="getInvoice"
        />
        <van-field
          label="买家留言"
          v-model="item.leave_message"
          type="textarea"
          placeholder="选填：留言内容尽量和商家沟通"
          rows="1"
          autosize
        />

        <CellFullCut v-if="item.full_cut.rule_id" :items="item.full_cut" />

        <van-cell>
          <div class="foot">
            共&nbsp;
            <span>{{ shopGoodsAmount(item.goods_list) }}</span
            >&nbsp;件商品&nbsp;&nbsp;&nbsp;小计：
            <span class="price">{{ item.shop_amount | yuan }}</span>
          </div>
        </van-cell>
        <PopupCoupon
          v-model="item.coupon_show"
          :items="item.coupon_list"
          :title="item.shop_name"
          :get-type="1"
          @use="onUseCoupon"
        />
        <PopupStore
          v-if="$store.state.config.addons.store"
          v-model="item.store_show"
          :store_id="item.store_id"
          :list="item.store_list || []"
          @select="onStore"
        />
      </van-cell-group>
      <CellPointDeduct
        class="card-group-box"
        :isPointDeduct="isPointDeduct"
        :info="pointInfo"
        @load-data="onPointDeduct"
      />
      <CellInfoGroup
        class="card-group-box"
        :columns="cellInfoGroup"
        textAlign="right"
      />
    </div>
    <SubmitBar
      :price="order_data.total_amount"
      button-text="提交订单"
      :disabled="isDisabled"
      :loading="isLoading"
      @submit="onSubmit"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { Stepper } from "vant";
import SubmitBar from "@/components/SubmitBar";
import PopupCoupon from "@/components/PopupCoupon";
import PopupStore from "@/components/PopupStore";
import ChoiceAddress from "@/components/ChoiceAddress";
import GoodsCard from "@/components/GoodsCard";
import FormGroup from "@/components/FormGroup";
import CellFullCut from "@/components/CellFullCut";
import CellPresell from "./component/CellPresell";
import CellInfoGroup from "./component/CellInfoGroup";
import CellPointDeduct from "./component/CellPointDeduct";
import CellPayTypeGroup from "./component/CellPayTypeGroup";
import CellInvoiceGroup from "./component/CellInvoiceGroup";
import { GET_ORDERINFO, CREATE_ORDER, PAY_DPAY } from "@/api/order";
import { GET_STORELIST } from "@/api/store";
import { isEmpty, isIos } from "@/utils/util";
import { _decode } from "@/utils/base64";
import { yuan } from "@/utils/filter";
import { getSession } from "@/utils/storage";

export default sfc({
  name: "order-confirm",
  data() {
    const params = JSON.parse(_decode(this.$route.query.params));
    // cart_from  购物车来源 1=>平台购物车 2=>门店购物车
    // goodsType 商品类型 1==> 普通商品  0 ==> 线下商品（计时计次） 3 ==> 虚拟商品

    let shipping_type = 1; // 配送方式 1==> 快递  2==> 自提  0 ==> 虚拟（无）
    if (params.order_tag == "buy_now") {
      if (params.goodsType == 3 || params.goodsType == 4) {
        shipping_type = 0;
      } else if (params.goodsType == 0 || params.sku_list[0].store_id) {
        // 计时计次商品或门店商品
        shipping_type = 2;
      }
    }
    return {
      params,
      orderType: params.order_tag, //提交类型 cart ==> 购物车 /  buy_now ==> 立即购买

      address: {},
      items: null,
      goods_amount: 0, // 总商品小计
      promotion_amount: 0, // 总优惠券金额
      total_shipping: 0, // 总运费
      total_amount: 0, // 结算金额
      pointInfo: {},
      isPointDeduct: false, //是否可以积分抵扣
      isGivePoint: false, //是否购物返积分
      givePoint: 0,

      isLoading: false,
      disabledStepper: false,

      isPickup: 0, // 是否可以线下自提 1为可以
      noExpress: 0, //是否可以快递配送 默认为0 可以，其他表示为不可快递配送

      formList: [],

      shipping_type,

      payType: 1, //支付方式  默认在线支付

      is_tax_fee: 0, //是否打开发票
      total_tax: 0,
      invoice_list: [],
      shop_id_list: [],
      shop_invoice_list: []
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
  watch: {
    shipping_type(e) {
      if (e) {
        if (e == 2) {
          this.params.shipping_type = e;
        } else {
          delete this.params.shipping_type;
          this.params.sku_list.forEach(s => {
            s.store_id = 0;
            s.store_name = null;
          });
        }
        this.loadData(true);
      }
    }
  },
  computed: {
    cellInfoGroup() {
      const {
        goods_amount,
        promotion_amount,
        total_shipping,
        isGivePoint,
        givePoint
      } = this.$data;
      let arr = [];
      if (isGivePoint && givePoint > 0) {
        arr.push({
          title: "获得" + this.$store.state.member.memberSetText.point_style,
          value: givePoint,
          color: "#ff454e"
        });
      }
      arr.push({
        title: "商品小计",
        value: yuan(goods_amount),
        color: "#ff454e"
      });
      if (!this.params.presell_id && !this.isVirtualGoods) {
        arr.push({
          title: "运费",
          value: yuan(total_shipping),
          color: "#ff454e"
        });
      }
      arr.push({
        title: "优惠金额",
        value: yuan(promotion_amount),
        color: "#ff454e"
      });
      if (this.is_tax_fee) {
        arr.push({
          title: "税费",
          value: yuan(this.total_tax),
          color: "#ff454e"
        });
      }

      return arr;
    },
    order_data() {
      const items = this.items;
      const obj = {};
      obj.custom_order = "";
      obj.order_from =
        this.$store.state.isWeixin && this.$store.getters.config.is_wchat
          ? 1
          : 2;
      obj.is_deduction = this.params.is_deduction ? 1 : 0;
      obj.total_amount = this.total_amount > 0 ? this.total_amount : 0;
      if (this.orderType === "cart") {
        obj.cart_from = this.params.cart_from || 1;
      }
      if (this.shipping_type !== 0) {
        obj.shipping_type = this.shipping_type;
        obj.address_id = this.address.id;
      }
      if (this.isOfflineGoods) {
        //线下商品（计时/计次商品）配送方式为门店自提
        obj.shipping_type = 2;
      }
      if (this.params.group_id) {
        obj.group_id = this.params.group_id;
        if (this.params.record_id) obj.record_id = this.params.record_id;
      }
      if (this.params.shopkeeper_id || getSession("shopkeeper_id")) {
        obj.shopkeeper_id =
          this.params.shopkeeper_id || getSession("shopkeeper_id");
      }
      obj.shop_list = [];
      if (!items) return {};
      items.forEach(e => {
        let shop_obj = {};
        shop_obj.leave_message = e.leave_message;
        shop_obj.shop_id = e.shop_id;
        shop_obj.rule_id = e.full_cut.rule_id ? e.full_cut.rule_id : "";
        shop_obj.coupon_id = e.coupon_id;
        shop_obj.shop_amount =
          e.shop_amount <= 0 ? (e.shop_amount = 0) : e.shop_amount;
        if (this.shipping_type == 2) {
          if (this.isOfflineGoods) {
            //计时计次商品门店id参数名为card_store_id
            shop_obj.card_store_id = e.store_id;
          } else {
            shop_obj.store_id = e.store_id;
            shop_obj.has_store = e.has_store;
          }
        }
        // 发票
        if (this.is_tax_fee) {
          this.shop_invoice_list.forEach(i_item => {
            if (shop_obj.shop_id == i_item.shop_id) {
              shop_obj.invoice = i_item.invoice;
              shop_obj.invoice.invoice_tax =
                e.tax_fee[i_item.invoice.invoice_tax_key];
            }
          });
        }
        shop_obj.goods_list = [];
        e.goods_list.forEach(g => {
          let goods_obj = {};
          goods_obj.goods_id = g.goods_id;
          goods_obj.goods_name = g.goods_name;
          goods_obj.sku_id = g.sku_id;
          goods_obj.price = g.price;
          goods_obj.num = g.num;
          goods_obj.discount_price = g.discount_price;
          goods_obj.seckill_id = g.seckill_id;
          goods_obj.channel_id = g.channel_id ? g.channel_id : "";
          goods_obj.discount_id = g.discount_id;
          goods_obj.bargain_id = g.bargain_id ? g.bargain_id : "";
          if (this.params.presell_id) {
            goods_obj.presell_id = this.params.presell_id;
          }
          shop_obj.goods_list.push(goods_obj);
        });
        obj.shop_list.push(shop_obj);
      });
      // console.log(obj);
      return obj;
    },
    isDisabled() {
      return this.order_data.total_amount >= 0 &&
        this.order_data.total_amount !== undefined
        ? false
        : true;
    },
    isForm() {
      return !isEmpty(this.formList);
    },
    // 是否显示配送
    isShowShipping() {
      return (
        !this.isVirtualGoods && !this.isOfflineGoods && !this.isCourseGoods
      );
    },
    // 是否显示配送地址
    isShowAddress() {
      let flag = true;
      if (this.shipping_type == 2 && this.items) {
        flag = !this.items.every(({ has_store }) => parseInt(has_store));
      }
      return flag;
    },
    // 配送/自提相关提示
    shippingTip() {
      let text = null;
      if (this.noExpress) {
        // 该单不可快递配送文案提示
        text = "由于部分商品商城缺货，只能通过线下自提下单取货。";
      } else {
        if (this.shipping_type == 2 && this.items) {
          // 线下自提时，多店铺情况下，部分店铺不支持自提文案提示
          if (!this.items.every(({ has_store }) => parseInt(has_store))) {
            text =
              "由于部分商家不支持线下自提，请为不支持线下自提的订单商品选择收货地址";
          }
        }
      }
      return text;
    },
    cellShippingText() {
      let text = "配送方式";
      if (this.isOfflineGoods) {
        text = "使用门店";
      }
      return text;
    },
    // 是否虚拟商品
    isVirtualGoods() {
      let flag = false;
      if (this.orderType === "buy_now" && this.params.goodsType == 3) {
        flag = true;
      }
      return flag;
    },
    // 是否线下商品(计时/次商品)
    isOfflineGoods() {
      let flag = false;
      if (this.orderType === "buy_now" && this.params.goodsType == 0) {
        flag = true;
      }
      return flag;
    },
    // 是否知识付费商品
    isCourseGoods() {
      let flag = false;
      if (this.orderType === "buy_now" && this.params.goodsType == 4) {
        flag = true;
      }
      return flag;
    },
    // 货到付款
    isDpay() {
      return (
        !this.params.presell_id &&
        !this.isOfflineGoods &&
        !this.isVirtualGoods &&
        !this.isCourseGoods &&
        this.$store.getters.config.dpay
      );
    },
    // 门店购物车订单
    isStoreCartOrder() {
      return this.params.cart_from == 2;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    onAddress(address) {
      this.address = address;
      this.params.address_id = address.id;
      this.loadData(true);
    },
    cellShippingValue({ shipping_fee, store_name, has_store }) {
      // has_store  是否可以选择门店，为1可以选择门店则否
      const $this = this;
      if ($this.isOfflineGoods) {
        return store_name || "请选择门店";
      }
      let text = "";
      if ($this.shipping_type == 1) {
        fnText();
      }
      if ($this.shipping_type == 2) {
        if (has_store) {
          text = store_name || "请选择自提门店";
        } else {
          fnText();
        }
      }
      function fnText() {
        text = "快递 ¥" + shipping_fee;
        if (parseFloat(shipping_fee) == 0) {
          text = $this.params.presell_id ? "快递" : "快递 包邮";
        }
        if (!$this.address.id) {
          text = "未选择收货地址";
        }
      }
      return text;
    },
    isSelectStore(item) {
      return !!(
        this.isOfflineGoods ||
        (item.has_store && this.shipping_type == 2)
      );
    },
    onPopupStore(item) {
      item.store_show = this.isSelectStore(item);
    },
    onStepperChange(value) {
      if (this.orderType === "buy_now") {
        this.disabledStepper = true;
        this.loadData(true);
      }
    },
    shopGoodsAmount(item) {
      const arr = item.map(({ num }) => num);
      return arr.reduce((x, y) => x + y);
    },
    onStepperLimit(action) {
      if (action === "plus") {
        this.$Toast("已超出最大购买数！");
      } else {
        this.$Toast("至少选择一件！");
      }
    },
    onPointDeduct(checked) {
      this.params.is_deduction = checked ? 1 : 0;
      this.loadData(true);
    },
    loadData(showLoading) {
      const $this = this;
      $this.getLocation().then(() => {
        GET_ORDERINFO($this.params, showLoading)
          .then(({ data, message }) => {
            if (message) $this.$Toast(message);
            $this.goods_amount = data.goods_amount;
            $this.promotion_amount = data.promotion_amount;
            $this.total_shipping = data.total_shipping;
            $this.total_amount = data.amount;
            $this.isPickup = parseInt(data.has_store);
            $this.noExpress = data.has_express;
            if ($this.noExpress) {
              $this.shipping_type = 2;
            }
            $this.items = data.shop.map((e, i) => {
              e.leave_message = $this.items ? $this.items[i].leave_message : "";
              e.shop_amount = e.total_amount;

              // 优惠券相关
              let current_coupon_id = e.goods_list[0].coupon_id;
              e.coupon_show = false;
              e.coupon_id = "";
              e.coupon_name = "";
              e.coupon_list.forEach(c => {
                c.shop_id = e.shop_id;
                c.selected = false;
                c.loading = false;
              });
              if (current_coupon_id) {
                e.coupon_list.forEach(c => {
                  if (c.coupon_id == current_coupon_id) {
                    e.coupon_id = c.coupon_id;
                    e.coupon_name = c.coupon_name;
                    c.selected = true;
                    c.loading = !c.selected;
                  }
                });
              }

              if (e.tax_fee) {
                $this.is_tax_fee = 1;
                $this.total_tax = data.total_tax;
              }

              // 门店相关
              e.store_show = false;
              e.store_id = e.store_id || "";
              e.store_name = e.store_name || "";

              // 预售相关
              if ($this.params.presell_id) {
                e.goods_list.forEach(g => {
                  g.presell_price = e.presell_info.allmoney;
                  e.presell_info.frontMoney =
                    parseFloat(e.presell_info.firstmoney) * parseInt(g.num); // 计算定金
                  e.presell_info.tailMoney = parseFloat(
                    e.presell_info.final_real_money
                  ); // 计算尾款
                  e.presell_info.tailText =
                    "(含运费" + (e.tax_fee ? "税费" : "") + ")";
                  g.stock = e.presell_info.presellnum;
                  g.max_buy = e.presell_info.maxbuy;
                });
              }
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
            // 是否开启积分抵扣
            $this.isPointDeduct = parseInt(data.is_point_deduction)
              ? true
              : false;
            $this.pointInfo = data.deduction_point;
            // 是否开启购物返积分
            $this.isGivePoint = parseInt(data.is_point) ? true : false;
            $this.givePoint = data.total_give_point;

            if ($this.orderType === "buy_now") {
              $this.disabledStepper = false;
            }
            $this.formList = !isEmpty(data.customform) ? data.customform : [];
            $this.$refs.load.success();
          })
          .catch(({ code, message }) => {
            $this.$Dialog.alert({ message }).then(() => {
              if (code == -2) {
                $this.$router.replace("/mall/cart");
              }
            });
            if (
              $this.$store.getters.isBingFlag &&
              $this.$store.getters.isBindMobile
            ) {
              $this.$refs.load.fail();
            } else {
              $this.$refs.load.result();
            }
          });
      });
    },
    getLocation() {
      return new Promise((resolve, reject) => {
        if (this.shipping_type == 2 || this.isOfflineGoods) {
          this.$store
            .dispatch("getBMapLocation")
            .then(({ location }) => {
              this.params.lng = location.lng;
              this.params.lat = location.lat;
              resolve();
            })
            .catch(error => {
              this.$Toast(error);
              resolve();
            });
        } else {
          resolve();
        }
      });
    },
    // 选择门店
    onStore({ shop_id, store_id, store_name }) {
      this.items.forEach(shop => {
        if (shop.shop_id == shop_id) {
          shop.store_id = store_id;
          shop.store_name = store_name;
          this.params.sku_list.forEach((s, i) => {
            const goods_item = shop.goods_list.filter(
              g => g.sku_id == s.sku_id
            )[0];
            if (goods_item) {
              s.store_id = store_id;
              s.store_name = store_name;
            }
          });
          this.loadData(true);
        }
      });
    },
    // 使用优惠券
    onUseCoupon(item, shop_id) {
      const flag = this.items.some(({ coupon_id }) => {
        if (coupon_id == item.coupon_id) {
          this.$Toast("该优惠券只能使用一次！");
          return true;
        }
      });
      if (!flag) {
        this.items.forEach(shop => {
          if (shop.shop_id == item.shop_id) {
            shop.coupon_id = item.coupon_id;
            shop.coupon_list = shop.coupon_list.map(c => {
              c.selected = c.coupon_id == item.coupon_id ? true : false;
              c.loading = c.selected;
              return c;
            });
            this.params.sku_list.forEach((s, i) => {
              const goods_item = shop.goods_list.filter(
                g => g.sku_id == s.sku_id
              )[0];
              if (goods_item) {
                s.coupon_id = item.coupon_id;
              }
            });
            this.loadData(true);
          }
        });
      }
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
      if ($this.shipping_type == 2) {
        if ($this.isOfflineGoods) {
          //计时计次商品判断card_store_id
          if (
            !$this.order_data.shop_list.every(
              ({ card_store_id }) => card_store_id
            )
          ) {
            return $this.$Toast("请选择门店！");
          }
        } else {
          const flag = $this.order_data.shop_list.some(
            ({ store_id, has_store }, i) => {
              if (!has_store && !$this.order_data.address_id) {
                return $this.$Toast(
                  $this.items[i].shop_name + "需要选择收货地址！"
                );
              }
              if (has_store && !store_id) {
                return $this.$Toast(
                  $this.items[i].shop_name + "需要选择自提门店！"
                );
              }
            }
          );
          if (flag) return false;
        }
      }

      // console.log($this.order_data)
      // return
      $this.isLoading = true;
      CREATE_ORDER($this.order_data, $this.order_data.shipping_type == 2)
        .then(({ data }) => {
          if ($this.payType) {
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
          } else {
            $this.onDpay(data.out_trade_no);
          }
        })
        .catch(({ code, message }) => {
          if (code == -2) {
            this.loadData(true);
          }
          $this.isLoading = false;
        });
    },
    onDpay(out_trade_no) {
      PAY_DPAY({ out_trade_no })
        .then(() => {
          this.$router.replace({
            name: "pay-result",
            query: {
              out_trade_no,
              dpay_order: 1
            }
          });
        })
        .catch(() => {
          this.isLoading = false;
        });
    },
    //发票
    getInvoice(invoice, shop_id, is_tax) {
      const $this = this;

      let obj = {};
      obj.shop_id = shop_id;
      obj.tax_type = is_tax == 1 ? invoice.type : 0;

      let bill = {};
      bill.shop_id = shop_id;
      bill.invoice = is_tax == 1 ? invoice : {};

      if ($this.shop_id_list.indexOf(shop_id) == -1) {
        $this.shop_id_list.push(shop_id);
        $this.invoice_list.push(obj);
        $this.shop_invoice_list.push(bill);
      } else {
        $this.invoice_list.forEach(e => {
          if (e.shop_id == shop_id) {
            e.tax_type = is_tax == 1 ? invoice.type : 0;
          }
        });
        $this.shop_invoice_list.forEach(e => {
          if (e.shop_id == shop_id) {
            e.invoice = is_tax == 1 ? invoice : {};
          }
        });
      }
      $this.params.invoice_list = $this.invoice_list;
      $this.loadData(true);
    }
  },
  beforeDestroy() {
    var iframes = document.getElementsByTagName("iframe")[0];
    iframes && iframes.remove();
  },
  components: {
    SubmitBar,
    ChoiceAddress,
    PopupCoupon,
    PopupStore,
    FormGroup,
    CellFullCut,
    GoodsCard,
    CellPresell,
    CellInfoGroup,
    CellPointDeduct,
    CellPayTypeGroup,
    CellInvoiceGroup,
    [Stepper.name]: Stepper
  }
});
</script>

<style scoped>
.order-confirm {
  padding-bottom: 50px;
}
.shipping-tip {
  padding: 10px;
  color: #f56723;
  font-size: 12px;
  line-height: 1.5;
  background-color: #fff7cc;
}
.cell-right-text >>> .van-cell__value {
  font-size: 12px;
  color: #909399;
}

.stepper-box >>> .van-stepper__input[disabled] {
  color: #323233;
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
</style>
