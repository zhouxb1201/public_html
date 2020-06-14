<template>
  <div>
    <PromoteInfo
      v-if="promoteType"
      :type="promoteType"
      :params="info.params.promoteParams"
      :goods-info="info.goodsInfo||{}"
      :price-color="info.style.promotecolor"
      :light-color="info.style.promotelightcolor"
    />
    <GoodsInfoPanel
      :name="goodsInfo.name"
      :name-tag="goodsInfo.nameTag"
      :price="goodsInfo.price"
      :origin-price="goodsInfo.originPrice"
      :promote-text="goodsInfo.promoteText"
      :foot-info="goodsInfo.footInfo"
      :price-color="info.style.pricecolor"
      :price-light-color="info.style.pricelightcolor"
      :name-color="info.style.titlecolor"
    >
      <div
        slot="originPrice"
        class="front-money-text"
        v-if="goodsInfo.frontMoneyText"
      >{{goodsInfo.frontMoneyText}}</div>
      <DigitalAsset
        v-if="digitalItems.length"
        :class="digitalFlexRowClass"
        :slot="slotInfoPanelName"
        :is-flex-row="isDigitalFlexRow"
        :fee="digitalFee"
        :items="digitalItems"
      />
    </GoodsInfoPanel>
  </div>
</template>

<script>
import GoodsInfoPanel from "@/components/GoodsInfoPanel";
import PromoteInfo from "./PromoteInfo";
import DigitalAsset from "./DigitalAsset";
import { isEmpty, formatDate } from "@/utils/util";
import { yuan } from "@/utils/filter";
export default {
  data() {
    return {};
  },
  props: {
    info: Object
  },
  computed: {
    slotInfoPanelName() {
      const promoteType = this.promoteType;
      let name = promoteType ? "header" : "headerRight";
      return name;
    },
    promoteType() {
      let type = null;
      const { promoteType, promoteParams } = this.info.params;
      if (
        (promoteType == "seckill" && promoteParams.seckill_status == "going") ||
        promoteType == "group" ||
        (promoteType == "presell" && promoteParams.state == 1) ||
        (promoteType == "bargain" && promoteParams.status == 1) ||
        (promoteType == "limit" && promoteParams.status == 1)
      ) {
        type = promoteType;
      }
      return type;
    },
    // 是否横向布局显示数字资产信息
    isDigitalFlexRow() {
      const promoteType = this.promoteType;
      let flag = !!promoteType;
      return flag;
    },
    digitalFlexRowClass() {
      let name = "";
      !this.isDigitalFlexRow && (name = "digital-flex-row");
      return name;
    },
    // 手续费
    digitalFee() {
      const info = this.info.params.digitalAsset || {};
      return info.fee ? info.fee + "%" : "";
    },
    digitalItems() {
      const info = this.info.params.digitalAsset || {};
      let arr = [];
      const eth = parseFloat(info.eth);
      const eos = parseFloat(info.eos);
      const price = parseFloat(this.info.goodsInfo.goodsPrice || 0);
      if (!isNaN(eth)) {
        let ethObj = {
          name: "ETH",
          icon: "v-icon-eth",
          iconcolor: "#f52929",
          money: price / eth
        };
        arr.push(ethObj);
      }
      if (!isNaN(eos)) {
        let eosObj = {
          name: "EOS",
          icon: "v-icon-eos",
          iconcolor: "#ff8f00",
          money: price / eos
        };
        arr.push(eosObj);
      }
      return arr;
    },
    goodsInfo() {
      const info = this.info.goodsInfo;
      const { postage, sales, promoteType, promoteParams } = this.info.params;
      let obj = {};
      obj.name = info.title;
      obj.nameTag = this.getPromoteNameTag(promoteType, promoteParams);
      obj.price = yuan(info.goodsPrice);
      obj.originPrice = yuan(info.marketPrice);
      obj.frontMoneyText = this.getOriginFrontMoney(
        promoteType,
        promoteParams,
        info.frontMoney
      );
      obj.priceTag = "";
      obj.promoteText = this.getPromoteText(promoteType, promoteParams);
      if (info.goodsType == 4) {
        obj.footInfo = [{ name: (sales || 0) + "人学习", value: "" }];
      } else {
        obj.footInfo = [
          { name: "运费:", value: postage },
          { name: "销量:", value: (sales || 0) + "笔" }
        ];
      }
      return obj;
    }
  },
  methods: {
    getOriginFrontMoney(type, params, money = 0) {
      let price = "";
      if (type == "presell" && params.state != 1) {
        price = "定金：" + yuan(money);
      }
      return price;
    },
    // 获取活动商品标签
    getPromoteNameTag(type, params) {
      let name = "";
      if (type == "group") {
        name = params.group_name;
      } else if (type == "presell") {
        name = params.name;
      } else if (type == "bargain") {
        name = params.bargain_name;
      } else if (type == "limit") {
        name = params.discount_name;
      }
      return name;
    },
    // 获取活动相关文案
    getPromoteText(type, params) {
      let text = "";
      if (type == "presell" && params.state != 1) {
        text = formatDate(params.start_time, "YYYY年mm月dd日", true) + " 整点开售";
      } else if (type == "bargain" && params.status != 1) {
        text =
          formatDate(params.start_bargain_time, "YYYY年mm月dd日", true) +
          " 最低砍至 " +
          yuan(params.lowest_money);
      } else if (type == "seckill" && params.seckill_status != "going") {
        text =
          formatDate(params.start_time, "YYYY年mm月dd日", true) +
          " 秒杀价 " +
          yuan(params.discount_price);
      } else if (type == "limit" && params.status != 1) {
        text =
          formatDate(params.start_time, "YYYY年mm月dd日", true) +
          " 抢购价 " +
          yuan(params.discount_price);
      }
      return text;
    }
  },
  components: {
    GoodsInfoPanel,
    PromoteInfo,
    DigitalAsset
  }
};
</script>

<style scoped>
.digital-flex-row {
  position: relative;
}

.digital-flex-row::before {
  content: "";
  display: block;
  position: absolute;
  left: -10px;
  top: 50%;
  transform: translateY(-48%);
  border-left: 1px dashed #ebedf0;
  height: 70%;
}

.front-money-text {
  color: #ff454e;
  font-size: 12px;
}
</style>
