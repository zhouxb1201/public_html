<template>
  <van-sku
    v-model="show"
    :sku="skuInfo"
    :goods="goodsInfo"
    :goods-id="goodsInfo.id"
    :quota="quota"
    :close-on-click-overlay="closeOnClickOverlay"
    :reset-selected-sku-on-hide="resetSelectedSkuOnHide"
    :reset-stepper-on-hide="resetStepperOnHide"
    :custom-stepper-config="customStepperConfig"
    :initial-sku="initialSku"
    @stepper-change="stepperChange"
    @sku-selected="skuSelect"
    @sku-close="skuClose"
    :get-container="container"
    ref="getSkuData"
  >
    <slot name="header-price" slot="sku-header-price">
      <HeaderPirce :action="action" :goods-info="goodsInfo" :promote-type="promoteType" />
    </slot>
    <ActionBtn
      class="action-group"
      slot="sku-actions"
      slot-scope="props"
      :goods-info="goodsInfo"
      :promote-type="promoteType"
      :promote-params="promoteParams"
      :action="action"
      @action="onAction"
    />
  </van-sku>
</template>

<script>
import { Sku } from "vant";
import HeaderPirce from "./HeaderPirce";
import ActionBtn from "./ActionBtn";
const defaultInfo = {
  goods_id: 0,
  goods_image: "",
  goods_name: "",
  goods_type: 1,
  max_buy: 0,
  max_market_price: "0",
  max_price: "0",
  min_buy: 0,
  min_market_price: "0",
  min_price: "0",
  state: null,
  sku: {
    list: [
      {
        attr_value_items: "",
        group_limit_buy: "",
        group_price: "",
        id: 0,
        market_price: "0",
        min_buy: 0,
        price: "0",
        s: [],
        sku_name: "",
        stock_num: 0
      }
    ],
    tree: [
      {
        k: "",
        k_id: 0,
        v: [
          {
            id: 0,
            name: ""
          }
        ],
        k_s: ""
      }
    ]
  },
  is_allow_buy: true,
  is_allow_browse: true,
  member_is_label: 2
};
const defaultGoodsInfo = {
  id: 0,
  title: "",
  picture: "",
  goodsType: "",
  promoteType: "normal",
  selectedNum: 1,
  selectedSkuComb: null,
  isSpec: false,
  stock: 0,
  maxBuy: 0,
  goodsPrice: 0,
  marketPrice: 0,
  goodsState: 1,
  goodsStateText: ""
};
const defaultSkuInfo = {
  tree: [],
  list: [],
  price: 0,
  stock_num: 0
};
export default {
  data() {
    return {
      // 选中的sku数据
      selectedNum: 1,
      selectedSkuValue: null,
      selectedSkuComb: null,
      container: this.getContainer
        ? () => document.querySelector(this.getContainer)
        : null
    };
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    getContainer: String,
    // 商品信息
    info: {
      type: [String, Object],
      default: defaultInfo
    },
    getGoodsInfo: Function,
    /**
     * 活动类型
     * normal    =>    普通商品类型(默认)
     * seckill   =>    秒杀商品类型
     * group     =>    拼团商品类型
     * presell   =>    预售商品类型
     * bargain   =>    砍价商品类型
     * limit     =>    限时商品类型
     */
    promoteType: {
      type: String,
      default: "normal"
    },
    // 活动相关参数
    promoteParams: Object,
    /**
     * 单个行动按钮(为空则默认)
     * 传入指定类型，如  addCart/buy/group...
     * 只显示单个确定按钮
     */
    action: String,

    /**
     * 初始sku数据
     * id  ==> 选中的skuid
     * num ==> 选中的数量
     */
    initial: {
      type: Object,
      default: () => {}
    },

    closeOnClickOverlay: {
      type: Boolean,
      default: true
    },
    resetSelectedSkuOnHide: {
      type: Boolean,
      default: false
    },
    resetStepperOnHide: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    show: {
      get(e) {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    },
    goodsInfo() {
      if (!this.info) return defaultGoodsInfo;
      let selectedSkuComb = this.selectedSkuComb;
      let selectedNum = this.selectedNum;
      if (this.$refs.getSkuData) {
        selectedSkuComb = this.currentSkuComb(
          this.$refs.getSkuData.selectedSkuComb
        );
        // selectedNum = this.$refs.getSkuData.selectedNum;
      }
      const info = Object.assign({}, this.info);
      let goods = {};
      goods.id = info.goods_id;
      goods.title = info.goods_name;
      goods.shopId = info.shop_id;
      goods.shopName = info.shop_name;
      goods.picture = this.currentSkuImg(info);
      goods.goodsType = info.goods_type;
      goods.promoteType = this.promoteType;
      goods.selectedNum = selectedNum;
      goods.selectedSkuComb = selectedSkuComb;
      goods.isCollect = info.is_collection;
      goods.isSpec = !this.skuInfo.none_sku;
      goods.stock = this.currentSkuStock(selectedSkuComb);
      goods.maxBuy =
        info.goods_type == 4 ? 1 : this.currentSkuMaxBuy(selectedSkuComb);
      goods.goodsPrice = this.currentPrice(selectedSkuComb);
      goods.marketPrice = this.currentMarketPrice(selectedSkuComb);
      if (this.promoteType == "group") {
        //拼团商品价格
        goods.groupGoodsPrice = this.currentGroupPrice(selectedSkuComb);
        goods.groupMaxBuy =
          info.goods_type == 4
            ? 1
            : this.currentGroupSkuMaxBuy(selectedSkuComb);
      }
      if (this.promoteType == "presell") {
        //预售定金和尾款
        goods.frontMoney = this.presellInfo(selectedSkuComb).frontMoney;
        goods.tailMoney = this.presellInfo(selectedSkuComb).tailMoney;
        goods.allMoney = this.presellInfo(selectedSkuComb).allMoney;
      }
      goods.goodsState = this.goodsState(info, goods.stock, goods.maxBuy);
      goods.goodsStateText = this.goodsStateText(goods.goodsState);
      if (info.goods_type == 4) {
        goods.isPaid = !!this.info.is_buy;
      }
      this.getGoodsInfo && this.getGoodsInfo(goods);
      return goods;
    },
    // 默认sku数据
    skuInfo() {
      if (!this.info) return defaultSkuInfo;
      const info = JSON.parse(JSON.stringify(this.info));
      let sku = info.sku || {};
      sku.tree = sku.tree || [];
      sku.list = this.tranList(sku.list);
      sku.price = info.min_price;
      sku.market_price = info.min_market_price;
      if (this.promoteType == "group") {
        //拼团商品价格及限购
        sku.group_price = sku.list.length > 0 ? sku.list[0].group_price : 0;
        sku.group_limit_buy =
          sku.list.length > 0 ? sku.list[0].group_limit_buy : 0;
      }
      sku.stock_num = this.countAllStock(sku.list);
      sku.max_buy = info.max_buy || 0;
      sku.collection_id = !sku.tree.length ? sku.list[0].id : 0;
      sku.none_sku = !sku.tree.length;
      sku.hide_stock = false;
      return sku;
    },
    customStepperConfig() {
      let quota = this.quota;
      return {
        quotaText:
          this.goodsInfo.selectedSkuComb && quota ? "限购" + quota + "件" : ""
      };
    },
    quota() {
      let quota = 0;
      quota =
        this.action == "group"
          ? this.goodsInfo.groupMaxBuy
          : this.goodsInfo.maxBuy; //单个显示拼团sku时，限购取拼团限购量
      return quota;
    },
    initialSku() {
      let init = {};
      if (this.initial) {
        this.skuInfo.list.forEach(e => {
          if (this.initial.id == e.id) {
            e.s &&
              e.s.forEach((s, i) => {
                for (let k in e) {
                  if (e[k] == s) {
                    init[k] = Number(s);
                  }
                }
              });
          }
        });
        init.selectedNum = this.initial.num || 1;
      }
      return init;
    }
  },
  mounted() {
    this.selectedSkuComb = this.currentSkuComb(
      this.$refs.getSkuData.selectedSkuComb
    );
    this.selectedNum = this.$refs.getSkuData.selectedNum;
  },
  methods: {
    // 当前sku选中数据
    currentSkuComb(selectedSkuComb) {
      // 无规格默认sku，list第一个
      return this.skuInfo.none_sku ? this.skuInfo.list[0] : selectedSkuComb;
    },
    // 转换sku列表数据 ( 元 => 分 )
    tranList(list = []) {
      let arr = list.map(e => {
        e.price = parseFloat(e.price) * 100;
        return e;
      });
      return arr;
    },
    //计算总库存
    countAllStock(list = []) {
      let stock = 0;
      list.forEach(e => {
        if (e.presell_num) {
          stock += parseInt(e.presell_num);
        } else {
          stock += parseInt(e.stock_num);
        }
      });
      return stock;
    },
    currentSkuImg(goods) {
      let img = goods.goods_image;
      img =
        this.selectedSkuValue && this.selectedSkuValue.imgUrl
          ? this.selectedSkuValue.imgUrl
          : img;
      return img;
    },
    //当前sku库存
    currentSkuStock(selectedSkuComb) {
      let stock = 0;
      stock = selectedSkuComb
        ? selectedSkuComb.stock_num
        : this.skuInfo.stock_num;
      return stock;
    },
    //当前sku限购
    currentSkuMaxBuy(selectedSkuComb) {
      let num = 0;
      num = selectedSkuComb ? selectedSkuComb.max_buy : this.skuInfo.max_buy;
      return num || 0;
    },
    //当前拼团sku限购
    currentGroupSkuMaxBuy(selectedSkuComb) {
      let num = 0;
      num = selectedSkuComb
        ? selectedSkuComb.group_limit_buy
        : this.skuInfo.group_limit_buy;
      return num || 0;
    },
    // 计算商品价格
    // currentPrice(selectedSkuComb) {
    //   const { member_discount, limit_discount, member_is_label } = this.info;
    //   const isPromoteGoods =
    //     this.promoteType == "seckill" ||
    //     this.promoteType == "bargain" ||
    //     this.promoteType == "presell"; //是否属活动商品(秒杀、砍价、预售)
    //   const roundFix = !isPromoteGoods && member_is_label ? 0 : 2; //是否取整(活动商品时不取整)
    //   let discount_price = 0;
    //   //无规格时默认取sku list 第一条price
    //   let price = parseFloat(
    //     selectedSkuComb
    //       ? selectedSkuComb.price / 100 //sku的价格单位为分，所以需转换为单位元显示
    //       : this.skuInfo.price
    //   ).toFixed(roundFix); // 商品售价
    //   let member_price = (price * member_discount).toFixed(roundFix); //会员价
    //   discount_price = (member_price * limit_discount).toFixed(roundFix); //（折扣价）会员限时折扣价
    //   // console.log(selectedSkuComb, discount_price);
    //   return isPromoteGoods ? price : discount_price; // 秒杀商品及其他活动商品不参与任何折扣
    // },

    // 商品售价
    currentPrice(selectedSkuComb) {
      let price = selectedSkuComb
        ? selectedSkuComb.price / 100 //sku的价格单位为分，所以需转换为单位元显示
        : this.skuInfo.price;
      return price;
    },
    // 市场价格
    currentMarketPrice(selectedSkuComb) {
      let price = selectedSkuComb
        ? selectedSkuComb.market_price
        : this.skuInfo.market_price;
      return price;
    },
    // 拼团商品价格
    currentGroupPrice(selectedSkuComb) {
      const roundFix = 2; //拼团价格不取整
      //无规格时默认取sku list 第一条price
      let groupPrice = parseFloat(
        selectedSkuComb
          ? selectedSkuComb.group_price || 0
          : this.skuInfo.group_price || 0
      ).toFixed(roundFix);
      return groupPrice;
    },
    // 预售价格库存信息
    presellInfo(selectedSkuComb) {
      let defaultInfo = this.skuInfo.list[0];
      let obj = {};
      if (selectedSkuComb) {
        obj.frontMoney = selectedSkuComb.first_money;
        obj.tailMoney =
          parseFloat(selectedSkuComb.all_money) -
          parseFloat(selectedSkuComb.first_money);
        obj.maxBuy = selectedSkuComb.max_buy;
        obj.stock = selectedSkuComb.presell_num;
        obj.allMoney = selectedSkuComb.all_money;
      } else {
        //无规格时默认取sku list 第一条price
        obj.frontMoney = defaultInfo.first_money;
        obj.tailMoney =
          parseFloat(defaultInfo.all_money) -
          parseFloat(defaultInfo.first_money);
        obj.maxBuy = defaultInfo.max_buy;
        obj.stock = defaultInfo.presell_num;
        obj.allMoney = defaultInfo.all_money;
      }
      // console.log(obj)
      return obj;
    },
    /**
     * state 商品状态
     * 1  ==> 正常
     * -1 ==> 无库存
     * -2 ==> 无权限购买
     * -3 ==> 超出最大限购量
     * 0  ==> 下架
     */
    goodsState(goods, stock, maxBuy) {
      if (!goods.state) {
        return 0;
      }
      if (!goods.is_allow_buy) {
        return -2;
      }
      if (maxBuy === -1) {
        return -3;
      }
      if (!stock) {
        return -1;
      }
      return goods.state;
    },
    // 商品状态名称
    goodsStateText(state) {
      let text = "";
      if (state == 0) {
        text = "商品已下架";
      } else if (state == -2) {
        text = "无购买权限";
      } else if (state == -3) {
        text = "已超出最大限购量";
      } else if (state == -1) {
        text = "商品已售罄";
      }
      return text;
    },
    stepperChange(num) {
      // 限制最大输入数量
      num = num <= 0 ? 1 : num;
      let quota = this.goodsInfo.maxBuy;
      let limitFlag = quota > 0; // 活动限购量 真为限购 否为不限购
      let stock = this.goodsInfo.stock; // 库存量
      let _limit = quota > stock ? stock : quota; //限购量
      let limitNum = num > _limit ? _limit : num; //当前数量不能大于限购数
      let limitStockNum = num > stock ? stock : num; //普通数量不能大于库存
      this.selectedNum = limitFlag ? limitNum : limitStockNum;
    },
    skuSelect(data) {
      // this.selectedSkuComb = data.selectedSkuComb;
      this.selectedSkuValue = data.skuValue;
    },
    skuClose() {
      this.$emit("close", this.promoteType);
    },
    onAction(action, data) {
      /**
       * action 触发方法名称汇总
       * data   返回当前sku数据（活动商品会携带相关活动参数）
       *
       * addCard  =>  加入购物车
       * buy      =>  立即购买
       * bargain  =>  砍价
       * group    =>  发起拼团
       * presell  =>  预售
       * seckill  =>  秒杀
       */
      this.$emit("action", action, data);
    }
  },
  components: {
    [Sku.name]: Sku,
    HeaderPirce,
    ActionBtn
  }
};
</script>

<style scoped>
.action-group {
  padding: 0 5px;
}
</style>